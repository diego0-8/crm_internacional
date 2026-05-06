<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/TiketeraModel.php';
require_once __DIR__ . '/../model/ClienteModel.php';

class TicketImportController {
    private $db;
    private $tiketeraModel;
    private $clienteModel;

    /** @var string[] */
    private static $estadosValidos = ['comunicacion', 'validacion', 'proceso_judicial', 'remate', 'recuperacion', 'cierre'];

    private static $maxFilas = 3000;

    public function __construct() {
        $this->db = getDB();
        $this->tiketeraModel = new TiketeraModel();
        $this->clienteModel = new ClienteModel();
    }

    public function asesorBelongsToCoordinador($asesorCedula, $coordinadorCedula) {
        $stmt = $this->db->prepare("
            SELECT 1 FROM usuarios u
            INNER JOIN roles r ON u.rol_id = r.id
            WHERE u.cedula = ?
              AND r.nombre = 'asesor'
              AND u.coordinador_cedula = ?
              AND u.activo = 1
        ");
        $stmt->execute([$asesorCedula, $coordinadorCedula]);
        return (bool) $stmt->fetch();
    }

    /**
     * Procesa CSV subido. Detecta automáticamente:
     *   - Delimitador (',' o ';').
     *   - Tipo de plantilla: "simple" (cliente_cedula/titulo/...) o "foreclosure"
     *     (Case Number, Parcel Number, Property Street, ...).
     *
     * @param array       $archivo                  $_FILES['archivo'] o equivalente.
     * @param string      $coordinadorCedula
     * @param bool        $crearClientesSiFaltan    Solo aplica al modo simple. En foreclosure
     *                                              siempre se crean los clientes.
     * @param string|null $asesorDefaultCedula      Asesor por defecto cuando la fila/cliente no traen.
     */
    public function procesarCsvTickets($archivo, $coordinadorCedula, $crearClientesSiFaltan, $asesorDefaultCedula = null) {
        $tmpName = $archivo['tmp_name'] ?? null;
        if (!$tmpName) {
            throw new Exception('No se ha subido ningún archivo');
        }
        $esSubidaHttp = is_uploaded_file($tmpName);
        $esArchivoLocal = (PHP_SAPI === 'cli' && file_exists($tmpName) && is_readable($tmpName));
        if (!$esSubidaHttp && !$esArchivoLocal) {
            throw new Exception('No se ha subido ningún archivo');
        }
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if ($extension !== 'csv') {
            throw new Exception('El archivo debe ser CSV');
        }

        if ($asesorDefaultCedula !== null && $asesorDefaultCedula !== '') {
            if (!$this->asesorBelongsToCoordinador($asesorDefaultCedula, $coordinadorCedula)) {
                throw new Exception('El asesor por defecto no pertenece a su coordinación o está inactivo');
            }
        } else {
            $asesorDefaultCedula = null;
        }

        $uploadDir = __DIR__ . '/../uploads/csv_tickets/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $nombreOriginal = (string) ($archivo['name'] ?? 'tickets.csv');
        $nombreGuardado = uniqid('tkt_', true) . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $nombreOriginal);
        $rutaAbsoluta = $uploadDir . $nombreGuardado;
        if ($esSubidaHttp) {
            if (!move_uploaded_file($tmpName, $rutaAbsoluta)) {
                throw new Exception('No se pudo guardar el archivo');
            }
        } else {
            if (!copy($tmpName, $rutaAbsoluta)) {
                throw new Exception('No se pudo guardar el archivo');
            }
        }

        $rutaRelativa = 'uploads/csv_tickets/' . $nombreGuardado;

        $stmt = $this->db->prepare("
            INSERT INTO ticket_import_batches
            (coordinador_cedula, nombre_archivo, ruta_archivo, estado, crear_clientes_modo)
            VALUES (?, ?, ?, 'procesando', ?)
        ");
        $stmt->execute([
            $coordinadorCedula,
            $nombreOriginal,
            $rutaRelativa,
            $crearClientesSiFaltan ? 1 : 0
        ]);
        $batchId = (int) $this->db->lastInsertId();

        $delimitador = $this->detectarDelimitador($rutaAbsoluta);

        $handle = fopen($rutaAbsoluta, 'r');
        if (!$handle) {
            $this->marcarBatchError($batchId, 'No se pudo leer el archivo');
            throw new Exception('No se pudo leer el archivo');
        }

        $headerRow = fgetcsv($handle, 0, $delimitador);
        if ($headerRow === false) {
            fclose($handle);
            $this->marcarBatchError($batchId, 'CSV vacío');
            throw new Exception('CSV vacío');
        }

        $map = [];
        foreach ($headerRow as $i => $col) {
            $key = strtolower(trim((string) $col));
            $key = preg_replace('/^\xEF\xBB\xBF/', '', $key);
            $map[$key] = $i;
        }

        $tipo = $this->detectarTipoCsv($map);

        if ($tipo === 'simple') {
            $requeridos = ['cliente_cedula', 'titulo'];
            foreach ($requeridos as $req) {
                if (!isset($map[$req])) {
                    fclose($handle);
                    $this->marcarBatchError($batchId, "Falta columna obligatoria en cabecera: {$req}");
                    throw new Exception("Falta columna obligatoria en cabecera: {$req}");
                }
            }
        }

        $numLinea = 1;
        $ok = 0;
        $err = 0;
        $total = 0;

        while (($data = fgetcsv($handle, 0, $delimitador)) !== false) {
            $numLinea++;
            if ($this->filaVacia($data)) {
                continue;
            }
            $total++;
            if ($total > self::$maxFilas) {
                $this->insertarFilaLog($batchId, $numLinea, 'error', 'Límite de filas excedido (' . self::$maxFilas . ')');
                $err++;
                break;
            }

            $rawLine = json_encode($data, JSON_UNESCAPED_UNICODE);

            try {
                if ($tipo === 'foreclosure') {
                    $ticketId = $this->procesarFilaForeclosure($data, $map, $coordinadorCedula, $batchId, $asesorDefaultCedula);
                } else {
                    $row = $this->mapearFila($data, $map);
                    $ticketId = $this->procesarFilaTicket($row, $coordinadorCedula, $crearClientesSiFaltan, $batchId, $asesorDefaultCedula);
                }
                $this->insertarFilaLog($batchId, $numLinea, 'ok', null, $ticketId, $rawLine);
                $ok++;
            } catch (Exception $e) {
                $this->insertarFilaLog($batchId, $numLinea, 'error', $e->getMessage(), null, $rawLine);
                $err++;
            }
        }

        fclose($handle);

        $stmt = $this->db->prepare("
            UPDATE ticket_import_batches SET
                total_filas = ?,
                filas_ok = ?,
                filas_error = ?,
                estado = 'completado',
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$total, $ok, $err, $batchId]);

        return [
            'success' => true,
            'batch_id' => $batchId,
            'tipo' => $tipo,
            'total_filas' => $total,
            'filas_ok' => $ok,
            'filas_error' => $err,
            'message' => "Importación finalizada ({$tipo}): {$ok} correctas, {$err} con error."
        ];
    }

    // ------------------------------------------------------------------
    // Detección de tipo y delimitador
    // ------------------------------------------------------------------

    private function detectarDelimitador($rutaAbsoluta) {
        $fp = @fopen($rutaAbsoluta, 'r');
        if (!$fp) return ',';
        $linea = fgets($fp, 65536);
        fclose($fp);
        if ($linea === false) return ',';
        $linea = preg_replace('/^\xEF\xBB\xBF/', '', $linea);
        $coma = substr_count($linea, ',');
        $punto = substr_count($linea, ';');
        return ($punto > $coma) ? ';' : ',';
    }

    private function detectarTipoCsv(array $map) {
        $foreClaves = ['case number', 'parcel number', 'property street'];
        $coincidencias = 0;
        foreach ($foreClaves as $k) {
            if (isset($map[$k])) $coincidencias++;
        }
        if ($coincidencias >= 2) {
            return 'foreclosure';
        }
        return 'simple';
    }

    // ------------------------------------------------------------------
    // Procesamiento foreclosure
    // ------------------------------------------------------------------

    /**
     * Procesa una fila del CSV de foreclosure dentro de una única transacción.
     */
    private function procesarFilaForeclosure(array $data, array $map, $coordinadorCedula, $batchId, $asesorDefaultCedula) {
        $get = $this->makeGetter($data, $map);

        $caseNumber   = $get('case number');
        $parcelNumber = $get('parcel number');
        if ($caseNumber === '' && $parcelNumber === '') {
            throw new Exception('Fila sin Case Number ni Parcel Number');
        }

        $cedulaCliente = $this->derivarCedulaCliente($caseNumber, $parcelNumber);
        if ($cedulaCliente === '') {
            throw new Exception('No se pudo derivar cliente_cedula a partir de Case/Parcel Number');
        }

        $firstName = $get('first name');
        $lastName  = $get('last name');
        $nombreCompleto = trim($firstName . ' ' . $lastName);
        if ($nombreCompleto === '') {
            $nombreCompleto = 'Sin nombre - ' . $cedulaCliente;
        }

        $clienteExistente = $this->clienteModel->getClienteByCedula($cedulaCliente);

        $iniciaba = $this->db->inTransaction();
        if (!$iniciaba) {
            $this->db->beginTransaction();
        }

        try {
            $coordinadorClienteOriginal = null;
            if ($clienteExistente && !empty($clienteExistente['coordinador_cedula'])) {
                $coordinadorClienteOriginal = $clienteExistente['coordinador_cedula'];
                if ($coordinadorClienteOriginal !== $coordinadorCedula) {
                    throw new Exception('El cliente ' . $cedulaCliente . ' pertenece a otro coordinador');
                }
            }

            $asesorCedula = '';
            if ($clienteExistente && !empty($clienteExistente['asesor_cedula'])) {
                $asesorCedula = $clienteExistente['asesor_cedula'];
            }
            if ($asesorCedula === '' && $asesorDefaultCedula) {
                $asesorCedula = $asesorDefaultCedula;
            }
            if ($asesorCedula === '') {
                throw new Exception('Sin asesor: configure "Asesor por defecto" en la importación o asigne uno al cliente');
            }
            if (!$this->asesorBelongsToCoordinador($asesorCedula, $coordinadorCedula)) {
                throw new Exception('El asesor seleccionado no pertenece a este coordinador o está inactivo');
            }

            if (!$clienteExistente) {
                $this->insertClienteForeclosure($cedulaCliente, $nombreCompleto, $get, $coordinadorCedula, $asesorCedula);
            } else {
                $this->actualizarClienteCamposVaciosForeclosure($cedulaCliente, $clienteExistente, $nombreCompleto, $get, $asesorCedula);
            }

            $predioId = $this->insertPredio($cedulaCliente, $caseNumber, $parcelNumber, $get);
            $this->insertarTelefonosCliente($cedulaCliente, $get);
            $this->insertarEmailsCliente($cedulaCliente, $get);
            $this->insertarReferencias($cedulaCliente, $get);

            $tituloIdent = $caseNumber !== '' ? $caseNumber : $parcelNumber;
            $titulo = trim('Foreclosure ' . $tituloIdent . ' - ' . $lastName . ($firstName !== '' ? ', ' . $firstName : ''));
            if (strlen($titulo) > 250) {
                $titulo = substr($titulo, 0, 250);
            }

            $descripcion = $this->construirDescripcionForeclosure($get, $caseNumber, $parcelNumber);

            $ticketId = $this->insertTicketForeclosure(
                $cedulaCliente,
                $asesorCedula,
                $titulo,
                $descripcion,
                $batchId
            );

            $stmt = $this->db->prepare("UPDATE predios SET ticket_id = ? WHERE id = ?");
            $stmt->execute([$ticketId, $predioId]);

            if (!$iniciaba) {
                $this->db->commit();
            }

            return $ticketId;
        } catch (Exception $e) {
            if (!$iniciaba && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function insertClienteForeclosure($cedula, $nombreCompleto, callable $get, $coordinadorCedula, $asesorCedula) {
        $age = $this->parsearEntero($get('age'));
        $deceased = $this->parsearYN($get('deceased'));
        $source = $get('source') !== '' ? $get('source') : null;

        $direccion = $get('mailing city-street') !== '' ? $get('mailing city-street') : null;
        $ciudad = null;
        $mailingState = $get('mailing state') !== '' ? $get('mailing state') : null;
        $mailingZip = $get('mailing zip code') !== '' ? $get('mailing zip code') : null;

        $stmt = $this->db->prepare("
            INSERT INTO clientes
                (cedula, nombre_completo, email, telefono, direccion, ciudad, pais,
                 asesor_cedula, coordinador_cedula, archivo_csv_id, estado, notas,
                 age, deceased, source, mailing_street, mailing_city, mailing_state, mailing_zip)
            VALUES (?, ?, NULL, NULL, ?, NULL, 'United States',
                    ?, ?, NULL, 'nuevo', NULL,
                    ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $cedula,
            $nombreCompleto,
            $direccion,
            $asesorCedula,
            $coordinadorCedula,
            $age,
            $deceased,
            $source,
            $direccion,
            $ciudad,
            $mailingState,
            $mailingZip,
        ]);
    }

    private function actualizarClienteCamposVaciosForeclosure($cedula, array $clienteExistente, $nombreCompleto, callable $get, $asesorCedula) {
        $sets = [];
        $vals = [];

        $candidatos = [
            'nombre_completo' => $nombreCompleto,
            'asesor_cedula'   => $asesorCedula,
            'age'             => $this->parsearEntero($get('age')),
            'deceased'        => $this->parsearYN($get('deceased')),
            'source'          => $get('source') !== '' ? $get('source') : null,
            'mailing_street'  => $get('mailing city-street') !== '' ? $get('mailing city-street') : null,
            'mailing_state'   => $get('mailing state') !== '' ? $get('mailing state') : null,
            'mailing_zip'     => $get('mailing zip code') !== '' ? $get('mailing zip code') : null,
        ];

        foreach ($candidatos as $col => $valor) {
            if ($valor === null || $valor === '') continue;
            $actual = $clienteExistente[$col] ?? null;
            if ($actual === null || $actual === '') {
                $sets[] = "{$col} = ?";
                $vals[] = $valor;
            }
        }

        if (empty($sets)) {
            return;
        }
        $vals[] = $cedula;
        $sql = "UPDATE clientes SET " . implode(', ', $sets) . ", updated_at = CURRENT_TIMESTAMP WHERE cedula = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($vals);
    }

    private function insertPredio($cedulaCliente, $caseNumber, $parcelNumber, callable $get) {
        $valorADevolver  = $this->parsearMontoUSD($get('valor a devolver'));
        $valorVendido    = $this->parsearMontoUSD($get('valor de vendido'));
        $valorInicial    = $this->parsearMontoUSD($get('valor inicial de la subasta'));
        $dateSold        = $this->parsearFechaMDY($get('date sold'));

        $tipoForeclosure = $get('type of foreclosure') !== '' ? $get('type of foreclosure') : null;
        $propertyStreet  = $get('property street') !== '' ? $get('property street') : null;
        $propertyCity    = $get('property city') !== '' ? $get('property city') : null;
        $propertyState   = $get('property state') !== '' ? $get('property state') : null;
        $propertyZip     = $get('property zip code') !== '' ? $get('property zip code') : null;
        $county          = $get('county') !== '' ? $get('county') : null;
        $source          = $get('source') !== '' ? $get('source') : null;

        // Idempotencia: si ya hay un predio con misma case_number+parcel_number+cliente, no se duplica.
        if ($caseNumber !== '' || $parcelNumber !== '') {
            $stmt = $this->db->prepare("
                SELECT id FROM predios
                WHERE cliente_cedula = ?
                  AND (case_number <=> ? OR (case_number IS NULL AND ? = ''))
                  AND (parcel_number <=> ? OR (parcel_number IS NULL AND ? = ''))
                LIMIT 1
            ");
            $stmt->execute([$cedulaCliente, $caseNumber, $caseNumber, $parcelNumber, $parcelNumber]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                return (int) $existing['id'];
            }
        }

        $stmt = $this->db->prepare("
            INSERT INTO predios
                (cliente_cedula, case_number, parcel_number, type_of_foreclosure,
                 property_street, property_city, property_state, property_zip, county,
                 source, valor_a_devolver, valor_vendido, valor_inicial_subasta, date_sold)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $cedulaCliente,
            $caseNumber !== '' ? $caseNumber : null,
            $parcelNumber !== '' ? $parcelNumber : null,
            $tipoForeclosure,
            $propertyStreet, $propertyCity, $propertyState, $propertyZip, $county,
            $source,
            $valorADevolver, $valorVendido, $valorInicial, $dateSold,
        ]);
        return (int) $this->db->lastInsertId();
    }

    private function insertarTelefonosCliente($cedulaCliente, callable $get) {
        for ($i = 1; $i <= 5; $i++) {
            $numero = $get('phone ' . $i);
            if ($numero === '') continue;

            $info = $this->normalizarTelefono($numero);
            $tipo = $this->normalizarTipoTelefono($get('phone ' . $i . ': type'));
            $dnc  = $this->parsearYN($get('phone ' . $i . ': dnc/litigator'));

            // Idempotencia: misma (cliente, normalizado) -> skip.
            $stmt = $this->db->prepare("
                SELECT id FROM cliente_telefonos
                WHERE cliente_cedula = ? AND numero_normalizado = ?
                LIMIT 1
            ");
            $stmt->execute([$cedulaCliente, $info['normalizado']]);
            if ($stmt->fetch()) continue;

            $stmt = $this->db->prepare("
                INSERT IGNORE INTO cliente_telefonos
                    (cliente_cedula, numero, numero_normalizado, tipo, dnc_litigator, orden)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$cedulaCliente, $info['numero'], $info['normalizado'], $tipo, $dnc, $i]);
        }
    }

    private function insertarEmailsCliente($cedulaCliente, callable $get) {
        for ($i = 1; $i <= 5; $i++) {
            $email = strtolower(trim($get('email ' . $i)));
            if ($email === '') continue;

            $stmt = $this->db->prepare("
                SELECT id FROM cliente_emails
                WHERE cliente_cedula = ? AND email = ?
                LIMIT 1
            ");
            $stmt->execute([$cedulaCliente, $email]);
            if ($stmt->fetch()) continue;

            $stmt = $this->db->prepare("
                INSERT IGNORE INTO cliente_emails (cliente_cedula, email, orden)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$cedulaCliente, $email, $i]);
        }
    }

    private function insertarReferencias($cedulaCliente, callable $get) {
        for ($r = 1; $r <= 5; $r++) {
            $prefix = 'relative ' . $r . ': ';
            $first = $get($prefix . 'first name');
            $last  = $get($prefix . 'last name');
            $type  = $get($prefix . 'possible type');
            $age   = $this->parsearEntero($get($prefix . 'age'));

            $tienePhone = false;
            for ($p = 1; $p <= 5; $p++) {
                if ($get($prefix . 'phone ' . $p) !== '') { $tienePhone = true; break; }
            }
            $tieneEmail = false;
            for ($e = 1; $e <= 5; $e++) {
                if ($get($prefix . 'email ' . $e) !== '') { $tieneEmail = true; break; }
            }

            if ($first === '' && $last === '' && !$tienePhone && !$tieneEmail) {
                continue;
            }

            $refId = $this->getOrCreateReferencia($cedulaCliente, $first, $last, $type, $age, $r);
            $this->insertarTelefonosReferencia($refId, $get, $prefix);
            $this->insertarEmailsReferencia($refId, $get, $prefix);
        }
    }

    private function getOrCreateReferencia($cedulaCliente, $first, $last, $type, $age, $orden) {
        $stmt = $this->db->prepare("
            SELECT id FROM referencias_personales
            WHERE cliente_cedula = ? AND orden = ?
            LIMIT 1
        ");
        $stmt->execute([$cedulaCliente, $orden]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            return (int) $existing['id'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO referencias_personales
                (cliente_cedula, nombre, apellido, possible_type, age, orden)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $cedulaCliente,
            $first !== '' ? $first : null,
            $last !== '' ? $last : null,
            $type !== '' ? $type : null,
            $age,
            $orden,
        ]);
        return (int) $this->db->lastInsertId();
    }

    private function insertarTelefonosReferencia($refId, callable $get, $prefix) {
        for ($i = 1; $i <= 5; $i++) {
            $numero = $get($prefix . 'phone ' . $i);
            if ($numero === '') continue;
            $info = $this->normalizarTelefono($numero);
            $tipo = $this->normalizarTipoTelefono($get($prefix . 'phone ' . $i . ': type'));
            $dnc  = $this->parsearYN($get($prefix . 'phone ' . $i . ': dnc/litigator'));

            $stmt = $this->db->prepare("
                SELECT id FROM referencia_telefonos
                WHERE referencia_id = ? AND numero_normalizado = ?
                LIMIT 1
            ");
            $stmt->execute([$refId, $info['normalizado']]);
            if ($stmt->fetch()) continue;

            $stmt = $this->db->prepare("
                INSERT IGNORE INTO referencia_telefonos
                    (referencia_id, numero, numero_normalizado, tipo, dnc_litigator, orden)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$refId, $info['numero'], $info['normalizado'], $tipo, $dnc, $i]);
        }
    }

    private function insertarEmailsReferencia($refId, callable $get, $prefix) {
        for ($i = 1; $i <= 5; $i++) {
            $email = strtolower(trim($get($prefix . 'email ' . $i)));
            if ($email === '') continue;

            $stmt = $this->db->prepare("
                SELECT id FROM referencia_emails
                WHERE referencia_id = ? AND email = ?
                LIMIT 1
            ");
            $stmt->execute([$refId, $email]);
            if ($stmt->fetch()) continue;

            $stmt = $this->db->prepare("
                INSERT IGNORE INTO referencia_emails (referencia_id, email, orden)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$refId, $email, $i]);
        }
    }

    private function insertTicketForeclosure($cedulaCliente, $asesorCedula, $titulo, $descripcion, $batchId) {
        $stmt = $this->db->prepare("
            INSERT INTO tiketera
                (cliente_cedula, asesor_cedula, titulo, descripcion, estado,
                 observaciones, pdf_archivo, categoria_id, origen, import_batch_id)
            VALUES (?, ?, ?, ?, 'comunicacion', NULL, NULL, NULL, 'csv', ?)
        ");
        $stmt->execute([$cedulaCliente, $asesorCedula, $titulo, $descripcion, $batchId]);
        $ticketId = (int) $this->db->lastInsertId();

        $this->tiketeraModel->assignNumeroTicket($ticketId);

        $stmt = $this->db->prepare("
            INSERT INTO ticket_estado_historial
                (ticket_id, estado_anterior, estado_nuevo, asesor_cedula, observacion)
            VALUES (?, NULL, 'comunicacion', ?, 'Importación foreclosure desde CSV')
        ");
        $stmt->execute([$ticketId, $asesorCedula]);

        return $ticketId;
    }

    private function construirDescripcionForeclosure(callable $get, $caseNumber, $parcelNumber) {
        $partes = [];
        $partes[] = 'Importado desde CSV foreclosure.';
        if ($caseNumber !== '') $partes[] = 'Case Number: ' . $caseNumber;
        if ($parcelNumber !== '') $partes[] = 'Parcel Number: ' . $parcelNumber;
        $tipo = $get('type of foreclosure');
        if ($tipo !== '') $partes[] = 'Type: ' . $tipo;
        $county = $get('county');
        if ($county !== '') $partes[] = 'County: ' . $county;
        $valorADev = $get('valor a devolver');
        if ($valorADev !== '') $partes[] = 'Valor a devolver: ' . $valorADev;
        $valorVend = $get('valor de vendido');
        if ($valorVend !== '') $partes[] = 'Valor vendido: ' . $valorVend;
        $valorIni  = $get('valor inicial de la subasta');
        if ($valorIni !== '') $partes[] = 'Valor inicial subasta: ' . $valorIni;
        $dateSold  = $get('date sold');
        if ($dateSold !== '') $partes[] = 'Date Sold: ' . $dateSold;
        $propStreet = $get('property street');
        $propCity   = $get('property city');
        $propState  = $get('property state');
        $propZip    = $get('property zip code');
        $direccion = trim(implode(' ', array_filter([$propStreet, $propCity, $propState, $propZip], 'strlen')));
        if ($direccion !== '') $partes[] = 'Property: ' . $direccion;
        return implode("\n", $partes);
    }

    // ------------------------------------------------------------------
    // Helpers de parseo
    // ------------------------------------------------------------------

    public function derivarCedulaCliente($caseNumber, $parcelNumber) {
        $base = trim((string) $caseNumber);
        if ($base === '') {
            $base = trim((string) $parcelNumber);
        }
        if ($base === '') {
            return '';
        }
        $upper = strtoupper($base);
        $normalizado = preg_replace('/[^A-Z0-9-]/', '', $upper);
        if ($normalizado === '') {
            return '';
        }
        if (strlen($normalizado) <= 20) {
            return $normalizado;
        }
        return 'FC-' . sprintf('%08X', crc32($upper));
    }

    public function parsearMontoUSD($txt) {
        $txt = trim((string) $txt);
        if ($txt === '') return null;
        $limpio = preg_replace('/[^0-9.\-]/', '', $txt);
        if ($limpio === '' || $limpio === '-' || $limpio === '.') return null;
        if (!is_numeric($limpio)) return null;
        return (float) $limpio;
    }

    public function parsearFechaMDY($txt) {
        $txt = trim((string) $txt);
        if ($txt === '') return null;
        if (preg_match('#^(\d{1,2})[/-](\d{1,2})[/-](\d{2,4})$#', $txt, $m)) {
            $mes = (int) $m[1];
            $dia = (int) $m[2];
            $anio = (int) $m[3];
            if ($anio < 100) $anio += 2000;
            if (checkdate($mes, $dia, $anio)) {
                return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
            }
        }
        if (preg_match('#^\d{4}-\d{2}-\d{2}$#', $txt)) {
            return $txt;
        }
        return null;
    }

    public function normalizarTelefono($txt) {
        $txt = trim((string) $txt);
        $digitos = preg_replace('/\D+/', '', $txt);
        if (strlen($digitos) > 20) {
            $digitos = substr($digitos, -20);
        }
        return [
            'numero'      => $txt !== '' ? $txt : null,
            'normalizado' => $digitos !== '' ? $digitos : null,
        ];
    }

    public function normalizarTipoTelefono($txt) {
        $t = strtolower(trim((string) $txt));
        if ($t === '') return 'other';
        if (strpos($t, 'land') !== false) return 'landline';
        if (strpos($t, 'wire') !== false || strpos($t, 'mob') !== false || strpos($t, 'cell') !== false) return 'wireless';
        if (strpos($t, 'voip') !== false) return 'voip';
        return 'other';
    }

    private function parsearYN($txt) {
        $t = strtoupper(trim((string) $txt));
        if ($t === 'Y' || $t === 'YES' || $t === 'TRUE' || $t === '1') return 'Y';
        if ($t === 'N' || $t === 'NO' || $t === 'FALSE' || $t === '0') return 'N';
        return null;
    }

    private function parsearEntero($txt) {
        $txt = trim((string) $txt);
        if ($txt === '') return null;
        if (!ctype_digit($txt)) return null;
        $n = (int) $txt;
        if ($n < 0 || $n > 255) return null;
        return $n;
    }

    private function makeGetter(array $data, array $map) {
        return function ($key) use ($data, $map) {
            $k = strtolower(trim($key));
            if (!isset($map[$k])) {
                return '';
            }
            $idx = $map[$k];
            return isset($data[$idx]) ? trim((string) $data[$idx]) : '';
        };
    }

    // ------------------------------------------------------------------
    // Procesamiento simple (compatibilidad hacia atrás)
    // ------------------------------------------------------------------

    private function marcarBatchError($batchId, $msg) {
        $stmt = $this->db->prepare("
            UPDATE ticket_import_batches SET estado = 'error', mensaje_error = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?
        ");
        $stmt->execute([$msg, $batchId]);
    }

    private function filaVacia($data) {
        foreach ($data as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }
        return true;
    }

    private function mapearFila($data, $map) {
        $get = function ($key) use ($data, $map) {
            if (!isset($map[$key])) {
                return '';
            }
            $idx = $map[$key];
            return isset($data[$idx]) ? trim((string) $data[$idx]) : '';
        };

        $estadoIn = strtolower($get('estado'));
        $estadoMap = [
            ''           => 'comunicacion',
            'abierto'    => 'comunicacion',
            'procesando' => 'validacion',
            'terminado'  => 'cierre',
            'cancelado'  => 'cierre',
        ];
        if (isset($estadoMap[$estadoIn])) {
            $estadoIn = $estadoMap[$estadoIn];
        }

        $nombreCompleto = $get('nombre_completo');
        if ($nombreCompleto === '') {
            $nombre = $get('nombre');
            $apellido = $get('apellido');
            $nombreCompleto = trim($nombre . ' ' . $apellido);
        }

        return [
            'cliente_cedula'   => $get('cliente_cedula'),
            'titulo'           => $get('titulo'),
            'descripcion'      => $get('descripcion'),
            'estado'           => $estadoIn,
            'asesor_cedula'    => $get('asesor_cedula'),
            'categoria_codigo' => strtoupper($get('categoria_codigo')),
            'nombre_completo'  => $nombreCompleto,
            'email'            => $get('email'),
            'telefono'         => $get('telefono'),
        ];
    }

    private function procesarFilaTicket(array $row, $coordinadorCedula, $crearClientesSiFaltan, $batchId, $asesorDefaultCedula = null) {
        $cedulaCliente = $row['cliente_cedula'];
        if ($cedulaCliente === '') {
            throw new Exception('cliente_cedula vacío');
        }

        $titulo = $row['titulo'];
        if ($titulo === '') {
            throw new Exception('titulo vacío');
        }

        $estado = $row['estado'];
        if (!in_array($estado, self::$estadosValidos, true)) {
            throw new Exception('estado inválido: use ' . implode('|', self::$estadosValidos));
        }

        $cliente = $this->clienteModel->getClienteByCedula($cedulaCliente);

        if (!$cliente) {
            if (!$crearClientesSiFaltan) {
                throw new Exception('Cliente no existe y la opción crear clientes está desactivada');
            }
            if ($row['nombre_completo'] === '' || $row['email'] === '') {
                throw new Exception('Cliente inexistente: indicar nombre_completo y email para crearlo');
            }
            $this->clienteModel->createCliente([
                'cedula'             => $cedulaCliente,
                'nombre_completo'    => $row['nombre_completo'],
                'email'              => $row['email'],
                'telefono'           => $row['telefono'] ?: null,
                'coordinador_cedula' => $coordinadorCedula,
                'asesor_cedula'      => null,
                'estado'             => 'nuevo',
            ]);
            $cliente = $this->clienteModel->getClienteByCedula($cedulaCliente);
        }

        if (!$this->clienteModel->clienteBelongsToCoordinador($cedulaCliente, $coordinadorCedula)) {
            throw new Exception('El cliente no pertenece a su coordinación');
        }

        $asesorCedula = $row['asesor_cedula'];
        if ($asesorCedula === '') {
            $asesorCedula = $cliente['asesor_cedula'] ?? '';
        }
        if ($asesorCedula === '' && $asesorDefaultCedula) {
            $asesorCedula = $asesorDefaultCedula;
        }
        if ($asesorCedula === '') {
            throw new Exception('Sin asesor: indique asesor_cedula en el CSV o asigne un asesor al cliente');
        }

        if (!$this->asesorBelongsToCoordinador($asesorCedula, $coordinadorCedula)) {
            throw new Exception('El asesor no está asignado a este coordinador o está inactivo');
        }

        $categoriaId = null;
        if ($row['categoria_codigo'] !== '') {
            $categoriaId = $this->tiketeraModel->getCategoriaIdByCodigo($row['categoria_codigo']);
            if ($categoriaId === null) {
                throw new Exception('categoria_codigo no encontrada: ' . $row['categoria_codigo']);
            }
        }

        return $this->tiketeraModel->createTicket([
            'cliente_cedula'   => $cedulaCliente,
            'asesor_cedula'    => $asesorCedula,
            'titulo'           => $titulo,
            'descripcion'      => $row['descripcion'] ?: null,
            'estado'           => $estado,
            'observaciones'    => null,
            'pdf_archivo'      => null,
            'categoria_id'     => $categoriaId,
            'origen'           => 'csv',
            'import_batch_id'  => $batchId,
        ]);
    }

    private function insertarFilaLog($batchId, $numLinea, $estado, $mensajeError = null, $ticketId = null, $rawLine = null) {
        $stmt = $this->db->prepare("
            INSERT INTO ticket_import_filas (batch_id, numero_linea, estado, mensaje_error, ticket_id, raw_line)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$batchId, $numLinea, $estado, $mensajeError, $ticketId, $rawLine]);
    }

    public function listarLotes($coordinadorCedula, $limite = 50) {
        $limite = min(100, max(1, (int) $limite));
        $stmt = $this->db->prepare("
            SELECT id, nombre_archivo, total_filas, filas_ok, filas_error, estado, crear_clientes_modo, created_at
            FROM ticket_import_batches
            WHERE coordinador_cedula = ?
            ORDER BY created_at DESC
            LIMIT {$limite}
        ");
        $stmt->execute([$coordinadorCedula]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function detalleLoteErrores($batchId, $coordinadorCedula) {
        $stmt = $this->db->prepare("SELECT id FROM ticket_import_batches WHERE id = ? AND coordinador_cedula = ?");
        $stmt->execute([$batchId, $coordinadorCedula]);
        if (!$stmt->fetch()) {
            throw new Exception('Lote no encontrado');
        }
        $stmt = $this->db->prepare("
            SELECT numero_linea, estado, mensaje_error, ticket_id
            FROM ticket_import_filas
            WHERE batch_id = ? AND estado = 'error'
            ORDER BY numero_linea
        ");
        $stmt->execute([$batchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

<?php
require_once __DIR__ . '/../config.php';

/**
 * Importación CSV tipo reparto / foreclosure (cabecera como priemer repartocsv.csv)
 * hacia titulares, propiedades, telefonos, correos, referencias (+ tel/correo ref).
 */
class RepartoImportModel {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Detecta formato reparto por nombres de columnas esperados.
     */
    public static function esCabeceraReparto(array $headers): bool {
        $h = [];
        foreach ($headers as $x) {
            $h[trim((string) $x)] = true;
        }
        return isset($h['First Name']) && isset($h['Surplus Amount']) && isset($h['Case Number']);
    }

    public function filaAsociativa(array $headers, array $data): array {
        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        }
        $assoc = [];
        $n = max(count($headers), count($data));
        for ($i = 0; $i < $n; $i++) {
            $key = isset($headers[$i]) ? trim((string) $headers[$i]) : '';
            if ($key === '') {
                continue;
            }
            $assoc[$key] = isset($data[$i]) ? trim((string) $data[$i]) : '';
        }
        return $assoc;
    }

    public function filaVacia(array $row): bool {
        foreach ($row as $v) {
            if (trim((string) $v) !== '') {
                return false;
            }
        }
        return true;
    }

    private function cut(string $s, int $max): string {
        if (mb_strlen($s, 'UTF-8') <= $max) {
            return $s;
        }
        return mb_substr($s, 0, $max, 'UTF-8');
    }

    private function val(array $row, string $key): string {
        return isset($row[$key]) ? trim((string) $row[$key]) : '';
    }

    private function dnc(?string $raw): ?string {
        if ($raw === null || $raw === '') {
            return null;
        }
        $t = strtoupper(trim($raw));
        if ($t === 'Y' || $t === 'N') {
            return $t[0];
        }
        return null;
    }

    private function edadTitular(?string $age): ?int {
        if ($age === null || trim($age) === '') {
            return null;
        }
        if (!preg_match('/^-?\d+/', trim($age), $m)) {
            return null;
        }
        $n = (int) $m[0];
        if ($n < 0 || $n > 150) {
            return null;
        }
        return $n;
    }

    private function diasInt(?string $v): ?int {
        if ($v === null || trim($v) === '') {
            return null;
        }
        if (!preg_match('/^-?\d+/', trim($v), $m)) {
            return null;
        }
        return (int) $m[0];
    }

    /**
     * Importa una fila de reparto. Transacción interna.
     *
     * @return int id_cliente del titular creado
     */
    public function importarFila(array $row, ?int $archivoCsvId, ?string $coordinadorCedula): int {
        $fn = $this->cut($this->val($row, 'First Name'), 128);
        $ln = $this->cut($this->val($row, 'Last Name'), 128);
        if ($fn === '' && $ln === '') {
            throw new Exception('Fila sin First Name ni Last Name');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('
                INSERT INTO titulares (
                    primer_nombre, apellido, mailing_calle, mailing_ciudad, mailing_estado, mailing_codigo_postal,
                    edad, fallecido, reg_int, base_d, f_correo, agente, prioridad, archivo_csv_id, coordinador_cedula
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ');
            $stmt->execute([
                $fn !== '' ? $fn : null,
                $ln !== '' ? $ln : null,
                $this->cut($this->val($row, 'Mailing Street'), 255) ?: null,
                $this->cut($this->val($row, 'Mailing City'), 120) ?: null,
                $this->cut($this->val($row, 'Mailing State'), 30) ?: null,
                $this->cut($this->val($row, 'Mailing ZIP Code'), 30) ?: null,
                $this->edadTitular($this->val($row, 'Age')),
                $this->dnc($this->val($row, 'Deceased')),
                $this->cut($this->val($row, 'Reg_Int'), 64) ?: null,
                $this->cut($this->val($row, 'BaseD'), 128) ?: null,
                $this->cut($this->val($row, 'F_Correo'), 32) ?: null,
                $this->cut($this->val($row, 'Agente'), 128) ?: null,
                $this->cut($this->val($row, 'Prioridad'), 64) ?: null,
                $archivoCsvId,
                $coordinadorCedula ?: null,
            ]);
            $idCliente = (int) $this->db->lastInsertId();

            $stmtP = $this->db->prepare('
                INSERT INTO propiedades (
                    id_cliente, excedente, facturacion, monetizacion, puja_cierre, puja_apertura, fecha_venta,
                    dias_transcurridos, numero_caso, numero_parcela, tipo_foreclosure,
                    propiedad_calle, propiedad_ciudad, propiedad_estado, propiedad_codigo_postal,
                    condado, fuente
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ');
            $stmtP->execute([
                $idCliente,
                $this->cut($this->val($row, 'Surplus Amount'), 64) ?: null,
                $this->cut($this->val($row, 'Facturacion'), 64) ?: null,
                $this->cut($this->val($row, 'Monetizacion'), 128) ?: null,
                $this->cut($this->val($row, 'Closing Bid'), 64) ?: null,
                $this->cut($this->val($row, 'Opening Bid'), 64) ?: null,
                $this->cut($this->val($row, 'Date Sold'), 32) ?: null,
                $this->diasInt($this->val($row, 'Dias_Transc')),
                $this->cut($this->val($row, 'Case Number'), 128) ?: null,
                $this->cut($this->val($row, 'Parcel Number'), 128) ?: null,
                $this->cut($this->val($row, 'Type of foreclosure'), 128) ?: null,
                $this->cut($this->val($row, 'Property Street'), 255) ?: null,
                $this->cut($this->val($row, 'Property City'), 120) ?: null,
                $this->cut($this->val($row, 'Property State'), 30) ?: null,
                $this->cut($this->val($row, 'Property ZIP Code'), 30) ?: null,
                $this->cut($this->val($row, 'County'), 120) ?: null,
                $this->cut($this->val($row, 'Source'), 600) ?: null,
            ]);

            $stmtTel = $this->db->prepare('
                INSERT INTO telefonos (id_cliente, orden, numero, tipo, dnc_litigator) VALUES (?,?,?,?,?)
            ');
            for ($i = 1; $i <= 5; $i++) {
                $num = $this->cut($this->val($row, "Phone $i"), 80);
                if ($num === '') {
                    continue;
                }
                $tipo = $this->cut($this->val($row, "Phone $i: Type"), 40) ?: null;
                $dnc = $this->dnc($this->val($row, "Phone $i: DNC/Litigator"));
                $stmtTel->execute([$idCliente, $i, $num, $tipo, $dnc]);
            }

            $stmtEm = $this->db->prepare('
                INSERT INTO correos (id_cliente, orden, email) VALUES (?,?,?)
            ');
            for ($i = 1; $i <= 5; $i++) {
                $em = $this->cut($this->val($row, "Email $i"), 255);
                if ($em === '') {
                    continue;
                }
                $stmtEm->execute([$idCliente, $i, $em]);
            }

            $stmtRef = $this->db->prepare('
                INSERT INTO referencias (id_cliente, orden, primer_nombre, apellido, tipo_posible, edad)
                VALUES (?,?,?,?,?,?)
            ');
            $stmtRefTel = $this->db->prepare('
                INSERT INTO referencias_telefonos (id_referencia, orden, numero, tipo, dnc_litigator) VALUES (?,?,?,?,?)
            ');
            $stmtRefEm = $this->db->prepare('
                INSERT INTO referencias_correos (id_referencia, orden, email) VALUES (?,?,?)
            ');

            $ordenRef = 1;
            for ($rel = 1; $rel <= 5; $rel++) {
                $pfx = "RELATIVE $rel: ";
                $rfn = $this->cut($this->val($row, $pfx . 'First Name'), 128);
                $rln = $this->cut($this->val($row, $pfx . 'Last Name'), 128);
                $rtp = $this->cut($this->val($row, $pfx . 'Possible Type'), 128) ?: null;
                $rage = $this->val($row, $pfx . 'Age');
                $rageDb = $rage !== '' ? $this->cut($rage, 32) : null;

                $hasRef = ($rfn !== '' || $rln !== '' || $rtp !== null || $rageDb !== null);
                $hasTel = false;
                $hasEm = false;
                for ($t = 1; $t <= 5; $t++) {
                    if ($this->val($row, $pfx . "Phone $t") !== '') {
                        $hasTel = true;
                        break;
                    }
                }
                for ($e = 1; $e <= 5; $e++) {
                    if ($this->val($row, $pfx . "Email $e") !== '') {
                        $hasEm = true;
                        break;
                    }
                }

                if (!$hasRef && !$hasTel && !$hasEm) {
                    continue;
                }

                $stmtRef->execute([
                    $idCliente,
                    $ordenRef,
                    $rfn !== '' ? $rfn : null,
                    $rln !== '' ? $rln : null,
                    $rtp,
                    $rageDb,
                ]);
                $idRef = (int) $this->db->lastInsertId();

                for ($t = 1; $t <= 5; $t++) {
                    $num = $this->cut($this->val($row, $pfx . "Phone $t"), 80);
                    if ($num === '') {
                        continue;
                    }
                    $tipo = $this->cut($this->val($row, $pfx . "Phone $t: Type"), 40) ?: null;
                    $dnc = $this->dnc($this->val($row, $pfx . "Phone $t: DNC/Litigator"));
                    $stmtRefTel->execute([$idRef, $t, $num, $tipo, $dnc]);
                }
                for ($e = 1; $e <= 5; $e++) {
                    $em = $this->cut($this->val($row, $pfx . "Email $e"), 255);
                    if ($em === '') {
                        continue;
                    }
                    $stmtRefEm->execute([$idRef, $e, $em]);
                }
                $ordenRef++;
            }

            $this->db->commit();
            return $idCliente;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}

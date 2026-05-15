<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'internacional2');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('APP_NAME', 'CRM Internacional');
define('APP_URL', 'http://localhost/crm_internacional');
define('APP_VERSION', '1.0.0');
/** Zona horaria para cómputos de negocio (p. ej. días de mora hasta «hoy»). Ajuste en servidor si aplica otro huso. */
define('APP_TIMEZONE', 'America/Bogota');

// Configuración de seguridad
// Preferir variable de entorno para evitar secretos hardcodeados.
// En desarrollo, permite fallback; en producción se recomienda configurar JWT_SECRET.
if (!defined('JWT_SECRET')) {
    $rawSecret = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET');
    $jwtSecret = is_string($rawSecret) ? trim($rawSecret) : '';
    if ($jwtSecret === '') {
        // Fallback solo para dev/local. Si lo usas en producción, configúralo por ENV.
        $jwtSecret = 'dev_only_change_me';
    }
    define('JWT_SECRET', $jwtSecret);
}
define('SESSION_LIFETIME', 3600); // 1 hora en segundos
define('SESSION_REGENERATION_TIME', 300); // 5 minutos para regenerar ID
/** Nombre de cookie de sesión PHP exclusivo de este proyecto (evita colisión con otros sitios en el mismo dominio/host). */
define('APP_SESSION_NAME', 'internacional2_SID');
define('REMEMBER_ME_LIFETIME', 2592000); // 30 días en segundos

// Configuración de archivos
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB

// Configuración de email (para futuras funcionalidades)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');

// Clase para conexión a la base de datos
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

// Función para obtener la conexión a la base de datos
function getDB() {
    return Database::getInstance()->getConnection();
}

// Función para sanitizar datos de entrada
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Función para generar token de sesión
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Función para verificar si el usuario está logueado
function isLoggedIn() {
    return isset($_SESSION['user_cedula']) && !empty($_SESSION['user_cedula']);
}

// Función para obtener el usuario actual
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT u.*, r.nombre as rol_nombre FROM usuarios u 
                         JOIN roles r ON u.rol_id = r.id 
                         WHERE u.cedula = ? AND u.activo = 1");
    $stmt->execute([$_SESSION['user_cedula']]);
    return $stmt->fetch();
}

// Función para verificar permisos de rol
function hasRole($requiredRole) {
    $user = getCurrentUser();
    if (!$user) return false;

    // Verificar rol exacto para asesor, coordinador y admin
    if ($requiredRole === 'asesor') {
        return $user['rol_nombre'] === 'asesor';
    } elseif ($requiredRole === 'coordinador') {
        return in_array($user['rol_nombre'], ['coordinador', 'admin']);
    } elseif ($requiredRole === 'admin') {
        return $user['rol_nombre'] === 'admin';
    }

    return false;
}

// Función para configurar cookie "Remember Me"
function setRememberMeCookie($userCedula) {
    $token = generateToken(32);
    $expires = time() + REMEMBER_ME_LIFETIME;

    // Guardar token en base de datos
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO remember_tokens (user_cedula, token, expires_at) VALUES (?, ?, ?)
                         ON DUPLICATE KEY UPDATE token = ?, expires_at = ?");
    $stmt->execute([$userCedula, $token, date('Y-m-d H:i:s', $expires), $token, date('Y-m-d H:i:s', $expires)]);

    // Establecer cookie segura
    setcookie('crm_remember', $token, $expires, '/', '', isset($_SERVER['HTTPS']), true);
}

// Función para verificar cookie "Remember Me"
function checkRememberMeCookie() {
    if (!isset($_COOKIE['crm_remember'])) {
        return false;
    }

    $token = $_COOKIE['crm_remember'];
    $db = getDB();

    $stmt = $db->prepare("SELECT user_cedula FROM remember_tokens
                         WHERE token = ? AND expires_at > NOW() AND used = 0");
    $stmt->execute([$token]);
    $result = $stmt->fetch();

    if ($result) {
        // Marcar token como usado y crear nueva sesión
        $stmt = $db->prepare("UPDATE remember_tokens SET used = 1 WHERE token = ?");
        $stmt->execute([$token]);

        // Obtener datos del usuario y crear sesión
        $user = getUserByCedula($result['user_cedula']);
        if ($user && $user['activo']) {
            $_SESSION['user_cedula'] = $user['cedula'];
            $_SESSION['user_usuario'] = $user['usuario'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['nombre'] . ' ' . $user['apellido'];
            $_SESSION['user_role'] = $user['rol_id'];
            $_SESSION['user_role_name'] = $user['rol_nombre'];

            // Nuevo token para futuras sesiones
            setRememberMeCookie($user['cedula']);

            return true;
        }
    }

    // Token inválido, eliminar cookie
    setcookie('crm_remember', '', time() - 3600, '/');
    return false;
}

// Función para obtener usuario por cédula
function getUserByCedula($cedula) {
    $db = getDB();
    $stmt = $db->prepare("SELECT u.*, r.nombre as rol_nombre FROM usuarios u
                         JOIN roles r ON u.rol_id = r.id
                         WHERE u.cedula = ? AND u.activo = 1");
    $stmt->execute([$cedula]);
    return $stmt->fetch();
}

// Función para logout forzado (cierra sesión en todas las pestañas)
function forceLogout() {
    if (isLoggedIn()) {
        $_SESSION['force_logout'] = true;
        logActivity('force_logout', 'Logout forzado desde otra pestaña/dispositivo');
    }
}

// Función para registrar actividad en logs
function logActivity($action, $description = '') {
    if (!isLoggedIn()) return;
    
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO logs_actividad (usuario_cedula, accion, descripcion, ip_address) 
                         VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_cedula'],
        $action,
        $description,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
}

// Función para redireccionar
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Función para mostrar mensajes de error/éxito
function setMessage($message, $type = 'info') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
}

function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $type = $_SESSION['message_type'] ?? 'info';
        unset($_SESSION['message'], $_SESSION['message_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

// Configuración avanzada de sesiones
// Evitar warnings cuando este archivo se incluye después de haber enviado output (p.ej. en tests CLI).
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE && !headers_sent()) {
    // Configurar sesiones seguras
    ini_set('session.cookie_httponly', 1);        // Prevenir XSS
    ini_set('session.use_strict_mode', 1);       // Modo estricto
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    ini_set('session.cookie_lifetime', 0);        // Cookie de sesión

    // Solo HTTPS en producción
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);      // Solo HTTPS
    }

    session_name(APP_SESSION_NAME);
    session_start();

    // Regenerar ID de sesión periódicamente para prevenir fixation
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > SESSION_REGENERATION_TIME) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }

    // Verificar si hay logout forzado desde otra pestaña
    if (isset($_SESSION['force_logout']) && $_SESSION['force_logout']) {
        session_destroy();
        // Ruta absoluta desde APP_URL: un Location relativo falla si el script está en /api/ u otra carpeta.
        header('Location: ' . rtrim(APP_URL, '/') . '/views/login.php');
        exit;
    }
}
?>

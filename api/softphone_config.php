<?php
/**
 * Configuración WebRTC softphone para el asesor autenticado.
 * Credenciales: columnas opcionales usuarios.sip_extension / usuarios.sip_secret
 * o constantes SOFTPHONE_FALLBACK_* en config/asterisk.php
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../config/asterisk.php';

if (!isLoggedIn() || !hasRole('asesor')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$user = getCurrentUser();
$db = getDB();
$asterisk = getWebRTCConfig();

$extension = '';
$secret = '';

try {
    $stmt = $db->prepare('SELECT sip_extension, sip_secret FROM usuarios WHERE cedula = ? LIMIT 1');
    $stmt->execute([$user['cedula']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $ex = isset($row['sip_extension']) ? trim((string) $row['sip_extension']) : '';
        $sec = isset($row['sip_secret']) ? trim((string) $row['sip_secret']) : '';
        if ($ex !== '' && $sec !== '') {
            $extension = $ex;
            $secret = $sec;
        }
    }
} catch (Throwable $e) {
    // Columnas ausentes u otro error: se usará fallback
}

if ($extension === '' && defined('SOFTPHONE_FALLBACK_EXTENSION')) {
    $extension = trim((string) SOFTPHONE_FALLBACK_EXTENSION);
}
if ($secret === '' && defined('SOFTPHONE_FALLBACK_SECRET')) {
    $secret = trim((string) SOFTPHONE_FALLBACK_SECRET);
}

$enabled = ($extension !== '' && $secret !== '');

$iceServers = [];
foreach ($asterisk['iceServers'] ?? [] as $row) {
    $u = isset($row['urls']) ? trim((string) $row['urls']) : '';
    if ($u === '') {
        continue;
    }
    if (stripos($u, 'stun:') !== 0 && stripos($u, 'turn:') !== 0) {
        $u = 'stun:' . $u;
    }
    $iceServers[] = ['urls' => $u];
}

$audioBase = '';
if (defined('APP_URL')) {
    $audioBase = rtrim((string) APP_URL, '/') . '/assets/audio/';
}

$config = [
    'extension' => $extension,
    'password' => $secret,
    'wss_server' => $asterisk['wss_server'] ?? '',
    'sip_domain' => $asterisk['sip_domain'] ?? '',
    'iceServers' => $iceServers,
    'debug_mode' => !empty($asterisk['debug_mode']),
    'trace_sip' => !empty($asterisk['trace_sip']),
    'is_local_network' => false,
    'audioBaseUrl' => $audioBase,
];

echo json_encode([
    'success' => true,
    'enabled' => $enabled,
    'message' => $enabled ? '' : 'Configure sip_extension y sip_secret en su usuario (BD) o SOFTPHONE_FALLBACK_* en config/asterisk.php.',
    'config' => $config,
], JSON_UNESCAPED_SLASHES);

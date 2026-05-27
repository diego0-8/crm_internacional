<?php
/**
 * Configuración de Asterisk/Issabel para WebRTC Softphone
 * Optimizada para conexiones SEGURAS (WSS) vía puerto 8089
 * 
 * CONFIGURACIÓN:
 * - Puerto 8089: Puerto seguro estándar de Asterisk/Issabel (WSS)
 * - Protocolo WSS: Conexión segura WebSocket sobre SSL/TLS
 * 
 * REQUISITOS:
 * 1. El servidor debe tener certificado SSL válido para WSS
 * 2. El puerto 8089 debe estar abierto y escuchando en el servidor
 * 3. La ruta /ws debe estar configurada en http.conf del servidor
 */

// =====================================================================
// CONFIGURACIÓN DEL SERVIDOR
// =====================================================================

// Dominio o IP del PBX
define('ASTERISK_WSS_SERVER', 'pbx.tysbpo.com');
define('ASTERISK_SIP_DOMAIN', 'pbx.tysbpo.com');

// Puerto WebSocket Seguro (WSS) - 8089 (Puerto seguro estándar de Asterisk/Issabel)
// NOTA: Puerto 8089 es el puerto seguro (WSS) estándar de Asterisk/Issabel
// Este puerto requiere conexión segura (wss://) con certificado SSL válido
define('ASTERISK_WSS_PORT', '8089');

// Ruta del WebSocket (Estándar en Issabel/Asterisk es /ws)
define('ASTERISK_WSS_PATH', '/ws');

// Servidores STUN --stun.alphacron.de:3478 % stun.l.google.com:19302
define('ASTERISK_STUN_SERVER', 'stun.alphacron.de:3478');

/**
 * TURN (recomendado en producción si el asesor está fuera de la LAN del PBX).
 * Sin TURN, el navegador no puede enlazar RTP con Asterisk cuando el SDP del PBX
 * solo trae IPs privadas (192.168.x.x). Descomente y complete si tiene coturn/relay.
 */
// define('ASTERISK_TURN_SERVER', 'turn:turn.ejemplo.com:3478');
// define('ASTERISK_TURN_USERNAME', 'usuario');
// define('ASTERISK_TURN_CREDENTIAL', 'clave');

// Modo Debug 
define('ASTERISK_DEBUG_MODE', true);

/**
 * Retorna la configuración de WebRTC como array para el Frontend
 * La URL completa del WebSocket se construye aquí
 */
function getWebRTCConfig()
{
    // Construir la URL completa usando wss:// (WebSocket Seguro) para puerto 8089
    // El puerto 8089 es el puerto seguro estándar que requiere WSS
    $wssUrl = 'wss://' . ASTERISK_WSS_SERVER . ':' . ASTERISK_WSS_PORT . ASTERISK_WSS_PATH;

    return [
        'wss_server' => $wssUrl,
        'sip_domain' => ASTERISK_SIP_DOMAIN,
        'wss_port' => ASTERISK_WSS_PORT,
        'wss_path' => ASTERISK_WSS_PATH,
        'iceServers' => (function () {
            $servers = [];
            if (defined('ASTERISK_STUN_SERVER') && ASTERISK_STUN_SERVER !== '') {
                $stun = ASTERISK_STUN_SERVER;
                if (stripos($stun, 'stun:') !== 0) {
                    $stun = 'stun:' . $stun;
                }
                $servers[] = ['urls' => $stun];
            }
            if (defined('ASTERISK_TURN_SERVER') && ASTERISK_TURN_SERVER !== ''
                && defined('ASTERISK_TURN_USERNAME') && ASTERISK_TURN_USERNAME !== ''
                && defined('ASTERISK_TURN_CREDENTIAL') && ASTERISK_TURN_CREDENTIAL !== '') {
                $turn = ASTERISK_TURN_SERVER;
                if (stripos($turn, 'turn:') !== 0 && stripos($turn, 'turns:') !== 0) {
                    $turn = 'turn:' . $turn;
                }
                $servers[] = [
                    'urls' => $turn,
                    'username' => ASTERISK_TURN_USERNAME,
                    'credential' => ASTERISK_TURN_CREDENTIAL,
                ];
            }
            return $servers;
        })(),
        'debug_mode' => ASTERISK_DEBUG_MODE,
        'trace_sip' => true
    ];
}

/**
 * Credenciales SIP/WebRTC de respaldo cuando la tabla usuarios no tiene columnas sip_*.
 * En producción use sip_extension / sip_secret por usuario (ver database/migration_softphone_usuarios.sql).
 */
if (!defined('SOFTPHONE_FALLBACK_EXTENSION')) {
    define('SOFTPHONE_FALLBACK_EXTENSION', '');
}
if (!defined('SOFTPHONE_FALLBACK_SECRET')) {
    define('SOFTPHONE_FALLBACK_SECRET', '');
}

?>
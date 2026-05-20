<?php
/** Icono de pestaña del navegador (favicon). */
$faviconUrl = rtrim(APP_URL, '/') . '/img/pestaña_logo.png';
?>
<link rel="icon" type="image/png" href="<?php echo htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8'); ?>">
<link rel="apple-touch-icon" href="<?php echo htmlspecialchars($faviconUrl, ENT_QUOTES, 'UTF-8'); ?>">

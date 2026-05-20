<?php
/** Favicon, base URL para recursos y navegación con URL limpia. */
require __DIR__ . '/favicon.php';
$appBase = rtrim(APP_URL, '/') . '/';
?>
<base href="<?php echo htmlspecialchars($appBase, ENT_QUOTES, 'UTF-8'); ?>">
<script>
(function () {
    var APP_HOME = <?php echo json_encode($appBase, JSON_UNESCAPED_SLASHES); ?>;

    window.appNav = function (route, params) {
        var url = new URL(APP_HOME, window.location.origin);
        url.searchParams.set('_route', route);
        params = params || {};
        Object.keys(params).forEach(function (key) {
            if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
                url.searchParams.set(key, params[key]);
            }
        });
        return url.toString();
    };

    window.appGo = function (route, params) {
        window.location.href = window.appNav(route, params || {});
    };

    window.appGoLogin = function () {
        window.appGo('login');
    };
})();
</script>

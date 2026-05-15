<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controller/LoginController.php';

// Sesión: nombre único en config.php (APP_SESSION_NAME). Respaldo si config no pudo iniciarla (p. ej. headers ya enviados).
if (session_status() === PHP_SESSION_NONE) {
    session_name(APP_SESSION_NAME);
    session_start();
}

// Verificar si hay cookie "Remember Me" válida
if (!isLoggedIn() && checkRememberMeCookie()) {
    // Usuario recordado, redirigir al dashboard
    $loginController = new LoginController();
    $loginController->redirectToDashboard();
    exit;
}

// Crear instancia del controlador
$loginController = new LoginController();

// Procesar la petición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginController->processLogin();
}

// Obtener variables de la sesión para mostrar en la vista
$error = $_SESSION['login_error'] ?? '';
$usuario = $_SESSION['login_usuario'] ?? '';

// Limpiar variables de sesión después de usarlas
unset($_SESSION['login_error'], $_SESSION['login_usuario']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/login.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="../img/logo2.png" alt="Logo CRM">
            <p>Iniciar Sesión</p>
        </div>

        <?php if (isset($error) && !empty($error)): ?>
            <?php
            // Determinar el tipo de error y el icono correspondiente
            $errorType = 'error';
            $icon = 'fas fa-exclamation-triangle';
            
            if (strpos($error, 'Usuario no encontrado') !== false) {
                $errorType = 'error-user';
                $icon = 'fas fa-user-times';
            } elseif (strpos($error, 'Contraseña incorrecta') !== false) {
                $errorType = 'error-password';
                $icon = 'fas fa-lock';
            } elseif (strpos($error, 'deshabilitada') !== false) {
                $errorType = 'error-disabled';
                $icon = 'fas fa-ban';
            }
            ?>
            <div class="error-message <?php echo $errorType; ?>">
                <i class="<?php echo $icon; ?>"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="usuario">Usuario</label>
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" id="usuario" name="usuario" class="form-control" 
                           placeholder="tu usuario" required value="<?php echo htmlspecialchars($usuario ?? ''); ?>">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Tu contraseña" required>
                    <i class="fas fa-eye password-toggle" id="passwordToggle"></i>
                </div>
            </div>

            <div class="form-group remember-me">
                <label class="checkbox-label">
                    <input type="checkbox" id="remember_me" name="remember_me" value="1">
                    <span class="checkmark"></span>
                    Recordarme por 30 días
                </label>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
            </button>
        </form>

        <div class="login-footer">
            <p>¿Problemas para acceder? <a href="#">Contactar Soporte</a></p>
        </div>
    </div>

    <script>
        // Animación de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.querySelector('.login-container');
            container.style.opacity = '0';
            container.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                container.style.transition = 'all 0.6s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        });

        // Mostrar/ocultar contraseña
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.getElementById('passwordToggle');
            
            toggleBtn.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    toggleBtn.className = 'fas fa-eye-slash password-toggle';
                } else {
                    passwordInput.type = 'password';
                    toggleBtn.className = 'fas fa-eye password-toggle';
                }
            });
        });
    </script>
</body>
</html>

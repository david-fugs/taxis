<?php
require_once '../../config.php';

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se usan cookies de sesión, eliminar también
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Redirigir al login
header('Location: ' . BASE_URL . 'modules/auth/login.php');
exit();
?>

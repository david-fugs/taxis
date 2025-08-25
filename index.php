<?php
require_once 'config.php';

// Si está logueado, redirigir al dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Si no está logueado, redirigir al login
header('Location: modules/auth/login.php');
exit();
?>

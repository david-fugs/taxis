<?php
session_start();

// Configuración de la aplicación
define('APP_NAME', 'Sistema de Gestión de Taxis');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/taxis/');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Crear directorios de uploads si no existen
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
if (!file_exists(UPLOAD_PATH . 'fotos/')) {
    mkdir(UPLOAD_PATH . 'fotos/', 0755, true);
}
if (!file_exists(UPLOAD_PATH . 'documentos/')) {
    mkdir(UPLOAD_PATH . 'documentos/', 0755, true);
}

// Incluir conexión a la base de datos
require_once 'conexion.php';

// Función para verificar si el usuario está autenticado
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Función para verificar si es admin
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Función para redireccionar si no está autenticado
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'modules/auth/login.php');
        exit();
    }
}

// Función para redireccionar si no es admin
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit();
    }
}

// Función para limpiar datos de entrada
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para formatear fechas
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

// Función para calcular edad
function calculateAge($birthdate) {
    $today = new DateTime();
    $birth = new DateTime($birthdate);
    return $today->diff($birth)->y;
}
?>

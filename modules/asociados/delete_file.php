<?php
require_once '../../config.php';
requireLogin();

$file_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$asociado_id = isset($_GET['asociado']) ? (int)$_GET['asociado'] : 0;

if ($file_id === 0 || $asociado_id === 0) {
    header('Location: index.php');
    exit();
}

// Obtener información del archivo
$stmt = $mysqli->prepare("SELECT * FROM asociados_archivos WHERE id = ? AND asociado_id = ?");
$stmt->bind_param("ii", $file_id, $asociado_id);
$stmt->execute();
$result = $stmt->get_result();
$archivo = $result->fetch_assoc();

if (!$archivo) {
    $_SESSION['message'] = 'Archivo no encontrado.';
    $_SESSION['message_type'] = 'error';
    header("Location: edit.php?id=$asociado_id");
    exit();
}

// Eliminar archivo físico
if (file_exists($archivo['ruta_archivo'])) {
    unlink($archivo['ruta_archivo']);
}

// Eliminar registro de la base de datos
$delete_stmt = $mysqli->prepare("DELETE FROM asociados_archivos WHERE id = ?");
$delete_stmt->bind_param("i", $file_id);

if ($delete_stmt->execute()) {
    $_SESSION['message'] = 'Archivo eliminado exitosamente.';
    $_SESSION['message_type'] = 'success';
} else {
    $_SESSION['message'] = 'Error al eliminar el archivo.';
    $_SESSION['message_type'] = 'error';
}

$delete_stmt->close();
header("Location: edit.php?id=$asociado_id");
exit();
?>

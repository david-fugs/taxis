<?php
require_once '../../config.php';
require_once '../../conexion.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar que el usuario tenga permisos
if (!isLoggedIn() || !isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$estado = isset($_POST['estado']) ? trim($_POST['estado']) : '';

$allowed = ['activo', 'inactivo', 'mantenimiento', 'vendido'];
if ($id <= 0 || !in_array($estado, $allowed)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
    exit();
}

$stmt = $mysqli->prepare("UPDATE parque_automotor SET estado = ? WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la consulta']);
    exit();
}

$stmt->bind_param('si', $estado, $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Estado actualizado', 'estado' => $estado]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el estado']);
}

$stmt->close();

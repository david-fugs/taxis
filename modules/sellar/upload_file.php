<?php
require_once '../../config.php';
require_once '../../conexion.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$sellar_id = isset($_POST['sellar_id']) ? (int)$_POST['sellar_id'] : 0;
$tipo_archivo = $_POST['tipo_archivo'] ?? 'foto_vehiculo';
$descripcion = trim($_POST['descripcion'] ?? '');
$usuario_id = $_SESSION['user_id'];

if (!$sellar_id) {
    echo json_encode(['success' => false, 'message' => 'ID de sellado no válido']);
    exit();
}

// Verificar que el registro de sellado existe
$sql = "SELECT id FROM sellar WHERE id = $sellar_id";
$result = $mysqli->query($sql);
if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Registro de sellado no encontrado']);
    exit();
}

// Verificar archivo
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Error al subir el archivo']);
    exit();
}

$archivo = $_FILES['archivo'];
$nombre_original = $archivo['name'];
$tmp_name = $archivo['tmp_name'];
$tamaño = $archivo['size'];
$tipo_mime = $archivo['type'];

// Validar tipo de archivo
$extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
$extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));

if (!in_array($extension, $extensiones_permitidas)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']);
    exit();
}

// Validar tamaño (10MB máximo)
if ($tamaño > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande (máx. 10MB)']);
    exit();
}

// Crear directorio si no existe
$upload_dir = '../../uploads/sellar/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Generar nombre único para el archivo
$nombre_archivo = date('Y-m-d_H-i-s') . '_' . uniqid() . '.' . $extension;
$ruta_destino = $upload_dir . $nombre_archivo;

// Mover archivo
if (!move_uploaded_file($tmp_name, $ruta_destino)) {
    echo json_encode(['success' => false, 'message' => 'Error al guardar el archivo']);
    exit();
}

// Guardar en base de datos
$sql = "INSERT INTO sellar_archivos (
    sellar_id, nombre_original, nombre_archivo, tipo_archivo, descripcion, 
    ruta_archivo, tamaño_archivo, tipo_mime, usuario_id
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    // Si falla, eliminar archivo
    unlink($ruta_destino);
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
    exit();
}

$ruta_relativa = 'uploads/sellar/' . $nombre_archivo;
$stmt->bind_param(
    'isssssisi',
    $sellar_id,
    $nombre_original,
    $nombre_archivo,
    $tipo_archivo,
    $descripcion,
    $ruta_relativa,
    $tamaño,
    $tipo_mime,
    $usuario_id
);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true, 
        'message' => 'Archivo subido correctamente',
        'archivo_id' => $mysqli->insert_id
    ]);
} else {
    // Si falla, eliminar archivo
    unlink($ruta_destino);
    echo json_encode(['success' => false, 'message' => 'Error al guardar en la base de datos']);
}

$stmt->close();
?>
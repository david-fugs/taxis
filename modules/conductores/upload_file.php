<?php
require_once '../../config.php';
require_once '../../conexion.php';

// Solo responder JSON
header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

// Solo procesar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

$conductor_id = $_POST['conductor_id'] ?? 0;

// Validar conductor_id
if (!$conductor_id || !is_numeric($conductor_id)) {
    echo json_encode(['success' => false, 'message' => 'ID de conductor inválido']);
    exit();
}

// Verificar que el conductor existe
$sql = "SELECT nombre_completo FROM conductores WHERE id = $conductor_id";
$result = $mysqli->query($sql);
if (!$result || !$result->fetch_assoc()) {
    echo json_encode(['success' => false, 'message' => 'Conductor no encontrado']);
    exit();
}

// Verificar que se envió un archivo
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor.',
        UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido.',
        UPLOAD_ERR_PARTIAL => 'El archivo fue subido parcialmente.',
        UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo.',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal.',
        UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco.',
        UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo.'
    ];
    
    $error_code = $_FILES['archivo']['error'] ?? UPLOAD_ERR_NO_FILE;
    $error_message = $error_messages[$error_code] ?? 'Error desconocido al subir el archivo.';
    
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit();
}

$allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
$max_file_size = 10 * 1024 * 1024; // 10MB

$file = $_FILES['archivo'];
$nombre_archivo = $file['name'];
$tipo_archivo = $_POST['tipo_archivo'] ?? 'general';
$descripcion = $_POST['descripcion'] ?? '';

// Validar extensión
$extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
if (!in_array($extension, $allowed_extensions)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Extensiones permitidas: ' . implode(', ', $allowed_extensions)]);
    exit();
}

// Validar tamaño
if ($file['size'] > $max_file_size) {
    echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande. Tamaño máximo: 10MB']);
    exit();
}

// Crear directorio si no existe
$upload_dir = '../../uploads/documentos/';
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Error al crear el directorio de subida']);
        exit();
    }
}

// Generar nombre único
$nombre_unico = date('YmdHis') . '_' . uniqid() . '.' . $extension;
$ruta_completa = $upload_dir . $nombre_unico;

if (move_uploaded_file($file['tmp_name'], $ruta_completa)) {
    // Escapar datos para la base de datos
    $nombre_unico_esc = mysqli_real_escape_string($mysqli, $nombre_unico);
    $nombre_archivo_esc = mysqli_real_escape_string($mysqli, $nombre_archivo);
    $tipo_archivo_esc = mysqli_real_escape_string($mysqli, $tipo_archivo);
    $descripcion_esc = mysqli_real_escape_string($mysqli, $descripcion);
    
    // Guardar en base de datos
    $sql = "INSERT INTO conductores_archivos (conductor_id, nombre_archivo, nombre_original, tipo_archivo, descripcion, fecha_subida, usuario_id) VALUES ($conductor_id, '$nombre_unico_esc', '$nombre_archivo_esc', '$tipo_archivo_esc', '$descripcion_esc', NOW(), {$_SESSION['user_id']})";
    
    if ($mysqli->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Archivo subido correctamente']);
    } else {
        // Si falla la BD, eliminar el archivo
        unlink($ruta_completa);
        echo json_encode(['success' => false, 'message' => 'Error al guardar en la base de datos: ' . $mysqli->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error al subir el archivo al servidor']);
}
?>

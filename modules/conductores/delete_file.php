<?php
require_once '../../config.php';
require_once '../../mysqli.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $archivo_id = $_POST['archivo_id'] ?? 0;
    
    if (!$archivo_id) {
        echo json_encode(['success' => false, 'message' => 'ID de archivo inválido']);
        exit();
    }
    
    try {
        // Obtener información del archivo
        $sql = "SELECT nombre_archivo FROM conductores_archivos WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $archivo_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $archivo = $result->fetch_assoc();
        
        if (!$archivo) {
            echo json_encode(['success' => false, 'message' => 'Archivo no encontrado']);
            exit();
        }
        
        // Eliminar archivo físico
        $ruta_archivo = '../../uploads/documentos/' . $archivo['nombre_archivo'];
        if (file_exists($ruta_archivo)) {
            unlink($ruta_archivo);
        }
        
        // Eliminar de la base de datos
        $sql = "DELETE FROM conductores_archivos WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $archivo_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Archivo eliminado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el archivo de la base de datos']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>

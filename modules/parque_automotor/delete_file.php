<?php
require_once '../../config.php';
requireLogin();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $file_id = (int)($_POST['file_id'] ?? 0);
    
    if ($file_id > 0) {
        // Obtener información del archivo
        $stmt = $mysqli->prepare("SELECT * FROM parque_automotor_archivos WHERE id = ?");
        $stmt->bind_param("i", $file_id);
        $stmt->execute();
        $file = $stmt->get_result()->fetch_assoc();
        
        if ($file) {
            // Eliminar archivo físico
            if (file_exists($file['ruta_archivo'])) {
                unlink($file['ruta_archivo']);
            }
            
            // Eliminar registro de la base de datos
            $delete_stmt = $mysqli->prepare("DELETE FROM parque_automotor_archivos WHERE id = ?");
            $delete_stmt->bind_param("i", $file_id);
            
            if ($delete_stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Archivo eliminado exitosamente.';
            } else {
                $response['message'] = 'Error al eliminar el archivo de la base de datos.';
            }
            $delete_stmt->close();
        } else {
            $response['message'] = 'Archivo no encontrado.';
        }
        $stmt->close();
    } else {
        $response['message'] = 'ID de archivo inválido.';
    }
} else {
    $response['message'] = 'Método no permitido.';
}

echo json_encode($response);
?>

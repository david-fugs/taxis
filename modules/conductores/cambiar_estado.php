<?php
require_once '../../config.php';
require_once '../../conexion.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $estado = $_POST['estado'] ?? '';
    
    if (!$id || !in_array($estado, ['activo', 'inactivo', 'suspendido'])) {
        echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
        exit();
    }
    
    try {
        $sql = "UPDATE conductores SET estado = ?, usuario_actualizacion = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sii", $estado, $_SESSION['user_id'], $id);
        
        if ($stmt->execute()) {
            $mensaje = $estado === 'activo' ? 'Conductor activado correctamente' : 
                      ($estado === 'suspendido' ? 'Conductor suspendido correctamente' : 'Estado actualizado correctamente');
            
            echo json_encode(['success' => true, 'message' => $mensaje]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>

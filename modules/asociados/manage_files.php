<?php
require_once '../../config.php';
requireLogin();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'delete_file':
            $file_id = (int)($_POST['file_id'] ?? 0);
            
            if ($file_id > 0) {
                // Obtener información del archivo
                $stmt = $mysqli->prepare("SELECT * FROM asociados_archivos WHERE id = ?");
                $stmt->bind_param("i", $file_id);
                $stmt->execute();
                $file = $stmt->get_result()->fetch_assoc();
                
                if ($file) {
                    // Eliminar archivo físico
                    if (file_exists($file['ruta_archivo'])) {
                        unlink($file['ruta_archivo']);
                    }
                    
                    // Eliminar registro de la base de datos
                    $delete_stmt = $mysqli->prepare("DELETE FROM asociados_archivos WHERE id = ?");
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
            break;
            
        case 'upload_file':
            $asociado_id = (int)($_POST['asociado_id'] ?? 0);
            $tipo_documento = $_POST['tipo_documento'] ?? 'otro';
            $descripcion = cleanInput($_POST['descripcion'] ?? '');
            
            if ($asociado_id > 0 && isset($_FILES['archivo'])) {
                $file = $_FILES['archivo'];
                
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $file_tmp = $file['tmp_name'];
                    $file_name = $file['name'];
                    $file_size = $file['size'];
                    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    
                    // Obtener cédula del asociado
                    $cedula_stmt = $mysqli->prepare("SELECT cedula FROM asociados WHERE id = ?");
                    $cedula_stmt->bind_param("i", $asociado_id);
                    $cedula_stmt->execute();
                    $cedula_result = $cedula_stmt->get_result()->fetch_assoc();
                    
                    if ($cedula_result) {
                        $cedula = $cedula_result['cedula'];
                        $new_filename = $cedula . '_doc_' . time() . '.' . $file_extension;
                        $file_path = UPLOAD_PATH . 'documentos/' . $new_filename;
                        
                        if ($file_size <= MAX_FILE_SIZE) {
                            if (move_uploaded_file($file_tmp, $file_path)) {
                                // Guardar en base de datos
                                $insert_stmt = $mysqli->prepare("INSERT INTO asociados_archivos (asociado_id, nombre_archivo, nombre_original, tipo_archivo, tamaño, ruta_archivo, tipo_documento, descripcion, usuario_subida) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                $file_type = mime_content_type($file_path);
                                $insert_stmt->bind_param("isssiissi", $asociado_id, $new_filename, $file_name, $file_type, $file_size, $file_path, $tipo_documento, $descripcion, $_SESSION['user_id']);
                                
                                if ($insert_stmt->execute()) {
                                    $response['success'] = true;
                                    $response['message'] = 'Archivo subido exitosamente.';
                                    $response['file_id'] = $mysqli->insert_id;
                                } else {
                                    $response['message'] = 'Error al guardar el archivo en la base de datos.';
                                }
                                $insert_stmt->close();
                            } else {
                                $response['message'] = 'Error al subir el archivo al servidor.';
                            }
                        } else {
                            $response['message'] = 'El archivo es demasiado grande (máximo 5MB).';
                        }
                    } else {
                        $response['message'] = 'Asociado no encontrado.';
                    }
                    $cedula_stmt->close();
                } else {
                    $response['message'] = 'Error en la subida del archivo.';
                }
            } else {
                $response['message'] = 'Datos incompletos para subir el archivo.';
            }
            break;
            
        default:
            $response['message'] = 'Acción no válida.';
            break;
    }
} else {
    $response['message'] = 'Método no permitido.';
}

echo json_encode($response);
?>

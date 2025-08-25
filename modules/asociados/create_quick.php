<?php
require_once '../../config.php';
requireLogin();

$success = false;
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recopilar todos los datos del formulario
    $data = [
        'cedula' => cleanInput($_POST['cedula']),
        'nombres' => cleanInput($_POST['nombres']),
        'apellidos' => cleanInput($_POST['apellidos']),
        'direccion' => cleanInput($_POST['direccion']),
        'ciudad' => cleanInput($_POST['ciudad']),
        'telefono1' => cleanInput($_POST['telefono1']),
        'telefono2' => cleanInput($_POST['telefono2']),
        'celular' => cleanInput($_POST['celular']),
        'lugar_nacimiento' => cleanInput($_POST['lugar_nacimiento']),
        'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?: null,
        'edad' => (int)$_POST['edad'] ?: null,
        'rh' => cleanInput($_POST['rh']),
        'estado_civil' => $_POST['estado_civil'],
        'fecha_ingreso' => $_POST['fecha_ingreso'] ?: null,
        'conyuge' => cleanInput($_POST['conyuge']),
        'urgencia_avisar' => cleanInput($_POST['urgencia_avisar']),
        'otro_avisar' => cleanInput($_POST['otro_avisar']),
        'direccion_avisar' => cleanInput($_POST['direccion_avisar']),
        'telefono_avisar' => cleanInput($_POST['telefono_avisar']),
        'observaciones' => cleanInput($_POST['observaciones']),
        'placa_carro' => cleanInput($_POST['placa_carro']),
        'marca' => cleanInput($_POST['marca']),
        'modelo' => cleanInput($_POST['modelo']),
        'nib' => cleanInput($_POST['nib']),
        'tarjeta_operacion' => cleanInput($_POST['tarjeta_operacion']),
        'beneficiario_funebre' => cleanInput($_POST['beneficiario_funebre']),
        'beneficiario_auxilio_muerte' => cleanInput($_POST['beneficiario_auxilio_muerte']),
        'email' => cleanInput($_POST['email'])
    ];
    
    // Validaciones básicas
    if (empty($data['cedula']) || empty($data['nombres']) || empty($data['apellidos'])) {
        $message = 'La cédula, nombres y apellidos son obligatorios.';
    } else {
        // Verificar si la cédula ya existe
        $check_stmt = $mysqli->prepare("SELECT id FROM asociados WHERE cedula = ?");
        $check_stmt->bind_param("s", $data['cedula']);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $message = 'Ya existe un asociado con esta cédula.';
        } else {
            // Procesar foto si se subió
            $foto_nombre = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $foto_tmp = $_FILES['foto']['tmp_name'];
                $foto_extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $foto_nombre = $data['cedula'] . '_foto.' . $foto_extension;
                $foto_path = UPLOAD_PATH . 'fotos/' . $foto_nombre;
                
                // Validar tipo de archivo
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($foto_extension, $allowed_types)) {
                    $message = 'Solo se permiten archivos de imagen (JPG, PNG, GIF).';
                } elseif ($_FILES['foto']['size'] > MAX_FILE_SIZE) {
                    $message = 'El archivo de foto es demasiado grande (máximo 5MB).';
                } else {
                    if (!move_uploaded_file($foto_tmp, $foto_path)) {
                        $message = 'Error al subir la foto.';
                    }
                }
            }
            
            if (empty($message)) {
                // Insertar asociado completo
                $sql = "INSERT INTO asociados (
                    cedula, nombres, apellidos, direccion, ciudad, telefono1, telefono2, celular,
                    lugar_nacimiento, fecha_nacimiento, edad, rh, estado_civil, fecha_ingreso,
                    conyuge, urgencia_avisar, otro_avisar, direccion_avisar, telefono_avisar,
                    observaciones, placa_carro, marca, modelo, nib, tarjeta_operacion,
                    beneficiario_funebre, beneficiario_auxilio_muerte, email, foto, usuario_creacion
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )";
                
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("ssssssssssississsssssssssssssi",
                    $data['cedula'], $data['nombres'], $data['apellidos'], $data['direccion'],
                    $data['ciudad'], $data['telefono1'], $data['telefono2'], $data['celular'],
                    $data['lugar_nacimiento'], $data['fecha_nacimiento'], $data['edad'], $data['rh'],
                    $data['estado_civil'], $data['fecha_ingreso'], $data['conyuge'], $data['urgencia_avisar'],
                    $data['otro_avisar'], $data['direccion_avisar'], $data['telefono_avisar'],
                    $data['observaciones'], $data['placa_carro'], $data['marca'], $data['modelo'],
                    $data['nib'], $data['tarjeta_operacion'], $data['beneficiario_funebre'],
                    $data['beneficiario_auxilio_muerte'], $data['email'], $foto_nombre, $_SESSION['user_id']
                );
                
                if ($stmt->execute()) {
                    $asociado_id = $mysqli->insert_id;
                    
                    // Procesar archivos adicionales
                    if (isset($_FILES['documentos']) && !empty($_FILES['documentos']['name'][0])) {
                        $upload_errors = [];
                        
                        for ($i = 0; $i < count($_FILES['documentos']['name']); $i++) {
                            if ($_FILES['documentos']['error'][$i] === UPLOAD_ERR_OK) {
                                $file_tmp = $_FILES['documentos']['tmp_name'][$i];
                                $file_name = $_FILES['documentos']['name'][$i];
                                $file_size = $_FILES['documentos']['size'][$i];
                                $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                                
                                $new_filename = $data['cedula'] . '_doc_' . time() . '_' . $i . '.' . $file_extension;
                                $file_path = UPLOAD_PATH . 'documentos/' . $new_filename;
                                
                                if ($file_size <= MAX_FILE_SIZE) {
                                    if (move_uploaded_file($file_tmp, $file_path)) {
                                        // Guardar en base de datos
                                        $file_stmt = $mysqli->prepare("INSERT INTO asociados_archivos (asociado_id, nombre_archivo, nombre_original, tipo_archivo, tamaño, ruta_archivo, usuario_subida) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                        $file_type = mime_content_type($file_path);
                                        $file_stmt->bind_param("isssiis", $asociado_id, $new_filename, $file_name, $file_type, $file_size, $file_path, $_SESSION['user_id']);
                                        $file_stmt->execute();
                                        $file_stmt->close();
                                    } else {
                                        $upload_errors[] = "Error al subir el archivo: $file_name";
                                    }
                                } else {
                                    $upload_errors[] = "Archivo $file_name es demasiado grande (máximo 5MB)";
                                }
                            }
                        }
                        
                        if (!empty($upload_errors)) {
                            $message = 'Asociado creado, pero hubo errores con algunos archivos: ' . implode(', ', $upload_errors);
                        } else {
                            $success = true;
                            $message = 'Asociado creado exitosamente con todos los archivos.';
                        }
                    } else {
                        $success = true;
                        $message = 'Asociado creado exitosamente.';
                    }
                } else {
                    $message = 'Error al crear el asociado: ' . $mysqli->error;
                }
                $stmt->close();
            }
        }
        $check_stmt->close();
    }
}

// Redireccionar con mensaje
$_SESSION['message'] = $message;
$_SESSION['message_type'] = $success ? 'success' : 'error';
header('Location: index.php');
exit();
?>

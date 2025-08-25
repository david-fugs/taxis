<?php
require_once '../../config.php';
requireLogin();

$page_title = 'Editar Asociado';
$success_message = '';
$error_message = '';
$asociado = null;

// Obtener ID del asociado
$asociado_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($asociado_id === 0) {
    header('Location: index.php');
    exit();
}

// Obtener datos del asociado
$stmt = $mysqli->prepare("SELECT * FROM asociados WHERE id = ?");
$stmt->bind_param("i", $asociado_id);
$stmt->execute();
$result = $stmt->get_result();
$asociado = $result->fetch_assoc();

if (!$asociado) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recopilar datos del formulario
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
        $error_message = 'La cédula, nombres y apellidos son obligatorios.';
    } else {
        // Verificar si la cédula ya existe (excluyendo el actual)
        $check_stmt = $mysqli->prepare("SELECT id FROM asociados WHERE cedula = ? AND id != ?");
        $check_stmt->bind_param("si", $data['cedula'], $asociado_id);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error_message = 'Ya existe otro asociado con esta cédula.';
        } else {
            // Procesar nueva foto si se subió
            $foto_nombre = $asociado['foto']; // Mantener la foto actual por defecto
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $foto_tmp = $_FILES['foto']['tmp_name'];
                $foto_extension = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                $foto_nombre_new = $data['cedula'] . '_foto.' . $foto_extension;
                $foto_path = UPLOAD_PATH . 'fotos/' . $foto_nombre_new;
                
                // Validar tipo de archivo
                $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                if (!in_array($foto_extension, $allowed_types)) {
                    $error_message = 'Solo se permiten archivos de imagen (JPG, PNG, GIF).';
                } elseif ($_FILES['foto']['size'] > MAX_FILE_SIZE) {
                    $error_message = 'El archivo de foto es demasiado grande (máximo 5MB).';
                } else {
                    // Eliminar foto anterior si existe
                    if (!empty($asociado['foto']) && file_exists(UPLOAD_PATH . 'fotos/' . $asociado['foto'])) {
                        unlink(UPLOAD_PATH . 'fotos/' . $asociado['foto']);
                    }
                    
                    if (move_uploaded_file($foto_tmp, $foto_path)) {
                        $foto_nombre = $foto_nombre_new;
                    } else {
                        $error_message = 'Error al subir la nueva foto.';
                    }
                }
            }
            
            if (empty($error_message)) {
                // Actualizar asociado
                $sql = "UPDATE asociados SET 
                    cedula = ?, nombres = ?, apellidos = ?, direccion = ?, ciudad = ?, 
                    telefono1 = ?, telefono2 = ?, celular = ?, lugar_nacimiento = ?, 
                    fecha_nacimiento = ?, edad = ?, rh = ?, estado_civil = ?, fecha_ingreso = ?,
                    conyuge = ?, urgencia_avisar = ?, otro_avisar = ?, direccion_avisar = ?, 
                    telefono_avisar = ?, observaciones = ?, placa_carro = ?, marca = ?, 
                    modelo = ?, nib = ?, tarjeta_operacion = ?, beneficiario_funebre = ?, 
                    beneficiario_auxilio_muerte = ?, email = ?, foto = ?, usuario_actualizacion = ?
                    WHERE id = ?";
                
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("ssssssssssisssssssssssssssssii",
                    $data['cedula'], $data['nombres'], $data['apellidos'], $data['direccion'],
                    $data['ciudad'], $data['telefono1'], $data['telefono2'], $data['celular'],
                    $data['lugar_nacimiento'], $data['fecha_nacimiento'], $data['edad'], $data['rh'],
                    $data['estado_civil'], $data['fecha_ingreso'], $data['conyuge'], $data['urgencia_avisar'],
                    $data['otro_avisar'], $data['direccion_avisar'], $data['telefono_avisar'],
                    $data['observaciones'], $data['placa_carro'], $data['marca'], $data['modelo'],
                    $data['nib'], $data['tarjeta_operacion'], $data['beneficiario_funebre'],
                    $data['beneficiario_auxilio_muerte'], $data['email'], $foto_nombre, 
                    $_SESSION['user_id'], $asociado_id
                );
                
                if ($stmt->execute()) {
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
                                    } else {
                                        $upload_errors[] = "Error al subir el archivo: $file_name";
                                    }
                                } else {
                                    $upload_errors[] = "Archivo $file_name es demasiado grande";
                                }
                            }
                        }
                        
                        if (!empty($upload_errors)) {
                            $error_message = implode('<br>', $upload_errors);
                        }
                    }
                    
                    if (empty($error_message)) {
                        $success_message = 'Asociado actualizado exitosamente.';
                        // Recargar datos actualizados
                        $stmt_reload = $mysqli->prepare("SELECT * FROM asociados WHERE id = ?");
                        $stmt_reload->bind_param("i", $asociado_id);
                        $stmt_reload->execute();
                        $asociado = $stmt_reload->get_result()->fetch_assoc();
                    }
                } else {
                    $error_message = 'Error al actualizar el asociado: ' . $mysqli->error;
                }
                $stmt->close();
            }
        }
        $check_stmt->close();
    }
}

// Obtener archivos del asociado
$archivos_query = $mysqli->prepare("SELECT * FROM asociados_archivos WHERE asociado_id = ? ORDER BY fecha_subida DESC");
$archivos_query->bind_param("i", $asociado_id);
$archivos_query->execute();
$archivos = $archivos_query->get_result();

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-user-edit me-2"></i>Editar Asociado: <?php echo htmlspecialchars($asociado['nombres'] . ' ' . $asociado['apellidos']); ?>
                    </h4>
                    <div>
                        <a href="view.php?id=<?php echo $asociado_id; ?>" class="btn btn-info me-2">
                            <i class="fas fa-eye me-2"></i>Ver Detalles
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver a la Lista
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <!-- Información Personal -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Información Personal</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="cedula" class="form-label">Cédula *</label>
                                        <input type="text" class="form-control cedula-input" id="cedula" name="cedula" 
                                               value="<?php echo htmlspecialchars($asociado['cedula']); ?>" required>
                                        <div class="invalid-feedback">Por favor, ingrese la cédula.</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="nombres" class="form-label">Nombres *</label>
                                        <input type="text" class="form-control" id="nombres" name="nombres" 
                                               value="<?php echo htmlspecialchars($asociado['nombres']); ?>" required>
                                        <div class="invalid-feedback">Por favor, ingrese los nombres.</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="apellidos" class="form-label">Apellidos *</label>
                                        <input type="text" class="form-control" id="apellidos" name="apellidos" 
                                               value="<?php echo htmlspecialchars($asociado['apellidos']); ?>" required>
                                        <div class="invalid-feedback">Por favor, ingrese los apellidos.</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" 
                                               value="<?php echo $asociado['fecha_nacimiento']; ?>">
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="edad" class="form-label">Edad</label>
                                        <input type="number" class="form-control" id="edad" name="edad" 
                                               value="<?php echo $asociado['edad']; ?>" min="18" max="100">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="rh" class="form-label">RH</label>
                                        <select class="form-select" id="rh" name="rh">
                                            <option value="">Seleccionar</option>
                                            <option value="O+" <?php echo $asociado['rh'] === 'O+' ? 'selected' : ''; ?>>O+</option>
                                            <option value="O-" <?php echo $asociado['rh'] === 'O-' ? 'selected' : ''; ?>>O-</option>
                                            <option value="A+" <?php echo $asociado['rh'] === 'A+' ? 'selected' : ''; ?>>A+</option>
                                            <option value="A-" <?php echo $asociado['rh'] === 'A-' ? 'selected' : ''; ?>>A-</option>
                                            <option value="B+" <?php echo $asociado['rh'] === 'B+' ? 'selected' : ''; ?>>B+</option>
                                            <option value="B-" <?php echo $asociado['rh'] === 'B-' ? 'selected' : ''; ?>>B-</option>
                                            <option value="AB+" <?php echo $asociado['rh'] === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                            <option value="AB-" <?php echo $asociado['rh'] === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="lugar_nacimiento" class="form-label">Lugar de Nacimiento</label>
                                        <input type="text" class="form-control" id="lugar_nacimiento" name="lugar_nacimiento" 
                                               value="<?php echo htmlspecialchars($asociado['lugar_nacimiento']); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="estado_civil" class="form-label">Estado Civil</label>
                                        <select class="form-select" id="estado_civil" name="estado_civil">
                                            <option value="">Seleccionar</option>
                                            <option value="soltero" <?php echo $asociado['estado_civil'] === 'soltero' ? 'selected' : ''; ?>>Soltero</option>
                                            <option value="casado" <?php echo $asociado['estado_civil'] === 'casado' ? 'selected' : ''; ?>>Casado</option>
                                            <option value="viudo" <?php echo $asociado['estado_civil'] === 'viudo' ? 'selected' : ''; ?>>Viudo</option>
                                            <option value="divorciado" <?php echo $asociado['estado_civil'] === 'divorciado' ? 'selected' : ''; ?>>Divorciado</option>
                                            <option value="union_libre" <?php echo $asociado['estado_civil'] === 'union_libre' ? 'selected' : ''; ?>>Unión Libre</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="conyuge" class="form-label">Cónyuge</label>
                                        <input type="text" class="form-control" id="conyuge" name="conyuge" 
                                               value="<?php echo htmlspecialchars($asociado['conyuge']); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="fecha_ingreso" class="form-label">Fecha de Ingreso</label>
                                        <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso" 
                                               value="<?php echo $asociado['fecha_ingreso']; ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="direccion" class="form-label">Dirección</label>
                                        <textarea class="form-control" id="direccion" name="direccion" rows="2"><?php echo htmlspecialchars($asociado['direccion']); ?></textarea>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="ciudad" class="form-label">Ciudad</label>
                                        <input type="text" class="form-control" id="ciudad" name="ciudad" 
                                               value="<?php echo htmlspecialchars($asociado['ciudad']); ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($asociado['email']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Información de Contacto -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-phone me-2"></i>Información de Contacto</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="telefono1" class="form-label">Teléfono 1</label>
                                        <input type="text" class="form-control phone-input" id="telefono1" name="telefono1" 
                                               value="<?php echo htmlspecialchars($asociado['telefono1']); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="telefono2" class="form-label">Teléfono 2</label>
                                        <input type="text" class="form-control phone-input" id="telefono2" name="telefono2" 
                                               value="<?php echo htmlspecialchars($asociado['telefono2']); ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="celular" class="form-label">Celular</label>
                                        <input type="text" class="form-control phone-input" id="celular" name="celular" 
                                               value="<?php echo htmlspecialchars($asociado['celular']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Información del Vehículo -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-car me-2"></i>Información del Vehículo</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="placa_carro" class="form-label">Placa del Vehículo</label>
                                        <input type="text" class="form-control placa-input" id="placa_carro" name="placa_carro" 
                                               value="<?php echo htmlspecialchars($asociado['placa_carro']); ?>" style="text-transform: uppercase;">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="marca" class="form-label">Marca</label>
                                        <input type="text" class="form-control" id="marca" name="marca" 
                                               value="<?php echo htmlspecialchars($asociado['marca']); ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="modelo" class="form-label">Modelo</label>
                                        <input type="text" class="form-control" id="modelo" name="modelo" 
                                               value="<?php echo htmlspecialchars($asociado['modelo']); ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="nib" class="form-label">NIB</label>
                                        <input type="text" class="form-control" id="nib" name="nib" 
                                               value="<?php echo htmlspecialchars($asociado['nib']); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="tarjeta_operacion" class="form-label">Tarjeta de Operación</label>
                                    <input type="text" class="form-control" id="tarjeta_operacion" name="tarjeta_operacion" 
                                           value="<?php echo htmlspecialchars($asociado['tarjeta_operacion']); ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contactos de Emergencia -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Contactos de Emergencia</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="urgencia_avisar" class="form-label">Urgencia Avisar</label>
                                        <input type="text" class="form-control" id="urgencia_avisar" name="urgencia_avisar" 
                                               value="<?php echo htmlspecialchars($asociado['urgencia_avisar']); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="otro_avisar" class="form-label">Otro Avisar</label>
                                        <input type="text" class="form-control" id="otro_avisar" name="otro_avisar" 
                                               value="<?php echo htmlspecialchars($asociado['otro_avisar']); ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="direccion_avisar" class="form-label">Dirección de Contacto</label>
                                        <textarea class="form-control" id="direccion_avisar" name="direccion_avisar" rows="2"><?php echo htmlspecialchars($asociado['direccion_avisar']); ?></textarea>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="telefono_avisar" class="form-label">Teléfono de Contacto</label>
                                        <input type="text" class="form-control phone-input" id="telefono_avisar" name="telefono_avisar" 
                                               value="<?php echo htmlspecialchars($asociado['telefono_avisar']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Beneficiarios -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Beneficiarios</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="beneficiario_funebre" class="form-label">Beneficiario Servicio Fúnebre</label>
                                        <input type="text" class="form-control" id="beneficiario_funebre" name="beneficiario_funebre" 
                                               value="<?php echo htmlspecialchars($asociado['beneficiario_funebre']); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="beneficiario_auxilio_muerte" class="form-label">Beneficiario Auxilio por Muerte</label>
                                        <input type="text" class="form-control" id="beneficiario_auxilio_muerte" name="beneficiario_auxilio_muerte" 
                                               value="<?php echo htmlspecialchars($asociado['beneficiario_auxilio_muerte']); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Observaciones -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Observaciones</h5>
                            </div>
                            <div class="card-body">
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="4" 
                                          placeholder="Observaciones adicionales..."><?php echo htmlspecialchars($asociado['observaciones']); ?></textarea>
                            </div>
                        </div>

                        <!-- Archivos actuales -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-folder me-2"></i>Archivos Actuales</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Foto Actual:</h6>
                                        <?php if (!empty($asociado['foto']) && file_exists(UPLOAD_PATH . 'fotos/' . $asociado['foto'])): ?>
                                        <img src="<?php echo BASE_URL; ?>uploads/fotos/<?php echo $asociado['foto']; ?>" 
                                             class="img-thumbnail" style="max-width: 200px;">
                                        <?php else: ?>
                                        <p class="text-muted">No hay foto</p>
                                        <?php endif; ?>
                                        
                                        <div class="mt-3">
                                            <label for="foto" class="form-label">Nueva Foto</label>
                                            <input type="file" class="form-control image-preview-input" id="foto" name="foto" accept="image/*">
                                            <img class="image-preview mt-2" style="max-width: 150px; display: none;">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Documentos:</h6>
                                        <?php if ($archivos->num_rows > 0): ?>
                                        <div class="list-group" id="files-list">
                                            <?php while ($archivo = $archivos->fetch_assoc()): ?>
                                            <div class="list-group-item d-flex justify-content-between align-items-start" id="file-<?php echo $archivo['id']; ?>">
                                                <div class="me-auto">
                                                    <div class="fw-bold">
                                                        <i class="fas fa-file me-2"></i>
                                                        <?php echo htmlspecialchars($archivo['nombre_original']); ?>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?php echo formatDate($archivo['fecha_subida']); ?>
                                                        <?php if (!empty($archivo['descripcion'])): ?>
                                                            - <?php echo htmlspecialchars($archivo['descripcion']); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                    <?php if (!empty($archivo['tipo_documento']) && $archivo['tipo_documento'] !== 'otro'): ?>
                                                        <br><span class="badge bg-info"><?php echo ucfirst(str_replace('_', ' ', $archivo['tipo_documento'])); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="btn-group">
                                                    <a href="<?php echo BASE_URL . str_replace('\\', '/', $archivo['ruta_archivo']); ?>" 
                                                       class="btn btn-sm btn-info" target="_blank" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if (isAdmin()): ?>
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            onclick="deleteFile(<?php echo $archivo['id']; ?>)" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endwhile; ?>
                                        </div>
                                        <?php else: ?>
                                        <p class="text-muted" id="no-files-message">No hay documentos</p>
                                        <?php endif; ?>
                                        
                                        <div class="mt-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <label class="form-label mb-0">Gestión de Documentos</label>
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadFileModal">
                                                    <i class="fas fa-plus me-1"></i>Subir Archivo
                                                </button>
                                            </div>
                                            <label for="documentos" class="form-label">Nuevos Documentos (Subida Múltiple)</label>
                                            <input type="file" class="form-control" id="documentos" name="documentos[]" multiple>
                                            <div class="form-text">También puede usar el botón "Subir Archivo" para mayor control sobre el tipo y descripción.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save me-2"></i>Actualizar Asociado
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para subir archivos -->
<div class="modal fade" id="uploadFileModal" tabindex="-1" aria-labelledby="uploadFileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadFileModalLabel">
                    <i class="fas fa-upload me-2"></i>Subir Archivo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="uploadFileForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="asociado_id" value="<?php echo $asociado_id; ?>">
                    <input type="hidden" name="action" value="upload_file">
                    
                    <div class="mb-3">
                        <label for="archivo" class="form-label">Seleccionar Archivo *</label>
                        <input type="file" class="form-control" id="archivo" name="archivo" required>
                        <div class="form-text">Máximo 5MB por archivo.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tipo_documento" class="form-label">Tipo de Documento</label>
                        <select class="form-select" id="tipo_documento" name="tipo_documento">
                            <option value="otro">Otro</option>
                            <option value="cedula">Cédula</option>
                            <option value="licencia">Licencia</option>
                            <option value="seguro">Seguro</option>
                            <option value="tarjeta_propiedad">Tarjeta de Propiedad</option>
                            <option value="foto">Foto</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="2" placeholder="Descripción opcional del archivo..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>Subir Archivo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Función para eliminar archivo
function deleteFile(fileId) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('manage_files.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=delete_file&file_id=' + fileId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('file-' + fileId).remove();
                    
                    // Verificar si ya no hay archivos
                    const filesList = document.getElementById('files-list');
                    if (!filesList || filesList.children.length === 0) {
                        filesList.outerHTML = '<p class="text-muted" id="no-files-message">No hay documentos</p>';
                    }
                    
                    Swal.fire('Eliminado', data.message, 'success');
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Error al eliminar el archivo', 'error');
            });
        }
    });
}

// Manejar subida de archivos
document.getElementById('uploadFileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('manage_files.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Éxito', data.message, 'success').then(() => {
                location.reload(); // Recargar para mostrar el nuevo archivo
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    })
    .catch(error => {
        Swal.fire('Error', 'Error al subir el archivo', 'error');
    });
});

// Calcular edad automáticamente
document.getElementById('fecha_nacimiento').addEventListener('change', function() {
    const fechaNacimiento = new Date(this.value);
    const hoy = new Date();
    let edad = hoy.getFullYear() - fechaNacimiento.getFullYear();
    const mes = hoy.getMonth() - fechaNacimiento.getMonth();
    
    if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNacimiento.getDate())) {
        edad--;
    }
    
    if (edad >= 0 && edad <= 120) {
        document.getElementById('edad').value = edad;
    }
});

// Preview de imagen
document.getElementById('foto').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.querySelector('.image-preview');
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});

// Formatear entrada de teléfonos
document.querySelectorAll('.phone-input').forEach(function(input) {
    input.addEventListener('input', function(e) {
        // Permitir solo números, espacios, guiones y paréntesis
        this.value = this.value.replace(/[^\d\s\-\(\)]/g, '');
    });
});

// Formatear entrada de cédula
document.querySelector('.cedula-input').addEventListener('input', function(e) {
    // Permitir solo números y guiones
    this.value = this.value.replace(/[^\d\-]/g, '');
});

// Formatear entrada de placa
document.querySelector('.placa-input').addEventListener('input', function(e) {
    // Convertir a mayúsculas y permitir solo letras, números y guiones
    this.value = this.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
});

// Validación del formulario
document.querySelector('form').addEventListener('submit', function(e) {
    const cedula = document.getElementById('cedula').value;
    const nombres = document.getElementById('nombres').value;
    const apellidos = document.getElementById('apellidos').value;
    
    if (!cedula || !nombres || !apellidos) {
        e.preventDefault();
        Swal.fire('Error', 'Los campos Cédula, Nombres y Apellidos son obligatorios.', 'error');
        return false;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>

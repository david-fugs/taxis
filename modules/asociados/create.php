<?php
require_once '../../config.php';
requireLogin();

$page_title = 'Nuevo Asociado';
$success_message = '';
$error_message = '';

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
        // Verificar si la cédula ya existe
        $check_stmt = $mysqli->prepare("SELECT id FROM asociados WHERE cedula = ?");
        $check_stmt->bind_param("s", $data['cedula']);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error_message = 'Ya existe un asociado con esta cédula.';
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
                    $error_message = 'Solo se permiten archivos de imagen (JPG, PNG, GIF).';
                } elseif ($_FILES['foto']['size'] > MAX_FILE_SIZE) {
                    $error_message = 'El archivo de foto es demasiado grande (máximo 5MB).';
                } else {
                    if (!move_uploaded_file($foto_tmp, $foto_path)) {
                        $error_message = 'Error al subir la foto.';
                    }
                }
            }
            
            if (empty($error_message)) {
                // Insertar asociado
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
                        $success_message = 'Asociado creado exitosamente.';
                        // Limpiar formulario
                        $data = array_fill_keys(array_keys($data), '');
                    }
                } else {
                    $error_message = 'Error al crear el asociado: ' . $mysqli->error;
                }
                $stmt->close();
            }
        }
        $check_stmt->close();
    }
}

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>Nuevo Asociado
                    </h4>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver a la Lista
                    </a>
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
                                               value="<?php echo isset($data['cedula']) ? htmlspecialchars($data['cedula']) : ''; ?>" required>
                                        <div class="invalid-feedback">Por favor, ingrese la cédula.</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="nombres" class="form-label">Nombres *</label>
                                        <input type="text" class="form-control" id="nombres" name="nombres" 
                                               value="<?php echo isset($data['nombres']) ? htmlspecialchars($data['nombres']) : ''; ?>" required>
                                        <div class="invalid-feedback">Por favor, ingrese los nombres.</div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="apellidos" class="form-label">Apellidos *</label>
                                        <input type="text" class="form-control" id="apellidos" name="apellidos" 
                                               value="<?php echo isset($data['apellidos']) ? htmlspecialchars($data['apellidos']) : ''; ?>" required>
                                        <div class="invalid-feedback">Por favor, ingrese los apellidos.</div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="direccion" class="form-label">Dirección</label>
                                        <textarea class="form-control" id="direccion" name="direccion" rows="2"><?php echo isset($data['direccion']) ? htmlspecialchars($data['direccion']) : ''; ?></textarea>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="ciudad" class="form-label">Ciudad</label>
                                        <input type="text" class="form-control" id="ciudad" name="ciudad" 
                                               value="<?php echo isset($data['ciudad']) ? htmlspecialchars($data['ciudad']) : ''; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo isset($data['email']) ? htmlspecialchars($data['email']) : ''; ?>">
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
                                               value="<?php echo isset($data['telefono1']) ? htmlspecialchars($data['telefono1']) : ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="telefono2" class="form-label">Teléfono 2</label>
                                        <input type="text" class="form-control phone-input" id="telefono2" name="telefono2" 
                                               value="<?php echo isset($data['telefono2']) ? htmlspecialchars($data['telefono2']) : ''; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="celular" class="form-label">Celular</label>
                                        <input type="text" class="form-control phone-input" id="celular" name="celular" 
                                               value="<?php echo isset($data['celular']) ? htmlspecialchars($data['celular']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información Personal Adicional -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información Adicional</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                        <input type="date" class="form-control fecha-nacimiento" id="fecha_nacimiento" name="fecha_nacimiento" 
                                               value="<?php echo isset($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : ''; ?>">
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="edad" class="form-label">Edad</label>
                                        <input type="number" class="form-control edad-calculada" id="edad" name="edad" min="0" max="120" readonly
                                               value="<?php echo isset($data['edad']) ? $data['edad'] : ''; ?>">
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="rh" class="form-label">RH</label>
                                        <select class="form-select" id="rh" name="rh">
                                            <option value="">Seleccionar</option>
                                            <option value="O+" <?php echo (isset($data['rh']) && $data['rh'] === 'O+') ? 'selected' : ''; ?>>O+</option>
                                            <option value="O-" <?php echo (isset($data['rh']) && $data['rh'] === 'O-') ? 'selected' : ''; ?>>O-</option>
                                            <option value="A+" <?php echo (isset($data['rh']) && $data['rh'] === 'A+') ? 'selected' : ''; ?>>A+</option>
                                            <option value="A-" <?php echo (isset($data['rh']) && $data['rh'] === 'A-') ? 'selected' : ''; ?>>A-</option>
                                            <option value="B+" <?php echo (isset($data['rh']) && $data['rh'] === 'B+') ? 'selected' : ''; ?>>B+</option>
                                            <option value="B-" <?php echo (isset($data['rh']) && $data['rh'] === 'B-') ? 'selected' : ''; ?>>B-</option>
                                            <option value="AB+" <?php echo (isset($data['rh']) && $data['rh'] === 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                            <option value="AB-" <?php echo (isset($data['rh']) && $data['rh'] === 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="estado_civil" class="form-label">Estado Civil</label>
                                        <select class="form-select" id="estado_civil" name="estado_civil">
                                            <option value="">Seleccionar</option>
                                            <option value="soltero" <?php echo (isset($data['estado_civil']) && $data['estado_civil'] === 'soltero') ? 'selected' : ''; ?>>Soltero</option>
                                            <option value="casado" <?php echo (isset($data['estado_civil']) && $data['estado_civil'] === 'casado') ? 'selected' : ''; ?>>Casado</option>
                                            <option value="viudo" <?php echo (isset($data['estado_civil']) && $data['estado_civil'] === 'viudo') ? 'selected' : ''; ?>>Viudo</option>
                                            <option value="divorciado" <?php echo (isset($data['estado_civil']) && $data['estado_civil'] === 'divorciado') ? 'selected' : ''; ?>>Divorciado</option>
                                            <option value="union_libre" <?php echo (isset($data['estado_civil']) && $data['estado_civil'] === 'union_libre') ? 'selected' : ''; ?>>Unión Libre</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="fecha_ingreso" class="form-label">Fecha Ingreso</label>
                                        <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso" 
                                               value="<?php echo isset($data['fecha_ingreso']) ? $data['fecha_ingreso'] : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="lugar_nacimiento" class="form-label">Lugar de Nacimiento</label>
                                        <input type="text" class="form-control" id="lugar_nacimiento" name="lugar_nacimiento" 
                                               value="<?php echo isset($data['lugar_nacimiento']) ? htmlspecialchars($data['lugar_nacimiento']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="conyuge" class="form-label">Cónyuge</label>
                                        <input type="text" class="form-control" id="conyuge" name="conyuge" 
                                               value="<?php echo isset($data['conyuge']) ? htmlspecialchars($data['conyuge']) : ''; ?>">
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
                                               value="<?php echo isset($data['placa_carro']) ? htmlspecialchars($data['placa_carro']) : ''; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="marca" class="form-label">Marca</label>
                                        <input type="text" class="form-control" id="marca" name="marca" 
                                               value="<?php echo isset($data['marca']) ? htmlspecialchars($data['marca']) : ''; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="modelo" class="form-label">Modelo</label>
                                        <input type="text" class="form-control" id="modelo" name="modelo" 
                                               value="<?php echo isset($data['modelo']) ? htmlspecialchars($data['modelo']) : ''; ?>">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="nib" class="form-label">NIB</label>
                                        <input type="text" class="form-control" id="nib" name="nib" 
                                               value="<?php echo isset($data['nib']) ? htmlspecialchars($data['nib']) : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="tarjeta_operacion" class="form-label">Tarjeta de Operación</label>
                                        <input type="text" class="form-control" id="tarjeta_operacion" name="tarjeta_operacion" 
                                               value="<?php echo isset($data['tarjeta_operacion']) ? htmlspecialchars($data['tarjeta_operacion']) : ''; ?>">
                                    </div>
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
                                               value="<?php echo isset($data['urgencia_avisar']) ? htmlspecialchars($data['urgencia_avisar']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="otro_avisar" class="form-label">Otro Avisar</label>
                                        <input type="text" class="form-control" id="otro_avisar" name="otro_avisar" 
                                               value="<?php echo isset($data['otro_avisar']) ? htmlspecialchars($data['otro_avisar']) : ''; ?>">
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="direccion_avisar" class="form-label">Dirección de Contacto</label>
                                        <textarea class="form-control" id="direccion_avisar" name="direccion_avisar" rows="2"><?php echo isset($data['direccion_avisar']) ? htmlspecialchars($data['direccion_avisar']) : ''; ?></textarea>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="telefono_avisar" class="form-label">Teléfono de Contacto</label>
                                        <input type="text" class="form-control phone-input" id="telefono_avisar" name="telefono_avisar" 
                                               value="<?php echo isset($data['telefono_avisar']) ? htmlspecialchars($data['telefono_avisar']) : ''; ?>">
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
                                               value="<?php echo isset($data['beneficiario_funebre']) ? htmlspecialchars($data['beneficiario_funebre']) : ''; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="beneficiario_auxilio_muerte" class="form-label">Beneficiario Auxilio por Muerte</label>
                                        <input type="text" class="form-control" id="beneficiario_auxilio_muerte" name="beneficiario_auxilio_muerte" 
                                               value="<?php echo isset($data['beneficiario_auxilio_muerte']) ? htmlspecialchars($data['beneficiario_auxilio_muerte']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Archivos -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-file-upload me-2"></i>Archivos y Documentos</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="foto" class="form-label">Foto del Asociado</label>
                                        <input type="file" class="form-control image-preview-input" id="foto" name="foto" accept="image/*">
                                        <img class="image-preview mt-2" style="max-width: 150px; display: none;">
                                        <small class="form-text text-muted">Formatos permitidos: JPG, PNG, GIF. Máximo 5MB.</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="documentos" class="form-label">Documentos Adicionales</label>
                                        <input type="file" class="form-control" id="documentos" name="documentos[]" multiple>
                                        <small class="form-text text-muted">Puede seleccionar múltiples archivos. Máximo 5MB por archivo.</small>
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
                                <div class="mb-3">
                                    <textarea class="form-control" id="observaciones" name="observaciones" rows="4" placeholder="Observaciones adicionales..."><?php echo isset($data['observaciones']) ? htmlspecialchars($data['observaciones']) : ''; ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar Asociado
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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
            if (preview) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            } else {
                // Crear elemento de preview si no existe
                const img = document.createElement('img');
                img.className = 'image-preview mt-2';
                img.style.maxWidth = '150px';
                img.src = e.target.result;
                document.getElementById('foto').parentNode.appendChild(img);
            }
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
const placaInput = document.querySelector('.placa-input');
if (placaInput) {
    placaInput.addEventListener('input', function(e) {
        // Convertir a mayúsculas y permitir solo letras, números y guiones
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9\-]/g, '');
    });
}

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

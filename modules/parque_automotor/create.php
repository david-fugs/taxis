<?php
require_once '../../config.php';
requireLogin();

$page_title = 'Nuevo Vehículo - Parque Automotor';
$success_message = '';
$error_message = '';

// Obtener lista de asociados para el select
$asociados_query = "SELECT id, cedula, nombres, apellidos FROM asociados WHERE estado = 'activo' ORDER BY nombres, apellidos";
$asociados_result = $mysqli->query($asociados_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recopilar datos del formulario
    $data = [
        'placa' => strtoupper(cleanInput($_POST['placa'])),
        'marca' => cleanInput($_POST['marca']),
        'modelo' => cleanInput($_POST['modelo']),
        'nib' => cleanInput($_POST['nib']),
        'chassis' => cleanInput($_POST['chassis']),
        'motor' => cleanInput($_POST['motor']),
        'radio_telefono' => cleanInput($_POST['radio_telefono']),
        'serial' => cleanInput($_POST['serial']),
        'compania_soat' => cleanInput($_POST['compania_soat']),
        'soat' => cleanInput($_POST['soat']),
        'vencimiento_soat' => $_POST['vencimiento_soat'] ?: null,
        'certificado_movilizacion' => cleanInput($_POST['certificado_movilizacion']),
        'fecha_vencimiento_certificado' => $_POST['fecha_vencimiento_certificado'] ?: null,
        'tipo_combustible' => $_POST['tipo_combustible'],
        'tarjeta_operacion' => cleanInput($_POST['tarjeta_operacion']),
        'fecha_tarjeta_operacion' => $_POST['fecha_tarjeta_operacion'] ?: null,
        'inicio_tarjeta_operacion' => $_POST['inicio_tarjeta_operacion'] ?: null,
        'final_tarjeta_operacion' => $_POST['final_tarjeta_operacion'] ?: null,
        'revision_preventiva' => cleanInput($_POST['revision_preventiva']),
        'vencimiento_preventiva' => $_POST['vencimiento_preventiva'] ?: null,
        'poliza_responsabilidad_civil' => cleanInput($_POST['poliza_responsabilidad_civil']),
        'observaciones' => cleanInput($_POST['observaciones']),
        'empresa' => cleanInput($_POST['empresa']),
        'asociado_id' => !empty($_POST['asociado_id']) ? (int)$_POST['asociado_id'] : null,
        'estado' => $_POST['estado']
    ];
    
    // Validaciones básicas
    if (empty($data['placa']) || empty($data['marca']) || empty($data['modelo'])) {
        $error_message = 'La placa, marca y modelo son obligatorios.';
    } else {
        // Verificar si la placa ya existe
        $check_stmt = $mysqli->prepare("SELECT id FROM parque_automotor WHERE placa = ?");
        $check_stmt->bind_param("s", $data['placa']);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error_message = 'Ya existe un vehículo con esta placa.';
        } else {
            if (empty($error_message)) {
                // Insertar vehículo
                $sql = "INSERT INTO parque_automotor (
                    placa, marca, modelo, nib, chassis, motor, radio_telefono, serial,
                    compania_soat, soat, vencimiento_soat, certificado_movilizacion,
                    fecha_vencimiento_certificado, tipo_combustible, tarjeta_operacion,
                    fecha_tarjeta_operacion, inicio_tarjeta_operacion, final_tarjeta_operacion,
                    revision_preventiva, vencimiento_preventiva, poliza_responsabilidad_civil,
                    observaciones, empresa, asociado_id, estado, usuario_creacion
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )";
                
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("sssssssssssssssssssssssssi",
                    $data['placa'], $data['marca'], $data['modelo'], $data['nib'],
                    $data['chassis'], $data['motor'], $data['radio_telefono'], $data['serial'],
                    $data['compania_soat'], $data['soat'], $data['vencimiento_soat'],
                    $data['certificado_movilizacion'], $data['fecha_vencimiento_certificado'],
                    $data['tipo_combustible'], $data['tarjeta_operacion'], $data['fecha_tarjeta_operacion'],
                    $data['inicio_tarjeta_operacion'], $data['final_tarjeta_operacion'],
                    $data['revision_preventiva'], $data['vencimiento_preventiva'],
                    $data['poliza_responsabilidad_civil'], $data['observaciones'],
                    $data['empresa'], $data['asociado_id'], $data['estado'], $_SESSION['user_id']
                );
                
                if ($stmt->execute()) {
                    $vehiculo_id = $mysqli->insert_id;
                    
                    // Procesar archivos si se subieron
                    if (isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
                        $upload_success = true;
                        $upload_errors = [];
                        
                        for ($i = 0; $i < count($_FILES['archivos']['name']); $i++) {
                            if ($_FILES['archivos']['error'][$i] === UPLOAD_ERR_OK) {
                                $file_tmp = $_FILES['archivos']['tmp_name'][$i];
                                $file_name = $_FILES['archivos']['name'][$i];
                                $file_size = $_FILES['archivos']['size'][$i];
                                $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                                $tipo_doc = $_POST['tipo_documento'][$i] ?? 'otro';
                                $descripcion = cleanInput($_POST['descripcion_archivo'][$i] ?? '');
                                
                                $new_filename = $data['placa'] . '_doc_' . time() . '_' . $i . '.' . $file_extension;
                                $file_path = UPLOAD_PATH . 'documentos/' . $new_filename;
                                
                                if ($file_size <= MAX_FILE_SIZE) {
                                    if (move_uploaded_file($file_tmp, $file_path)) {
                                        // Guardar en base de datos
                                        $insert_file_stmt = $mysqli->prepare("INSERT INTO parque_automotor_archivos (vehiculo_id, nombre_archivo, nombre_original, tipo_archivo, tamaño, ruta_archivo, tipo_documento, descripcion, usuario_subida) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                        $file_type = mime_content_type($file_path);
                                        $insert_file_stmt->bind_param("isssiissi", $vehiculo_id, $new_filename, $file_name, $file_type, $file_size, $file_path, $tipo_doc, $descripcion, $_SESSION['user_id']);
                                        
                                        if (!$insert_file_stmt->execute()) {
                                            $upload_errors[] = "Error al guardar información del archivo: " . $file_name;
                                        }
                                        $insert_file_stmt->close();
                                    } else {
                                        $upload_errors[] = "Error al subir el archivo: " . $file_name;
                                    }
                                } else {
                                    $upload_errors[] = "El archivo $file_name es demasiado grande (máximo 5MB)";
                                }
                            }
                        }
                        
                        if (!empty($upload_errors)) {
                            $_SESSION['message'] = 'Vehículo creado exitosamente, pero hubo problemas con algunos archivos: ' . implode(', ', $upload_errors);
                            $_SESSION['message_type'] = 'warning';
                        } else {
                            $_SESSION['message'] = 'Vehículo creado exitosamente con todos los archivos.';
                            $_SESSION['message_type'] = 'success';
                        }
                    } else {
                        $_SESSION['message'] = 'Vehículo creado exitosamente.';
                        $_SESSION['message_type'] = 'success';
                    }
                    
                    header('Location: index.php');
                    exit;
                } else {
                    $error_message = 'Error al crear el vehículo: ' . $mysqli->error;
                }
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-car-side me-2"></i>Nuevo Vehículo</h2>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Volver a la Lista
                </a>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-plus me-2"></i>Información del Vehículo
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" enctype="multipart/form-data" novalidate>
                        <!-- Información básica del vehículo -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="fas fa-car me-2"></i>Información Básica
                                </h6>
                            </div>
                            <div class="col-md-4">
                                <label for="placa" class="form-label">Placa <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="placa" name="placa" 
                                       value="<?php echo isset($_POST['placa']) ? htmlspecialchars($_POST['placa']) : ''; ?>" 
                                       required maxlength="10" style="text-transform: uppercase;">
                                <div class="invalid-feedback">La placa es obligatoria.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="marca" class="form-label">Marca <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="marca" name="marca" 
                                       value="<?php echo isset($_POST['marca']) ? htmlspecialchars($_POST['marca']) : ''; ?>" 
                                       required maxlength="50">
                                <div class="invalid-feedback">La marca es obligatoria.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="modelo" class="form-label">Modelo <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="modelo" name="modelo" 
                                       value="<?php echo isset($_POST['modelo']) ? htmlspecialchars($_POST['modelo']) : ''; ?>" 
                                       required maxlength="50">
                                <div class="invalid-feedback">El modelo es obligatorio.</div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label for="nib" class="form-label">NIB</label>
                                <input type="text" class="form-control" id="nib" name="nib" 
                                       value="<?php echo isset($_POST['nib']) ? htmlspecialchars($_POST['nib']) : ''; ?>" 
                                       maxlength="50">
                            </div>
                            <div class="col-md-4">
                                <label for="chassis" class="form-label">Chasis</label>
                                <input type="text" class="form-control" id="chassis" name="chassis" 
                                       value="<?php echo isset($_POST['chassis']) ? htmlspecialchars($_POST['chassis']) : ''; ?>" 
                                       maxlength="50">
                            </div>
                            <div class="col-md-4">
                                <label for="motor" class="form-label">Motor</label>
                                <input type="text" class="form-control" id="motor" name="motor" 
                                       value="<?php echo isset($_POST['motor']) ? htmlspecialchars($_POST['motor']) : ''; ?>" 
                                       maxlength="50">
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label for="radio_telefono" class="form-label">Radio Teléfono</label>
                                <input type="text" class="form-control" id="radio_telefono" name="radio_telefono" 
                                       value="<?php echo isset($_POST['radio_telefono']) ? htmlspecialchars($_POST['radio_telefono']) : ''; ?>" 
                                       maxlength="50">
                            </div>
                            <div class="col-md-4">
                                <label for="serial" class="form-label">Serial</label>
                                <input type="text" class="form-control" id="serial" name="serial" 
                                       value="<?php echo isset($_POST['serial']) ? htmlspecialchars($_POST['serial']) : ''; ?>" 
                                       maxlength="50">
                            </div>
                            <div class="col-md-4">
                                <label for="tipo_combustible" class="form-label">Tipo de Combustible</label>
                                <select class="form-select" id="tipo_combustible" name="tipo_combustible">
                                    <option value="gasolina" <?php echo (isset($_POST['tipo_combustible']) && $_POST['tipo_combustible'] === 'gasolina') ? 'selected' : ''; ?>>Gasolina</option>
                                    <option value="diesel" <?php echo (isset($_POST['tipo_combustible']) && $_POST['tipo_combustible'] === 'diesel') ? 'selected' : ''; ?>>Diesel</option>
                                    <option value="gas" <?php echo (isset($_POST['tipo_combustible']) && $_POST['tipo_combustible'] === 'gas') ? 'selected' : ''; ?>>Gas</option>
                                    <option value="electrico" <?php echo (isset($_POST['tipo_combustible']) && $_POST['tipo_combustible'] === 'electrico') ? 'selected' : ''; ?>>Eléctrico</option>
                                    <option value="hibrido" <?php echo (isset($_POST['tipo_combustible']) && $_POST['tipo_combustible'] === 'hibrido') ? 'selected' : ''; ?>>Híbrido</option>
                                </select>
                            </div>
                        </div>

                        <!-- Información de SOAT -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="fas fa-shield-alt me-2"></i>Información de SOAT
                                </h6>
                            </div>
                            <div class="col-md-4">
                                <label for="compania_soat" class="form-label">Compañía SOAT</label>
                                <input type="text" class="form-control" id="compania_soat" name="compania_soat" 
                                       value="<?php echo isset($_POST['compania_soat']) ? htmlspecialchars($_POST['compania_soat']) : ''; ?>" 
                                       maxlength="100">
                            </div>
                            <div class="col-md-4">
                                <label for="soat" class="form-label">Número SOAT</label>
                                <input type="text" class="form-control" id="soat" name="soat" 
                                       value="<?php echo isset($_POST['soat']) ? htmlspecialchars($_POST['soat']) : ''; ?>" 
                                       maxlength="50">
                            </div>
                            <div class="col-md-4">
                                <label for="vencimiento_soat" class="form-label">Vencimiento SOAT</label>
                                <input type="date" class="form-control" id="vencimiento_soat" name="vencimiento_soat" 
                                       value="<?php echo isset($_POST['vencimiento_soat']) ? $_POST['vencimiento_soat'] : ''; ?>">
                            </div>
                        </div>

                        <!-- Información de certificados -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="fas fa-certificate me-2"></i>Certificados y Permisos
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <label for="certificado_movilizacion" class="form-label">Certificado de Movilización</label>
                                <input type="text" class="form-control" id="certificado_movilizacion" name="certificado_movilizacion" 
                                       value="<?php echo isset($_POST['certificado_movilizacion']) ? htmlspecialchars($_POST['certificado_movilizacion']) : ''; ?>" 
                                       maxlength="50">
                            </div>
                            <div class="col-md-6">
                                <label for="fecha_vencimiento_certificado" class="form-label">Fecha Vencimiento Certificado</label>
                                <input type="date" class="form-control" id="fecha_vencimiento_certificado" name="fecha_vencimiento_certificado" 
                                       value="<?php echo isset($_POST['fecha_vencimiento_certificado']) ? $_POST['fecha_vencimiento_certificado'] : ''; ?>">
                            </div>
                        </div>

                        <!-- Información de tarjeta de operación -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="fas fa-id-card me-2"></i>Tarjeta de Operación
                                </h6>
                            </div>
                            <div class="col-md-3">
                                <label for="tarjeta_operacion" class="form-label">Tarjeta de Operación</label>
                                <input type="text" class="form-control" id="tarjeta_operacion" name="tarjeta_operacion" 
                                       value="<?php echo isset($_POST['tarjeta_operacion']) ? htmlspecialchars($_POST['tarjeta_operacion']) : ''; ?>" 
                                       maxlength="50">
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_tarjeta_operacion" class="form-label">Fecha T. Operación</label>
                                <input type="date" class="form-control" id="fecha_tarjeta_operacion" name="fecha_tarjeta_operacion" 
                                       value="<?php echo isset($_POST['fecha_tarjeta_operacion']) ? $_POST['fecha_tarjeta_operacion'] : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="inicio_tarjeta_operacion" class="form-label">Inicio T. Operación</label>
                                <input type="date" class="form-control" id="inicio_tarjeta_operacion" name="inicio_tarjeta_operacion" 
                                       value="<?php echo isset($_POST['inicio_tarjeta_operacion']) ? $_POST['inicio_tarjeta_operacion'] : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="final_tarjeta_operacion" class="form-label">Final T. Operación</label>
                                <input type="date" class="form-control" id="final_tarjeta_operacion" name="final_tarjeta_operacion" 
                                       value="<?php echo isset($_POST['final_tarjeta_operacion']) ? $_POST['final_tarjeta_operacion'] : ''; ?>">
                            </div>
                        </div>

                        <!-- Información de revisión preventiva -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="fas fa-tools me-2"></i>Revisión Preventiva y Pólizas
                                </h6>
                            </div>
                            <div class="col-md-4">
                                <label for="revision_preventiva" class="form-label">Revisión Preventiva</label>
                                <input type="text" class="form-control" id="revision_preventiva" name="revision_preventiva" 
                                       value="<?php echo isset($_POST['revision_preventiva']) ? htmlspecialchars($_POST['revision_preventiva']) : ''; ?>" 
                                       maxlength="50">
                            </div>
                            <div class="col-md-4">
                                <label for="vencimiento_preventiva" class="form-label">Vencimiento Preventiva</label>
                                <input type="date" class="form-control" id="vencimiento_preventiva" name="vencimiento_preventiva" 
                                       value="<?php echo isset($_POST['vencimiento_preventiva']) ? $_POST['vencimiento_preventiva'] : ''; ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="poliza_responsabilidad_civil" class="form-label">Póliza Responsabilidad Civil</label>
                                <input type="text" class="form-control" id="poliza_responsabilidad_civil" name="poliza_responsabilidad_civil" 
                                       value="<?php echo isset($_POST['poliza_responsabilidad_civil']) ? htmlspecialchars($_POST['poliza_responsabilidad_civil']) : ''; ?>" 
                                       maxlength="50">
                            </div>
                        </div>

                        <!-- Información adicional -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="fas fa-info-circle me-2"></i>Información Adicional
                                </h6>
                            </div>
                            <div class="col-md-4">
                                <label for="empresa" class="form-label">Empresa</label>
                                <input type="text" class="form-control" id="empresa" name="empresa" 
                                       value="<?php echo isset($_POST['empresa']) ? htmlspecialchars($_POST['empresa']) : ''; ?>" 
                                       maxlength="100">
                            </div>
                            <div class="col-md-4">
                                <label for="asociado_id" class="form-label">Asociado</label>
                                <select class="form-select" id="asociado_id" name="asociado_id">
                                    <option value="">Seleccionar asociado (opcional)</option>
                                    <?php while ($asociado = $asociados_result->fetch_assoc()): ?>
                                    <option value="<?php echo $asociado['id']; ?>" 
                                            <?php echo (isset($_POST['asociado_id']) && $_POST['asociado_id'] == $asociado['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($asociado['cedula'] . ' - ' . $asociado['nombres'] . ' ' . $asociado['apellidos']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="activo" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'activo') ? 'selected' : 'selected'; ?>>Activo</option>
                                    <option value="inactivo" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                                    <option value="mantenimiento" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'mantenimiento') ? 'selected' : ''; ?>>Mantenimiento</option>
                                    <option value="vendido" <?php echo (isset($_POST['estado']) && $_POST['estado'] === 'vendido') ? 'selected' : ''; ?>>Vendido</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <label for="observaciones" class="form-label">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="4"><?php echo isset($_POST['observaciones']) ? htmlspecialchars($_POST['observaciones']) : ''; ?></textarea>
                            </div>
                        </div>

                        <!-- Sección de archivos -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="fas fa-file me-2"></i>Archivos (Opcional)
                                </h6>
                                <div id="archivos-container">
                                    <div class="archivo-item border rounded p-3 mb-3">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <label class="form-label">Archivo</label>
                                                <input type="file" class="form-control" name="archivos[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
                                                <div class="form-text">Máximo 5MB</div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Tipo de Documento</label>
                                                <select class="form-select" name="tipo_documento[]">
                                                    <option value="tarjeta_propiedad">Tarjeta de Propiedad</option>
                                                    <option value="soat">SOAT</option>
                                                    <option value="revision_tecnica">Revisión Técnica</option>
                                                    <option value="poliza">Póliza</option>
                                                    <option value="foto_vehiculo">Foto del Vehículo</option>
                                                    <option value="otro">Otro</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label">Descripción</label>
                                                <input type="text" class="form-control" name="descripcion_archivo[]" placeholder="Descripción opcional">
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-outline-danger" onclick="removeArchivo(this)" style="display: none;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary" onclick="addArchivo()">
                                    <i class="fas fa-plus me-1"></i>Agregar Otro Archivo
                                </button>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Guardar Vehículo
                                    </button>
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i>Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validación del formulario
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Convertir placa a mayúsculas
document.getElementById('placa').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

// Funciones para manejar archivos múltiples
function addArchivo() {
    const container = document.getElementById('archivos-container');
    const archivoItem = document.createElement('div');
    archivoItem.className = 'archivo-item border rounded p-3 mb-3';
    archivoItem.innerHTML = `
        <div class="row">
            <div class="col-md-4">
                <label class="form-label">Archivo</label>
                <input type="file" class="form-control" name="archivos[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
                <div class="form-text">Máximo 5MB</div>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tipo de Documento</label>
                <select class="form-select" name="tipo_documento[]">
                    <option value="tarjeta_propiedad">Tarjeta de Propiedad</option>
                    <option value="soat">SOAT</option>
                    <option value="revision_tecnica">Revisión Técnica</option>
                    <option value="poliza">Póliza</option>
                    <option value="foto_vehiculo">Foto del Vehículo</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Descripción</label>
                <input type="text" class="form-control" name="descripcion_archivo[]" placeholder="Descripción opcional">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="button" class="btn btn-outline-danger" onclick="removeArchivo(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(archivoItem);
    updateRemoveButtons();
}

function removeArchivo(button) {
    button.closest('.archivo-item').remove();
    updateRemoveButtons();
}

function updateRemoveButtons() {
    const items = document.querySelectorAll('.archivo-item');
    items.forEach((item, index) => {
        const removeBtn = item.querySelector('.btn-outline-danger');
        if (items.length > 1) {
            removeBtn.style.display = 'block';
        } else {
            removeBtn.style.display = 'none';
        }
    });
}
</script>

<?php include '../../includes/footer.php'; ?>

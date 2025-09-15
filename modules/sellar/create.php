<?php
require_once '../../config.php';
requireLogin();

$page_title = 'Nuevo Sellado';
$success_message = '';
$error_message = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehiculo_id = (int)$_POST['vehiculo_id'];
    $conductor_id = !empty($_POST['conductor_id']) ? (int)$_POST['conductor_id'] : null;
    $observaciones = trim($_POST['observaciones'] ?? '');
    $estado = trim($_POST['estado'] ?? 'pendiente');
    $usuario_id = $_SESSION['user_id']; // Usar directamente la sesión
    
    if ($vehiculo_id > 0) {
        // Obtener datos del vehículo para verificar vigencias
    // ID ya fue casteado como entero arriba, ejecutar consulta directa
    $vehiculo_id = (int)$vehiculo_id;
    $vehiculo_query = "SELECT * FROM parque_automotor WHERE id = $vehiculo_id";
    $vehiculo_res = $mysqli->query($vehiculo_query);
    $vehiculo = $vehiculo_res ? $vehiculo_res->fetch_assoc() : null;
        
        if ($vehiculo) {
            // Verificar vigencias
            $hoy = new DateTime();
            $soat_vigente = false;
            $tarjeta_vigente = false;
            $licencia_vigente = false;
            
            // Verificar SOAT
            if ($vehiculo['vencimiento_soat']) {
                $fecha_soat = new DateTime($vehiculo['vencimiento_soat']);
                $soat_vigente = $fecha_soat >= $hoy;
            }
            
            // Verificar Tarjeta de Operación
            if ($vehiculo['final_tarjeta_operacion']) {
                $fecha_tarjeta = new DateTime($vehiculo['final_tarjeta_operacion']);
                $tarjeta_vigente = $fecha_tarjeta >= $hoy;
            }
            
            // Verificar licencia del conductor si está asignado
            if ($conductor_id) {
                $conductor_id = (int)$conductor_id;
                $conductor_query = "SELECT * FROM conductores WHERE id = $conductor_id";
                $conductor_res = $mysqli->query($conductor_query);
                $conductor = $conductor_res ? $conductor_res->fetch_assoc() : null;
                
                if ($conductor && $conductor['licencia_vence']) {
                    $fecha_licencia = new DateTime($conductor['licencia_vence']);
                    $licencia_vigente = $fecha_licencia >= $hoy;
                }
            }
            
            // Insertar registro de sellado
            $fecha_vencimiento_licencia = ($conductor && $conductor['licencia_vence']) ? $conductor['licencia_vence'] : null;
            
            $insert_query = "
                INSERT INTO sellar (
                    vehiculo_id, conductor_id, observaciones, estado, usuario_creacion,
                    soat_vigente, tarjeta_operacion_vigente, licencia_vigente,
                    fecha_vencimiento_soat, fecha_vencimiento_tarjeta, fecha_vencimiento_licencia
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $stmt = $mysqli->prepare($insert_query);
            $stmt->bind_param(
                "iissiiiisss", 
                $vehiculo_id, 
                $conductor_id, 
                $observaciones, 
                $estado,
                $usuario_id,
                $soat_vigente,
                $tarjeta_vigente,
                $licencia_vigente,
                $vehiculo['vencimiento_soat'],
                $vehiculo['final_tarjeta_operacion'],
                $fecha_vencimiento_licencia
            );
            
            if ($stmt->execute()) {
                $sellar_id = $mysqli->insert_id;
                
                // Si es una petición AJAX (para manejo de archivos), devolver el ID
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    echo "Location: view.php?id=" . $sellar_id;
                    exit();
                }
                
                $_SESSION['message'] = 'Registro de sellado creado exitosamente.';
                $_SESSION['message_type'] = 'success';
                header('Location: view.php?id=' . $sellar_id);
                exit();
            } else {
                $error_message = 'Error al crear el registro de sellado.';
            }
        } else {
            $error_message = 'Vehículo no encontrado.';
        }
    } else {
        $error_message = 'Debe seleccionar un vehículo.';
    }
}

// Obtener vehículos activos
$vehiculos_query = "
    SELECT v.*, a.nombres, a.apellidos 
    FROM parque_automotor v 
    LEFT JOIN asociados a ON v.asociado_id = a.id 
    WHERE v.estado = 'activo' 
    ORDER BY v.placa
";
$vehiculos = $mysqli->query($vehiculos_query);

// Obtener conductores activos
$conductores_query = "
    SELECT * FROM conductores 
    WHERE estado = 'activo' 
    ORDER BY nombre_completo
";
$conductores = $mysqli->query($conductores_query);

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-stamp me-2"></i>Nuevo Registro de Sellado</h2>
                    <p class="text-muted mb-0">Verificación de vigencias para aprobación de vehículo</p>
                </div>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Volver
                </a>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-plus me-2"></i>Datos del Sellado
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="selladoForm">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="vehiculo_id" class="form-label">Vehículo <span class="text-danger">*</span></label>
                                            <select class="form-select" id="vehiculo_id" name="vehiculo_id" required onchange="loadVehicleData()">
                                                <option value="">Seleccione un vehículo</option>
                                                <?php while ($vehiculo = $vehiculos->fetch_assoc()): ?>
                                                <option value="<?php echo $vehiculo['id']; ?>" 
                                                        data-placa="<?php echo htmlspecialchars($vehiculo['placa']); ?>"
                                                        data-marca="<?php echo htmlspecialchars($vehiculo['marca']); ?>"
                                                        data-modelo="<?php echo htmlspecialchars($vehiculo['modelo']); ?>"
                                                        data-soat="<?php echo $vehiculo['vencimiento_soat']; ?>"
                                                        data-tarjeta="<?php echo $vehiculo['final_tarjeta_operacion']; ?>"
                                                        data-asociado="<?php echo htmlspecialchars($vehiculo['nombres'] . ' ' . $vehiculo['apellidos']); ?>">
                                                    <?php echo htmlspecialchars($vehiculo['placa'] . ' - ' . $vehiculo['marca'] . ' ' . $vehiculo['modelo']); ?>
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="conductor_id" class="form-label">Conductor (Opcional)</label>
                                            <select class="form-select" id="conductor_id" name="conductor_id" onchange="loadConductorData()">
                                                <option value="">Sin conductor asignado</option>
                                                <?php while ($conductor = $conductores->fetch_assoc()): ?>
                                                <option value="<?php echo $conductor['id']; ?>"
                                                        data-nombre="<?php echo htmlspecialchars($conductor['nombre_completo']); ?>"
                                                        data-cedula="<?php echo htmlspecialchars($conductor['cedula']); ?>"
                                                        data-licencia="<?php echo $conductor['licencia_vence']; ?>">
                                                    <?php echo htmlspecialchars($conductor['nombre_completo'] . ' - ' . $conductor['cedula']); ?>
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                                            <select class="form-select" id="estado" name="estado" required>
                                                <option value="pendiente" selected>Pendiente</option>
                                                <option value="aprobado">Aprobado</option>
                                                <option value="rechazado">Rechazado</option>
                                                <option value="observaciones">Observaciones</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <!-- Espacio para futuros campos -->
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="observaciones" class="form-label">Observaciones</label>
                                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3" 
                                              placeholder="Observaciones adicionales sobre el proceso de sellado..."></textarea>
                                </div>

                                <!-- Sección de archivos -->
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-3">
                                        <i class="fas fa-paperclip me-2"></i>Archivos Adjuntos
                                    </h6>
                                    
                                    <div class="upload-area border border-2 border-dashed rounded p-4 text-center" 
                                         onclick="document.getElementById('fileInput').click()" 
                                         ondrop="handleDrop(event)" 
                                         ondragover="handleDragOver(event)" 
                                         ondragleave="handleDragLeave(event)">
                                        <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                        <p class="mb-0">Haga clic aquí o arrastre archivos para subir</p>
                                        <small class="text-muted">Imágenes (JPG, PNG) y documentos (PDF) máximo 5MB cada uno</small>
                                        <input type="file" id="fileInput" class="d-none" multiple accept=".jpg,.jpeg,.png,.pdf" onchange="handleFiles(this.files)">
                                    </div>
                                    
                                    <div id="uploadProgress" class="mt-3" style="display: none;"></div>
                                    <div id="filesList" class="mt-3"></div>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Crear Registro
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Panel de información del vehículo -->
                    <div class="card" id="vehicleInfo" style="display: none;">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-car me-2"></i>Información del Vehículo
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="vehicleDetails"></div>
                        </div>
                    </div>

                    <!-- Panel de información del conductor -->
                    <div class="card mt-3" id="conductorInfo" style="display: none;">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-user me-2"></i>Información del Conductor
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="conductorDetails"></div>
                        </div>
                    </div>

                    <!-- Panel de verificación de vigencias -->
                    <div class="card mt-3" id="vigenciasInfo" style="display: none;">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-check-circle me-2"></i>Verificación de Vigencias
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="vigenciasDetails"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadVehicleData() {
    const select = document.getElementById('vehiculo_id');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        const vehicleInfo = document.getElementById('vehicleInfo');
        const vehicleDetails = document.getElementById('vehicleDetails');
        
        vehicleDetails.innerHTML = `
            <div class="mb-2">
                <strong>Placa:</strong> ${option.dataset.placa}
            </div>
            <div class="mb-2">
                <strong>Marca/Modelo:</strong> ${option.dataset.marca} ${option.dataset.modelo}
            </div>
            <div class="mb-2">
                <strong>Asociado:</strong> ${option.dataset.asociado || 'No asignado'}
            </div>
        `;
        
        vehicleInfo.style.display = 'block';
        updateVigencias();
    } else {
        document.getElementById('vehicleInfo').style.display = 'none';
        document.getElementById('vigenciasInfo').style.display = 'none';
    }
}

function loadConductorData() {
    const select = document.getElementById('conductor_id');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        const conductorInfo = document.getElementById('conductorInfo');
        const conductorDetails = document.getElementById('conductorDetails');
        
        conductorDetails.innerHTML = `
            <div class="mb-2">
                <strong>Nombre:</strong> ${option.dataset.nombre}
            </div>
            <div class="mb-2">
                <strong>Cédula:</strong> ${option.dataset.cedula}
            </div>
        `;
        
        conductorInfo.style.display = 'block';
    } else {
        document.getElementById('conductorInfo').style.display = 'none';
    }
    
    updateVigencias();
}

function updateVigencias() {
    const vehiculoSelect = document.getElementById('vehiculo_id');
    const conductorSelect = document.getElementById('conductor_id');
    const vehiculoOption = vehiculoSelect.options[vehiculoSelect.selectedIndex];
    const conductorOption = conductorSelect.options[conductorSelect.selectedIndex];
    
    if (vehiculoOption.value) {
        const vigenciasInfo = document.getElementById('vigenciasInfo');
        const vigenciasDetails = document.getElementById('vigenciasDetails');
        
        const hoy = new Date();
        let html = '';
        
        // Verificar SOAT
        if (vehiculoOption.dataset.soat) {
            const fechaSoat = new Date(vehiculoOption.dataset.soat);
            const vigente = fechaSoat >= hoy;
            html += `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>SOAT:</span>
                    <span class="badge ${vigente ? 'bg-success' : 'bg-danger'}">
                        ${vigente ? 'VIGENTE' : 'VENCIDO'}
                    </span>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Vence: ${fechaSoat.toLocaleDateString('es-CO')}</small>
                </div>
            `;
        }
        
        // Verificar Tarjeta de Operación
        if (vehiculoOption.dataset.tarjeta) {
            const fechaTarjeta = new Date(vehiculoOption.dataset.tarjeta);
            const vigente = fechaTarjeta >= hoy;
            html += `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Tarjeta Operación:</span>
                    <span class="badge ${vigente ? 'bg-success' : 'bg-danger'}">
                        ${vigente ? 'VIGENTE' : 'VENCIDO'}
                    </span>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Vence: ${fechaTarjeta.toLocaleDateString('es-CO')}</small>
                </div>
            `;
        }
        
        // Verificar Licencia del conductor
        if (conductorOption && conductorOption.value && conductorOption.dataset.licencia) {
            const fechaLicencia = new Date(conductorOption.dataset.licencia);
            const vigente = fechaLicencia >= hoy;
            html += `
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span>Licencia Conducir:</span>
                    <span class="badge ${vigente ? 'bg-success' : 'bg-danger'}">
                        ${vigente ? 'VIGENTE' : 'VENCIDA'}
                    </span>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Vence: ${fechaLicencia.toLocaleDateString('es-CO')}</small>
                </div>
            `;
        }
        
        vigenciasDetails.innerHTML = html;
        vigenciasInfo.style.display = 'block';
    }
}

// Variables para manejar archivos
let filesToUpload = [];
let uploadedFiles = [];

// Funciones de manejo de archivos
function handleDragOver(e) {
    e.preventDefault();
    e.currentTarget.classList.add('border-primary', 'bg-light');
}

function handleDragLeave(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('border-primary', 'bg-light');
}

function handleDrop(e) {
    e.preventDefault();
    e.currentTarget.classList.remove('border-primary', 'bg-light');
    const files = e.dataTransfer.files;
    handleFiles(files);
}

function handleFiles(files) {
    Array.from(files).forEach(file => {
        if (validateFile(file)) {
            filesToUpload.push(file);
            displayFile(file);
        }
    });
}

function validateFile(file) {
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    
    if (file.size > maxSize) {
        alert('El archivo es demasiado grande. Máximo 5MB permitido.');
        return false;
    }
    
    if (!allowedTypes.includes(file.type)) {
        alert('Tipo de archivo no permitido. Solo JPG, PNG y PDF.');
        return false;
    }
    
    return true;
}

function displayFile(file) {
    const filesList = document.getElementById('filesList');
    const fileId = Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    
    const fileDiv = document.createElement('div');
    fileDiv.className = 'border rounded p-3 mb-2';
    fileDiv.id = 'file_' + fileId;
    
    const icon = file.type.includes('image') ? 'fa-image' : 'fa-file-pdf';
    const fileSize = (file.size / 1024 / 1024).toFixed(2);
    
    fileDiv.innerHTML = `
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <i class="fas ${icon} me-2"></i>
                <div>
                    <div class="fw-medium">${file.name}</div>
                    <small class="text-muted">${fileSize} MB</small>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile('${fileId}')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="progress mt-2" style="height: 4px;">
            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
        </div>
    `;
    
    filesList.appendChild(fileDiv);
    
    // Asociar el archivo con el ID
    file.tempId = fileId;
}

function removeFile(fileId) {
    // Remover del array
    filesToUpload = filesToUpload.filter(file => file.tempId !== fileId);
    
    // Remover del DOM
    const fileDiv = document.getElementById('file_' + fileId);
    if (fileDiv) {
        fileDiv.remove();
    }
}

// Modificar el envío del formulario para incluir archivos
document.getElementById('selladoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (filesToUpload.length > 0) {
        // Si hay archivos, crearemos el registro primero y luego subiremos los archivos
        submitFormWithFiles();
    } else {
        // Si no hay archivos, enviar formulario normal
        this.submit();
    }
});

function submitFormWithFiles() {
    const formData = new FormData(document.getElementById('selladoForm'));
    
    // Enviar primero el formulario
    fetch('create.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Si el formulario se procesó correctamente, obtenemos el ID del registro
        if (data.includes('Location: view.php?id=')) {
            const match = data.match(/Location: view\.php\?id=(\d+)/);
            if (match) {
                const sellarId = match[1];
                uploadFilesForRecord(sellarId);
            } else {
                // Fallback: recargar la página si no podemos obtener el ID
                window.location.reload();
            }
        } else {
            // Si hay error, mostrar la respuesta
            document.body.innerHTML = data;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar el formulario');
    });
}

function uploadFilesForRecord(sellarId) {
    if (filesToUpload.length === 0) {
        window.location.href = 'view.php?id=' + sellarId;
        return;
    }
    
    let uploadedCount = 0;
    const totalFiles = filesToUpload.length;
    
    filesToUpload.forEach(file => {
        const formData = new FormData();
        formData.append('archivo', file);
        formData.append('sellar_id', sellarId);
        
        fetch('upload_file.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            uploadedCount++;
            
            // Actualizar barra de progreso
            const fileDiv = document.getElementById('file_' + file.tempId);
            if (fileDiv) {
                const progressBar = fileDiv.querySelector('.progress-bar');
                if (data.success) {
                    progressBar.style.width = '100%';
                    progressBar.classList.add('bg-success');
                } else {
                    progressBar.classList.add('bg-danger');
                }
            }
            
            // Si todos los archivos se subieron, redirigir
            if (uploadedCount === totalFiles) {
                setTimeout(() => {
                    window.location.href = 'view.php?id=' + sellarId;
                }, 1000);
            }
        })
        .catch(error => {
            console.error('Error uploading file:', error);
            uploadedCount++;
            
            if (uploadedCount === totalFiles) {
                setTimeout(() => {
                    window.location.href = 'view.php?id=' + sellarId;
                }, 1000);
            }
        });
    });
}
</script>

<style>
.upload-area {
    cursor: pointer;
    transition: all 0.3s ease;
}

.upload-area:hover {
    border-color: #0d6efd !important;
    background-color: #f8f9fa !important;
}

.upload-area.border-primary {
    border-color: #0d6efd !important;
}
</style>

<?php include '../../includes/footer.php'; ?>
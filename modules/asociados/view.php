<?php
require_once '../../config.php';
requireLogin();

$page_title = 'Ver Asociado';
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
                        <i class="fas fa-user me-2"></i>Detalles del Asociado
                    </h4>
                    <div>
                        <a href="edit.php?id=<?php echo $asociado_id; ?>" class="btn btn-warning me-2">
                            <i class="fas fa-edit me-2"></i>Editar
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Volver a la Lista
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Información principal -->
                        <div class="col-lg-8">
                            <!-- Datos personales -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Información Personal</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <strong>Cédula:</strong><br>
                                            <span class="h5"><?php echo htmlspecialchars($asociado['cedula']); ?></span>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong>Nombre Completo:</strong><br>
                                            <span class="h5"><?php echo htmlspecialchars($asociado['nombres'] . ' ' . $asociado['apellidos']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <strong>Fecha de Nacimiento:</strong><br>
                                            <?php echo $asociado['fecha_nacimiento'] ? formatDate($asociado['fecha_nacimiento']) : 'No especificada'; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong>Edad:</strong><br>
                                            <?php echo $asociado['edad'] ? $asociado['edad'] . ' años' : 'No especificada'; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <strong>RH:</strong><br>
                                            <?php echo $asociado['rh'] ?: 'No especificado'; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong>Estado Civil:</strong><br>
                                            <?php echo $asociado['estado_civil'] ? ucfirst(str_replace('_', ' ', $asociado['estado_civil'])) : 'No especificado'; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <strong>Lugar de Nacimiento:</strong><br>
                                            <?php echo htmlspecialchars($asociado['lugar_nacimiento']) ?: 'No especificado'; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong>Cónyuge:</strong><br>
                                            <?php echo htmlspecialchars($asociado['conyuge']) ?: 'No especificado'; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Dirección:</strong><br>
                                        <?php echo nl2br(htmlspecialchars($asociado['direccion'])) ?: 'No especificada'; ?>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <strong>Ciudad:</strong><br>
                                            <?php echo htmlspecialchars($asociado['ciudad']) ?: 'No especificada'; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong>Email:</strong><br>
                                            <?php if (!empty($asociado['email'])): ?>
                                                <a href="mailto:<?php echo htmlspecialchars($asociado['email']); ?>">
                                                    <?php echo htmlspecialchars($asociado['email']); ?>
                                                </a>
                                            <?php else: ?>
                                                No especificado
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Información de contacto -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-phone me-2"></i>Información de Contacto</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <strong>Teléfono 1:</strong><br>
                                            <?php echo htmlspecialchars($asociado['telefono1']) ?: 'No especificado'; ?>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <strong>Teléfono 2:</strong><br>
                                            <?php echo htmlspecialchars($asociado['telefono2']) ?: 'No especificado'; ?>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <strong>Celular:</strong><br>
                                            <?php echo htmlspecialchars($asociado['celular']) ?: 'No especificado'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Información del vehículo -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-car me-2"></i>Información del Vehículo</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <strong>Placa:</strong><br>
                                            <?php if (!empty($asociado['placa_carro'])): ?>
                                                <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($asociado['placa_carro']); ?></span>
                                            <?php else: ?>
                                                No especificada
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <strong>Marca:</strong><br>
                                            <?php echo htmlspecialchars($asociado['marca']) ?: 'No especificada'; ?>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <strong>Modelo:</strong><br>
                                            <?php echo htmlspecialchars($asociado['modelo']) ?: 'No especificado'; ?>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <strong>NIB:</strong><br>
                                            <?php echo htmlspecialchars($asociado['nib']) ?: 'No especificado'; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Tarjeta de Operación:</strong><br>
                                        <?php echo htmlspecialchars($asociado['tarjeta_operacion']) ?: 'No especificada'; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Contactos de emergencia -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Contactos de Emergencia</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <strong>Urgencia Avisar:</strong><br>
                                            <?php echo htmlspecialchars($asociado['urgencia_avisar']) ?: 'No especificado'; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong>Otro Avisar:</strong><br>
                                            <?php echo htmlspecialchars($asociado['otro_avisar']) ?: 'No especificado'; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <strong>Dirección de Contacto:</strong><br>
                                            <?php echo nl2br(htmlspecialchars($asociado['direccion_avisar'])) ?: 'No especificada'; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong>Teléfono de Contacto:</strong><br>
                                            <?php echo htmlspecialchars($asociado['telefono_avisar']) ?: 'No especificado'; ?>
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
                                            <strong>Beneficiario Servicio Fúnebre:</strong><br>
                                            <?php echo htmlspecialchars($asociado['beneficiario_funebre']) ?: 'No especificado'; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong>Beneficiario Auxilio por Muerte:</strong><br>
                                            <?php echo htmlspecialchars($asociado['beneficiario_auxilio_muerte']) ?: 'No especificado'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Observaciones -->
                            <?php if (!empty($asociado['observaciones'])): ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Observaciones</h5>
                                </div>
                                <div class="card-body">
                                    <?php echo nl2br(htmlspecialchars($asociado['observaciones'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Sidebar derecho -->
                        <div class="col-lg-4">
                            <!-- Foto del asociado -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-camera me-2"></i>Foto del Asociado</h5>
                                </div>
                                <div class="card-body text-center">
                                    <?php if (!empty($asociado['foto']) && file_exists(UPLOAD_PATH . 'fotos/' . $asociado['foto'])): ?>
                                        <img src="<?php echo BASE_URL; ?>uploads/fotos/<?php echo $asociado['foto']; ?>" 
                                             class="img-fluid rounded" style="max-width: 100%; max-height: 400px;">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                             style="height: 300px;">
                                            <div class="text-center">
                                                <i class="fas fa-user fa-5x text-muted mb-3"></i>
                                                <p class="text-muted">Sin foto</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Información del sistema -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información del Sistema</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong>Estado:</strong><br>
                                        <span class="badge bg-<?php echo $asociado['estado'] === 'activo' ? 'success' : 'secondary'; ?> fs-6">
                                            <?php echo ucfirst($asociado['estado']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Fecha de Ingreso:</strong><br>
                                        <?php echo $asociado['fecha_ingreso'] ? formatDate($asociado['fecha_ingreso']) : 'No especificada'; ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <strong>Fecha de Registro:</strong><br>
                                        <?php echo formatDate($asociado['fecha_creacion']); ?>
                                    </div>
                                    
                                    <?php if ($asociado['fecha_actualizacion'] != $asociado['fecha_creacion']): ?>
                                    <div class="mb-3">
                                        <strong>Última Actualización:</strong><br>
                                        <?php echo formatDate($asociado['fecha_actualizacion']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Archivos y documentos -->
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-folder me-2"></i>Documentos</h5>
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadFileModal">
                                        <i class="fas fa-plus me-1"></i>Subir Archivo
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div id="files-list">
                                        <?php if ($archivos->num_rows > 0): ?>
                                            <div class="list-group list-group-flush">
                                                <?php while ($archivo = $archivos->fetch_assoc()): ?>
                                                <div class="list-group-item d-flex justify-content-between align-items-start px-0" id="file-<?php echo $archivo['id']; ?>">
                                                    <div class="me-auto">
                                                        <div class="fw-bold">
                                                            <i class="fas fa-file me-1"></i>
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
                                                           class="btn btn-sm btn-outline-primary" target="_blank" title="Ver archivo">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if (isAdmin()): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="deleteFile(<?php echo $archivo['id']; ?>)" title="Eliminar archivo">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <?php endwhile; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center text-muted" id="no-files-message">
                                                <i class="fas fa-folder-open fa-3x mb-3"></i>
                                                <p>No hay documentos subidos</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
                    const filesList = document.querySelector('#files-list .list-group');
                    if (!filesList || filesList.children.length === 0) {
                        document.getElementById('files-list').innerHTML = `
                            <div class="text-center text-muted" id="no-files-message">
                                <i class="fas fa-folder-open fa-3x mb-3"></i>
                                <p>No hay documentos subidos</p>
                            </div>
                        `;
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
</script>

<?php include '../../includes/footer.php'; ?>

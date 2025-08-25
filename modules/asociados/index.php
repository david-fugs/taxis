<?php
require_once '../../config.php';
requireLogin();

$page_title = 'Lista de Asociados';
$success_message = '';
$error_message = '';

// Verificar mensajes de sesión
if (isset($_SESSION['message'])) {
    if ($_SESSION['message_type'] === 'success') {
        $success_message = $_SESSION['message'];
    } else {
        $error_message = $_SESSION['message'];
    }
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Procesar eliminación
if (isset($_GET['delete']) && isAdmin()) {
    $delete_id = (int)$_GET['delete'];
    
    // Eliminar archivos asociados físicamente
    $files_query = $mysqli->prepare("SELECT ruta_archivo FROM asociados_archivos WHERE asociado_id = ?");
    $files_query->bind_param("i", $delete_id);
    $files_query->execute();
    $files_result = $files_query->get_result();
    
    while ($file = $files_result->fetch_assoc()) {
        if (file_exists($file['ruta_archivo'])) {
            unlink($file['ruta_archivo']);
        }
    }
    
    // Eliminar asociado (esto eliminará automáticamente los archivos por CASCADE)
    $delete_stmt = $mysqli->prepare("DELETE FROM asociados WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);
    
    if ($delete_stmt->execute()) {
        $success_message = 'Asociado eliminado exitosamente.';
    } else {
        $error_message = 'Error al eliminar el asociado.';
    }
    $delete_stmt->close();
}

// Obtener parámetros de búsqueda
$search_cedula = $_GET['search_cedula'] ?? '';
$search_nombre = $_GET['search_nombre'] ?? '';
$search_placa = $_GET['search_placa'] ?? '';

// Construir consulta con filtros
$where_conditions = ["a.estado = 'activo'"];
$params = [];
$param_types = '';

if (!empty($search_cedula)) {
    $where_conditions[] = "a.cedula LIKE ?";
    $params[] = "%$search_cedula%";
    $param_types .= 's';
}

if (!empty($search_nombre)) {
    $where_conditions[] = "(a.nombres LIKE ? OR a.apellidos LIKE ?)";
    $params[] = "%$search_nombre%";
    $params[] = "%$search_nombre%";
    $param_types .= 'ss';
}

if (!empty($search_placa)) {
    $where_conditions[] = "a.placa_carro LIKE ?";
    $params[] = "%$search_placa%";
    $param_types .= 's';
}

$where_clause = implode(' AND ', $where_conditions);

$query = "
    SELECT 
        a.*,
        COUNT(aa.id) as total_archivos
    FROM asociados a
    LEFT JOIN asociados_archivos aa ON a.id = aa.asociado_id
    WHERE $where_clause
    GROUP BY a.id
    ORDER BY a.fecha_creacion DESC
";

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$asociados = $stmt->get_result();

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-users me-2"></i>Lista de Asociados
                    </h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAsociadoModal">
                        <i class="fas fa-plus me-2"></i>Nuevo Asociado
                    </button>
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
                    
                    <!-- Filtros de búsqueda -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label for="search_cedula" class="form-label">Buscar por Cédula</label>
                                    <input type="text" class="form-control" id="search_cedula" name="search_cedula" 
                                           value="<?php echo htmlspecialchars($search_cedula); ?>" placeholder="Ingrese cédula">
                                </div>
                                <div class="col-md-3">
                                    <label for="search_nombre" class="form-label">Buscar por Nombre</label>
                                    <input type="text" class="form-control" id="search_nombre" name="search_nombre" 
                                           value="<?php echo htmlspecialchars($search_nombre); ?>" placeholder="Nombre o apellido">
                                </div>
                                <div class="col-md-3">
                                    <label for="search_placa" class="form-label">Buscar por Placa</label>
                                    <input type="text" class="form-control" id="search_placa" name="search_placa" 
                                           value="<?php echo htmlspecialchars($search_placa); ?>" placeholder="Placa del vehículo">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-2"></i>Buscar
                                        </button>
                                        <a href="index.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-2"></i>Limpiar
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Tabla de asociados -->
                    <div class="table-responsive">
                        <table class="table table-striped data-table">
                            <thead>
                                <tr>
                                    <th>Foto</th>
                                    <th>Cédula</th>
                                    <th>Nombre Completo</th>
                                    <th>Teléfono</th>
                                    <th>Ciudad</th>
                                    <th>Placa</th>
                                    <th>Vehículo</th>
                                    <th>Archivos</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($asociado = $asociados->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($asociado['foto']) && file_exists(UPLOAD_PATH . 'fotos/' . $asociado['foto'])): ?>
                                        <img src="<?php echo BASE_URL; ?>uploads/fotos/<?php echo $asociado['foto']; ?>" 
                                             class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                                        <?php else: ?>
                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 40px; height: 40px;">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($asociado['cedula']); ?></strong>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($asociado['nombres'] . ' ' . $asociado['apellidos']); ?></strong>
                                            <?php if (!empty($asociado['email'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($asociado['email']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($asociado['celular'])): ?>
                                        <i class="fas fa-mobile-alt me-1"></i><?php echo htmlspecialchars($asociado['celular']); ?><br>
                                        <?php endif; ?>
                                        <?php if (!empty($asociado['telefono1'])): ?>
                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($asociado['telefono1']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($asociado['ciudad']); ?></td>
                                    <td>
                                        <?php if (!empty($asociado['placa_carro'])): ?>
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($asociado['placa_carro']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($asociado['marca']) || !empty($asociado['modelo'])): ?>
                                        <?php echo htmlspecialchars($asociado['marca'] . ' ' . $asociado['modelo']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $asociado['total_archivos']; ?> archivos
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $asociado['estado'] === 'activo' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($asociado['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="view.php?id=<?php echo $asociado['id']; ?>" 
                                               class="btn btn-sm btn-info" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" 
                                                    class="btn btn-sm btn-warning" 
                                                    title="Editar"
                                                    onclick="editAsociado(<?php echo $asociado['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <?php if (isAdmin()): ?>
                                            <a href="index.php?delete=<?php echo $asociado['id']; ?>" 
                                               class="btn btn-sm btn-danger btn-delete" 
                                               data-name="<?php echo htmlspecialchars($asociado['nombres'] . ' ' . $asociado['apellidos']); ?>"
                                               title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para crear/editar asociado -->
<div class="modal fade" id="createAsociadoModal" tabindex="-1" aria-labelledby="createAsociadoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createAsociadoModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Nuevo Asociado
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="asociadoForm" method="POST" enctype="multipart/form-data">
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <!-- Información Personal -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-user me-2"></i>Información Personal</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="modal_cedula" class="form-label">Cédula *</label>
                                    <input type="text" class="form-control" id="modal_cedula" name="cedula" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="modal_nombres" class="form-label">Nombres *</label>
                                    <input type="text" class="form-control" id="modal_nombres" name="nombres" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="modal_apellidos" class="form-label">Apellidos *</label>
                                    <input type="text" class="form-control" id="modal_apellidos" name="apellidos" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="modal_fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                    <input type="date" class="form-control" id="modal_fecha_nacimiento" name="fecha_nacimiento">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="modal_edad" class="form-label">Edad</label>
                                    <input type="number" class="form-control" id="modal_edad" name="edad" min="18" max="100">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="modal_rh" class="form-label">RH</label>
                                    <select class="form-select" id="modal_rh" name="rh">
                                        <option value="">Seleccionar</option>
                                        <option value="O+">O+</option>
                                        <option value="O-">O-</option>
                                        <option value="A+">A+</option>
                                        <option value="A-">A-</option>
                                        <option value="B+">B+</option>
                                        <option value="B-">B-</option>
                                        <option value="AB+">AB+</option>
                                        <option value="AB-">AB-</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="modal_lugar_nacimiento" class="form-label">Lugar de Nacimiento</label>
                                    <input type="text" class="form-control" id="modal_lugar_nacimiento" name="lugar_nacimiento">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="modal_estado_civil" class="form-label">Estado Civil</label>
                                    <select class="form-select" id="modal_estado_civil" name="estado_civil">
                                        <option value="">Seleccionar</option>
                                        <option value="soltero">Soltero</option>
                                        <option value="casado">Casado</option>
                                        <option value="viudo">Viudo</option>
                                        <option value="divorciado">Divorciado</option>
                                        <option value="union_libre">Unión Libre</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="modal_conyuge" class="form-label">Cónyuge</label>
                                    <input type="text" class="form-control" id="modal_conyuge" name="conyuge">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="modal_fecha_ingreso" class="form-label">Fecha de Ingreso</label>
                                    <input type="date" class="form-control" id="modal_fecha_ingreso" name="fecha_ingreso">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="modal_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="modal_email" name="email">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="modal_direccion" class="form-label">Dirección</label>
                                <textarea class="form-control" id="modal_direccion" name="direccion" rows="2"></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="modal_ciudad" class="form-label">Ciudad</label>
                                <input type="text" class="form-control" id="modal_ciudad" name="ciudad">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información de Contacto -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-phone me-2"></i>Información de Contacto</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="modal_telefono1" class="form-label">Teléfono 1</label>
                                    <input type="text" class="form-control" id="modal_telefono1" name="telefono1">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="modal_telefono2" class="form-label">Teléfono 2</label>
                                    <input type="text" class="form-control" id="modal_telefono2" name="telefono2">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="modal_celular" class="form-label">Celular</label>
                                    <input type="text" class="form-control" id="modal_celular" name="celular">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Información del Vehículo -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-car me-2"></i>Información del Vehículo</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="modal_placa_carro" class="form-label">Placa del Vehículo</label>
                                    <input type="text" class="form-control" id="modal_placa_carro" name="placa_carro">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="modal_marca" class="form-label">Marca</label>
                                    <input type="text" class="form-control" id="modal_marca" name="marca">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="modal_modelo" class="form-label">Modelo</label>
                                    <input type="text" class="form-control" id="modal_modelo" name="modelo">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="modal_nib" class="form-label">NIB</label>
                                    <input type="text" class="form-control" id="modal_nib" name="nib">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="modal_tarjeta_operacion" class="form-label">Tarjeta de Operación</label>
                                <input type="text" class="form-control" id="modal_tarjeta_operacion" name="tarjeta_operacion">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contactos de Emergencia -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Contactos de Emergencia</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="modal_urgencia_avisar" class="form-label">Urgencia Avisar</label>
                                    <input type="text" class="form-control" id="modal_urgencia_avisar" name="urgencia_avisar">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="modal_otro_avisar" class="form-label">Otro Avisar</label>
                                    <input type="text" class="form-control" id="modal_otro_avisar" name="otro_avisar">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="modal_direccion_avisar" class="form-label">Dirección de Contacto</label>
                                    <textarea class="form-control" id="modal_direccion_avisar" name="direccion_avisar" rows="2"></textarea>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="modal_telefono_avisar" class="form-label">Teléfono de Contacto</label>
                                    <input type="text" class="form-control" id="modal_telefono_avisar" name="telefono_avisar">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Beneficiarios -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-users me-2"></i>Beneficiarios</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="modal_beneficiario_funebre" class="form-label">Beneficiario Servicio Fúnebre</label>
                                    <input type="text" class="form-control" id="modal_beneficiario_funebre" name="beneficiario_funebre">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="modal_beneficiario_auxilio_muerte" class="form-label">Beneficiario Auxilio por Muerte</label>
                                    <input type="text" class="form-control" id="modal_beneficiario_auxilio_muerte" name="beneficiario_auxilio_muerte">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Foto y Documentos -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-file-upload me-2"></i>Foto y Documentos</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="modal_foto" class="form-label">Foto del Asociado</label>
                                    <input type="file" class="form-control" id="modal_foto" name="foto" accept="image/*">
                                    <div class="form-text">Formatos permitidos: JPG, PNG, GIF. Máximo 5MB.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="modal_documentos" class="form-label">Documentos Adicionales</label>
                                    <input type="file" class="form-control" id="modal_documentos" name="documentos[]" multiple>
                                    <div class="form-text">Puede seleccionar múltiples archivos. Máximo 5MB por archivo.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Observaciones -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Observaciones</h6>
                        </div>
                        <div class="card-body">
                            <textarea class="form-control" id="modal_observaciones" name="observaciones" rows="3" placeholder="Observaciones adicionales..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Función para abrir modal de creación
function openCreateModal() {
    document.getElementById('createAsociadoModalLabel').innerHTML = '<i class="fas fa-user-plus me-2"></i>Nuevo Asociado';
    document.getElementById('asociadoForm').reset();
    document.getElementById('asociadoForm').action = 'create_quick.php';
    const modal = new bootstrap.Modal(document.getElementById('createAsociadoModal'));
    modal.show();
}

// Función para editar asociado
function editAsociado(id) {
    window.location.href = 'edit.php?id=' + id;
}

// Calcular edad automáticamente
document.getElementById('modal_fecha_nacimiento').addEventListener('change', function() {
    const fechaNacimiento = new Date(this.value);
    const hoy = new Date();
    let edad = hoy.getFullYear() - fechaNacimiento.getFullYear();
    const mes = hoy.getMonth() - fechaNacimiento.getMonth();
    
    if (mes < 0 || (mes === 0 && hoy.getDate() < fechaNacimiento.getDate())) {
        edad--;
    }
    
    if (edad >= 0 && edad <= 120) {
        document.getElementById('modal_edad').value = edad;
    }
});

// Validación de formulario
document.getElementById('asociadoForm').addEventListener('submit', function(e) {
    const cedula = document.getElementById('modal_cedula').value;
    const nombres = document.getElementById('modal_nombres').value;
    const apellidos = document.getElementById('modal_apellidos').value;
    
    if (!cedula || !nombres || !apellidos) {
        e.preventDefault();
        Swal.fire('Error', 'Los campos Cédula, Nombres y Apellidos son obligatorios.', 'error');
        return false;
    }
});

// Llamar desde el navbar
window.openCreateModal = openCreateModal;
</script>

<?php include '../../includes/footer.php'; ?>

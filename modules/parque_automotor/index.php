<?php
require_once '../../config.php';
requireLogin();

$page_title = 'Parque Automotor';
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
    $files_query = $mysqli->prepare("SELECT ruta_archivo FROM parque_automotor_archivos WHERE vehiculo_id = ?");
    $files_query->bind_param("i", $delete_id);
    $files_query->execute();
    $files_result = $files_query->get_result();
    
    while ($file = $files_result->fetch_assoc()) {
        if (file_exists($file['ruta_archivo'])) {
            unlink($file['ruta_archivo']);
        }
    }
    
    // Eliminar vehículo (esto eliminará automáticamente los archivos por CASCADE)
    $delete_stmt = $mysqli->prepare("DELETE FROM parque_automotor WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);
    
    if ($delete_stmt->execute()) {
        $success_message = 'Vehículo eliminado exitosamente.';
    } else {
        $error_message = 'Error al eliminar el vehículo.';
    }
    $delete_stmt->close();
}

// Obtener parámetros de búsqueda
$search_placa = $_GET['search_placa'] ?? '';
$search_marca = $_GET['search_marca'] ?? '';
$search_modelo = $_GET['search_modelo'] ?? '';
$search_empresa = $_GET['search_empresa'] ?? '';

// Construir consulta con filtros
$where_conditions = ["p.estado = 'activo'"];
$params = [];
$param_types = '';

if (!empty($search_placa)) {
    $where_conditions[] = "p.placa LIKE ?";
    $params[] = "%$search_placa%";
    $param_types .= 's';
}

if (!empty($search_marca)) {
    $where_conditions[] = "p.marca LIKE ?";
    $params[] = "%$search_marca%";
    $param_types .= 's';
}

if (!empty($search_modelo)) {
    $where_conditions[] = "p.modelo LIKE ?";
    $params[] = "%$search_modelo%";
    $param_types .= 's';
}

if (!empty($search_empresa)) {
    $where_conditions[] = "p.empresa LIKE ?";
    $params[] = "%$search_empresa%";
    $param_types .= 's';
}

$where_clause = implode(' AND ', $where_conditions);

$query = "
    SELECT 
        p.*,
        a.nombres as asociado_nombres,
        a.apellidos as asociado_apellidos,
        COUNT(pa.id) as total_archivos
    FROM parque_automotor p
    LEFT JOIN asociados a ON p.asociado_id = a.id
    LEFT JOIN parque_automotor_archivos pa ON p.id = pa.vehiculo_id
    WHERE $where_clause
    GROUP BY p.id
    ORDER BY p.fecha_creacion DESC
";

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$vehiculos = $stmt->get_result();

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-car me-2"></i>Parque Automotor</h2>
                <button type="button" class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fas fa-plus me-1"></i>Nuevo Vehículo
                </button>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filtros de búsqueda -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-search me-2"></i>Filtros de Búsqueda
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="search_placa" class="form-label">Placa</label>
                            <input type="text" class="form-control" id="search_placa" name="search_placa" 
                                   value="<?php echo htmlspecialchars($search_placa); ?>" placeholder="Placa del vehículo">
                        </div>
                        <div class="col-md-3">
                            <label for="search_marca" class="form-label">Marca</label>
                            <input type="text" class="form-control" id="search_marca" name="search_marca" 
                                   value="<?php echo htmlspecialchars($search_marca); ?>" placeholder="Marca del vehículo">
                        </div>
                        <div class="col-md-3">
                            <label for="search_modelo" class="form-label">Modelo</label>
                            <input type="text" class="form-control" id="search_modelo" name="search_modelo" 
                                   value="<?php echo htmlspecialchars($search_modelo); ?>" placeholder="Modelo del vehículo">
                        </div>
                        <div class="col-md-3">
                            <label for="search_empresa" class="form-label">Empresa</label>
                            <input type="text" class="form-control" id="search_empresa" name="search_empresa" 
                                   value="<?php echo htmlspecialchars($search_empresa); ?>" placeholder="Empresa">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i>Buscar
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times me-1"></i>Limpiar Filtros
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de vehículos -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>Lista de Vehículos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="vehiculosTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Placa</th>
                                    <th>Marca/Modelo</th>
                                    <th>Empresa</th>
                                    <th>Asociado</th>
                                    <th>SOAT</th>
                                    <th>T. Operación</th>
                                    <th>Archivos</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($vehiculo = $vehiculos->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($vehiculo['placa']); ?></strong></td>
                                    <td>
                                        <?php echo htmlspecialchars($vehiculo['marca']); ?><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($vehiculo['modelo']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($vehiculo['empresa'] ?? 'No asignada'); ?></td>
                                    <td>
                                        <?php if ($vehiculo['asociado_nombres']): ?>
                                            <?php echo htmlspecialchars($vehiculo['asociado_nombres'] . ' ' . $vehiculo['asociado_apellidos']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Sin asociado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($vehiculo['vencimiento_soat']): ?>
                                            <?php 
                                            $fecha_soat = new DateTime($vehiculo['vencimiento_soat']);
                                            $hoy = new DateTime();
                                            $diferencia = $fecha_soat->diff($hoy);
                                            $dias_restantes = $fecha_soat >= $hoy ? $diferencia->days : -$diferencia->days;
                                            
                                            $clase_color = '';
                                            if ($dias_restantes < 0) {
                                                $clase_color = 'text-danger';
                                            } elseif ($dias_restantes <= 30) {
                                                $clase_color = 'text-warning';
                                            } else {
                                                $clase_color = 'text-success';
                                            }
                                            ?>
                                            <span class="<?php echo $clase_color; ?>">
                                                <?php echo $fecha_soat->format('d/m/Y'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">No registrado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($vehiculo['final_tarjeta_operacion']): ?>
                                            <?php 
                                            $fecha_tarjeta = new DateTime($vehiculo['final_tarjeta_operacion']);
                                            $hoy = new DateTime();
                                            $diferencia = $fecha_tarjeta->diff($hoy);
                                            $dias_restantes = $fecha_tarjeta >= $hoy ? $diferencia->days : -$diferencia->days;
                                            
                                            $clase_color = '';
                                            if ($dias_restantes < 0) {
                                                $clase_color = 'text-danger';
                                            } elseif ($dias_restantes <= 30) {
                                                $clase_color = 'text-warning';
                                            } else {
                                                $clase_color = 'text-success';
                                            }
                                            ?>
                                            <span class="<?php echo $clase_color; ?>">
                                                <?php echo $fecha_tarjeta->format('d/m/Y'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">No registrado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $vehiculo['total_archivos']; ?> archivo(s)
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $estado_classes = [
                                            'activo' => 'bg-success',
                                            'inactivo' => 'bg-secondary',
                                            'mantenimiento' => 'bg-warning',
                                            'vendido' => 'bg-danger'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $estado_classes[$vehiculo['estado']] ?? 'bg-secondary'; ?>">
                                            <?php echo ucfirst($vehiculo['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="view.php?id=<?php echo $vehiculo['id']; ?>" 
                                               class="btn btn-outline-info" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit.php?id=<?php echo $vehiculo['id']; ?>" 
                                               class="btn btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="manage_files.php?id=<?php echo $vehiculo['id']; ?>" 
                                               class="btn btn-outline-secondary" title="Gestionar archivos">
                                                <i class="fas fa-file"></i>
                                            </a>
                                            <?php if (isAdmin()): ?>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="confirmDelete(<?php echo $vehiculo['id']; ?>, '<?php echo htmlspecialchars($vehiculo['placa']); ?>')" 
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
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

<!-- Modal de confirmación de eliminación -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar el vehículo <strong id="deleteVehicleName"></strong>?</p>
                <p class="text-danger"><small>Esta acción eliminará también todos los archivos asociados y no se puede deshacer.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Eliminar</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, placa) {
    document.getElementById('deleteVehicleName').textContent = placa;
    document.getElementById('confirmDeleteBtn').href = 'index.php?delete=' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function openCreateModal() {
    window.location.href = 'create.php';
}

// Inicializar DataTable
$(document).ready(function() {
    $('#vehiculosTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[0, 'asc']], // Ordenar por placa
        columnDefs: [
            { orderable: false, targets: -1 } // Desactivar ordenación en columna de acciones
        ]
    });
});
</script>

<?php include '../../includes/footer.php'; ?>

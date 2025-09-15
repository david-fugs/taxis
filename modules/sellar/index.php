<?php
require_once '../../config.php';
requireLogin();

$page_title = 'Sellado de Vehículos';
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

// Obtener parámetros de búsqueda
$search_consecutivo = $_GET['search_consecutivo'] ?? '';
$search_placa = $_GET['search_placa'] ?? '';
$search_conductor = $_GET['search_conductor'] ?? '';
$search_estado = $_GET['search_estado'] ?? '';

// Construir consulta con filtros
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search_consecutivo)) {
    $where_conditions[] = "s.consecutivo LIKE ?";
    $params[] = "%$search_consecutivo%";
    $param_types .= 's';
}

if (!empty($search_placa)) {
    $where_conditions[] = "v.placa LIKE ?";
    $params[] = "%$search_placa%";
    $param_types .= 's';
}

if (!empty($search_conductor)) {
    $where_conditions[] = "(c.nombre_completo LIKE ? OR c.cedula LIKE ?)";
    $params[] = "%$search_conductor%";
    $params[] = "%$search_conductor%";
    $param_types .= 'ss';
}

if (!empty($search_estado)) {
    $where_conditions[] = "s.estado = ?";
    $params[] = $search_estado;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$query = "
    SELECT 
        s.*,
        v.placa, v.marca, v.modelo, v.empresa,
        c.nombre_completo as conductor_nombre, c.cedula as conductor_cedula,
        u.username as usuario_nombre,
        ua.username as aprobado_por_nombre,
        COUNT(sa.id) as total_archivos
    FROM sellar s
    LEFT JOIN parque_automotor v ON s.vehiculo_id = v.id
    LEFT JOIN conductores c ON s.conductor_id = c.id
    LEFT JOIN usuarios u ON s.usuario_creacion = u.id
    LEFT JOIN usuarios ua ON s.aprobado_por = ua.id
    LEFT JOIN sellar_archivos sa ON s.id = sa.sellar_id
    $where_clause
    GROUP BY s.id
    ORDER BY s.fecha_creacion DESC
";

if (empty($params)) {
    // No dynamic params — ejecutar consulta directa
    $registros = $mysqli->query($query);
} else {
    $stmt = $mysqli->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $registros = $stmt->get_result();
}

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-stamp me-2"></i>Sellado de Vehículos</h2>
                <a href="create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>Nuevo Sellado
                </a>
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
                            <label for="search_consecutivo" class="form-label">Consecutivo</label>
                            <input type="text" class="form-control" id="search_consecutivo" name="search_consecutivo" 
                                   value="<?php echo htmlspecialchars($search_consecutivo); ?>" placeholder="2024-000001">
                        </div>
                        <div class="col-md-3">
                            <label for="search_placa" class="form-label">Placa</label>
                            <input type="text" class="form-control" id="search_placa" name="search_placa" 
                                   value="<?php echo htmlspecialchars($search_placa); ?>" placeholder="ABC123">
                        </div>
                        <div class="col-md-3">
                            <label for="search_conductor" class="form-label">Conductor</label>
                            <input type="text" class="form-control" id="search_conductor" name="search_conductor" 
                                   value="<?php echo htmlspecialchars($search_conductor); ?>" placeholder="Nombre o cédula">
                        </div>
                        <div class="col-md-3">
                            <label for="search_estado" class="form-label">Estado</label>
                            <select class="form-select" id="search_estado" name="search_estado">
                                <option value="">Todos los estados</option>
                                <option value="pendiente" <?php echo $search_estado === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                <option value="aprobado" <?php echo $search_estado === 'aprobado' ? 'selected' : ''; ?>>Aprobado</option>
                                <option value="rechazado" <?php echo $search_estado === 'rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                                <option value="observaciones" <?php echo $search_estado === 'observaciones' ? 'selected' : ''; ?>>Con Observaciones</option>
                            </select>
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

            <!-- Tabla de registros -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>Registros de Sellado
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="selladoTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Consecutivo</th>
                                    <th>Vehículo</th>
                                    <th>Conductor</th>
                                    <th>Vigencias</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th>Archivos</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($registro = $registros->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($registro['consecutivo']); ?></strong></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($registro['placa']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($registro['marca'] . ' ' . $registro['modelo']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($registro['conductor_nombre']): ?>
                                            <?php echo htmlspecialchars($registro['conductor_nombre']); ?><br>
                                            <small class="text-muted">CC: <?php echo htmlspecialchars($registro['conductor_cedula']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">Sin conductor asignado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge <?php echo $registro['soat_vigente'] ? 'bg-success' : 'bg-danger'; ?> badge-sm">
                                                SOAT <?php echo $registro['soat_vigente'] ? 'OK' : 'VENCIDO'; ?>
                                            </span>
                                            <span class="badge <?php echo $registro['tarjeta_operacion_vigente'] ? 'bg-success' : 'bg-danger'; ?> badge-sm">
                                                T.Op. <?php echo $registro['tarjeta_operacion_vigente'] ? 'OK' : 'VENCIDO'; ?>
                                            </span>
                                            <?php if ($registro['conductor_id']): ?>
                                            <span class="badge <?php echo $registro['licencia_vigente'] ? 'bg-success' : 'bg-danger'; ?> badge-sm">
                                                Lic. <?php echo $registro['licencia_vigente'] ? 'OK' : 'VENCIDO'; ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $estado_classes = [
                                            'pendiente' => 'bg-warning',
                                            'aprobado' => 'bg-success',
                                            'rechazado' => 'bg-danger',
                                            'observaciones' => 'bg-info'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $estado_classes[$registro['estado']] ?? 'bg-secondary'; ?>">
                                            <?php echo ucfirst($registro['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y H:i', strtotime($registro['fecha_creacion'])); ?><br>
                                        <small class="text-muted">por <?php echo htmlspecialchars($registro['usuario_nombre']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo $registro['total_archivos']; ?> archivo(s)
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="view.php?id=<?php echo $registro['id']; ?>" 
                                               class="btn btn-outline-info" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($registro['estado'] === 'pendiente' || $registro['estado'] === 'observaciones'): ?>
                                            <a href="edit.php?id=<?php echo $registro['id']; ?>" 
                                               class="btn btn-outline-primary" title="Editar/Aprobar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php endif; ?>
                                            <a href="manage_files.php?id=<?php echo $registro['id']; ?>" 
                                               class="btn btn-outline-secondary" title="Gestionar archivos">
                                                <i class="fas fa-camera"></i>
                                            </a>
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

<script>
// Inicializar DataTable
$(document).ready(function() {
    $('#selladoTable').DataTable({
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        responsive: true,
        pageLength: 25,
        order: [[0, 'desc']], // Ordenar por consecutivo descendente
        columnDefs: [
            { orderable: false, targets: -1 } // Desactivar ordenación en columna de acciones
        ]
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
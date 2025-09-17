<?php
require_once '../../config.php';
requireLogin();

$id = $_GET['id'] ?? 0;

if (!$id || !is_numeric($id)) {
    header('Location: index.php');
    exit();
}

// Obtener información del registro de sellado
$query = "
    SELECT 
        s.*,
        v.placa, v.marca, v.modelo, v.empresa,
        c.nombre_completo as conductor_nombre, c.cedula as conductor_cedula, 
        c.telefono as conductor_telefono, c.email as conductor_email,
        u.username as usuario_nombre,
        ua.username as aprobado_por_nombre
    FROM sellar s
    LEFT JOIN parque_automotor v ON s.vehiculo_id = v.id
    LEFT JOIN conductores c ON s.conductor_id = c.id
    LEFT JOIN usuarios u ON s.usuario_creacion = u.id
    LEFT JOIN usuarios ua ON s.aprobado_por = ua.id
    WHERE s.id = ?
";

$id = (int)$id;
$query = str_replace('WHERE s.id = ?', 'WHERE s.id = ' . $id, $query);
$res = $mysqli->query($query);
$registro = $res ? $res->fetch_assoc() : null;

if (!$registro) {
    header('Location: index.php');
    exit();
}

// Obtener archivos asociados
$archivos_query = "
    SELECT sa.*, u.username 
    FROM sellar_archivos sa 
    LEFT JOIN usuarios u ON sa.usuario_id = u.id 
    WHERE sa.sellar_id = ? 
    ORDER BY sa.fecha_subida DESC
";
$archivos_query = str_replace('WHERE sa.sellar_id = ?', 'WHERE sa.sellar_id = ' . $id, $archivos_query);
$archivos = $mysqli->query($archivos_query);

$page_title = 'Sellado ' . $registro['consecutivo'];
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-stamp me-2"></i>Sellado <?php echo htmlspecialchars($registro['consecutivo']); ?></h2>
                    <p class="text-muted mb-0">Detalles del proceso de sellado</p>
                </div>
                <div>
                    <?php if ($registro['estado'] === 'pendiente' || $registro['estado'] === 'observaciones'): ?>
                    <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-primary me-2">
                        <i class="fas fa-edit me-1"></i>Editar/Aprobar
                    </a>
                    <?php endif; ?>
                    <a href="manage_files.php?id=<?php echo $id; ?>" class="btn btn-secondary me-2">
                        <i class="fas fa-camera me-1"></i>Gestionar Archivos
                    </a>
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Información general -->
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-info-circle me-2"></i>Información General
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Consecutivo:</label>
                                        <p class="mb-0"><?php echo htmlspecialchars($registro['consecutivo']); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Estado:</label>
                                        <p class="mb-0">
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
                                        </p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Fecha de Creación:</label>
                                        <p class="mb-0"><?php echo date('d/m/Y H:i:s', strtotime($registro['fecha_creacion'])); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Creado por:</label>
                                        <p class="mb-0"><?php echo htmlspecialchars($registro['usuario_nombre']); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <?php if ($registro['estado'] === 'aprobado' || $registro['estado'] === 'rechazado'): ?>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Fecha de Aprobación/Rechazo:</label>
                                        <p class="mb-0"><?php echo $registro['fecha_aprobacion'] ? date('d/m/Y H:i:s', strtotime($registro['fecha_aprobacion'])) : 'N/A'; ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Aprobado/Rechazado por:</label>
                                        <p class="mb-0"><?php echo htmlspecialchars($registro['aprobado_por_nombre'] ?? 'N/A'); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($registro['observaciones']): ?>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Observaciones:</label>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($registro['observaciones'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($registro['motivo_rechazo']): ?>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Motivo de Rechazo:</label>
                                        <div class="alert alert-danger">
                                            <?php echo nl2br(htmlspecialchars($registro['motivo_rechazo'])); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información del vehículo -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-car me-2"></i>Información del Vehículo
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Placa:</label>
                                        <p class="mb-0"><?php echo htmlspecialchars($registro['placa']); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Marca/Modelo:</label>
                                        <p class="mb-0"><?php echo htmlspecialchars($registro['marca'] . ' ' . $registro['modelo']); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Color:</label>
                                        <p class="mb-0"><?php echo htmlspecialchars($registro['color'] ?? 'No especificado'); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Año:</label>
                                        <p class="mb-0"><?php echo htmlspecialchars($registro['año'] ?? 'No especificado'); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">NiV:</label>
                                        <p class="mb-0"><?php echo htmlspecialchars($registro['niv'] ?? 'No especificado'); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Empresa:</label>
                                        <p class="mb-0"><?php echo htmlspecialchars($registro['empresa'] ?? 'No asignada'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información del conductor -->
                    <?php if ($registro['conductor_id']): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user me-2"></i>Información del Conductor
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Nombre Completo:</label>
                                        <p class="mb-0"><?php echo htmlspecialchars($registro['conductor_nombre']); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Cédula:</label>
                                        <p class="mb-0"><?php echo htmlspecialchars($registro['conductor_cedula']); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Teléfono:</label>
                                        <p class="mb-0"><?php echo htmlspecialchars($registro['conductor_telefono'] ?? 'No especificado'); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Email:</label>
                                        <p class="mb-0"><?php echo htmlspecialchars($registro['conductor_email'] ?? 'No especificado'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Panel lateral -->
                <div class="col-lg-4">
                    <!-- Verificación de vigencias -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-check-circle me-2"></i>Verificación de Vigencias
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>SOAT:</span>
                                <span class="badge <?php echo $registro['soat_vigente'] ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $registro['soat_vigente'] ? 'VIGENTE' : 'VENCIDO'; ?>
                                </span>
                            </div>
                            <?php if ($registro['fecha_vencimiento_soat']): ?>
                            <div class="mb-3">
                                <small class="text-muted">Vence: <?php echo date('d/m/Y', strtotime($registro['fecha_vencimiento_soat'])); ?></small>
                            </div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Tarjeta de Operación:</span>
                                <span class="badge <?php echo $registro['tarjeta_operacion_vigente'] ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $registro['tarjeta_operacion_vigente'] ? 'VIGENTE' : 'VENCIDO'; ?>
                                </span>
                            </div>
                            <?php if ($registro['fecha_vencimiento_tarjeta']): ?>
                            <div class="mb-3">
                                <small class="text-muted">Vence: <?php echo date('d/m/Y', strtotime($registro['fecha_vencimiento_tarjeta'])); ?></small>
                            </div>
                            <?php endif; ?>

                            <?php if ($registro['conductor_id']): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Licencia de Conducir:</span>
                                <span class="badge <?php echo $registro['licencia_vigente'] ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $registro['licencia_vigente'] ? 'VIGENTE' : 'VENCIDA'; ?>
                                </span>
                            </div>
                            <?php if ($registro['fecha_vencimiento_licencia']): ?>
                            <div class="mb-3">
                                <small class="text-muted">Vence: <?php echo date('d/m/Y', strtotime($registro['fecha_vencimiento_licencia'])); ?></small>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Archivos adjuntos -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-camera me-2"></i>Archivos Adjuntos
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php if ($archivos->num_rows > 0): ?>
                                <?php while ($archivo = $archivos->fetch_assoc()): ?>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-file-image text-muted me-2"></i>
                                    <div class="flex-grow-1">
                                        <a href="<?php echo BASE_URL; ?>uploads/sellar/<?php echo $archivo['nombre_archivo']; ?>" 
                                           target="_blank" class="text-decoration-none">
                                            <?php echo htmlspecialchars($archivo['nombre_original']); ?>
                                        </a>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo ucfirst(str_replace('_', ' ', $archivo['tipo_archivo'])); ?> - 
                                            <?php echo date('d/m/Y H:i', strtotime($archivo['fecha_subida'])); ?>
                                        </small>
                                    </div>
                                </div>
                                <hr>
                                <?php endwhile; ?>
                                <div class="text-center">
                                    <a href="manage_files.php?id=<?php echo $id; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-plus me-1"></i>Gestionar Archivos
                                    </a>
                                </div>
                            <?php else: ?>
                                <p class="text-muted text-center mb-3">No hay archivos adjuntos</p>
                                <div class="text-center">
                                    <a href="manage_files.php?id=<?php echo $id; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-camera me-1"></i>Subir Archivos
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
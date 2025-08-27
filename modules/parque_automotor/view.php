<?php
require_once '../../config.php';
requireLogin();

$page_title = 'Ver Vehículo - Parque Automotor';
$vehiculo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($vehiculo_id === 0) {
    header('Location: index.php');
    exit();
}

// Obtener datos del vehículo
$stmt = $mysqli->prepare("
    SELECT p.*, 
           a.cedula as asociado_cedula,
           a.nombres as asociado_nombres,
           a.apellidos as asociado_apellidos,
           a.telefono1 as asociado_telefono,
           a.celular as asociado_celular
    FROM parque_automotor p
    LEFT JOIN asociados a ON p.asociado_id = a.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $vehiculo_id);
$stmt->execute();
$result = $stmt->get_result();
$vehiculo = $result->fetch_assoc();

if (!$vehiculo) {
    header('Location: index.php');
    exit();
}

// Obtener archivos del vehículo
$archivos_query = $mysqli->prepare("SELECT * FROM parque_automotor_archivos WHERE vehiculo_id = ? ORDER BY fecha_subida DESC");
$archivos_query->bind_param("i", $vehiculo_id);
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
                        <i class="fas fa-car me-2"></i>Detalles del Vehículo - Placa: <?php echo htmlspecialchars($vehiculo['placa']); ?>
                    </h4>
                    <div>
                        <a href="edit.php?id=<?php echo $vehiculo_id; ?>" class="btn btn-warning me-2">
                            <i class="fas fa-edit me-2"></i>Editar
                        </a>
                        <a href="manage_files.php?id=<?php echo $vehiculo_id; ?>" class="btn btn-info me-2">
                            <i class="fas fa-file me-2"></i>Gestionar Archivos
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
                            <!-- Datos básicos del vehículo -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-car me-2"></i>Información Básica del Vehículo</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <strong>Placa:</strong><br>
                                            <span class="h5 text-primary"><?php echo htmlspecialchars($vehiculo['placa']); ?></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <strong>Marca:</strong><br>
                                            <span class="h6"><?php echo htmlspecialchars($vehiculo['marca']); ?></span>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <strong>Modelo:</strong><br>
                                            <span class="h6"><?php echo htmlspecialchars($vehiculo['modelo']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <strong>NIB:</strong><br>
                                            <?php echo htmlspecialchars($vehiculo['nib']) ?: 'No especificado'; ?>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <strong>Chasis:</strong><br>
                                            <?php echo htmlspecialchars($vehiculo['chassis']) ?: 'No especificado'; ?>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <strong>Motor:</strong><br>
                                            <?php echo htmlspecialchars($vehiculo['motor']) ?: 'No especificado'; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <strong>Radio Teléfono:</strong><br>
                                            <?php echo htmlspecialchars($vehiculo['radio_telefono']) ?: 'No especificado'; ?>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <strong>Serial:</strong><br>
                                            <?php echo htmlspecialchars($vehiculo['serial']) ?: 'No especificado'; ?>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <strong>Tipo de Combustible:</strong><br>
                                            <?php echo ucfirst($vehiculo['tipo_combustible']); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <strong>Empresa:</strong><br>
                                            <?php echo htmlspecialchars($vehiculo['empresa']) ?: 'No asignada'; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong>Estado:</strong><br>
                                            <?php
                                            $estado_classes = [
                                                'activo' => 'success',
                                                'inactivo' => 'secondary',
                                                'mantenimiento' => 'warning',
                                                'vendido' => 'danger'
                                            ];
                                            $estado_class = $estado_classes[$vehiculo['estado']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $estado_class; ?> fs-6">
                                                <?php echo ucfirst($vehiculo['estado']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Información de SOAT -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Información de SOAT</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <strong>Compañía SOAT:</strong><br>
                                            <?php echo htmlspecialchars($vehiculo['compania_soat']) ?: 'No especificada'; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong>Número SOAT:</strong><br>
                                            <?php echo htmlspecialchars($vehiculo['soat']) ?: 'No especificado'; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <strong>Vencimiento SOAT:</strong><br>
                                            <?php 
                                            if ($vehiculo['vencimiento_soat']) {
                                                $fecha_soat = new DateTime($vehiculo['vencimiento_soat']);
                                                $hoy = new DateTime();
                                                $diferencia = $fecha_soat->diff($hoy);
                                                $dias_restantes = $fecha_soat >= $hoy ? $diferencia->days : -$diferencia->days;
                                                
                                                $clase_color = '';
                                                $mensaje_estado = '';
                                                if ($dias_restantes < 0) {
                                                    $clase_color = 'text-danger';
                                                    $mensaje_estado = ' (VENCIDO)';
                                                } elseif ($dias_restantes <= 30) {
                                                    $clase_color = 'text-warning';
                                                    $mensaje_estado = ' (Por vencer)';
                                                } else {
                                                    $clase_color = 'text-success';
                                                    $mensaje_estado = ' (Vigente)';
                                                }
                                                echo '<span class="' . $clase_color . '">' . $fecha_soat->format('d/m/Y') . $mensaje_estado . '</span>';
                                            } else {
                                                echo 'No registrado';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Certificados y permisos -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-certificate me-2"></i>Certificados y Permisos</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <strong>Certificado de Movilización:</strong><br>
                                            <?php echo htmlspecialchars($vehiculo['certificado_movilizacion']) ?: 'No especificado'; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong>Vencimiento Certificado:</strong><br>
                                            <?php 
                                            if ($vehiculo['fecha_vencimiento_certificado']) {
                                                $fecha_cert = new DateTime($vehiculo['fecha_vencimiento_certificado']);
                                                $hoy = new DateTime();
                                                $diferencia = $fecha_cert->diff($hoy);
                                                $dias_restantes = $fecha_cert >= $hoy ? $diferencia->days : -$diferencia->days;
                                                
                                                $clase_color = '';
                                                $mensaje_estado = '';
                                                if ($dias_restantes < 0) {
                                                    $clase_color = 'text-danger';
                                                    $mensaje_estado = ' (VENCIDO)';
                                                } elseif ($dias_restantes <= 30) {
                                                    $clase_color = 'text-warning';
                                                    $mensaje_estado = ' (Por vencer)';
                                                } else {
                                                    $clase_color = 'text-success';
                                                    $mensaje_estado = ' (Vigente)';
                                                }
                                                echo '<span class="' . $clase_color . '">' . $fecha_cert->format('d/m/Y') . $mensaje_estado . '</span>';
                                            } else {
                                                echo 'No registrado';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tarjeta de operación -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Tarjeta de Operación</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <strong>Tarjeta de Operación:</strong><br>
                                            <?php echo htmlspecialchars($vehiculo['tarjeta_operacion']) ?: 'No especificada'; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong>Fecha T. Operación:</strong><br>
                                            <?php echo $vehiculo['fecha_tarjeta_operacion'] ? formatDate($vehiculo['fecha_tarjeta_operacion']) : 'No registrada'; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <strong>Inicio T. Operación:</strong><br>
                                            <?php echo $vehiculo['inicio_tarjeta_operacion'] ? formatDate($vehiculo['inicio_tarjeta_operacion']) : 'No registrado'; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong>Final T. Operación:</strong><br>
                                            <?php 
                                            if ($vehiculo['final_tarjeta_operacion']) {
                                                $fecha_final = new DateTime($vehiculo['final_tarjeta_operacion']);
                                                $hoy = new DateTime();
                                                $diferencia = $fecha_final->diff($hoy);
                                                $dias_restantes = $fecha_final >= $hoy ? $diferencia->days : -$diferencia->days;
                                                
                                                $clase_color = '';
                                                $mensaje_estado = '';
                                                if ($dias_restantes < 0) {
                                                    $clase_color = 'text-danger';
                                                    $mensaje_estado = ' (VENCIDA)';
                                                } elseif ($dias_restantes <= 30) {
                                                    $clase_color = 'text-warning';
                                                    $mensaje_estado = ' (Por vencer)';
                                                } else {
                                                    $clase_color = 'text-success';
                                                    $mensaje_estado = ' (Vigente)';
                                                }
                                                echo '<span class="' . $clase_color . '">' . $fecha_final->format('d/m/Y') . $mensaje_estado . '</span>';
                                            } else {
                                                echo 'No registrado';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Revisión preventiva y pólizas -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Revisión Preventiva y Pólizas</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <strong>Revisión Preventiva:</strong><br>
                                            <?php echo htmlspecialchars($vehiculo['revision_preventiva']) ?: 'No especificada'; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong>Vencimiento Preventiva:</strong><br>
                                            <?php 
                                            if ($vehiculo['vencimiento_preventiva']) {
                                                $fecha_prev = new DateTime($vehiculo['vencimiento_preventiva']);
                                                $hoy = new DateTime();
                                                $diferencia = $fecha_prev->diff($hoy);
                                                $dias_restantes = $fecha_prev >= $hoy ? $diferencia->days : -$diferencia->days;
                                                
                                                $clase_color = '';
                                                $mensaje_estado = '';
                                                if ($dias_restantes < 0) {
                                                    $clase_color = 'text-danger';
                                                    $mensaje_estado = ' (VENCIDA)';
                                                } elseif ($dias_restantes <= 30) {
                                                    $clase_color = 'text-warning';
                                                    $mensaje_estado = ' (Por vencer)';
                                                } else {
                                                    $clase_color = 'text-success';
                                                    $mensaje_estado = ' (Vigente)';
                                                }
                                                echo '<span class="' . $clase_color . '">' . $fecha_prev->format('d/m/Y') . $mensaje_estado . '</span>';
                                            } else {
                                                echo 'No registrado';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <strong>Póliza Responsabilidad Civil:</strong><br>
                                            <?php echo htmlspecialchars($vehiculo['poliza_responsabilidad_civil']) ?: 'No especificada'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Asociado asignado -->
                            <?php if ($vehiculo['asociado_nombres']): ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Asociado Asignado</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <strong>Cédula:</strong><br>
                                            <?php echo htmlspecialchars($vehiculo['asociado_cedula']); ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong>Nombre Completo:</strong><br>
                                            <?php echo htmlspecialchars($vehiculo['asociado_nombres'] . ' ' . $vehiculo['asociado_apellidos']); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <strong>Teléfono:</strong><br>
                                            <?php echo htmlspecialchars($vehiculo['asociado_telefono']) ?: 'No especificado'; ?>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <strong>Celular:</strong><br>
                                            <?php echo htmlspecialchars($vehiculo['asociado_celular']) ?: 'No especificado'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Observaciones -->
                            <?php if ($vehiculo['observaciones']): ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-comment me-2"></i>Observaciones</h5>
                                </div>
                                <div class="card-body">
                                    <?php echo nl2br(htmlspecialchars($vehiculo['observaciones'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Sidebar con archivos -->
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-file me-2"></i>Archivos</h5>
                                    <a href="manage_files.php?id=<?php echo $vehiculo_id; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-plus me-1"></i>Gestionar
                                    </a>
                                </div>
                                <div class="card-body">
                                    <?php if ($archivos->num_rows > 0): ?>
                                        <div class="list-group list-group-flush">
                                            <?php while ($archivo = $archivos->fetch_assoc()): ?>
                                            <div class="list-group-item p-2">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($archivo['nombre_original']); ?></h6>
                                                        <small class="text-muted">
                                                            <?php echo ucfirst(str_replace('_', ' ', $archivo['tipo_documento'])); ?><br>
                                                            <?php echo number_format($archivo['tamaño'] / 1024, 1) . ' KB'; ?> - 
                                                            <?php echo formatDate($archivo['fecha_subida']); ?>
                                                        </small>
                                                    </div>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="../../uploads/documentos/<?php echo htmlspecialchars($archivo['nombre_archivo']); ?>" 
                                                           class="btn btn-outline-primary btn-sm" target="_blank" title="Ver archivo">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endwhile; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted text-center py-3">
                                            <i class="fas fa-folder-open fa-2x mb-2 d-block"></i>
                                            No hay archivos subidos
                                        </p>
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

<?php include '../../includes/footer.php'; ?>

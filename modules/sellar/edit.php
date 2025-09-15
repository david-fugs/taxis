<?php
require_once '../../config.php';
requireLogin();

$id = $_GET['id'] ?? 0;

if (!$id || !is_numeric($id)) {
    header('Location: index.php');
    exit();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estado = $_POST['estado'] ?? '';
    $observaciones = trim($_POST['observaciones'] ?? '');
    $motivo_rechazo = trim($_POST['motivo_rechazo'] ?? '');
    $usuario_id = $_SESSION['user_id'];
    
    $estados_validos = ['pendiente', 'aprobado', 'rechazado', 'observaciones'];
    
    if (in_array($estado, $estados_validos)) {
        $sql = "UPDATE sellar SET 
                estado = ?, 
                observaciones = ?, 
                motivo_rechazo = ?, 
                aprobado_por = ?, 
                fecha_aprobacion = CURRENT_TIMESTAMP 
                WHERE id = ?";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sssii', $estado, $observaciones, $motivo_rechazo, $usuario_id, $id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Registro actualizado exitosamente.';
            $_SESSION['message_type'] = 'success';
            header('Location: view.php?id=' . $id);
            exit();
        } else {
            $error_message = 'Error al actualizar el registro.';
        }
    } else {
        $error_message = 'Estado no válido.';
    }
}

// Obtener información del registro
$query = "
    SELECT 
        s.*,
        v.placa, v.marca, v.modelo,
        c.nombre_completo as conductor_nombre, c.cedula as conductor_cedula
    FROM sellar s
    LEFT JOIN parque_automotor v ON s.vehiculo_id = v.id
    LEFT JOIN conductores c ON s.conductor_id = c.id
    WHERE s.id = ?
";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$registro = $stmt->get_result()->fetch_assoc();

if (!$registro) {
    header('Location: index.php');
    exit();
}

$page_title = 'Editar Sellado ' . $registro['consecutivo'];
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-edit me-2"></i>Editar Sellado <?php echo htmlspecialchars($registro['consecutivo']); ?></h2>
                    <p class="text-muted mb-0">Aprobar, rechazar o agregar observaciones</p>
                </div>
                <div>
                    <a href="view.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>

            <?php if (isset($error_message)): ?>
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
                                <i class="fas fa-stamp me-2"></i>Actualizar Estado del Sellado
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="estado" class="form-label">Estado <span class="text-danger">*</span></label>
                                            <select class="form-select" id="estado" name="estado" required onchange="toggleFields()">
                                                <option value="pendiente" <?php echo $registro['estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                                <option value="aprobado" <?php echo $registro['estado'] === 'aprobado' ? 'selected' : ''; ?>>Aprobado</option>
                                                <option value="rechazado" <?php echo $registro['estado'] === 'rechazado' ? 'selected' : ''; ?>>Rechazado</option>
                                                <option value="observaciones" <?php echo $registro['estado'] === 'observaciones' ? 'selected' : ''; ?>>Con Observaciones</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Estado Actual:</label>
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
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="observaciones" class="form-label">Observaciones</label>
                                    <textarea class="form-control" id="observaciones" name="observaciones" rows="4" 
                                              placeholder="Observaciones generales sobre el sellado..."><?php echo htmlspecialchars($registro['observaciones']); ?></textarea>
                                </div>

                                <div class="mb-3" id="motivoRechazoField" style="display: none;">
                                    <label for="motivo_rechazo" class="form-label">Motivo de Rechazo <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="motivo_rechazo" name="motivo_rechazo" rows="3" 
                                              placeholder="Especifique el motivo por el cual se rechaza el sellado..."><?php echo htmlspecialchars($registro['motivo_rechazo']); ?></textarea>
                                    <div class="form-text">Este campo es obligatorio cuando se rechaza un sellado.</div>
                                </div>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Actualizar Sellado
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Información del vehículo -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-car me-2"></i>Información del Vehículo
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>Placa:</strong> <?php echo htmlspecialchars($registro['placa']); ?>
                            </div>
                            <div class="mb-2">
                                <strong>Marca/Modelo:</strong> <?php echo htmlspecialchars($registro['marca'] . ' ' . $registro['modelo']); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Información del conductor -->
                    <?php if ($registro['conductor_id']): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-user me-2"></i>Información del Conductor
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>Nombre:</strong> <?php echo htmlspecialchars($registro['conductor_nombre']); ?>
                            </div>
                            <div class="mb-2">
                                <strong>Cédula:</strong> <?php echo htmlspecialchars($registro['conductor_cedula']); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Verificación de vigencias -->
                    <div class="card">
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

                            <!-- Sugerencia automática -->
                            <div class="mt-3">
                                <div class="alert alert-<?php 
                                    $todas_vigentes = $registro['soat_vigente'] && $registro['tarjeta_operacion_vigente'] && 
                                                     (!$registro['conductor_id'] || $registro['licencia_vigente']);
                                    echo $todas_vigentes ? 'success' : 'warning'; 
                                ?> alert-sm">
                                    <strong>Sugerencia:</strong> 
                                    <?php if ($todas_vigentes): ?>
                                        Todas las vigencias están al día. Se recomienda aprobar.
                                    <?php else: ?>
                                        Hay documentos vencidos. Revisar antes de aprobar.
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

<script>
function toggleFields() {
    const estado = document.getElementById('estado').value;
    const motivoField = document.getElementById('motivoRechazoField');
    const motivoTextarea = document.getElementById('motivo_rechazo');
    
    if (estado === 'rechazado') {
        motivoField.style.display = 'block';
        motivoTextarea.setAttribute('required', 'required');
    } else {
        motivoField.style.display = 'none';
        motivoTextarea.removeAttribute('required');
        if (estado !== 'rechazado') {
            motivoTextarea.value = '';
        }
    }
}

// Ejecutar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    toggleFields();
});
</script>

<?php include '../../includes/footer.php'; ?>
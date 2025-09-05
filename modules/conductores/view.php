<?php
require_once '../../config.php';
require_once '../../conexion.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'modules/auth/login.php');
    exit();
}

$conductor_id = $_GET['id'] ?? 0;

// Obtener información del conductor con vehículo relacionado
$sql = "SELECT c.*, 
               p.placa, p.marca, p.modelo, p.nib,
               CASE 
                   WHEN c.licencia_vence IS NOT NULL THEN
                       CASE 
                           WHEN c.licencia_vence < CURDATE() THEN 'vencida'
                           WHEN c.licencia_vence <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'proxima_vencer'
                           ELSE 'vigente'
                       END
                   ELSE 'sin_fecha'
               END as estado_licencia,
               CASE 
                   WHEN c.inscripcion_vence IS NOT NULL THEN
                       CASE 
                           WHEN c.inscripcion_vence < CURDATE() THEN 'vencida'
                           WHEN c.inscripcion_vence <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'proxima_vencer'
                           ELSE 'vigente'
                       END
                   ELSE 'sin_fecha'
               END as estado_inscripcion
        FROM conductores c 
        LEFT JOIN parque_automotor p ON c.vehiculo_id = p.id 
        WHERE c.id = ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $conductor_id);
$stmt->execute();
$result = $stmt->get_result();
$conductor = $result->fetch_assoc();

if (!$conductor) {
    header('Location: ' . BASE_URL . 'modules/conductores/index.php');
    exit();
}

// Obtener archivos del conductor
$sql_archivos = "SELECT ca.*, u.username FROM conductores_archivos ca 
                 LEFT JOIN usuarios u ON ca.usuario_id = u.id 
                 WHERE ca.conductor_id = ? 
                 ORDER BY ca.fecha_subida DESC";
$stmt_archivos = $mysqli->prepare($sql_archivos);
$stmt_archivos->bind_param("i", $conductor_id);
$stmt_archivos->execute();
$archivos = $stmt_archivos->get_result();

$page_title = "Ver Conductor - " . $conductor['nombre_completo'];
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-user me-2"></i>Información del Conductor</h2>
                    <p class="text-muted mb-0"><?php echo htmlspecialchars($conductor['nombre_completo']); ?></p>
                </div>
                <div>
                    <a href="index.php" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                    <a href="edit.php?id=<?php echo $conductor['id']; ?>" class="btn btn-primary me-2">
                        <i class="fas fa-edit me-1"></i>Editar
                    </a>
                    <a href="manage_files.php?id=<?php echo $conductor['id']; ?>" class="btn btn-success">
                        <i class="fas fa-folder me-1"></i>Gestionar Archivos
                    </a>
                </div>
            </div>

            <div class="row">
                <!-- Información Personal -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-user me-2"></i>Información Personal</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <p><strong>Nombre Completo:</strong><br><?php echo htmlspecialchars($conductor['nombre_completo']); ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <p><strong>Cédula:</strong><br><?php echo htmlspecialchars($conductor['cedula']); ?> 
                                    <?php if ($conductor['expedida_en']): ?>(Exp: <?php echo htmlspecialchars($conductor['expedida_en']); ?>)<?php endif; ?></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <p><strong>Teléfono:</strong><br><?php echo htmlspecialchars($conductor['telefono'] ?: 'No registrado'); ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <p><strong>Celular:</strong><br><?php echo htmlspecialchars($conductor['celular'] ?: 'No registrado'); ?></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <p><strong>Email:</strong><br><?php echo htmlspecialchars($conductor['email'] ?: 'No registrado'); ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <p><strong>Ciudad:</strong><br><?php echo htmlspecialchars($conductor['ciudad'] ?: 'No registrado'); ?></p>
                                </div>
                            </div>
                            <?php if ($conductor['direccion']): ?>
                            <div class="row">
                                <div class="col-12">
                                    <p><strong>Dirección:</strong><br><?php echo htmlspecialchars($conductor['direccion']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="row">
                                <div class="col-sm-4">
                                    <p><strong>Fecha Nacimiento:</strong><br>
                                    <?php echo $conductor['fecha_nacimiento'] ? date('d/m/Y', strtotime($conductor['fecha_nacimiento'])) : 'No registrado'; ?></p>
                                </div>
                                <div class="col-sm-4">
                                    <p><strong>Edad:</strong><br><?php echo $conductor['edad'] ?: 'No registrado'; ?> años</p>
                                </div>
                                <div class="col-sm-4">
                                    <p><strong>RH:</strong><br><?php echo htmlspecialchars($conductor['rh'] ?: 'No registrado'); ?></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <p><strong>Estado Civil:</strong><br><?php echo ucfirst($conductor['estado_civil']); ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <p><strong>Lugar Nacimiento:</strong><br><?php echo htmlspecialchars($conductor['lugar_nacimiento'] ?: 'No registrado'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Licencia -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Información de Licencia</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <p><strong>Número Licencia:</strong><br><?php echo htmlspecialchars($conductor['licencia_numero'] ?: 'No registrado'); ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <p><strong>Categoría:</strong><br>
                                    <?php if ($conductor['categoria_licencia']): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($conductor['categoria_licencia']); ?></span>
                                    <?php else: ?>
                                        No registrado
                                    <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <p><strong>Expedida:</strong><br>
                                    <?php echo $conductor['licencia_expedida'] ? date('d/m/Y', strtotime($conductor['licencia_expedida'])) : 'No registrado'; ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <p><strong>Vence:</strong><br>
                                    <?php if ($conductor['licencia_vence']): ?>
                                        <span class="badge bg-<?php 
                                            echo $conductor['estado_licencia'] === 'vencida' ? 'danger' : 
                                                 ($conductor['estado_licencia'] === 'proxima_vencer' ? 'warning' : 'success'); 
                                        ?>">
                                            <?php echo date('d/m/Y', strtotime($conductor['licencia_vence'])); ?>
                                        </span>
                                    <?php else: ?>
                                        No registrado
                                    <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Vehículo Asignado -->
                <?php if ($conductor['vehiculo_id']): ?>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-car me-2"></i>Vehículo Asignado</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-sm-6">
                                    <p><strong>Placa:</strong><br><span class="badge bg-primary fs-6"><?php echo htmlspecialchars($conductor['placa']); ?></span></p>
                                </div>
                                <div class="col-sm-6">
                                    <p><strong>Fecha Asignación:</strong><br>
                                    <?php echo $conductor['fecha_relacionada_vehiculo'] ? date('d/m/Y', strtotime($conductor['fecha_relacionada_vehiculo'])) : 'No registrado'; ?></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <p><strong>Marca:</strong><br><?php echo htmlspecialchars($conductor['marca']); ?></p>
                                </div>
                                <div class="col-sm-6">
                                    <p><strong>Modelo:</strong><br><?php echo htmlspecialchars($conductor['modelo']); ?></p>
                                </div>
                            </div>
                            <?php if ($conductor['nib']): ?>
                            <div class="row">
                                <div class="col-12">
                                    <p><strong>NIB:</strong><br><?php echo htmlspecialchars($conductor['nib']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="row">
                                <div class="col-12">
                                    <a href="<?php echo BASE_URL; ?>modules/parque_automotor/view.php?id=<?php echo $conductor['vehiculo_id']; ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>Ver Detalles del Vehículo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Contacto de Emergencia -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Contacto de Emergencia</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12">
                                    <p><strong>Contacto:</strong><br><?php echo htmlspecialchars($conductor['emergencia_contacto'] ?: 'No registrado'); ?></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <p><strong>Teléfono:</strong><br><?php echo htmlspecialchars($conductor['emergencia_telefono'] ?: 'No registrado'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información Laboral -->
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>Información Laboral y Seguridad Social</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($conductor['experiencia']): ?>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <p><strong>Experiencia:</strong><br><?php echo nl2br(htmlspecialchars($conductor['experiencia'])); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="row">
                                <div class="col-md-4">
                                    <p><strong>ARP:</strong><br><?php echo htmlspecialchars($conductor['arp'] ?: 'No registrado'); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Salud:</strong><br><?php echo htmlspecialchars($conductor['salud'] ?: 'No registrado'); ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Pensión:</strong><br><?php echo htmlspecialchars($conductor['pension'] ?: 'No registrado'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información Adicional -->
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información Adicional</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Fecha de Ingreso:</strong><br>
                                    <?php echo $conductor['fecha_ingreso'] ? date('d/m/Y', strtotime($conductor['fecha_ingreso'])) : 'No registrado'; ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Inscripción Vence:</strong><br>
                                    <?php if ($conductor['inscripcion_vence']): ?>
                                        <span class="badge bg-<?php 
                                            echo $conductor['estado_inscripcion'] === 'vencida' ? 'danger' : 
                                                 ($conductor['estado_inscripcion'] === 'proxima_vencer' ? 'warning' : 'success'); 
                                        ?>">
                                            <?php echo date('d/m/Y', strtotime($conductor['inscripcion_vence'])); ?>
                                        </span>
                                    <?php else: ?>
                                        No registrado
                                    <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                            <?php if ($conductor['nombre_padres']): ?>
                            <div class="row">
                                <div class="col-12">
                                    <p><strong>Nombre de los Padres:</strong><br><?php echo htmlspecialchars($conductor['nombre_padres']); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($conductor['hijos_menores']): ?>
                            <div class="row">
                                <div class="col-12">
                                    <p><strong>Hijos Menores de 18 Años:</strong><br><?php echo nl2br(htmlspecialchars($conductor['hijos_menores'])); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($conductor['beneficiario_funebre']): ?>
                            <div class="row">
                                <div class="col-12">
                                    <p><strong>Beneficiario Servicio Fúnebre:</strong><br><?php echo nl2br(htmlspecialchars($conductor['beneficiario_funebre'])); ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Archivos -->
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-folder me-2"></i>Documentos</h5>
                            <a href="manage_files.php?id=<?php echo $conductor['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-upload me-1"></i>Gestionar Archivos
                            </a>
                        </div>
                        <div class="card-body">
                            <?php if ($archivos->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Archivo</th>
                                            <th>Tipo</th>
                                            <th>Descripción</th>
                                            <th>Fecha</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($archivo = $archivos->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-file-<?php 
                                                    $ext = strtolower(pathinfo($archivo['nombre_original'], PATHINFO_EXTENSION));
                                                    echo ($ext === 'pdf') ? 'pdf' : 
                                                         (in_array($ext, ['jpg', 'jpeg', 'png', 'gif']) ? 'image' : 'alt');
                                                ?> me-2"></i>
                                                <?php echo htmlspecialchars($archivo['nombre_original']); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php 
                                                    $tipos = [
                                                        'cedula' => 'Cédula',
                                                        'licencia' => 'Licencia',
                                                        'seguro_social' => 'Seguro Social',
                                                        'foto' => 'Fotografía',
                                                        'certificados' => 'Certificados',
                                                        'general' => 'General'
                                                    ];
                                                    echo $tipos[$archivo['tipo_archivo']] ?? 'General';
                                                    ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($archivo['descripcion']); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($archivo['fecha_subida'])); ?></td>
                                            <td>
                                                <a href="<?php echo BASE_URL; ?>uploads/documentos/<?php echo $archivo['nombre_archivo']; ?>" 
                                                   target="_blank" class="btn btn-outline-primary btn-sm" title="Ver archivo">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="text-muted text-center py-4">
                                <i class="fas fa-folder-open fa-3x mb-3"></i><br>
                                No hay documentos subidos
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Estado del Conductor -->
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Estado del Conductor</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <p><strong>Estado:</strong><br>
                                    <span class="badge bg-<?php 
                                        echo $conductor['estado'] === 'activo' ? 'success' : 
                                             ($conductor['estado'] === 'suspendido' ? 'warning' : 'secondary'); 
                                    ?> fs-6">
                                        <?php echo ucfirst($conductor['estado']); ?>
                                    </span>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Fecha Registro:</strong><br><?php echo date('d/m/Y H:i', strtotime($conductor['fecha_creacion'])); ?></p>
                                </div>
                                <div class="col-md-3">
                                    <p><strong>Última Actualización:</strong><br><?php echo date('d/m/Y H:i', strtotime($conductor['fecha_actualizacion'])); ?></p>
                                </div>
                                <div class="col-md-3">
                                    <?php if ($conductor['estado'] === 'activo'): ?>
                                    <button type="button" class="btn btn-warning" onclick="cambiarEstado(<?php echo $conductor['id']; ?>, 'suspendido')">
                                        <i class="fas fa-pause me-1"></i>Suspender
                                    </button>
                                    <?php elseif ($conductor['estado'] === 'suspendido'): ?>
                                    <button type="button" class="btn btn-success" onclick="cambiarEstado(<?php echo $conductor['id']; ?>, 'activo')">
                                        <i class="fas fa-play me-1"></i>Activar
                                    </button>
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
function cambiarEstado(id, nuevoEstado) {
    const titulo = nuevoEstado === 'activo' ? '¿Activar conductor?' : '¿Suspender conductor?';
    const texto = nuevoEstado === 'activo' ? 
        'El conductor podrá volver a operar normalmente' : 
        'El conductor no podrá operar hasta ser reactivado';
    
    Swal.fire({
        title: titulo,
        text: texto,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: nuevoEstado === 'activo' ? '#28a745' : '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: nuevoEstado === 'activo' ? 'Sí, activar' : 'Sí, suspender',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'cambiar_estado.php',
                type: 'POST',
                data: {
                    id: id,
                    estado: nuevoEstado
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: '¡Éxito!',
                            text: response.message,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error',
                            text: response.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error',
                        text: 'Error al cambiar el estado del conductor',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    });
}
</script>

<?php include '../../includes/footer.php'; ?>

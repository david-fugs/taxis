<?php
require_once '../../config.php';
require_once '../../conexion.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'modules/auth/login.php');
    exit();
}

// Obtener conductores con información del vehículo relacionado
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
        ORDER BY c.nombre_completo ASC";

$result = $mysqli->query($sql);

$page_title = "Gestión de Conductores";
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-users me-2"></i>Gestión de Conductores</h2>
                    <p class="text-muted mb-0">Administración de conductores del parque automotor</p>
                </div>
                <div>
                    <a href="create.php" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Nuevo Conductor
                    </a>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="filtro_estado" class="form-label">Estado</label>
                            <select class="form-select" id="filtro_estado">
                                <option value="">Todos los estados</option>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                                <option value="suspendido">Suspendido</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filtro_licencia" class="form-label">Estado Licencia</label>
                            <select class="form-select" id="filtro_licencia">
                                <option value="">Todas las licencias</option>
                                <option value="vigente">Vigente</option>
                                <option value="proxima_vencer">Próxima a vencer</option>
                                <option value="vencida">Vencida</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filtro_vehiculo" class="form-label">Con Vehículo</label>
                            <select class="form-select" id="filtro_vehiculo">
                                <option value="">Todos</option>
                                <option value="con_vehiculo">Con vehículo asignado</option>
                                <option value="sin_vehiculo">Sin vehículo</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="buscar" class="form-label">Buscar</label>
                            <input type="text" class="form-control" id="buscar" placeholder="Nombre, cédula, placa...">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de conductores -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="conductoresTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>Conductor</th>
                                    <th>Cédula</th>
                                    <th>Contacto</th>
                                    <th>Licencia</th>
                                    <th>Vehículo Asignado</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($conductor = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($conductor['nombre_completo']); ?></strong>
                                                <?php if ($conductor['ciudad']): ?>
                                                    <br><small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($conductor['ciudad']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <?php echo htmlspecialchars($conductor['cedula']); ?>
                                                <?php if ($conductor['expedida_en']): ?>
                                                    <br><small class="text-muted">Exp: <?php echo htmlspecialchars($conductor['expedida_en']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <?php if ($conductor['celular']): ?>
                                                    <i class="fas fa-mobile-alt me-1"></i><?php echo htmlspecialchars($conductor['celular']); ?><br>
                                                <?php endif; ?>
                                                <?php if ($conductor['telefono']): ?>
                                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($conductor['telefono']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <?php if ($conductor['licencia_numero']): ?>
                                                    <strong><?php echo htmlspecialchars($conductor['licencia_numero']); ?></strong>
                                                    <?php if ($conductor['categoria_licencia']): ?>
                                                        <br><span class="badge bg-info"><?php echo htmlspecialchars($conductor['categoria_licencia']); ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($conductor['licencia_vence']): ?>
                                                        <br>
                                                        <span class="badge bg-<?php 
                                                            echo $conductor['estado_licencia'] === 'vencida' ? 'danger' : 
                                                                 ($conductor['estado_licencia'] === 'proxima_vencer' ? 'warning' : 'success'); 
                                                        ?>">
                                                            Vence: <?php echo date('d/m/Y', strtotime($conductor['licencia_vence'])); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No registrada</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($conductor['placa']): ?>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($conductor['placa']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($conductor['marca'] . ' ' . $conductor['modelo']); ?></small>
                                                    <?php if ($conductor['fecha_relacionada_vehiculo']): ?>
                                                        <br><small class="text-muted">Desde: <?php echo date('d/m/Y', strtotime($conductor['fecha_relacionada_vehiculo'])); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">Sin vehículo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $conductor['estado'] === 'activo' ? 'success' : 
                                                     ($conductor['estado'] === 'suspendido' ? 'warning' : 'secondary'); 
                                            ?>">
                                                <?php echo ucfirst($conductor['estado']); ?>
                                            </span>
                                            <?php if ($conductor['inscripcion_vence']): ?>
                                                <br>
                                                <span class="badge bg-<?php 
                                                    echo $conductor['estado_inscripcion'] === 'vencida' ? 'danger' : 
                                                         ($conductor['estado_inscripcion'] === 'proxima_vencer' ? 'warning' : 'success'); 
                                                ?>">
                                                    Insc: <?php echo date('d/m/Y', strtotime($conductor['inscripcion_vence'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="view.php?id=<?php echo $conductor['id']; ?>" 
                                                   class="btn btn-outline-info" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?php echo $conductor['id']; ?>" 
                                                   class="btn btn-outline-primary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="manage_files.php?id=<?php echo $conductor['id']; ?>" 
                                                   class="btn btn-outline-secondary" title="Gestionar archivos">
                                                    <i class="fas fa-folder"></i>
                                                </a>
                                                <?php if ($conductor['estado'] === 'activo'): ?>
                                                <button type="button" class="btn btn-outline-warning" 
                                                        onclick="cambiarEstado(<?php echo $conductor['id']; ?>, 'suspendido')" 
                                                        title="Suspender">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                                <?php elseif ($conductor['estado'] === 'suspendido'): ?>
                                                <button type="button" class="btn btn-outline-success" 
                                                        onclick="cambiarEstado(<?php echo $conductor['id']; ?>, 'activo')" 
                                                        title="Activar">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-users fa-3x mb-3"></i>
                                            <p>No hay conductores registrados</p>
                                            <a href="create.php" class="btn btn-primary">Registrar primer conductor</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Inicializar DataTable
    var table = $('#conductoresTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        responsive: true,
        order: [[0, 'asc']],
        columnDefs: [
            { orderable: false, targets: -1 }
        ]
    });

    // Filtros personalizados
    $('#filtro_estado').on('change', function() {
        table.column(5).search(this.value).draw();
    });

    $('#filtro_licencia').on('change', function() {
        var searchTerm = this.value;
        if (searchTerm === 'vigente') {
            table.column(3).search('Vence:.*bg-success', true, false).draw();
        } else if (searchTerm === 'proxima_vencer') {
            table.column(3).search('bg-warning', true, false).draw();
        } else if (searchTerm === 'vencida') {
            table.column(3).search('bg-danger', true, false).draw();
        } else {
            table.column(3).search('').draw();
        }
    });

    $('#filtro_vehiculo').on('change', function() {
        var searchTerm = this.value;
        if (searchTerm === 'con_vehiculo') {
            table.column(4).search('^(?!.*Sin vehículo)', true, false).draw();
        } else if (searchTerm === 'sin_vehiculo') {
            table.column(4).search('Sin vehículo').draw();
        } else {
            table.column(4).search('').draw();
        }
    });

    $('#buscar').on('keyup', function() {
        table.search(this.value).draw();
    });
});

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

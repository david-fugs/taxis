<?php
require_once '../../config.php';
requireLogin();

$page_title = 'Generar Tarjetas de Control';

// Obtener conductores activos con sus fotos
$conductores_query = "
    SELECT 
        c.*,
        v.placa, v.marca, v.modelo,
        (SELECT nombre_archivo FROM conductores_archivos 
         WHERE conductor_id = c.id AND tipo_archivo = 'Foto del Conductor' 
         ORDER BY fecha_subida DESC LIMIT 1) as foto_conductor
    FROM conductores c
    LEFT JOIN parque_automotor v ON c.vehiculo_id = v.id
    WHERE c.estado = 'activo'
    ORDER BY c.nombre_completo
";

$conductores = $mysqli->query($conductores_query);
if (!$conductores) {
    // Mostrar error de consulta para facilitar depuración
    die('<div class="alert alert-danger">Error en la consulta: ' . htmlspecialchars($mysqli->error) . '</div>');
}

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-id-card me-2"></i>Generar Tarjetas de Control</h2>
                    <p class="text-muted mb-0">Seleccione un conductor para generar su tarjeta de control</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>Conductores Disponibles
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="conductoresTable">
                            <thead>
                                <tr>
                                    <th>Foto</th>
                                    <th>Conductor</th>
                                    <th>Cédula</th>
                                    <th>Teléfono</th>
                                    <th>Vehículo Asignado</th>
                                    <th>Licencia Vence</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($conductor = $conductores->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if ($conductor['foto_conductor']): ?>
                                            <img src="<?php echo BASE_URL; ?>uploads/documentos/<?php echo htmlspecialchars($conductor['foto_conductor']); ?>" 
                                                 alt="Foto" class="img-thumbnail" style="width: 40px; height: 40px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-secondary rounded d-flex align-items-center justify-content-center" 
                                                 style="width: 40px; height: 40px;">
                                                <i class="fas fa-user text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($conductor['nombre_completo']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($conductor['cedula']); ?></td>
                                    <td><?php echo htmlspecialchars($conductor['telefono'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php if ($conductor['placa']): ?>
                                            <span class="badge bg-primary">
                                                <?php echo htmlspecialchars($conductor['placa']); ?> - 
                                                <?php echo htmlspecialchars($conductor['marca'] . ' ' . $conductor['modelo']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Sin vehículo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($conductor['licencia_vence']): ?>
                                            <?php 
                                            $fecha_vence = new DateTime($conductor['licencia_vence']);
                                            $hoy = new DateTime();
                                            $vigente = $fecha_vence >= $hoy;
                                            ?>
                                            <span class="badge <?php echo $vigente ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $fecha_vence->format('d/m/Y'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">No registrada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($conductor['foto_conductor']): ?>
                                            <a href="generate.php?conductor_id=<?php echo $conductor['id']; ?>" 
                                               class="btn btn-primary btn-sm" target="_blank">
                                                <i class="fas fa-print me-1"></i>Generar Tarjeta
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" disabled title="Sin foto del conductor">
                                                <i class="fas fa-exclamation-triangle me-1"></i>Sin Foto
                                            </button>
                                        <?php endif; ?>
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
$(document).ready(function() {
    $('#conductoresTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
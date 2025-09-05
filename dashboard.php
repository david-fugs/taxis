<?php
require_once 'config.php';
requireLogin();

$page_title = 'Dashboard';

// Obtener estadísticas
$stats = [];

// Total de asociados activos
$result = $mysqli->query("SELECT COUNT(*) as total FROM asociados WHERE estado = 'activo'");
$stats['asociados_activos'] = $result->fetch_assoc()['total'];

// Total de asociados
$result = $mysqli->query("SELECT COUNT(*) as total FROM asociados");
$stats['total_asociados'] = $result->fetch_assoc()['total'];

// Asociados registrados este mes
$result = $mysqli->query("SELECT COUNT(*) as total FROM asociados WHERE MONTH(fecha_creacion) = MONTH(CURRENT_DATE()) AND YEAR(fecha_creacion) = YEAR(CURRENT_DATE())");
$stats['asociados_mes'] = $result->fetch_assoc()['total'];

// Total de archivos subidos
$result = $mysqli->query("SELECT COUNT(*) as total FROM asociados_archivos");
$stats['total_archivos'] = $result->fetch_assoc()['total'];

// Estadísticas del parque automotor
$result = $mysqli->query("SELECT COUNT(*) as total FROM parque_automotor WHERE estado = 'activo'");
$stats['vehiculos_activos'] = $result->fetch_assoc()['total'];

$result = $mysqli->query("SELECT COUNT(*) as total FROM parque_automotor");
$stats['total_vehiculos'] = $result->fetch_assoc()['total'];

$result = $mysqli->query("SELECT COUNT(*) as total FROM parque_automotor_archivos");
$stats['total_archivos_vehiculos'] = $result->fetch_assoc()['total'];

// Estadísticas de conductores
$result = $mysqli->query("SELECT COUNT(*) as total FROM conductores WHERE estado = 'activo'");
$stats['conductores_activos'] = $result->fetch_assoc()['total'];

$result = $mysqli->query("SELECT COUNT(*) as total FROM conductores");
$stats['total_conductores'] = $result->fetch_assoc()['total'];

$result = $mysqli->query("SELECT COUNT(*) as total FROM conductores_archivos");
$stats['total_archivos_conductores'] = $result->fetch_assoc()['total'];

// Últimos asociados registrados
$ultimos_asociados = $mysqli->query("
    SELECT id, cedula, nombres, apellidos, fecha_creacion, estado 
    FROM asociados 
    ORDER BY fecha_creacion DESC 
    LIMIT 5
");

include 'includes/header.php';
?>

<div class="container-fluid">
    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card">
                <div class="icon primary">
                    <i class="fas fa-users"></i>
                </div>
                <h3><?php echo number_format($stats['asociados_activos']); ?></h3>
                <p>Asociados Activos</p>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card">
                <div class="icon success">
                    <i class="fas fa-car"></i>
                </div>
                <h3><?php echo number_format($stats['vehiculos_activos']); ?></h3>
                <p>Vehículos Activos</p>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card">
                <div class="icon info">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h3><?php echo number_format($stats['conductores_activos']); ?></h3>
                <p>Conductores Activos</p>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card">
                <div class="icon warning">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <h3><?php echo number_format($stats['asociados_mes']); ?></h3>
                <p>Registrados este Mes</p>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stats-card">
                <div class="icon danger">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3><?php echo number_format($stats['total_archivos'] + $stats['total_archivos_vehiculos'] + $stats['total_archivos_conductores']); ?></h3>
                <p>Archivos Subidos</p>
            </div>
        </div>
    </div>

    <!-- Estadísticas adicionales -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-primary">
                        <i class="fas fa-user-check me-2"></i><?php echo number_format($stats['total_asociados']); ?>
                    </h5>
                    <p class="card-text">Total Asociados</p>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success">
                        <i class="fas fa-car-side me-2"></i><?php echo number_format($stats['total_vehiculos']); ?>
                    </h5>
                    <p class="card-text">Total Vehículos</p>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-warning">
                        <i class="fas fa-users-cog me-2"></i><?php echo number_format($stats['total_conductores']); ?>
                    </h5>
                    <p class="card-text">Total Conductores</p>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-info">
                        <i class="fas fa-file-upload me-2"></i><?php echo number_format($stats['total_archivos']); ?>
                    </h5>
                    <p class="card-text">Archivos Asociados</p>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-secondary">
                        <i class="fas fa-folder me-2"></i><?php echo number_format($stats['total_archivos_vehiculos']); ?>
                    </h5>
                    <p class="card-text">Archivos Vehículos</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Acciones rápidas -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Acciones Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="modules/asociados/create.php" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-user-plus me-2"></i>
                                <div>
                                    <strong>Nuevo Asociado</strong>
                                    <br><small>Registrar un nuevo asociado</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="modules/parque_automotor/create.php" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-car-side me-2"></i>
                                <div>
                                    <strong>Nuevo Vehículo</strong>
                                    <br><small>Registrar un nuevo vehículo</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="modules/asociados/index.php" class="btn btn-info btn-lg w-100">
                                <i class="fas fa-list me-2"></i>
                                <div>
                                    <strong>Ver Asociados</strong>
                                    <br><small>Listar todos los asociados</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="modules/parque_automotor/index.php" class="btn btn-warning btn-lg w-100">
                                <i class="fas fa-car me-2"></i>
                                <div>
                                    <strong>Ver Vehículos</strong>
                                    <br><small>Listar parque automotor</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="modules/conductores/index.php" class="btn btn-info btn-lg w-100">
                                <i class="fas fa-users-cog me-2"></i>
                                <div>
                                    <strong>Ver Conductores</strong>
                                    <br><small>Listar conductores</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="modules/conductores/create.php" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-user-plus me-2"></i>
                                <div>
                                    <strong>Nuevo Conductor</strong>
                                    <br><small>Registrar conductor</small>
                                </div>
                            </a>
                        </div>
                        <?php if (isAdmin()): ?>
                        <div class="col-md-6 mb-3">
                            <a href="modules/auth/users.php" class="btn btn-secondary btn-lg w-100">
                                <i class="fas fa-user-cog me-2"></i>
                                <div>
                                    <strong>Gestionar Usuarios</strong>
                                    <br><small>Administrar usuarios del sistema</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="#" class="btn btn-info btn-lg w-100" onclick="showAlert('Función en desarrollo', 'info')">
                                <i class="fas fa-chart-bar me-2"></i>
                                <div>
                                    <strong>Reportes</strong>
                                    <br><small>Ver reportes y estadísticas</small>
                                </div>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Últimos asociados -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>Últimos Registros
                    </h5>
                    <a href="modules/asociados/index.php" class="btn btn-sm btn-outline-primary">
                        Ver todos
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($ultimos_asociados->num_rows > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php while ($asociado = $ultimos_asociados->fetch_assoc()): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="ms-2 me-auto">
                                <div class="fw-bold">
                                    <?php echo htmlspecialchars($asociado['nombres'] . ' ' . $asociado['apellidos']); ?>
                                </div>
                                <small class="text-muted">
                                    CC: <?php echo htmlspecialchars($asociado['cedula']); ?>
                                </small>
                                <br>
                                <small class="text-muted">
                                    <?php echo formatDate($asociado['fecha_creacion']); ?>
                                </small>
                            </div>
                            <span class="badge bg-<?php echo $asociado['estado'] === 'activo' ? 'success' : 'secondary'; ?> rounded-pill">
                                <?php echo ucfirst($asociado['estado']); ?>
                            </span>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No hay asociados registrados aún.</p>
                        <a href="modules/asociados/create.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Registrar Primer Asociado
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Información del sistema -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Información del Sistema
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h6><i class="fas fa-user me-2"></i>Usuario Actual</h6>
                            <p class="mb-0"><?php echo htmlspecialchars($_SESSION['nombre_completo']); ?></p>
                            <small class="text-muted">
                                Tipo: <?php echo ucfirst($_SESSION['user_type']); ?>
                            </small>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="fas fa-server me-2"></i>Versión del Sistema</h6>
                            <p class="mb-0"><?php echo APP_VERSION; ?></p>
                            <small class="text-muted">
                                <?php echo APP_NAME; ?>
                            </small>
                        </div>
                        <div class="col-md-4">
                            <h6><i class="fas fa-calendar me-2"></i>Fecha y Hora</h6>
                            <p class="mb-0" id="current-datetime"></p>
                            <small class="text-muted">Hora del servidor</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = '
<script>
    // Actualizar fecha y hora en tiempo real
    function updateDateTime() {
        const now = new Date();
        const options = {
            year: "numeric",
            month: "long",
            day: "numeric",
            hour: "2-digit",
            minute: "2-digit",
            second: "2-digit"
        };
        document.getElementById("current-datetime").textContent = now.toLocaleDateString("es-CO", options);
    }
    
    // Actualizar cada segundo
    updateDateTime();
    setInterval(updateDateTime, 1000);
    
    // Animación de contadores
    function animateCounters() {
        const counters = document.querySelectorAll(".stats-card h3");
        
        counters.forEach(counter => {
            const target = parseInt(counter.textContent.replace(/,/g, ""));
            const increment = target / 50;
            let current = 0;
            
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    counter.textContent = target.toLocaleString();
                    clearInterval(timer);
                } else {
                    counter.textContent = Math.ceil(current).toLocaleString();
                }
            }, 20);
        });
    }
    
    // Ejecutar animación al cargar
    setTimeout(animateCounters, 500);
    
    // Función global para abrir modal de creación
    window.openCreateModal = function() {
        window.location.href = "modules/asociados/index.php";
    };
</script>';

include 'includes/footer.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">                       
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
    
    <?php if (isset($extra_css)) echo $extra_css; ?>
</head>
<body>
    <?php if (isLoggedIn()): ?>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>dashboard.php">
                <i class="fas fa-taxi me-2"></i><?php echo APP_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>dashboard.php">
                            <i class="fas fa-home me-1"></i>Inicio
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users me-1"></i>Asociados
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/asociados/index.php">
                                <i class="fas fa-list me-1"></i>Listar Asociados
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="openCreateModal()">
                                <i class="fas fa-plus me-1"></i>Nuevo Asociado
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-car me-1"></i>Parque Automotor
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/parque_automotor/index.php">
                                <i class="fas fa-list me-1"></i>Listar Vehículos
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/parque_automotor/create.php">
                                <i class="fas fa-plus me-1"></i>Nuevo Vehículo
                            </a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users-cog me-1"></i>Conductores
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/conductores/index.php">
                                <i class="fas fa-list me-1"></i>Listar Conductores
                            </a></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/conductores/create.php">
                                <i class="fas fa-plus me-1"></i>Nuevo Conductor
                            </a></li>
                        </ul>
                    </li>
                       
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="sellarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-stamp me-1"></i>Sellar
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="sellarDropdown">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/sellar/create.php">
                                    <i class="fas fa-plus me-1"></i>Crear
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/sellar/index.php">
                                    <i class="fas fa-list me-1"></i>Listado
                                </a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="tarjetasDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-id-card me-1"></i>Tarjetas de Control
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="tarjetasDropdown">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/tarjetas_control/index.php">
                                    <i class="fas fa-print me-1"></i>Generar Tarjeta
                                </a></li>
                            </ul>
                        </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-cog me-1"></i>Administración
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/auth/users.php">
                                <i class="fas fa-user-cog me-1"></i>Usuarios
                            </a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i><?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/auth/profile.php">
                                <i class="fas fa-user-edit me-1"></i>Perfil
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>modules/auth/logout.php">
                                <i class="fas fa-sign-out-alt me-1"></i>Cerrar Sesión
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <!-- Main Content -->
    <main class="<?php echo isLoggedIn() ? 'main-container mt-4' : ''; ?>">

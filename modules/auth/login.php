<?php
require_once '../../config.php';

// Si ya está logueado, redirigir al dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = 'Por favor, complete todos los campos.';
    } else {
        // Verificar credenciales
        $stmt = $mysqli->prepare("SELECT id, username, password, email, nombre_completo, tipo_usuario FROM usuarios WHERE username = ? AND estado = 'activo'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                // Login exitoso
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['nombre_completo'] = $user['nombre_completo'];
                $_SESSION['user_type'] = $user['tipo_usuario'];
                
                // Actualizar último acceso
                $update_stmt = $mysqli->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                
                header('Location: ' . BASE_URL . 'dashboard.php');
                exit();
            } else {
                $error_message = 'Credenciales incorrectas.';
            }
        } else {
            $error_message = 'Usuario no encontrado o inactivo.';
        }
        $stmt->close();
    }
}

$page_title = 'Iniciar Sesión';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>assets/css/style.css" rel="stylesheet">
    
    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
        
        .login-row {
            min-height: 100vh;
        }
        
        .login-left {
            background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), 
                        url('https://images.unsplash.com/photo-1583311115002-6e90da904718?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }
        
        .login-right {
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }
        
        .welcome-content h1 {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        .welcome-content p {
            font-size: 1.2rem;
            margin-bottom: 0;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }
        
        .login-form {
            width: 100%;
            max-width: 400px;
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-logo i {
            font-size: 4rem;
            color: #f39c12;
            margin-bottom: 15px;
        }
        
        .login-logo h2 {
            color: #2c3e50;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .login-left {
                display: none;
            }
            
            .login-right {
                padding: 20px;
            }
            
            .welcome-content h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="row login-row g-0">
            <!-- Lado izquierdo con imagen -->
            <div class="col-lg-7 login-left">
                <div class="welcome-content">
                    <h1><i class="fas fa-taxi me-3"></i>Bienvenido</h1>
                    <p>Sistema de Gestión de Empresa de Taxis</p>
                    <p class="mt-3">Administra tus asociados, conductores y vehículos de manera eficiente</p>
                </div>
            </div>
            
            <!-- Lado derecho con formulario -->
            <div class="col-lg-5 login-right">
                <div class="login-form">
                    <div class="login-logo">
                        <i class="fas fa-taxi"></i>
                        <h2><?php echo APP_NAME; ?></h2>
                        <p class="text-muted">Iniciar Sesión</p>
                    </div>
                    
                    <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="mb-4">
                            <label for="username" class="form-label">
                                <i class="fas fa-user me-2"></i>Usuario
                            </label>
                            <input type="text" class="form-control form-control-lg" id="username" name="username" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                   required>
                            <div class="invalid-feedback">
                                Por favor, ingrese su nombre de usuario.
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>Contraseña
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <div class="invalid-feedback">
                                    Por favor, ingrese su contraseña.
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center">
                        <small class="text-muted">
                            ¿Olvidaste tu contraseña? <a href="#" class="text-decoration-none" style="color: #f39c12;">Recuperar</a>
                        </small>
                    </div>
                    
                    <div class="text-center mt-4 p-3 bg-light rounded">
                        <small class="text-muted">
                            <strong>Demo:</strong><br>
                            Usuario: <strong>admin</strong><br>
                            Contraseña: <strong>password</strong>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Mostrar/ocultar contraseña
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Validación del formulario
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html>

<?php
require_once '../../config.php';
requireLogin();
requireAdmin();

$page_title = 'Gestión de Usuarios';
$success_message = '';
$error_message = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $username = cleanInput($_POST['username']);
            $email = cleanInput($_POST['email']);
            $nombre_completo = cleanInput($_POST['nombre_completo']);
            $password = $_POST['password'];
            $tipo_usuario = $_POST['tipo_usuario'];
            
            if (empty($username) || empty($email) || empty($nombre_completo) || empty($password)) {
                $error_message = 'Todos los campos son obligatorios.';
            } elseif (strlen($password) < 6) {
                $error_message = 'La contraseña debe tener al menos 6 caracteres.';
            } else {
                // Verificar si el usuario o email ya existen
                $check_stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE username = ? OR email = ?");
                $check_stmt->bind_param("ss", $username, $email);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    $error_message = 'El usuario o email ya existe.';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $mysqli->prepare("INSERT INTO usuarios (username, password, email, nombre_completo, tipo_usuario) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssss", $username, $hashed_password, $email, $nombre_completo, $tipo_usuario);
                    
                    if ($stmt->execute()) {
                        $success_message = 'Usuario creado exitosamente.';
                    } else {
                        $error_message = 'Error al crear el usuario.';
                    }
                    $stmt->close();
                }
                $check_stmt->close();
            }
            break;
            
        case 'toggle_status':
            $user_id = (int)$_POST['user_id'];
            $new_status = $_POST['status'] === 'activo' ? 'inactivo' : 'activo';
            
            $stmt = $mysqli->prepare("UPDATE usuarios SET estado = ? WHERE id = ? AND id != ?");
            $stmt->bind_param("sii", $new_status, $user_id, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $success_message = 'Estado del usuario actualizado.';
            } else {
                $error_message = 'Error al actualizar el estado.';
            }
            $stmt->close();
            break;
            
        case 'delete':
            $user_id = (int)$_POST['user_id'];
            
            if ($user_id != $_SESSION['user_id']) {
                $stmt = $mysqli->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                
                if ($stmt->execute()) {
                    $success_message = 'Usuario eliminado exitosamente.';
                } else {
                    $error_message = 'Error al eliminar el usuario.';
                }
                $stmt->close();
            } else {
                $error_message = 'No puedes eliminar tu propio usuario.';
            }
            break;
            
        case 'edit':
            $user_id = (int)$_POST['user_id'];
            $username = cleanInput($_POST['username']);
            $email = cleanInput($_POST['email']);
            $nombre_completo = cleanInput($_POST['nombre_completo']);
            $tipo_usuario = $_POST['tipo_usuario'];
            $password = $_POST['password'];
            
            if (empty($username) || empty($email) || empty($nombre_completo)) {
                $error_message = 'Todos los campos son obligatorios.';
            } else {
                // Verificar si el usuario o email ya existen (excluyendo el actual)
                $check_stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE (username = ? OR email = ?) AND id != ?");
                $check_stmt->bind_param("ssi", $username, $email, $user_id);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    $error_message = 'El usuario o email ya existe.';
                } else {
                    if (!empty($password)) {
                        // Actualizar con nueva contraseña
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $mysqli->prepare("UPDATE usuarios SET username = ?, password = ?, email = ?, nombre_completo = ?, tipo_usuario = ? WHERE id = ?");
                        $stmt->bind_param("sssssi", $username, $hashed_password, $email, $nombre_completo, $tipo_usuario, $user_id);
                    } else {
                        // Actualizar sin cambiar contraseña
                        $stmt = $mysqli->prepare("UPDATE usuarios SET username = ?, email = ?, nombre_completo = ?, tipo_usuario = ? WHERE id = ?");
                        $stmt->bind_param("ssssi", $username, $email, $nombre_completo, $tipo_usuario, $user_id);
                    }
                    
                    if ($stmt->execute()) {
                        $success_message = 'Usuario actualizado exitosamente.';
                    } else {
                        $error_message = 'Error al actualizar el usuario.';
                    }
                    $stmt->close();
                }
                $check_stmt->close();
            }
            break;
    }
}

// Obtener lista de usuarios
$users_query = "SELECT * FROM usuarios ORDER BY fecha_creacion DESC";
$users_result = $mysqli->query($users_query);

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-users me-2"></i>Gestión de Usuarios
                    </h4>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <i class="fas fa-plus me-2"></i>Nuevo Usuario
                    </button>
                </div>
                <div class="card-body">
                    <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-striped data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>Nombre Completo</th>
                                    <th>Email</th>
                                    <th>Tipo</th>
                                    <th>Estado</th>
                                    <th>Último Acceso</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $users_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['nombre_completo']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['tipo_usuario'] === 'admin' ? 'danger' : 'primary'; ?>">
                                            <?php echo ucfirst($user['tipo_usuario']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['estado'] === 'activo' ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($user['estado']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo $user['ultimo_acceso'] ? formatDate($user['ultimo_acceso']) : 'Nunca'; ?>
                                    </td>
                                    <td>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-warning" 
                                                    onclick="editUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo htmlspecialchars($user['email']); ?>', '<?php echo htmlspecialchars($user['nombre_completo']); ?>', '<?php echo $user['tipo_usuario']; ?>')"
                                                    title="Editar usuario">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $user['estado']; ?>">
                                                <button type="submit" class="btn btn-sm btn-<?php echo $user['estado'] === 'activo' ? 'secondary' : 'success'; ?>" 
                                                        title="<?php echo $user['estado'] === 'activo' ? 'Desactivar' : 'Activar'; ?>">
                                                    <i class="fas fa-<?php echo $user['estado'] === 'activo' ? 'pause' : 'play'; ?>"></i>
                                                </button>
                                            </form>
                                            
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')"
                                                    title="Eliminar usuario">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        <?php else: ?>
                                        <span class="badge bg-info">Usuario actual</span>
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

<!-- Modal para crear usuario -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>Crear Nuevo Usuario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Usuario *</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                        <div class="invalid-feedback">
                            Por favor, ingrese un nombre de usuario.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="invalid-feedback">
                            Por favor, ingrese un email válido.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nombre_completo" class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" required>
                        <div class="invalid-feedback">
                            Por favor, ingrese el nombre completo.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña *</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                        <div class="invalid-feedback">
                            La contraseña debe tener al menos 6 caracteres.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tipo_usuario" class="form-label">Tipo de Usuario *</label>
                        <select class="form-select" id="tipo_usuario" name="tipo_usuario" required>
                            <option value="">Seleccionar...</option>
                            <option value="usuario">Usuario</option>
                            <option value="admin">Administrador</option>
                        </select>
                        <div class="invalid-feedback">
                            Por favor, seleccione un tipo de usuario.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Crear Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para editar usuario -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-edit me-2"></i>Editar Usuario
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="needs-validation" novalidate>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="mb-3">
                        <label for="edit_username" class="form-label">Usuario *</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                        <div class="invalid-feedback">
                            Por favor, ingrese un nombre de usuario.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                        <div class="invalid-feedback">
                            Por favor, ingrese un email válido.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_nombre_completo" class="form-label">Nombre Completo *</label>
                        <input type="text" class="form-control" id="edit_nombre_completo" name="nombre_completo" required>
                        <div class="invalid-feedback">
                            Por favor, ingrese el nombre completo.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" id="edit_password" name="password" minlength="6">
                        <small class="form-text text-muted">Dejar en blanco para mantener la contraseña actual</small>
                        <div class="invalid-feedback">
                            La contraseña debe tener al menos 6 caracteres.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_tipo_usuario" class="form-label">Tipo de Usuario *</label>
                        <select class="form-select" id="edit_tipo_usuario" name="tipo_usuario" required>
                            <option value="">Seleccionar...</option>
                            <option value="usuario">Usuario</option>
                            <option value="admin">Administrador</option>
                        </select>
                        <div class="invalid-feedback">
                            Por favor, seleccione un tipo de usuario.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save me-2"></i>Actualizar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para confirmar eliminación -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar el usuario <strong id="delete_username"></strong>?</p>
                <p class="text-danger"><strong>Esta acción no se puede deshacer.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i>Eliminar Usuario
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editUser(id, username, email, nombre_completo, tipo_usuario) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_nombre_completo').value = nombre_completo;
    document.getElementById('edit_tipo_usuario').value = tipo_usuario;
    document.getElementById('edit_password').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}

function deleteUser(id, username) {
    document.getElementById('delete_user_id').value = id;
    document.getElementById('delete_username').textContent = username;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    modal.show();
}
</script>

<?php include '../../includes/footer.php'; ?>

<?php
require_once '../../config.php';
requireLogin();

$page_title = 'Nuevo Vehículo Rápido - Parque Automotor';
$success_message = '';
$error_message = '';

// Obtener lista de asociados para el select
$asociados_query = "SELECT id, cedula, nombres, apellidos FROM asociados WHERE estado = 'activo' ORDER BY nombres, apellidos";
$asociados_result = $mysqli->query($asociados_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recopilar datos básicos del formulario
    $data = [
        'placa' => strtoupper(cleanInput($_POST['placa'])),
        'marca' => cleanInput($_POST['marca']),
        'modelo' => cleanInput($_POST['modelo']),
        'empresa' => cleanInput($_POST['empresa']),
        'asociado_id' => !empty($_POST['asociado_id']) ? (int)$_POST['asociado_id'] : null,
        'tipo_combustible' => $_POST['tipo_combustible'] ?? 'gasolina',
        'estado' => 'activo'
    ];
    
    // Validaciones básicas
    if (empty($data['placa']) || empty($data['marca']) || empty($data['modelo'])) {
        $error_message = 'La placa, marca y modelo son obligatorios.';
    } else {
        // Verificar si la placa ya existe
        $check_stmt = $mysqli->prepare("SELECT id FROM parque_automotor WHERE placa = ?");
        $check_stmt->bind_param("s", $data['placa']);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            $error_message = 'Ya existe un vehículo con esta placa.';
        } else {
            // Insertar vehículo con datos básicos
            $sql = "INSERT INTO parque_automotor (
                placa, marca, modelo, empresa, asociado_id, tipo_combustible, estado, usuario_creacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("sssssssi",
                $data['placa'], $data['marca'], $data['modelo'], $data['empresa'],
                $data['asociado_id'], $data['tipo_combustible'], $data['estado'], $_SESSION['user_id']
            );
            
            if ($stmt->execute()) {
                $vehiculo_id = $mysqli->insert_id;
                
                $_SESSION['message'] = 'Vehículo creado exitosamente. Puede completar la información adicional desde la edición.';
                $_SESSION['message_type'] = 'success';
                header('Location: edit.php?id=' . $vehiculo_id);
                exit;
            } else {
                $error_message = 'Error al crear el vehículo: ' . $mysqli->error;
            }
        }
        $check_stmt->close();
    }
}

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-car-side me-2"></i>Registro Rápido de Vehículo</h2>
                <div>
                    <a href="create.php" class="btn btn-info me-2">
                        <i class="fas fa-plus-circle me-1"></i>Registro Completo
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Volver a la Lista
                    </a>
                </div>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tachometer-alt me-2"></i>Información Básica del Vehículo
                    </h5>
                    <small class="text-muted">Complete los datos básicos. Podrá agregar información adicional después del registro.</small>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="placa" class="form-label">Placa <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="placa" name="placa" 
                                           value="<?php echo isset($_POST['placa']) ? htmlspecialchars($_POST['placa']) : ''; ?>" 
                                           required maxlength="10" style="text-transform: uppercase;"
                                           placeholder="Ej: ABC123">
                                    <div class="invalid-feedback">La placa es obligatoria.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="marca" class="form-label">Marca <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="marca" name="marca" 
                                           value="<?php echo isset($_POST['marca']) ? htmlspecialchars($_POST['marca']) : ''; ?>" 
                                           required maxlength="50"
                                           placeholder="Ej: Toyota, Chevrolet, Hyundai">
                                    <div class="invalid-feedback">La marca es obligatoria.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="modelo" class="form-label">Modelo <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" id="modelo" name="modelo" 
                                           value="<?php echo isset($_POST['modelo']) ? htmlspecialchars($_POST['modelo']) : ''; ?>" 
                                           required maxlength="50"
                                           placeholder="Ej: Corolla, Aveo, Accent">
                                    <div class="invalid-feedback">El modelo es obligatorio.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tipo_combustible" class="form-label">Tipo de Combustible</label>
                                    <select class="form-select form-select-lg" id="tipo_combustible" name="tipo_combustible">
                                        <option value="gasolina" <?php echo (isset($_POST['tipo_combustible']) && $_POST['tipo_combustible'] === 'gasolina') ? 'selected' : 'selected'; ?>>Gasolina</option>
                                        <option value="diesel" <?php echo (isset($_POST['tipo_combustible']) && $_POST['tipo_combustible'] === 'diesel') ? 'selected' : ''; ?>>Diesel</option>
                                        <option value="gas" <?php echo (isset($_POST['tipo_combustible']) && $_POST['tipo_combustible'] === 'gas') ? 'selected' : ''; ?>>Gas</option>
                                        <option value="electrico" <?php echo (isset($_POST['tipo_combustible']) && $_POST['tipo_combustible'] === 'electrico') ? 'selected' : ''; ?>>Eléctrico</option>
                                        <option value="hibrido" <?php echo (isset($_POST['tipo_combustible']) && $_POST['tipo_combustible'] === 'hibrido') ? 'selected' : ''; ?>>Híbrido</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="empresa" class="form-label">Empresa</label>
                                    <input type="text" class="form-control" id="empresa" name="empresa" 
                                           value="<?php echo isset($_POST['empresa']) ? htmlspecialchars($_POST['empresa']) : ''; ?>" 
                                           maxlength="100"
                                           placeholder="Empresa o cooperativa">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="asociado_id" class="form-label">Asociado (Opcional)</label>
                                    <select class="form-select" id="asociado_id" name="asociado_id">
                                        <option value="">Seleccionar asociado (opcional)</option>
                                        <?php while ($asociado = $asociados_result->fetch_assoc()): ?>
                                        <option value="<?php echo $asociado['id']; ?>" 
                                                <?php echo (isset($_POST['asociado_id']) && $_POST['asociado_id'] == $asociado['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($asociado['cedula'] . ' - ' . $asociado['nombres'] . ' ' . $asociado['apellidos']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-1"></i>Guardar y Continuar Editando
                                    </button>
                                    <a href="create.php" class="btn btn-outline-info">
                                        <i class="fas fa-plus-circle me-1"></i>Registro Completo
                                    </a>
                                    <a href="index.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Cancelar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Información adicional -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle me-2"></i>Información</h5>
                        <p class="mb-0">
                            Este es un registro rápido que permite crear un vehículo con la información básica. 
                            Después del registro, será redirigido a la página de edición donde podrá completar 
                            información adicional como SOAT, tarjeta de operación, revisión preventiva, etc.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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

// Convertir placa a mayúsculas
document.getElementById('placa').addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});

// Capitalizar primera letra de marca y modelo
document.getElementById('marca').addEventListener('input', function() {
    this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
});

document.getElementById('modelo').addEventListener('input', function() {
    this.value = this.value.charAt(0).toUpperCase() + this.value.slice(1);
});
</script>

<?php include '../../includes/footer.php'; ?>

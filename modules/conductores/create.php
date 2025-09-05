<?php
require_once '../../config.php';
require_once '../../conexion.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'modules/auth/login.php');
    exit();
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Obtener datos del formulario
        $nombre_completo = $_POST['nombre_completo'] ?? '';
        $cedula = $_POST['cedula'] ?? '';
        $expedida_en = $_POST['expedida_en'] ?? '';
        $telefono = $_POST['telefono'] ?? '';
        $celular = $_POST['celular'] ?? '';
        $direccion = $_POST['direccion'] ?? '';
        $ciudad = $_POST['ciudad'] ?? '';
        $lugar_nacimiento = $_POST['lugar_nacimiento'] ?? '';
        $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
        $edad = $_POST['edad'] ?? null;
        $estado_civil = $_POST['estado_civil'] ?? 'soltero';
        $rh = $_POST['rh'] ?? '';
        $email = $_POST['email'] ?? '';
        $licencia_numero = $_POST['licencia_numero'] ?? '';
        $categoria_licencia = $_POST['categoria_licencia'] ?? '';
        $licencia_expedida = $_POST['licencia_expedida'] ?? null;
        $licencia_vence = $_POST['licencia_vence'] ?? null;
        $emergencia_contacto = $_POST['emergencia_contacto'] ?? '';
        $emergencia_telefono = $_POST['emergencia_telefono'] ?? '';
        $experiencia = $_POST['experiencia'] ?? '';
        $arp = $_POST['arp'] ?? '';
        $salud = $_POST['salud'] ?? '';
        $pension = $_POST['pension'] ?? '';
        $vehiculo_id = $_POST['vehiculo_id'] ?? null;
        $fecha_relacionada_vehiculo = $_POST['fecha_relacionada_vehiculo'] ?? null;
        $beneficiario_funebre = $_POST['beneficiario_funebre'] ?? '';
        $nombre_padres = $_POST['nombre_padres'] ?? '';
        $hijos_menores = $_POST['hijos_menores'] ?? '';
        $fecha_ingreso = $_POST['fecha_ingreso'] ?? null;
        $inscripcion_vence = $_POST['inscripcion_vence'] ?? null;

        // Validaciones básicas
        if (empty($nombre_completo) || empty($cedula)) {
            throw new Exception('El nombre completo y la cédula son obligatorios');
        }

        // Convertir fechas vacías a NULL
        $fecha_nacimiento = empty($fecha_nacimiento) ? null : $fecha_nacimiento;
        $licencia_expedida = empty($licencia_expedida) ? null : $licencia_expedida;
        $licencia_vence = empty($licencia_vence) ? null : $licencia_vence;
        $fecha_relacionada_vehiculo = empty($fecha_relacionada_vehiculo) ? null : $fecha_relacionada_vehiculo;
        $fecha_ingreso = empty($fecha_ingreso) ? null : $fecha_ingreso;
        $inscripcion_vence = empty($inscripcion_vence) ? null : $inscripcion_vence;
        $vehiculo_id = empty($vehiculo_id) ? null : $vehiculo_id;
        $edad = empty($edad) ? null : $edad;

        // Escapar datos para evitar SQL injection
        $nombre_completo = mysqli_real_escape_string($mysqli, $nombre_completo);
        $cedula = mysqli_real_escape_string($mysqli, $cedula);
        $expedida_en = mysqli_real_escape_string($mysqli, $expedida_en);
        $telefono = mysqli_real_escape_string($mysqli, $telefono);
        $celular = mysqli_real_escape_string($mysqli, $celular);
        $direccion = mysqli_real_escape_string($mysqli, $direccion);
        $ciudad = mysqli_real_escape_string($mysqli, $ciudad);
        $lugar_nacimiento = mysqli_real_escape_string($mysqli, $lugar_nacimiento);
        $estado_civil = mysqli_real_escape_string($mysqli, $estado_civil);
        $rh = mysqli_real_escape_string($mysqli, $rh);
        $email = mysqli_real_escape_string($mysqli, $email);
        $licencia_numero = mysqli_real_escape_string($mysqli, $licencia_numero);
        $categoria_licencia = mysqli_real_escape_string($mysqli, $categoria_licencia);
        $emergencia_contacto = mysqli_real_escape_string($mysqli, $emergencia_contacto);
        $emergencia_telefono = mysqli_real_escape_string($mysqli, $emergencia_telefono);
        $experiencia = mysqli_real_escape_string($mysqli, $experiencia);
        $arp = mysqli_real_escape_string($mysqli, $arp);
        $salud = mysqli_real_escape_string($mysqli, $salud);
        $pension = mysqli_real_escape_string($mysqli, $pension);
        $beneficiario_funebre = mysqli_real_escape_string($mysqli, $beneficiario_funebre);
        $nombre_padres = mysqli_real_escape_string($mysqli, $nombre_padres);
        $hijos_menores = mysqli_real_escape_string($mysqli, $hijos_menores);

        // Formatear fechas para MySQL o NULL
        $fecha_nacimiento_sql = $fecha_nacimiento ? "'$fecha_nacimiento'" : 'NULL';
        $licencia_expedida_sql = $licencia_expedida ? "'$licencia_expedida'" : 'NULL';
        $licencia_vence_sql = $licencia_vence ? "'$licencia_vence'" : 'NULL';
        $fecha_relacionada_vehiculo_sql = $fecha_relacionada_vehiculo ? "'$fecha_relacionada_vehiculo'" : 'NULL';
        $fecha_ingreso_sql = $fecha_ingreso ? "'$fecha_ingreso'" : 'NULL';
        $inscripcion_vence_sql = $inscripcion_vence ? "'$inscripcion_vence'" : 'NULL';
        $vehiculo_id_sql = $vehiculo_id ? $vehiculo_id : 'NULL';
        $edad_sql = $edad ? $edad : 'NULL';

        // Insertar en la base de datos
        $sql = "INSERT INTO conductores (
                    nombre_completo, cedula, expedida_en, telefono, celular, direccion, ciudad,
                    lugar_nacimiento, fecha_nacimiento, edad, estado_civil, rh, email,
                    licencia_numero, categoria_licencia, licencia_expedida, licencia_vence,
                    emergencia_contacto, emergencia_telefono, experiencia, arp, salud, pension,
                    vehiculo_id, fecha_relacionada_vehiculo, beneficiario_funebre, nombre_padres,
                    hijos_menores, fecha_ingreso, inscripcion_vence, usuario_creacion
                ) VALUES (
                    '$nombre_completo', '$cedula', '$expedida_en', '$telefono', '$celular', '$direccion', '$ciudad',
                    '$lugar_nacimiento', $fecha_nacimiento_sql, $edad_sql, '$estado_civil', '$rh', '$email',
                    '$licencia_numero', '$categoria_licencia', $licencia_expedida_sql, $licencia_vence_sql,
                    '$emergencia_contacto', '$emergencia_telefono', '$experiencia', '$arp', '$salud', '$pension',
                    $vehiculo_id_sql, $fecha_relacionada_vehiculo_sql, '$beneficiario_funebre', '$nombre_padres',
                    '$hijos_menores', $fecha_ingreso_sql, $inscripcion_vence_sql, {$_SESSION['user_id']}
                )";

        if ($mysqli->query($sql)) {
            $conductor_id = mysqli_insert_id($mysqli);

            // Procesar archivos si se subieron
            if (!empty($_FILES['archivos']['name'][0])) {
                $upload_dir = '../../uploads/documentos/';

                // Crear directorio si no existe
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                for ($i = 0; $i < count($_FILES['archivos']['name']); $i++) {
                    if ($_FILES['archivos']['error'][$i] === UPLOAD_ERR_OK) {
                        $nombre_original = $_FILES['archivos']['name'][$i];
                        $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
                        $nombre_archivo = date('YmdHis') . '_' . uniqid() . '.' . $extension;
                        $ruta_completa = $upload_dir . $nombre_archivo;

                        if (move_uploaded_file($_FILES['archivos']['tmp_name'][$i], $ruta_completa)) {
                            $tipo_archivo = mysqli_real_escape_string($mysqli, $_POST['tipos_archivo'][$i] ?? 'general');
                            $descripcion = mysqli_real_escape_string($mysqli, $_POST['descripciones'][$i] ?? '');
                            $nombre_archivo_esc = mysqli_real_escape_string($mysqli, $nombre_archivo);
                            $nombre_original_esc = mysqli_real_escape_string($mysqli, $nombre_original);

                            $sql_archivo = "INSERT INTO conductores_archivos (conductor_id, nombre_archivo, nombre_original, tipo_archivo, descripcion, usuario_id) VALUES ($conductor_id, '$nombre_archivo_esc', '$nombre_original_esc', '$tipo_archivo', '$descripcion', {$_SESSION['user_id']})";
                            $mysqli->query($sql_archivo);
                        }
                    }
                }
            }

            $mensaje = "Conductor registrado exitosamente";
            $tipo_mensaje = "success";

            // Limpiar formulario
            $_POST = array();
        } else {
            throw new Exception('Error al registrar el conductor: ' . $mysqli->error);
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = "danger";
    }
}

// Obtener vehículos disponibles (sin conductor asignado o todos si estamos editando)
$sql_vehiculos = "SELECT pa.id, pa.placa, pa.marca, pa.modelo, pa.nib,
                         CASE WHEN c.id IS NOT NULL THEN 1 ELSE 0 END as tiene_conductor
                  FROM parque_automotor pa 
                  LEFT JOIN conductores c ON pa.id = c.vehiculo_id 
                  WHERE pa.estado = 'activo'
                  ORDER BY pa.placa ASC";
$vehiculos = $mysqli->query($sql_vehiculos);

$page_title = "Registrar Conductor";
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-user-plus me-2"></i>Registrar Nuevo Conductor</h2>
                    <p class="text-muted mb-0">Complete el formulario para registrar un nuevo conductor</p>
                </div>
                <div>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>

            <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?> alert-dismissible fade show" role="alert">
                    <?php echo $mensaje; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="conductorForm">
                <div class="row">
                    <!-- Información Personal -->
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Información Personal</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nombre_completo" class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="nombre_completo" name="nombre_completo"
                                                value="<?php echo htmlspecialchars($_POST['nombre_completo'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="cedula" class="form-label">Cédula <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="cedula" name="cedula"
                                                value="<?php echo htmlspecialchars($_POST['cedula'] ?? ''); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="expedida_en" class="form-label">Expedida en</label>
                                            <input type="text" class="form-control" id="expedida_en" name="expedida_en"
                                                value="<?php echo htmlspecialchars($_POST['expedida_en'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="telefono" class="form-label">Teléfono</label>
                                            <input type="text" class="form-control" id="telefono" name="telefono"
                                                value="<?php echo htmlspecialchars($_POST['telefono'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="celular" class="form-label">Celular</label>
                                            <input type="text" class="form-control" id="celular" name="celular"
                                                value="<?php echo htmlspecialchars($_POST['celular'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email"
                                                value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="ciudad" class="form-label">Ciudad</label>
                                            <input type="text" class="form-control" id="ciudad" name="ciudad"
                                                value="<?php echo htmlspecialchars($_POST['ciudad'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="direccion" class="form-label">Dirección</label>
                                            <textarea class="form-control" id="direccion" name="direccion" rows="2"><?php echo htmlspecialchars($_POST['direccion'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="lugar_nacimiento" class="form-label">Lugar de Nacimiento</label>
                                            <input type="text" class="form-control" id="lugar_nacimiento" name="lugar_nacimiento"
                                                value="<?php echo htmlspecialchars($_POST['lugar_nacimiento'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento"
                                                value="<?php echo htmlspecialchars($_POST['fecha_nacimiento'] ?? ''); ?>" onchange="calcularEdad()">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="edad" class="form-label">Edad</label>
                                            <input type="number" class="form-control" id="edad" name="edad" readonly
                                                value="<?php echo htmlspecialchars($_POST['edad'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="estado_civil" class="form-label">Estado Civil</label>
                                            <select class="form-select" id="estado_civil" name="estado_civil">
                                                <option value="soltero" <?php echo ($_POST['estado_civil'] ?? '') === 'soltero' ? 'selected' : ''; ?>>Soltero</option>
                                                <option value="casado" <?php echo ($_POST['estado_civil'] ?? '') === 'casado' ? 'selected' : ''; ?>>Casado</option>
                                                <option value="viudo" <?php echo ($_POST['estado_civil'] ?? '') === 'viudo' ? 'selected' : ''; ?>>Viudo</option>
                                                <option value="divorciado" <?php echo ($_POST['estado_civil'] ?? '') === 'divorciado' ? 'selected' : ''; ?>>Divorciado</option>
                                                <option value="union_libre" <?php echo ($_POST['estado_civil'] ?? '') === 'union_libre' ? 'selected' : ''; ?>>Unión Libre</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="rh" class="form-label">RH</label>
                                            <select class="form-select" id="rh" name="rh">
                                                <option value="">Seleccionar</option>
                                                <option value="O+" <?php echo ($_POST['rh'] ?? '') === 'O+' ? 'selected' : ''; ?>>O+</option>
                                                <option value="O-" <?php echo ($_POST['rh'] ?? '') === 'O-' ? 'selected' : ''; ?>>O-</option>
                                                <option value="A+" <?php echo ($_POST['rh'] ?? '') === 'A+' ? 'selected' : ''; ?>>A+</option>
                                                <option value="A-" <?php echo ($_POST['rh'] ?? '') === 'A-' ? 'selected' : ''; ?>>A-</option>
                                                <option value="B+" <?php echo ($_POST['rh'] ?? '') === 'B+' ? 'selected' : ''; ?>>B+</option>
                                                <option value="B-" <?php echo ($_POST['rh'] ?? '') === 'B-' ? 'selected' : ''; ?>>B-</option>
                                                <option value="AB+" <?php echo ($_POST['rh'] ?? '') === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                                <option value="AB-" <?php echo ($_POST['rh'] ?? '') === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de Licencia -->
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Información de Licencia</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="licencia_numero" class="form-label">Número de Licencia</label>
                                            <input type="text" class="form-control" id="licencia_numero" name="licencia_numero"
                                                value="<?php echo htmlspecialchars($_POST['licencia_numero'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="categoria_licencia" class="form-label">Categoría</label>
                                            <select class="form-select" id="categoria_licencia" name="categoria_licencia">
                                                <option value="">Seleccionar</option>
                                                <option value="A1" <?php echo ($_POST['categoria_licencia'] ?? '') === 'A1' ? 'selected' : ''; ?>>A1</option>
                                                <option value="A2" <?php echo ($_POST['categoria_licencia'] ?? '') === 'A2' ? 'selected' : ''; ?>>A2</option>
                                                <option value="B1" <?php echo ($_POST['categoria_licencia'] ?? '') === 'B1' ? 'selected' : ''; ?>>B1</option>
                                                <option value="B2" <?php echo ($_POST['categoria_licencia'] ?? '') === 'B2' ? 'selected' : ''; ?>>B2</option>
                                                <option value="B3" <?php echo ($_POST['categoria_licencia'] ?? '') === 'B3' ? 'selected' : ''; ?>>B3</option>
                                                <option value="C1" <?php echo ($_POST['categoria_licencia'] ?? '') === 'C1' ? 'selected' : ''; ?>>C1</option>
                                                <option value="C2" <?php echo ($_POST['categoria_licencia'] ?? '') === 'C2' ? 'selected' : ''; ?>>C2</option>
                                                <option value="C3" <?php echo ($_POST['categoria_licencia'] ?? '') === 'C3' ? 'selected' : ''; ?>>C3</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="licencia_expedida" class="form-label">Expedida</label>
                                            <input type="date" class="form-control" id="licencia_expedida" name="licencia_expedida"
                                                value="<?php echo htmlspecialchars($_POST['licencia_expedida'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="licencia_vence" class="form-label">Vence</label>
                                            <input type="date" class="form-control" id="licencia_vence" name="licencia_vence"
                                                value="<?php echo htmlspecialchars($_POST['licencia_vence'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contacto de Emergencia -->
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Contacto de Emergencia</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="emergencia_contacto" class="form-label">En caso de emergencia llamar a</label>
                                            <input type="text" class="form-control" id="emergencia_contacto" name="emergencia_contacto"
                                                value="<?php echo htmlspecialchars($_POST['emergencia_contacto'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="emergencia_telefono" class="form-label">Teléfono de emergencia</label>
                                            <input type="text" class="form-control" id="emergencia_telefono" name="emergencia_telefono"
                                                value="<?php echo htmlspecialchars($_POST['emergencia_telefono'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información Laboral -->
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>Información Laboral y Seguridad Social</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="experiencia" class="form-label">Experiencia</label>
                                            <textarea class="form-control" id="experiencia" name="experiencia" rows="3"><?php echo htmlspecialchars($_POST['experiencia'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="arp" class="form-label">ARP</label>
                                            <input type="text" class="form-control" id="arp" name="arp"
                                                value="<?php echo htmlspecialchars($_POST['arp'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="salud" class="form-label">Salud</label>
                                            <input type="text" class="form-control" id="salud" name="salud"
                                                value="<?php echo htmlspecialchars($_POST['salud'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="pension" class="form-label">Pensión</label>
                                            <input type="text" class="form-control" id="pension" name="pension"
                                                value="<?php echo htmlspecialchars($_POST['pension'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Vehículo Asignado -->
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-car me-2"></i>Vehículo Asignado</h5>

                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="vehiculo_id" class="form-label">Seleccionar Vehículo</label>
                                            <select class="form-select" id="vehiculo_id" name="vehiculo_id" onchange="cargarDatosVehiculo()">
                                                <option value="">Sin vehículo asignado</option>
                                                <?php while ($vehiculo = $vehiculos->fetch_assoc()): ?>
                                                    <option value="<?php echo $vehiculo['id']; ?>"
                                                        data-placa="<?php echo htmlspecialchars($vehiculo['placa']); ?>"
                                                        data-marca="<?php echo htmlspecialchars($vehiculo['marca']); ?>"
                                                        data-modelo="<?php echo htmlspecialchars($vehiculo['modelo']); ?>"
                                                        data-nib="<?php echo htmlspecialchars($vehiculo['nib']); ?>"
                                                        <?php echo $vehiculo['tiene_conductor'] ? 'class="text-warning" title="Ya tiene conductor asignado"' : ''; ?>
                                                        <?php echo ($_POST['vehiculo_id'] ?? '') == $vehiculo['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($vehiculo['placa']); ?> - <?php echo htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']); ?>
                                                        <?php echo $vehiculo['tiene_conductor'] ? ' (Ocupado)' : ''; ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>

                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="placa_display" class="form-label">Placa</label>
                                            <input type="text" class="form-control" id="placa_display" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="marca_display" class="form-label">Marca</label>
                                            <input type="text" class="form-control" id="marca_display" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label for="modelo_display" class="form-label">Modelo</label>
                                            <input type="text" class="form-control" id="modelo_display" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <label for="fecha_relacionada_vehiculo" class="form-label">Fecha de Asignación</label>
                                            <input type="date" class="form-control" id="fecha_relacionada_vehiculo" name="fecha_relacionada_vehiculo"
                                                value="<?php echo htmlspecialchars($_POST['fecha_relacionada_vehiculo'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información Adicional -->
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información Adicional</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-3">
                                            <label for="beneficiario_funebre" class="form-label">Beneficiario Servicio Fúnebre</label>
                                            <textarea class="form-control" id="beneficiario_funebre" name="beneficiario_funebre" rows="2"><?php echo htmlspecialchars($_POST['beneficiario_funebre'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nombre_padres" class="form-label">Nombre de los Padres</label>
                                            <input type="text" class="form-control" id="nombre_padres" name="nombre_padres"
                                                value="<?php echo htmlspecialchars($_POST['nombre_padres'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="hijos_menores" class="form-label">Hijos Menores de 18 Años</label>
                                            <textarea class="form-control" id="hijos_menores" name="hijos_menores" rows="2"><?php echo htmlspecialchars($_POST['hijos_menores'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="fecha_ingreso" class="form-label">Fecha de Ingreso</label>
                                            <input type="date" class="form-control" id="fecha_ingreso" name="fecha_ingreso"
                                                value="<?php echo htmlspecialchars($_POST['fecha_ingreso'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="inscripcion_vence" class="form-label">Inscripción Vence</label>
                                            <input type="date" class="form-control" id="inscripcion_vence" name="inscripcion_vence"
                                                value="<?php echo htmlspecialchars($_POST['inscripcion_vence'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Archivos -->
                    <div class="col-12">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-folder me-2"></i>Documentos</h5>
                            </div>
                            <div class="card-body">
                                <div id="archivos-container">
                                    <div class="archivo-item row mb-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Archivo</label>
                                            <input type="file" class="form-control" name="archivos[]" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Tipo</label>
                                            <select class="form-select" name="tipos_archivo[]">
                                                <option value="cedula">Cédula</option>
                                                <option value="licencia">Licencia de Conducir</option>
                                                <option value="seguro_social">Seguro Social</option>
                                                <option value="foto">Fotografía</option>
                                                <option value="certificados">Certificados</option>
                                                <option value="general">General</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Descripción</label>
                                            <input type="text" class="form-control" name="descripciones[]" placeholder="Descripción opcional">
                                        </div>
                                        <div class="col-md-1 d-flex align-items-end">
                                            <button type="button" class="btn btn-danger btn-sm" onclick="removerArchivo(this)" style="display: none;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="agregarArchivo()">
                                    <i class="fas fa-plus me-1"></i>Agregar otro archivo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mb-4">
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Registrar Conductor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function calcularEdad() {
        const fechaNacimiento = document.getElementById('fecha_nacimiento').value;
        if (fechaNacimiento) {
            const hoy = new Date();
            const nacimiento = new Date(fechaNacimiento);
            let edad = hoy.getFullYear() - nacimiento.getFullYear();
            const m = hoy.getMonth() - nacimiento.getMonth();
            if (m < 0 || (m === 0 && hoy.getDate() < nacimiento.getDate())) {
                edad--;
            }
            document.getElementById('edad').value = edad;
        }
    }

    function cargarDatosVehiculo() {
        const select = document.getElementById('vehiculo_id');
        const option = select.options[select.selectedIndex];

        if (select.value) {
            document.getElementById('placa_display').value = option.dataset.placa || '';
            document.getElementById('marca_display').value = option.dataset.marca || '';
            document.getElementById('modelo_display').value = option.dataset.modelo || '';
        } else {
            document.getElementById('placa_display').value = '';
            document.getElementById('marca_display').value = '';
            document.getElementById('modelo_display').value = '';
        }
    }

    function agregarArchivo() {
        const container = document.getElementById('archivos-container');
        const archivoItem = container.querySelector('.archivo-item').cloneNode(true);

        // Limpiar valores
        archivoItem.querySelectorAll('input, select').forEach(input => {
            if (input.type !== 'file') {
                input.value = '';
            } else {
                input.value = null;
            }
        });

        // Mostrar botón de eliminar
        archivoItem.querySelector('.btn-danger').style.display = 'block';

        container.appendChild(archivoItem);
    }

    function removerArchivo(btn) {
        btn.closest('.archivo-item').remove();
    }

    // Cargar datos del vehículo si ya hay uno seleccionado
    document.addEventListener('DOMContentLoaded', function() {
        cargarDatosVehiculo();
    });
</script>

<?php include '../../includes/footer.php'; ?>
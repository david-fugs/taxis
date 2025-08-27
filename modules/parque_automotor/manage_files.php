<?php
require_once '../../config.php';
require_once '../../conexion.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'modules/auth/login.php');
    exit();
}

$vehiculo_id = $_GET['id'] ?? 0;

// Obtener información del vehículo
$sql = "SELECT placa, marca, modelo FROM parque_automotor WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $vehiculo_id);
$stmt->execute();
$result = $stmt->get_result();
$vehiculo = $result->fetch_assoc();

if (!$vehiculo) {
    header('Location: ' . BASE_URL . 'modules/parque_automotor/index.php');
    exit();
}

// Si es una petición AJAX, manejar las operaciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['delete'])) {
    header('Content-Type: application/json');
    
    // Manejar eliminación de archivos
    if (isset($_GET['delete'])) {
        $archivo_id = $_GET['delete'];
        
        // Obtener información del archivo
        $sql = "SELECT nombre_archivo FROM parque_automotor_archivos WHERE id = ? AND vehiculo_id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ii", $archivo_id, $vehiculo_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $archivo = $result->fetch_assoc();
        
        if ($archivo) {
            // Eliminar archivo físico
            $ruta_archivo = '../../uploads/documentos/' . $archivo['nombre_archivo'];
            if (file_exists($ruta_archivo)) {
                unlink($ruta_archivo);
            }
            
            // Eliminar de la base de datos
            $sql = "DELETE FROM parque_automotor_archivos WHERE id = ? AND vehiculo_id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ii", $archivo_id, $vehiculo_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Archivo eliminado correctamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar el archivo']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Archivo no encontrado']);
        }
        exit();
    }
    
    // Manejar subida de archivos
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif'];
        $max_file_size = 10 * 1024 * 1024; // 10MB
        
        if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor.',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo permitido.',
                UPLOAD_ERR_PARTIAL => 'El archivo fue subido parcialmente.',
                UPLOAD_ERR_NO_FILE => 'No se seleccionó ningún archivo.',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal.',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco.',
                UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida del archivo.'
            ];
            
            $error_code = $_FILES['archivo']['error'] ?? UPLOAD_ERR_NO_FILE;
            $error_message = $error_messages[$error_code] ?? 'Error desconocido al subir el archivo.';
            
            echo json_encode(['success' => false, 'message' => $error_message]);
            exit();
        }
        
        $file = $_FILES['archivo'];
        $nombre_archivo = $file['name'];
        $tipo_archivo = $_POST['tipo_archivo'] ?? 'general';
        $descripcion = $_POST['descripcion'] ?? '';
        
        // Validar extensión
        $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed_extensions)) {
            echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Extensiones permitidas: ' . implode(', ', $allowed_extensions)]);
            exit();
        }
        
        // Validar tamaño
        if ($file['size'] > $max_file_size) {
            echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande. Tamaño máximo: 10MB']);
            exit();
        }
        
        // Crear directorio si no existe
        $upload_dir = '../../uploads/documentos/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                echo json_encode(['success' => false, 'message' => 'Error al crear el directorio de subida']);
                exit();
            }
        }
        
        // Generar nombre único
        $nombre_unico = date('YmdHis') . '_' . uniqid() . '.' . $extension;
        $ruta_completa = $upload_dir . $nombre_unico;
        
        if (move_uploaded_file($file['tmp_name'], $ruta_completa)) {
            // Guardar en base de datos
            $sql = "INSERT INTO parque_automotor_archivos (vehiculo_id, nombre_archivo, nombre_original, tipo_archivo, descripcion, fecha_subida, usuario_id) VALUES (?, ?, ?, ?, ?, NOW(), ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("issssi", $vehiculo_id, $nombre_unico, $nombre_archivo, $tipo_archivo, $descripcion, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Archivo subido correctamente']);
            } else {
                // Si falla la BD, eliminar el archivo
                unlink($ruta_completa);
                echo json_encode(['success' => false, 'message' => 'Error al guardar en la base de datos']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al subir el archivo al servidor']);
        }
        exit();
    }
}

// Obtener archivos del vehículo para mostrar en la página
$sql = "SELECT a.*, u.username FROM parque_automotor_archivos a 
        LEFT JOIN usuarios u ON a.usuario_id = u.id 
        WHERE a.vehiculo_id = ? 
        ORDER BY a.fecha_subida DESC";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $vehiculo_id);
$stmt->execute();
$archivos = $stmt->get_result();

$page_title = "Gestionar Archivos - " . $vehiculo['placa'];
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-folder-open me-2"></i>Gestión de Archivos</h2>
                    <p class="text-muted mb-0">Vehículo: <?php echo htmlspecialchars($vehiculo['placa']); ?> - <?php echo htmlspecialchars($vehiculo['marca'] . ' ' . $vehiculo['modelo']); ?></p>
                </div>
                <div>
                    <a href="<?php echo BASE_URL; ?>modules/parque_automotor/view.php?id=<?php echo $vehiculo_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                </div>
            </div>

            <!-- Formulario para subir archivos -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-upload me-2"></i>Subir Nuevo Archivo</h5>
                </div>
                <div class="card-body">
                    <form id="uploadForm" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="archivo" class="form-label">Archivo <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="archivo" name="archivo" required>
                                    <div class="form-text">Formatos permitidos: PDF, DOC, DOCX, JPG, PNG, GIF (máx. 10MB)</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="tipo_archivo" class="form-label">Tipo de Documento</label>
                                    <select class="form-select" id="tipo_archivo" name="tipo_archivo">
                                        <option value="soat">SOAT</option>
                                        <option value="certificado_movilizacion">Certificado de Movilización</option>
                                        <option value="tarjeta_operacion">Tarjeta de Operación</option>
                                        <option value="revision_preventiva">Revisión Preventiva</option>
                                        <option value="poliza">Póliza Responsabilidad Civil</option>
                                        <option value="fotografia">Fotografía</option>
                                        <option value="general">General</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="descripcion" class="form-label">Descripción</label>
                                    <input type="text" class="form-control" id="descripcion" name="descripcion" placeholder="Descripción opcional">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-3">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary d-block w-100">
                                        <i class="fas fa-upload me-1"></i>Subir
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de archivos -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-files-o me-2"></i>Archivos del Vehículo</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="archivosTable">
                            <thead>
                                <tr>
                                    <th>Archivo</th>
                                    <th>Tipo</th>
                                    <th>Descripción</th>
                                    <th>Fecha</th>
                                    <th>Usuario</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($archivo = $archivos->fetch_assoc()): ?>
                                <tr id="archivo-<?php echo $archivo['id']; ?>">
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
                                                'soat' => 'SOAT',
                                                'certificado_movilizacion' => 'Cert. Movilización',
                                                'tarjeta_operacion' => 'Tarjeta Operación',
                                                'revision_preventiva' => 'Rev. Preventiva',
                                                'poliza' => 'Póliza',
                                                'fotografia' => 'Fotografía',
                                                'general' => 'General'
                                            ];
                                            echo $tipos[$archivo['tipo_archivo']] ?? 'General';
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($archivo['descripcion']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($archivo['fecha_subida'])); ?></td>
                                    <td><?php echo htmlspecialchars($archivo['username'] ?? 'Sistema'); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo BASE_URL; ?>uploads/documentos/<?php echo $archivo['nombre_archivo']; ?>" 
                                               target="_blank" class="btn btn-outline-primary" title="Ver archivo">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="eliminarArchivo(<?php echo $archivo['id']; ?>)" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
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
    // Inicializar DataTable
    $('#archivosTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        },
        order: [[3, 'desc']]
    });

    // Manejar envío del formulario
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Deshabilitar botón y mostrar loading
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Subiendo...');
        
        $.ajax({
            url: 'manage_files.php?id=<?php echo $vehiculo_id; ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                console.log('Success response:', response);
                if (response.success) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'OK'
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
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                console.error('Response:', xhr.responseText);
                
                let errorMessage = 'Error al subir el archivo';
                try {
                    const response = JSON.parse(xhr.responseText);
                    errorMessage = response.message || errorMessage;
                } catch(e) {
                    console.error('Error parsing JSON:', e);
                }
                
                Swal.fire({
                    title: 'Error',
                    text: errorMessage,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                // Rehabilitar botón
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
});

function eliminarArchivo(archivoId) {
    Swal.fire({
        title: '¿Está seguro?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'manage_files.php?id=<?php echo $vehiculo_id; ?>&delete=' + archivoId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#archivo-' + archivoId).fadeOut(300, function() {
                            $(this).remove();
                        });
                        
                        Swal.fire({
                            title: '¡Eliminado!',
                            text: response.message,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
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
                        text: 'Error al eliminar el archivo',
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

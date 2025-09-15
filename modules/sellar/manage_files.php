<?php
require_once '../../config.php';
require_once '../../conexion.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'modules/auth/login.php');
    exit();
}

$sellar_id = $_GET['id'] ?? 0;

// Validar que el sellar_id sea válido
if (!$sellar_id || !is_numeric($sellar_id)) {
    header('Location: ' . BASE_URL . 'modules/sellar/index.php');
    exit();
}

// Obtener información del registro de sellado
$sellar_id = (int)$sellar_id;
$sql = "SELECT s.consecutivo, v.placa, v.marca, v.modelo 
        FROM sellar s 
        LEFT JOIN parque_automotor v ON s.vehiculo_id = v.id 
        WHERE s.id = $sellar_id";
$result = $mysqli->query($sql);

if (!$result) {
    die("Error en la consulta: " . $mysqli->error);
}

$registro = $result->fetch_assoc();

if (!$registro) {
    header('Location: ' . BASE_URL . 'modules/sellar/index.php');
    exit();
}

// Si es una petición AJAX, manejar eliminación
if (isset($_GET['delete'])) {
    ob_clean();
    header('Content-Type: application/json');
    
    $archivo_id = (int)$_GET['delete'];
    
    // Obtener información del archivo
    $sql = "SELECT nombre_archivo FROM sellar_archivos WHERE id = $archivo_id AND sellar_id = $sellar_id";
    $result = $mysqli->query($sql);
    $archivo = $result->fetch_assoc();
    
    if ($archivo) {
        // Eliminar archivo físico
        $ruta_archivo = '../../uploads/sellar/' . $archivo['nombre_archivo'];
        if (file_exists($ruta_archivo)) {
            unlink($ruta_archivo);
        }
        
        // Eliminar de la base de datos
        $sql = "DELETE FROM sellar_archivos WHERE id = $archivo_id AND sellar_id = $sellar_id";
        
        if ($mysqli->query($sql)) {
            echo json_encode(['success' => true, 'message' => 'Archivo eliminado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar el archivo']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Archivo no encontrado']);
    }
    exit();
}

// Obtener archivos del registro
$sql = "SELECT sa.*, u.username FROM sellar_archivos sa 
        LEFT JOIN usuarios u ON sa.usuario_id = u.id 
        WHERE sa.sellar_id = $sellar_id 
        ORDER BY sa.fecha_subida DESC";
$archivos = $mysqli->query($sql);

$page_title = "Gestionar Archivos - Sellado " . $registro['consecutivo'];
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-camera me-2"></i>Gestión de Archivos</h2>
                    <p class="text-muted mb-0">
                        Sellado: <?php echo htmlspecialchars($registro['consecutivo']); ?> - 
                        Vehículo: <?php echo htmlspecialchars($registro['placa']); ?> 
                        (<?php echo htmlspecialchars($registro['marca'] . ' ' . $registro['modelo']); ?>)
                    </p>
                </div>
                <div>
                    <a href="view.php?id=<?php echo $sellar_id; ?>" class="btn btn-secondary">
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
                    <form id="uploadForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="sellar_id" value="<?php echo $sellar_id; ?>">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="archivo" class="form-label">Archivo <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="archivo" name="archivo" required>
                                    <div class="form-text">Formatos permitidos: JPG, PNG, GIF, PDF (máx. 10MB)</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="tipo_archivo" class="form-label">Tipo de Archivo</label>
                                    <select class="form-select" id="tipo_archivo" name="tipo_archivo">
                                        <option value="foto_vehiculo">Foto del Vehículo</option>
                                        <option value="foto_conductor">Foto del Conductor</option>
                                        <option value="documento_adicional">Documento Adicional</option>
                                        <option value="evidencia">Evidencia</option>
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
                    <h5 class="mb-0"><i class="fas fa-images me-2"></i>Archivos del Sellado</h5>
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
                                                'foto_vehiculo' => 'Foto Vehículo',
                                                'foto_conductor' => 'Foto Conductor',
                                                'documento_adicional' => 'Documento',
                                                'evidencia' => 'Evidencia'
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
                                            <a href="<?php echo BASE_URL; ?>uploads/sellar/<?php echo $archivo['nombre_archivo']; ?>" 
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
function waitForLibraries() {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        console.log('jQuery cargado correctamente');
        $(document).ready(function() {
            initializeFileManager();
        });
    } else {
        console.log('Esperando a que jQuery se cargue...');
        setTimeout(waitForLibraries, 100);
    }
}

function initializeFileManager() {
    console.log('Inicializando gestor de archivos...');
    
    // Inicializar DataTable
    if (typeof $('#archivosTable').DataTable === 'function') {
        $('#archivosTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            },
            order: [[3, 'desc']]
        });
        console.log('DataTable inicializado');
    }

    // Manejar envío del formulario
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();
        console.log('Formulario enviado');
        
        const formData = new FormData(this);
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Deshabilitar botón y mostrar loading
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Subiendo...');
        
        $.ajax({
            url: 'upload_file.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                console.log('Success response:', response);
                if (response.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '¡Éxito!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = 'manage_files.php?id=<?php echo $sellar_id; ?>';
                        });
                    } else {
                        alert('Archivo subido correctamente');
                        window.location.href = 'manage_files.php?id=<?php echo $sellar_id; ?>';
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Error',
                            text: response.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        alert('Error: ' + response.message);
                    }
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
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Error',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                } else {
                    alert('Error: ' + errorMessage);
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
}

// Iniciar la verificación
waitForLibraries();

function eliminarArchivo(archivoId) {
    if (typeof Swal !== 'undefined') {
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
                eliminarArchivoAjax(archivoId);
            }
        });
    } else {
        if (confirm('¿Está seguro de eliminar este archivo?')) {
            eliminarArchivoAjax(archivoId);
        }
    }
}

function eliminarArchivoAjax(archivoId) {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        $.ajax({
            url: 'manage_files.php?id=<?php echo $sellar_id; ?>&delete=' + archivoId,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#archivo-' + archivoId).fadeOut(300, function() {
                        $(this).remove();
                    });
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '¡Eliminado!',
                            text: response.message,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        alert('Archivo eliminado correctamente');
                        location.reload();
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Error',
                            text: response.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        alert('Error: ' + response.message);
                    }
                }
            },
            error: function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Error',
                        text: 'Error al eliminar el archivo',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                } else {
                    alert('Error al eliminar el archivo');
                }
            }
        });
    } else {
        window.location.href = 'manage_files.php?id=<?php echo $sellar_id; ?>&delete=' + archivoId;
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
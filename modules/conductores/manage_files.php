<?php
require_once '../../config.php';
require_once '../../conexion.php';

// Verificar que el usuario esté logueado
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'modules/auth/login.php');
    exit();
}

$conductor_id = $_GET['id'] ?? 0;

// Validar que el conductor_id sea válido
if (!$conductor_id || !is_numeric($conductor_id)) {
    header('Location: ' . BASE_URL . 'modules/conductores/index.php');
    exit();
}

// Obtener información del conductor
$conductor_id = (int)$conductor_id; // Asegurar que sea entero
$sql = "SELECT nombre_completo, cedula FROM conductores WHERE id = $conductor_id";
$result = $mysqli->query($sql);

if (!$result) {
    die("Error en la consulta: " . $mysqli->error);
}

$conductor = $result->fetch_assoc();

if (!$conductor) {
    header('Location: ' . BASE_URL . 'modules/conductores/index.php');
    exit();
}

// Si es una petición AJAX, manejar solo eliminación
if (isset($_GET['delete'])) {
    // Asegurarse de que solo devolvemos JSON
    ob_clean(); // Limpiar cualquier output previo
    header('Content-Type: application/json');
    
    // Manejar eliminación de archivos
    $archivo_id = (int)$_GET['delete'];
    
    // Obtener información del archivo
    $sql = "SELECT nombre_archivo FROM conductores_archivos WHERE id = $archivo_id AND conductor_id = $conductor_id";
    $result = $mysqli->query($sql);
    $archivo = $result->fetch_assoc();
    
    if ($archivo) {
        // Eliminar archivo físico
        $ruta_archivo = '../../uploads/documentos/' . $archivo['nombre_archivo'];
        if (file_exists($ruta_archivo)) {
            unlink($ruta_archivo);
        }
        
        // Eliminar de la base de datos
        $sql = "DELETE FROM conductores_archivos WHERE id = $archivo_id AND conductor_id = $conductor_id";
        
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

// Obtener archivos del conductor para mostrar en la página
$sql = "SELECT ca.*, u.username FROM conductores_archivos ca 
        LEFT JOIN usuarios u ON ca.usuario_id = u.id 
        WHERE ca.conductor_id = $conductor_id 
        ORDER BY ca.fecha_subida DESC";
$archivos = $mysqli->query($sql);

// Debug temporal: verificar si hay archivos
$total_archivos = $archivos ? $archivos->num_rows : 0;
echo "<!-- Debug: Total archivos encontrados: $total_archivos para conductor ID: $conductor_id -->"; 
echo "<!-- Debug: Conductor: " . ($conductor ? $conductor['nombre_completo'] : 'No encontrado') . " -->";

$page_title = "Gestionar Archivos - " . $conductor['nombre_completo'];
include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-folder-open me-2"></i>Gestión de Archivos</h2>
                    <p class="text-muted mb-0">Conductor: <?php echo htmlspecialchars($conductor['nombre_completo']); ?> - Cédula: <?php echo htmlspecialchars($conductor['cedula']); ?></p>
                </div>
                <div>
                    <a href="view.php?id=<?php echo $conductor_id; ?>" class="btn btn-secondary">
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
                        <input type="hidden" name="conductor_id" value="<?php echo $conductor_id; ?>">
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
                                        <option value="cedula">Cédula</option>
                                        <option value="licencia">Licencia de Conducir</option>
                                        <option value="seguro_social">Seguro Social</option>
                                        <option value="foto">Fotografía</option>
                                        <option value="certificados">Certificados</option>
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
                    <h5 class="mb-0"><i class="fas fa-files-o me-2"></i>Archivos del Conductor</h5>
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
                                                'cedula' => 'Cédula',
                                                'licencia' => 'Licencia',
                                                'seguro_social' => 'Seguro Social',
                                                'foto' => 'Fotografía',
                                                'certificados' => 'Certificados',
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
// Función para inicializar cuando todo esté listo
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
        
        // Debug: mostrar datos del formulario
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }
        
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
                            // Recargar la misma página con el mismo ID
                            window.location.href = 'manage_files.php?id=<?php echo $conductor_id; ?>';
                        });
                    } else {
                        alert('Archivo subido correctamente');
                        window.location.href = 'manage_files.php?id=<?php echo $conductor_id; ?>';
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
                // Rehabilitar botón
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
}

// Verificar y esperar a que las librerías estén cargadas
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

// Iniciar la verificación
waitForLibraries();

function eliminarArchivo(archivoId) {
    // Usar SweetAlert si está disponible, sino un confirm normal
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
            url: 'manage_files.php?id=<?php echo $conductor_id; ?>&delete=' + archivoId,
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
        // Fallback sin jQuery
        window.location.href = 'manage_files.php?id=<?php echo $conductor_id; ?>&delete=' + archivoId;
    }
}
</script>

<?php include '../../includes/footer.php'; ?>

<?php
// Archivo de prueba para debug de subida de archivos
require_once '../../config.php';
require_once '../../conexion.php';

echo "<h2>Debug de Subida de Archivos</h2>";

echo "<h3>Información de la sesión:</h3>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";

echo "<h3>Método de la petición:</h3>";
echo "<p>Método: " . $_SERVER['REQUEST_METHOD'] . "</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Datos POST:</h3>";
    echo "<pre>";
    var_dump($_POST);
    echo "</pre>";
    
    echo "<h3>Datos FILES:</h3>";
    echo "<pre>";
    var_dump($_FILES);
    echo "</pre>";
    
    echo "<h3>Conductor ID desde GET:</h3>";
    echo "<p>ID: " . ($_GET['id'] ?? 'No definido') . "</p>";
    
    // Verificar si el conductor existe
    $conductor_id = $_GET['id'] ?? 0;
    if ($conductor_id) {
        $sql = "SELECT nombre_completo, cedula FROM conductores WHERE id = $conductor_id";
        $result = $mysqli->query($sql);
        if ($result && $conductor = $result->fetch_assoc()) {
            echo "<h3>Conductor encontrado:</h3>";
            echo "<pre>";
            var_dump($conductor);
            echo "</pre>";
        } else {
            echo "<h3>Error:</h3>";
            echo "<p>No se encontró el conductor con ID: $conductor_id</p>";
            echo "<p>Error MySQL: " . $mysqli->error . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Upload</title>
</head>
<body>
    <form method="POST" enctype="multipart/form-data" action="test_upload.php?id=<?php echo $_GET['id'] ?? 1; ?>">
        <p>
            <label>Archivo:</label>
            <input type="file" name="archivo" required>
        </p>
        <p>
            <label>Tipo:</label>
            <select name="tipo_archivo">
                <option value="general">General</option>
                <option value="cedula">Cédula</option>
            </select>
        </p>
        <p>
            <label>Descripción:</label>
            <input type="text" name="descripcion" value="Prueba">
        </p>
        <p>
            <button type="submit">Subir</button>
        </p>
    </form>
</body>
</html>

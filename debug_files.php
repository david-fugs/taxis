<?php
require_once 'config.php';

echo "<h3>Archivo Types in Database:</h3>";
$query = $mysqli->query("SELECT tipo_archivo, COUNT(*) as count FROM conductores_archivos GROUP BY tipo_archivo");
while($row = $query->fetch_assoc()) {
    echo "<p>" . htmlspecialchars($row['tipo_archivo']) . ": " . $row['count'] . "</p>";
}

echo "<h3>Sample Conductor Files:</h3>";
$query = $mysqli->query("SELECT c.nombre_completo, ca.nombre_archivo, ca.tipo_archivo FROM conductores_archivos ca JOIN conductores c ON ca.conductor_id = c.id WHERE ca.tipo_archivo = 'Foto de Conductor' LIMIT 5");
while($row = $query->fetch_assoc()) {
    echo "<p><strong>" . htmlspecialchars($row['nombre_completo']) . "</strong>: " . htmlspecialchars($row['nombre_archivo']) . " (" . htmlspecialchars($row['tipo_archivo']) . ")</p>";
}
?>
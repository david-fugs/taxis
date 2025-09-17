<?php
require_once 'config.php';

echo "<h3>Archivo Types in Database:</h3>";
$query = $mysqli->query("SELECT tipo_archivo, COUNT(*) as count FROM conductores_archivos GROUP BY tipo_archivo");
while($row = $query->fetch_assoc()) {
    echo "<p>" . htmlspecialchars($row['tipo_archivo']) . ": " . $row['count'] . "</p>";
}

echo "<h3>Sample Conductor Files:</h3>";
$query = $mysqli->query("SELECT c.id as conductor_id, c.nombre_completo, ca.nombre_archivo, ca.tipo_archivo FROM conductores_archivos ca JOIN conductores c ON ca.conductor_id = c.id WHERE ca.tipo_archivo IN ('Foto de Conductor','Foto del Conductor') LIMIT 10");
while($row = $query->fetch_assoc()) {
    $id = (int)$row['conductor_id'];
    $link = 'modules/tarjetas_control/generate.php?conductor_id=' . $id;
    echo "<p><strong>" . htmlspecialchars($row['nombre_completo']) . "</strong>: " . htmlspecialchars($row['nombre_archivo']) . " (" . htmlspecialchars($row['tipo_archivo']) . ") " . " - <a href='" . htmlspecialchars($link) . "' target='_blank'>Ver tarjeta</a></p>";
}
?>
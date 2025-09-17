<?php
require 'config.php';
$sql = "SELECT c.id, c.nombre_completo, (SELECT nombre_archivo FROM conductores_archivos ca WHERE ca.conductor_id=c.id AND ca.tipo_archivo IN ('Foto de Conductor','Foto del Conductor') ORDER BY ca.fecha_subida DESC LIMIT 1) as foto FROM conductores c WHERE (SELECT COUNT(*) FROM conductores_archivos ca WHERE ca.conductor_id=c.id AND ca.tipo_archivo IN ('Foto de Conductor','Foto del Conductor'))>0";
$q = $mysqli->query($sql);
while($r = $q->fetch_assoc()){
    echo $r['id'] . ' - ' . $r['nombre_completo'] . ' - ' . $r['foto'] . "\n";
}
?>
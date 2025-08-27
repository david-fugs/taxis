<?php
require_once '../../config.php';
require_once '../../conexion.php';

echo "<h2>Prueba de Conexión y Datos</h2>";

// Verificar conexión
if ($mysqli) {
    echo "<p style='color: green;'>✓ Conexión a la base de datos exitosa</p>";
} else {
    echo "<p style='color: red;'>✗ Error en la conexión a la base de datos</p>";
    die();
}

// Verificar tabla parque_automotor
echo "<h3>Vehículos en parque_automotor:</h3>";
$sql = "SELECT COUNT(*) as total FROM parque_automotor";
$result = $mysqli->query($sql);
$total = $result->fetch_assoc()['total'];
echo "<p>Total de vehículos: $total</p>";

if ($total > 0) {
    $sql = "SELECT id, placa, marca, modelo, estado FROM parque_automotor LIMIT 5";
    $result = $mysqli->query($sql);
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Placa</th><th>Marca</th><th>Modelo</th><th>Estado</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['placa'] . "</td>";
        echo "<td>" . $row['marca'] . "</td>";
        echo "<td>" . $row['modelo'] . "</td>";
        echo "<td>" . $row['estado'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Verificar vehículos activos
echo "<h3>Vehículos activos disponibles:</h3>";
$sql = "SELECT COUNT(*) as total FROM parque_automotor WHERE estado = 'activo'";
$result = $mysqli->query($sql);
$activos = $result->fetch_assoc()['total'];
echo "<p>Vehículos activos: $activos</p>";

// Verificar tabla conductores
echo "<h3>Conductores registrados:</h3>";
$sql = "SELECT COUNT(*) as total FROM conductores";
$result = $mysqli->query($sql);
$total_conductores = $result->fetch_assoc()['total'];
echo "<p>Total de conductores: $total_conductores</p>";

// Consulta exacta que usa el formulario
echo "<h3>Consulta del formulario:</h3>";
$sql_vehiculos = "SELECT pa.id, pa.placa, pa.marca, pa.modelo, pa.nib,
                         CASE WHEN c.id IS NOT NULL THEN 1 ELSE 0 END as tiene_conductor
                  FROM parque_automotor pa 
                  LEFT JOIN conductores c ON pa.id = c.vehiculo_id 
                  WHERE pa.estado = 'activo'
                  ORDER BY pa.placa ASC";
$vehiculos = $mysqli->query($sql_vehiculos);
$count = 0;
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Placa</th><th>Marca</th><th>Modelo</th><th>NIB</th><th>Tiene Conductor</th></tr>";
while ($vehiculo = $vehiculos->fetch_assoc()) {
    $count++;
    echo "<tr>";
    echo "<td>" . $vehiculo['id'] . "</td>";
    echo "<td>" . $vehiculo['placa'] . "</td>";
    echo "<td>" . $vehiculo['marca'] . "</td>";
    echo "<td>" . $vehiculo['modelo'] . "</td>";
    echo "<td>" . $vehiculo['nib'] . "</td>";
    echo "<td>" . ($vehiculo['tiene_conductor'] ? 'Sí' : 'No') . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "<p>Total de resultados de la consulta: $count</p>";
?>

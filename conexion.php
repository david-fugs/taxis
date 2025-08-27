<?php
	
	$mysqli = new mysqli("localhost", "taxi", "password", "taxis");
	
	// Configurar charset UTF-8 mejorado
	if (!$mysqli->connect_error) {
		// Establecer charset y collation para la conexión
		$mysqli->set_charset("utf8mb4");
		$mysqli->query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
		$mysqli->query("SET CHARACTER SET 'utf8mb4'");
		$mysqli->query("SET SESSION collation_connection = 'utf8mb4_unicode_ci'");
	}
	
	// Verificar conexión
if ($mysqli->connect_error) {
		die("Error en la conexión: " . $mysqli->connect_error);
}
?>

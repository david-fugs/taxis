<?php
require_once '../../config.php';
requireLogin();

if (!isset($_GET['conductor_id']) || !is_numeric($_GET['conductor_id'])) {
    header('Location: index.php');
    exit();
}
include '../../includes/header.php';
$conductor_id = (int)$_GET['conductor_id'];

// Obtener datos completos del conductor
$conductor_query = "
    SELECT 
        c.*,
        v.placa, v.marca, v.modelo, v.empresa,
        v.vencimiento_soat, v.final_tarjeta_operacion,
        (SELECT nombre_archivo FROM conductores_archivos 
         WHERE conductor_id = c.id AND tipo_archivo = 'Foto del Conductor' 
         ORDER BY fecha_subida DESC LIMIT 1) as foto_conductor
    FROM conductores c
    LEFT JOIN parque_automotor v ON c.vehiculo_id = v.id
    WHERE c.id = $conductor_id AND c.estado = 'activo'
";

$result = $mysqli->query($conductor_query);
if (!$result) {
    die('<div class="alert alert-danger">Error en la consulta: ' . htmlspecialchars($mysqli->error) . '</div>');
}

$conductor = $result ? $result->fetch_assoc() : null;

if (!$conductor) {
    header('Location: index.php');
    exit();
}

// Verificar vigencias
$hoy = new DateTime();
$licencia_vigente = false;
$soat_vigente = false;
$tarjeta_vigente = false;

if ($conductor['licencia_vence']) {
    $fecha_licencia = new DateTime($conductor['licencia_vence']);
    $licencia_vigente = $fecha_licencia >= $hoy;
}

if ($conductor['vencimiento_soat']) {
    $fecha_soat = new DateTime($conductor['vencimiento_soat']);
    $soat_vigente = $fecha_soat >= $hoy;
}

if ($conductor['final_tarjeta_operacion']) {
    $fecha_tarjeta = new DateTime($conductor['final_tarjeta_operacion']);
    $tarjeta_vigente = $fecha_tarjeta >= $hoy;
}

$page_title = 'Tarjeta de Control - ' . $conductor['nombre_completo'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; }
            .card { box-shadow: none !important; border: 1px solid #000 !important; }
        }
        
        .tarjeta-control {
            width: 85.6mm;
            height: 54mm;
            border: 2px solid #000;
            margin: 20px auto;
            padding: 4mm;
            background: white;
            position: relative;
            font-family: Arial, sans-serif;
            font-size: 8px;
            line-height: 1.1;
        }
        
        .tarjeta-header {
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 2mm;
            margin-bottom: 2mm;
        }
        
        .tarjeta-title {
            font-size: 10px;
            font-weight: bold;
            color: #000;
            margin: 0;
        }
        
        .tarjeta-subtitle {
            font-size: 7px;
            color: #666;
            margin: 0;
        }
        
        .photo-frame {
            width: 15mm;
            height: 18mm;
            border: 1px solid #000;
            float: left;
            margin-right: 3mm;
            margin-bottom: 2mm;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f6f6f6;
        }

        .photo-frame img {
            width: auto;
            height: 100%;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            object-position: center;
            display: block;
        }
        
        .conductor-info {
            font-size: 7px;
            line-height: 1.2;
        }
        
        .info-row {
            margin-bottom: 1mm;
        }
        
        .label {
            font-weight: bold;
            color: #000;
        }
        
        .value {
            color: #333;
        }
        
        .vehiculo-info {
            clear: both;
            border-top: 1px solid #000;
            padding-top: 2mm;
            margin-top: 2mm;
        }
        
        .vigencia {
            font-size: 6px;
            text-align: center;
            margin-top: 1mm;
        }
        
        .vigente { color: #0066cc; }
        .vencido { color: #cc0000; }
        
        .tarjeta-footer {
            position: absolute;
            bottom: 2mm;
            left: 4mm;
            right: 4mm;
            text-align: center;
            font-size: 6px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 1mm;
        }

        /* Print-specific adjustments for images */
        @media print {
            .photo-frame { border: 1px solid #000; }
            .photo-frame img { print-color-adjust: exact; -webkit-print-color-adjust: exact; object-fit: contain; }
            .tarjeta-control { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="container-fluid no-print">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-id-card me-2"></i>Tarjeta de Control</h4>
                <div>
                    <a href="index.php" class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i>Volver
                    </a>
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print me-1"></i>Imprimir
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="tarjeta-control">
    <div class="tarjeta-header">
        <h1 class="tarjeta-title">TARJETA DE CONTROL</h1>
        <p class="tarjeta-subtitle">SERVICIO PÚBLICO DE TRANSPORTE</p>
    </div>
    
    <div class="conductor-section">
        <?php if ($conductor['foto_conductor']): ?>
            <div class="photo-frame">
                <img src="<?php echo BASE_URL; ?>uploads/documentos/<?php echo htmlspecialchars($conductor['foto_conductor']); ?>" 
                     alt="Foto del Conductor">
            </div>
        <?php else: ?>
            <div class="photo-frame d-flex align-items-center justify-content-center bg-light">
                <i class="fas fa-user"></i>
            </div>
        <?php endif; ?>
        
        <div class="conductor-info">
            <div class="info-row">
                <span class="label">CONDUCTOR:</span>
                <span class="value"><?php echo strtoupper(htmlspecialchars($conductor['nombre_completo'])); ?></span>
            </div>
            <div class="info-row">
                <span class="label">C.C.:</span>
                <span class="value"><?php echo htmlspecialchars($conductor['cedula']); ?></span>
            </div>
            <div class="info-row">
                <span class="label">TELÉFONO:</span>
                <span class="value"><?php echo htmlspecialchars($conductor['telefono'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-row">
                <span class="label">DIRECCIÓN:</span>
                <span class="value"><?php echo htmlspecialchars($conductor['direccion'] ?? 'N/A'); ?></span>
            </div>
            <div class="info-row">
                <span class="label">LIC. VENCE:</span>
                <span class="value <?php echo $licencia_vigente ? 'vigente' : 'vencido'; ?>">
                    <?php echo $conductor['licencia_vence'] ? date('d/m/Y', strtotime($conductor['licencia_vence'])) : 'N/A'; ?>
                </span>
            </div>
        </div>
    </div>
    
    <?php if ($conductor['placa']): ?>
    <div class="vehiculo-info">
        <div class="info-row">
            <span class="label">PLACA:</span>
            <span class="value"><?php echo strtoupper(htmlspecialchars($conductor['placa'])); ?></span>
            <span class="label" style="margin-left: 5mm;">MARCA:</span>
            <span class="value"><?php echo strtoupper(htmlspecialchars($conductor['marca'])); ?></span>
        </div>
        <div class="info-row">
            <span class="label">MODELO:</span>
            <span class="value"><?php echo htmlspecialchars($conductor['modelo']); ?></span>
            <span class="label" style="margin-left: 5mm;">AÑO:</span>
            <span class="value">N/A</span>
        </div>
        <div class="info-row">
            <span class="label">EMPRESA:</span>
            <span class="value"><?php echo strtoupper(htmlspecialchars($conductor['empresa'] ?? 'N/A')); ?></span>
        </div>
        
        <div class="vigencia">
            <span class="label">SOAT:</span>
            <span class="<?php echo $soat_vigente ? 'vigente' : 'vencido'; ?>">
                <?php echo $conductor['vencimiento_soat'] ? date('d/m/Y', strtotime($conductor['vencimiento_soat'])) : 'N/A'; ?>
            </span>
            |
            <span class="label">T.O.:</span>
            <span class="<?php echo $tarjeta_vigente ? 'vigente' : 'vencido'; ?>">
                <?php echo $conductor['final_tarjeta_operacion'] ? date('d/m/Y', strtotime($conductor['final_tarjeta_operacion'])) : 'N/A'; ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="tarjeta-footer">
        Válida únicamente con documentos vigentes | Emitida: <?php echo date('d/m/Y H:i'); ?>
    </div>
</div>

<!-- Vista previa adicional no-print -->
<div class="container-fluid no-print">
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title mb-0">Información del Conductor</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Nombre:</strong> <?php echo htmlspecialchars($conductor['nombre_completo']); ?></p>
                            <p><strong>Cédula:</strong> <?php echo htmlspecialchars($conductor['cedula']); ?></p>
                            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($conductor['telefono'] ?? 'N/A'); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($conductor['email'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Dirección:</strong> <?php echo htmlspecialchars($conductor['direccion'] ?? 'N/A'); ?></p>
                            <?php if ($conductor['foto_conductor']): ?>
                                <p><strong>Foto (vista previa):</strong></p>
                                <a href="<?php echo BASE_URL . 'uploads/documentos/' . htmlspecialchars($conductor['foto_conductor']); ?>" target="_blank" class="d-inline-block mb-2">
                                    <div class="photo-frame" style="width:60px; height:80px; border:1px solid #ccc;">
                                        <img src="<?php echo BASE_URL . 'uploads/documentos/' . htmlspecialchars($conductor['foto_conductor']); ?>" alt="Preview">
                                    </div>
                                </a>
                            <?php endif; ?>
                            <p><strong>Licencia Vence:</strong> 
                                <span class="badge <?php echo $licencia_vigente ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $conductor['licencia_vence'] ? date('d/m/Y', strtotime($conductor['licencia_vence'])) : 'N/A'; ?>
                                </span>
                            </p>
                            <p><strong>Estado:</strong> 
                                <span class="badge bg-success"><?php echo ucfirst($conductor['estado']); ?></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($conductor['placa']): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="card-title mb-0">Información del Vehículo</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Placa:</strong> <?php echo htmlspecialchars($conductor['placa']); ?></p>
                            <p><strong>Marca:</strong> <?php echo htmlspecialchars($conductor['marca']); ?></p>
                            <p><strong>Modelo:</strong> <?php echo htmlspecialchars($conductor['modelo']); ?></p>
                            <p><strong>Año:</strong> N/A</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Color:</strong> N/A</p>
                            <p><strong>Empresa:</strong> <?php echo htmlspecialchars($conductor['empresa'] ?? 'N/A'); ?></p>
                            <p><strong>SOAT Vence:</strong> 
                                <span class="badge <?php echo $soat_vigente ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $conductor['vencimiento_soat'] ? date('d/m/Y', strtotime($conductor['vencimiento_soat'])) : 'N/A'; ?>
                                </span>
                            </p>
                            <p><strong>Tarjeta Operación Vence:</strong> 
                                <span class="badge <?php echo $tarjeta_vigente ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $conductor['final_tarjeta_operacion'] ? date('d/m/Y', strtotime($conductor['final_tarjeta_operacion'])) : 'N/A'; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto-focus para imprimir
window.addEventListener('load', function() {
    // Opcional: auto-imprimir cuando se carga la página
    // window.print();
});
</script>

</body>
</html>
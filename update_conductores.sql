-- Actualización de la base de datos para el módulo de conductores

USE `taxis`;

-- Eliminar tabla anterior de conductores si existe
DROP TABLE IF EXISTS `conductores`;

-- Crear tabla de conductores actualizada
CREATE TABLE IF NOT EXISTS `conductores` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nombre_completo` varchar(200) NOT NULL,
    `cedula` varchar(20) NOT NULL UNIQUE,
    `expedida_en` varchar(100),
    `telefono` varchar(20),
    `celular` varchar(20),
    `direccion` text,
    `ciudad` varchar(100),
    `lugar_nacimiento` varchar(100),
    `fecha_nacimiento` date,
    `edad` int(3),
    `estado_civil` enum('soltero','casado','viudo','divorciado','union_libre') DEFAULT 'soltero',
    `rh` varchar(10),
    `email` varchar(100),
    `licencia_numero` varchar(50),
    `categoria_licencia` varchar(20),
    `licencia_expedida` date,
    `licencia_vence` date,
    `emergencia_contacto` varchar(200),
    `emergencia_telefono` varchar(20),
    `experiencia` text,
    `arp` varchar(100),
    `salud` varchar(100),
    `pension` varchar(100),
    `vehiculo_id` int(11) NULL,
    `fecha_relacionada_vehiculo` date,
    `beneficiario_funebre` text,
    `nombre_padres` varchar(300),
    `hijos_menores` text,
    `fecha_ingreso` date,
    `inscripcion_vence` date,
    `estado` enum('activo','inactivo','suspendido') NOT NULL DEFAULT 'activo',
    `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `usuario_creacion` int(11),
    `usuario_actualizacion` int(11),
    PRIMARY KEY (`id`),
    KEY `idx_cedula_conductor` (`cedula`),
    KEY `idx_licencia` (`licencia_numero`),
    KEY `idx_vehiculo` (`vehiculo_id`),
    KEY `idx_estado` (`estado`),
    KEY `idx_nombre` (`nombre_completo`),
    FOREIGN KEY (`vehiculo_id`) REFERENCES `parque_automotor`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`usuario_creacion`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`usuario_actualizacion`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de archivos de conductores
CREATE TABLE IF NOT EXISTS `conductores_archivos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `conductor_id` int(11) NOT NULL,
    `nombre_archivo` varchar(255) NOT NULL,
    `nombre_original` varchar(255) NOT NULL,
    `tipo_archivo` varchar(100),
    `descripcion` text,
    `fecha_subida` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `usuario_id` int(11),
    PRIMARY KEY (`id`),
    KEY `idx_conductor` (`conductor_id`),
    FOREIGN KEY (`conductor_id`) REFERENCES `conductores`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar comentarios
ALTER TABLE `conductores` COMMENT = 'Información de conductores del parque automotor';
ALTER TABLE `conductores_archivos` COMMENT = 'Archivos y documentos de conductores';

-- Insertar algunos datos de ejemplo
INSERT INTO `conductores` (
    `nombre_completo`, `cedula`, `expedida_en`, `telefono`, `celular`, `email`, 
    `ciudad`, `fecha_nacimiento`, `edad`, `estado_civil`, `rh`, 
    `licencia_numero`, `categoria_licencia`, `licencia_expedida`, `licencia_vence`,
    `fecha_ingreso`, `usuario_creacion`
) VALUES 
(
    'Carlos Alberto Ramírez Gómez', '1234567890', 'Bogotá', '601-555-0123', '300-555-0123', 'carlos.ramirez@email.com',
    'Bogotá', '1985-03-15', 39, 'casado', 'O+',
    'LIC123456789', 'B2', '2020-01-15', '2030-01-15',
    '2024-01-01', 1
),
(
    'María Elena Rodríguez Silva', '9876543210', 'Medellín', '604-555-0456', '310-555-0456', 'maria.rodriguez@email.com',
    'Medellín', '1990-07-22', 34, 'soltera', 'A+',
    'LIC987654321', 'B3', '2019-05-10', '2029-05-10',
    '2024-02-15', 1
);

SELECT 'Actualización completada exitosamente' as mensaje;

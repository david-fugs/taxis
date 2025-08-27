-- Script de base de datos para el sistema de gestión de taxis
-- Ejecutar este script en phpMyAdmin o MySQL Workbench

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS `taxis` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `taxis`;

-- Tabla de usuarios del sistema
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL,
    `email` varchar(100) NOT NULL UNIQUE,
    `nombre_completo` varchar(100) NOT NULL,
    `tipo_usuario` enum('admin','usuario') NOT NULL DEFAULT 'usuario',
    `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
    `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `ultimo_acceso` timestamp NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de asociados
CREATE TABLE IF NOT EXISTS `asociados` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `cedula` varchar(20) NOT NULL UNIQUE,
    `nombres` varchar(100) NOT NULL,
    `apellidos` varchar(100) NOT NULL,
    `direccion` text,
    `ciudad` varchar(100),
    `telefono1` varchar(20),
    `telefono2` varchar(20),
    `celular` varchar(20),
    `lugar_nacimiento` varchar(100),
    `fecha_nacimiento` date,
    `edad` int(3),
    `rh` varchar(10),
    `estado_civil` enum('soltero','casado','viudo','divorciado','union_libre') DEFAULT 'soltero',
    `fecha_ingreso` date,
    `conyuge` varchar(200),
    `urgencia_avisar` varchar(200),
    `otro_avisar` varchar(200),
    `direccion_avisar` text,
    `telefono_avisar` varchar(20),
    `observaciones` text,
    `placa_carro` varchar(10),
    `marca` varchar(50),
    `modelo` varchar(50),
    `nib` varchar(50),
    `tarjeta_operacion` varchar(50),
    `beneficiario_funebre` varchar(200),
    `beneficiario_auxilio_muerte` varchar(200),
    `email` varchar(100),
    `foto` varchar(255),
    `estado` enum('activo','inactivo') NOT NULL DEFAULT 'activo',
    `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `usuario_creacion` int(11),
    `usuario_actualizacion` int(11),
    PRIMARY KEY (`id`),
    KEY `idx_cedula` (`cedula`),
    KEY `idx_nombres` (`nombres`, `apellidos`),
    KEY `idx_placa` (`placa_carro`),
    KEY `idx_estado` (`estado`),
    FOREIGN KEY (`usuario_creacion`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`usuario_actualizacion`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de archivos asociados
CREATE TABLE IF NOT EXISTS `asociados_archivos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `asociado_id` int(11) NOT NULL,
    `nombre_archivo` varchar(255) NOT NULL,
    `nombre_original` varchar(255) NOT NULL,
    `tipo_archivo` varchar(100),
    `tamaño` int(11),
    `ruta_archivo` varchar(500) NOT NULL,
    `tipo_documento` enum('cedula','licencia','seguro','tarjeta_propiedad','foto','otro') DEFAULT 'otro',
    `descripcion` text,
    `fecha_subida` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `usuario_subida` int(11),
    PRIMARY KEY (`id`),
    KEY `idx_asociado` (`asociado_id`),
    KEY `idx_tipo` (`tipo_documento`),
    FOREIGN KEY (`asociado_id`) REFERENCES `asociados`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`usuario_subida`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de parque automotor (debe ir antes de conductores)
CREATE TABLE IF NOT EXISTS `parque_automotor` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `placa` varchar(10) NOT NULL UNIQUE,
    `marca` varchar(50) NOT NULL,
    `modelo` varchar(50) NOT NULL,
    `nib` varchar(50),
    `chassis` varchar(50),
    `motor` varchar(50),
    `radio_telefono` varchar(50),
    `serial` varchar(50),
    `compania_soat` varchar(100),
    `soat` varchar(50),
    `vencimiento_soat` date,
    `certificado_movilizacion` varchar(50),
    `fecha_vencimiento_certificado` date,
    `tipo_combustible` enum('gasolina','diesel','gas','electrico','hibrido') DEFAULT 'gasolina',
    `tarjeta_operacion` varchar(50),
    `fecha_tarjeta_operacion` date,
    `inicio_tarjeta_operacion` date,
    `final_tarjeta_operacion` date,
    `revision_preventiva` varchar(50),
    `vencimiento_preventiva` date,
    `poliza_responsabilidad_civil` varchar(50),
    `observaciones` text,
    `empresa` varchar(100),
    `asociado_id` int(11) NULL,
    `estado` enum('activo','inactivo','mantenimiento','vendido') NOT NULL DEFAULT 'activo',
    `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `usuario_creacion` int(11),
    `usuario_actualizacion` int(11),
    PRIMARY KEY (`id`),
    KEY `idx_placa` (`placa`),
    KEY `idx_marca_modelo` (`marca`, `modelo`),
    KEY `idx_estado` (`estado`),
    KEY `idx_asociado` (`asociado_id`),
    FOREIGN KEY (`asociado_id`) REFERENCES `asociados`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`usuario_creacion`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`usuario_actualizacion`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de archivos del parque automotor
CREATE TABLE IF NOT EXISTS `parque_automotor_archivos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `vehiculo_id` int(11) NOT NULL,
    `nombre_archivo` varchar(255) NOT NULL,
    `nombre_original` varchar(255) NOT NULL,
    `tipo_archivo` varchar(100),
    `tamaño` int(11),
    `ruta_archivo` varchar(500) NOT NULL,
    `tipo_documento` enum('tarjeta_propiedad','soat','revision_tecnica','poliza','foto_vehiculo','otro') DEFAULT 'otro',
    `descripcion` text,
    `fecha_subida` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `usuario_subida` int(11),
    PRIMARY KEY (`id`),
    KEY `idx_vehiculo` (`vehiculo_id`),
    KEY `idx_tipo` (`tipo_documento`),
    FOREIGN KEY (`vehiculo_id`) REFERENCES `parque_automotor`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`usuario_subida`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de conductores
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

-- Tabla de auditoría (para futura expansión)
CREATE TABLE IF NOT EXISTS `auditoria` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `tabla` varchar(50) NOT NULL,
    `registro_id` int(11) NOT NULL,
    `accion` enum('INSERT','UPDATE','DELETE') NOT NULL,
    `datos_anteriores` json,
    `datos_nuevos` json,
    `usuario_id` int(11),
    `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `ip_usuario` varchar(45),
    PRIMARY KEY (`id`),
    KEY `idx_tabla_registro` (`tabla`, `registro_id`),
    KEY `idx_usuario_auditoria` (`usuario_id`),
    KEY `idx_fecha_auditoria` (`fecha`),
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuario administrador por defecto
INSERT INTO `usuarios` (`username`, `password`, `email`, `nombre_completo`, `tipo_usuario`) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@taxis.com', 'Administrador del Sistema', 'admin')
ON DUPLICATE KEY UPDATE `username` = `username`;

-- Insertar algunos datos de ejemplo para asociados (opcional)
INSERT INTO `asociados` (`cedula`, `nombres`, `apellidos`, `ciudad`, `telefono1`, `celular`, `fecha_nacimiento`, `edad`, `rh`, `estado_civil`, `fecha_ingreso`, `placa_carro`, `marca`, `modelo`, `email`) VALUES
('12345678', 'Juan Carlos', 'Pérez García', 'Bogotá', '601-234-5678', '300-123-4567', '1980-05-15', 43, 'O+', 'casado', '2020-01-15', 'ABC-123', 'Toyota', 'Corolla', 'juan.perez@email.com'),
('87654321', 'María Elena', 'Rodríguez López', 'Medellín', '604-876-5432', '310-987-6543', '1975-08-22', 48, 'A+', 'soltera', '2019-03-10', 'XYZ-789', 'Chevrolet', 'Aveo', 'maria.rodriguez@email.com')
ON DUPLICATE KEY UPDATE `cedula` = `cedula`;

-- Crear índices adicionales para optimizar consultas
CREATE INDEX `idx_asociados_busqueda` ON `asociados` (`cedula`, `nombres`, `apellidos`, `placa_carro`);
CREATE INDEX `idx_fecha_creacion` ON `asociados` (`fecha_creacion`);
CREATE INDEX `idx_usuarios_tipo` ON `usuarios` (`tipo_usuario`, `estado`);

-- Crear vistas útiles
CREATE OR REPLACE VIEW `vista_asociados_completa` AS
SELECT 
    a.*,
    u1.username AS creado_por,
    u2.username AS actualizado_por,
    COUNT(aa.id) AS total_archivos
FROM `asociados` a
LEFT JOIN `usuarios` u1 ON a.usuario_creacion = u1.id
LEFT JOIN `usuarios` u2 ON a.usuario_actualizacion = u2.id
LEFT JOIN `asociados_archivos` aa ON a.id = aa.asociado_id
GROUP BY a.id;

-- Procedimiento almacenado para buscar asociados
DELIMITER //
CREATE PROCEDURE `BuscarAsociados`(
    IN `termino_busqueda` VARCHAR(255)
)
BEGIN
    SELECT * FROM `asociados` 
    WHERE `estado` = 'activo' 
    AND (
        `cedula` LIKE CONCAT('%', termino_busqueda, '%') OR
        `nombres` LIKE CONCAT('%', termino_busqueda, '%') OR
        `apellidos` LIKE CONCAT('%', termino_busqueda, '%') OR
        `placa_carro` LIKE CONCAT('%', termino_busqueda, '%')
    )
    ORDER BY `nombres`, `apellidos`;
END //
DELIMITER ;

-- Configurar permisos y configuraciones de la base de datos

SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- Comentarios sobre las tablas
ALTER TABLE `usuarios` COMMENT = 'Tabla de usuarios del sistema';
ALTER TABLE `asociados` COMMENT = 'Tabla principal de asociados de la empresa de taxis';
ALTER TABLE `asociados_archivos` COMMENT = 'Archivos y documentos asociados a cada asociado';
ALTER TABLE `parque_automotor` COMMENT = 'Tabla del parque automotor de vehículos';
ALTER TABLE `parque_automotor_archivos` COMMENT = 'Archivos y documentos de vehículos del parque automotor';
-- Tabla de archivos de conductores
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
ALTER TABLE `conductores` COMMENT = 'Información de conductores del parque automotor';
ALTER TABLE `conductores_archivos` COMMENT = 'Archivos y documentos de conductores';
ALTER TABLE `auditoria` COMMENT = 'Registro de auditoría de cambios en el sistema';

-- El usuario y contraseña por defecto del admin es:
-- Usuario: admin
-- Contraseña: password

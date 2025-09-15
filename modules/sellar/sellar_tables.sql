-- Tabla principal de sellado
CREATE TABLE sellar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consecutivo VARCHAR(20) UNIQUE NOT NULL,
    vehiculo_id INT NOT NULL,
    conductor_id INT NULL,
    
    -- Estado del proceso
    estado ENUM('pendiente', 'aprobado', 'rechazado', 'observaciones') DEFAULT 'pendiente',
    
    -- Verificaciones automáticas
    soat_vigente BOOLEAN DEFAULT FALSE,
    tarjeta_operacion_vigente BOOLEAN DEFAULT FALSE,
    licencia_vigente BOOLEAN DEFAULT FALSE,
    seguro_social_vigente BOOLEAN DEFAULT FALSE,
    
    -- Fechas de documentos verificadas
    fecha_vencimiento_soat DATE NULL,
    fecha_vencimiento_tarjeta DATE NULL,
    fecha_vencimiento_licencia DATE NULL,
    
    -- Observaciones y aprobación
    observaciones TEXT NULL,
    motivo_rechazo TEXT NULL,
    aprobado_por INT NULL,
    fecha_aprobacion DATETIME NULL,
    
    -- Auditoría
    usuario_creacion INT NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Claves foráneas
    FOREIGN KEY (vehiculo_id) REFERENCES parque_automotor(id) ON DELETE CASCADE,
    FOREIGN KEY (conductor_id) REFERENCES conductores(id) ON DELETE SET NULL,
    FOREIGN KEY (aprobado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (usuario_creacion) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    -- Índices
    INDEX idx_consecutivo (consecutivo),
    INDEX idx_estado (estado),
    INDEX idx_fecha_creacion (fecha_creacion),
    INDEX idx_vehiculo (vehiculo_id),
    INDEX idx_conductor (conductor_id)
);

-- Tabla de archivos para el módulo sellar
CREATE TABLE sellar_archivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sellar_id INT NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    tipo_archivo ENUM('foto_vehiculo', 'foto_conductor', 'documento_adicional', 'evidencia') DEFAULT 'foto_vehiculo',
    descripcion TEXT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    tamaño_archivo INT NOT NULL,
    tipo_mime VARCHAR(100) NOT NULL,
    usuario_id INT NOT NULL,
    fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (sellar_id) REFERENCES sellar(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    INDEX idx_sellar (sellar_id),
    INDEX idx_tipo (tipo_archivo)
);

-- Trigger para generar consecutivo automático
DELIMITER $$

CREATE TRIGGER trg_sellar_consecutivo 
BEFORE INSERT ON sellar
FOR EACH ROW
BEGIN
    DECLARE next_num INT;
    DECLARE year_prefix VARCHAR(4);
    
    SET year_prefix = YEAR(NOW());
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(consecutivo, 6) AS UNSIGNED)), 0) + 1 
    INTO next_num 
    FROM sellar 
    WHERE consecutivo LIKE CONCAT(year_prefix, '-%');
    
    SET NEW.consecutivo = CONCAT(year_prefix, '-', LPAD(next_num, 6, '0'));
END$$

DELIMITER ;

-- Insertar algunos datos de prueba (opcional)
-- INSERT INTO sellar (vehiculo_id, conductor_id, usuario_creacion) VALUES (1, 1, 1);
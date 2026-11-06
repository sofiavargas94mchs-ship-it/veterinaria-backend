-- === SISTEMA DE GESTIÓN DE CLÍNICA VETERINARIA===

DROP DATABASE IF EXISTS clinica_veterinaria;
CREATE DATABASE clinica_veterinaria
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE clinica_veterinaria;

-- =====================================================================
-- 1. SEGURIDAD Y ACCESO
-- =====================================================================
CREATE TABLE usuarios (
    id_usuario            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre                VARCHAR(100) NOT NULL,
    apellido              VARCHAR(100) NOT NULL,
    correo                VARCHAR(150) NOT NULL,
    password_hash         VARCHAR(255) NOT NULL COMMENT 'Hash bcrypt/argon2 generado por el backend; nunca texto plano',
    rol                   ENUM('Administrador','Recepcionista','Veterinario') NOT NULL,
    registro_profesional  VARCHAR(30) NULL COMMENT 'Tarjeta profesional (solo veterinarios)',
    activo                TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso         DATETIME NULL,
    PRIMARY KEY (id_usuario),
    UNIQUE KEY uq_usuarios_correo (correo),
    CONSTRAINT chk_usuarios_correo CHECK (correo LIKE '%_@_%._%')
) ENGINE=InnoDB;

-- =====================================================================
-- 2. CLIENTES Y PACIENTES
-- =====================================================================
CREATE TABLE propietarios (
    id_propietario  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo_documento  ENUM('CC','CE','TI','PAS','NIT') NOT NULL DEFAULT 'CC',
    documento       VARCHAR(20)  NOT NULL COMMENT 'Cédula / documento: identificador único del cliente',
    nombres         VARCHAR(100) NOT NULL,
    apellidos       VARCHAR(100) NOT NULL,
    telefono        VARCHAR(20)  NOT NULL,
    direccion       VARCHAR(200) NULL,
    correo          VARCHAR(150) NULL,
    fecha_registro  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_propietario),
    UNIQUE KEY uq_propietarios_documento (documento),
    UNIQUE KEY uq_propietarios_correo (correo),
    KEY idx_propietarios_apellidos (apellidos, nombres)
) ENGINE=InnoDB;

-- Catálogos que eliminan la dependencia transitiva raza -> especie (3FN)
CREATE TABLE especies (
    id_especie  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_especie),
    UNIQUE KEY uq_especies_nombre (nombre)
) ENGINE=InnoDB;

CREATE TABLE razas (
    id_raza     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    especie_id  INT UNSIGNED NOT NULL,
    nombre      VARCHAR(80) NOT NULL,
    PRIMARY KEY (id_raza),
    UNIQUE KEY uq_razas_especie_nombre (especie_id, nombre),
    CONSTRAINT fk_razas_especie FOREIGN KEY (especie_id)
        REFERENCES especies (id_especie)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE mascotas (
    id_mascota        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    propietario_id    INT UNSIGNED NOT NULL,
    raza_id           INT UNSIGNED NOT NULL,
    nombre            VARCHAR(80) NOT NULL,
    fecha_nacimiento  DATE NULL COMMENT 'La edad se calcula (ver vista_mascotas_detalle), no se almacena',
    sexo              ENUM('Macho','Hembra') NOT NULL,
    color             VARCHAR(50) NULL,
    esterilizado      TINYINT(1) NOT NULL DEFAULT 0,
    microchip         VARCHAR(30) NULL,
    activo            TINYINT(1) NOT NULL DEFAULT 1,
    fecha_registro    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_mascota),
    UNIQUE KEY uq_mascotas_microchip (microchip),
    KEY idx_mascotas_propietario (propietario_id),
    KEY idx_mascotas_raza (raza_id),
    KEY idx_mascotas_nombre (nombre),
    CONSTRAINT fk_mascotas_propietario FOREIGN KEY (propietario_id)
        REFERENCES propietarios (id_propietario)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_mascotas_raza FOREIGN KEY (raza_id)
        REFERENCES razas (id_raza)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 3. AGENDA MÉDICA
-- =====================================================================
CREATE TABLE citas (
    id_cita          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    mascota_id       INT UNSIGNED NOT NULL,
    veterinario_id   INT UNSIGNED NOT NULL COMMENT 'Usuario con rol Veterinario (validado por trigger)',
    creada_por       INT UNSIGNED NOT NULL COMMENT 'Usuario que agendó la cita',
    fecha_hora       DATETIME NOT NULL,
    duracion_min     SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    motivo           VARCHAR(255) NOT NULL,
    estado           ENUM('Programada','En consulta','Completada','Cancelada') NOT NULL DEFAULT 'Programada',
    observaciones    VARCHAR(500) NULL,
    fecha_creacion   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_cita),
    -- Clave candidata que permite a historias_clinicas garantizar coherencia con la cita
    UNIQUE KEY uq_citas_cita_mascota_vet (id_cita, mascota_id, veterinario_id),
    KEY idx_citas_vet_fecha (veterinario_id, fecha_hora),
    KEY idx_citas_mascota (mascota_id, fecha_hora),
    KEY idx_citas_fecha (fecha_hora),
    KEY idx_citas_creada_por (creada_por),
    CONSTRAINT chk_citas_duracion CHECK (duracion_min BETWEEN 5 AND 480),
    CONSTRAINT fk_citas_mascota FOREIGN KEY (mascota_id)
        REFERENCES mascotas (id_mascota)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_citas_veterinario FOREIGN KEY (veterinario_id)
        REFERENCES usuarios (id_usuario)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_citas_creada_por FOREIGN KEY (creada_por)
        REFERENCES usuarios (id_usuario)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 4. FARMACIA E INVENTARIO
-- =====================================================================
CREATE TABLE medicamentos (
    id_medicamento   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo           VARCHAR(20)  NOT NULL,
    nombre           VARCHAR(120) NOT NULL,
    principio_activo VARCHAR(120) NULL,
    presentacion     VARCHAR(80)  NOT NULL COMMENT 'Unidad de venta: tableta, frasco 20 ml, tubo...',
    stock_minimo     INT UNSIGNED NOT NULL DEFAULT 5,
    precio_compra    DECIMAL(10,2) NOT NULL,
    precio_venta     DECIMAL(10,2) NOT NULL,
    activo           TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_medicamento),
    UNIQUE KEY uq_medicamentos_codigo (codigo),
    KEY idx_medicamentos_nombre (nombre),
    CONSTRAINT chk_medicamentos_precios CHECK (precio_compra >= 0 AND precio_venta >= 0)
) ENGINE=InnoDB;

-- Kardex: el stock NO se guarda en medicamentos (dato derivado); se calcula
-- como SUM(cantidad). Entradas positivas, salidas negativas.
CREATE TABLE movimientos_inventario (
    id_movimiento       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    medicamento_id      INT UNSIGNED NOT NULL,
    usuario_id          INT UNSIGNED NOT NULL,
    tipo                ENUM('Compra','Venta','Ajuste','Merma') NOT NULL,
    cantidad            INT NOT NULL,
    lote                VARCHAR(40) NULL,
    fecha_vencimiento   DATE NULL,
    fecha_movimiento    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    detalle_factura_id  INT UNSIGNED NULL COMMENT 'Se llena automáticamente en ventas',
    observacion         VARCHAR(255) NULL,
    PRIMARY KEY (id_movimiento),
    UNIQUE KEY uq_mov_detalle_factura (detalle_factura_id),
    KEY idx_mov_medicamento (medicamento_id, fecha_movimiento),
    KEY idx_mov_usuario (usuario_id),
    CONSTRAINT chk_mov_cantidad CHECK (
           (tipo = 'Compra' AND cantidad > 0)
        OR (tipo IN ('Venta','Merma') AND cantidad < 0)
        OR (tipo = 'Ajuste' AND cantidad <> 0)
    ),
    CONSTRAINT fk_mov_medicamento FOREIGN KEY (medicamento_id)
        REFERENCES medicamentos (id_medicamento)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_mov_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id_usuario)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 5. HISTORIA CLÍNICA Y FÓRMULA MÉDICA
-- =====================================================================
CREATE TABLE historias_clinicas (
    id_historia      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    mascota_id       INT UNSIGNED NOT NULL,
    veterinario_id   INT UNSIGNED NOT NULL,
    cita_id          INT UNSIGNED NULL COMMENT 'NULL en urgencias atendidas sin cita previa',
    fecha_atencion   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    peso_kg          DECIMAL(6,2) NULL,
    temperatura_c    DECIMAL(4,1) NULL,
    sintomas         TEXT NOT NULL,
    diagnostico      TEXT NOT NULL,
    tratamiento      TEXT NOT NULL,
    observaciones    TEXT NULL,
    proxima_revision DATE NULL,
    PRIMARY KEY (id_historia),
    UNIQUE KEY uq_historias_cita (cita_id),                                -- 1:1 cita <-> evolución
    UNIQUE KEY uq_historias_hist_mascota (id_historia, mascota_id),        -- clave candidata para vacunación
    KEY idx_historias_mascota (mascota_id, fecha_atencion),
    KEY idx_historias_veterinario (veterinario_id, fecha_atencion),
    KEY idx_historias_cita_mascota_vet (cita_id, mascota_id, veterinario_id),
    CONSTRAINT chk_historias_peso CHECK (peso_kg IS NULL OR peso_kg > 0),
    CONSTRAINT fk_historias_mascota FOREIGN KEY (mascota_id)
        REFERENCES mascotas (id_mascota)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_historias_veterinario FOREIGN KEY (veterinario_id)
        REFERENCES usuarios (id_usuario)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    -- FK compuesta: si hay cita, mascota y veterinario DEBEN coincidir con los de la cita.
    -- Con cita_id NULL (MATCH SIMPLE) la restricción no se evalúa.
    CONSTRAINT fk_historias_cita_coherente FOREIGN KEY (cita_id, mascota_id, veterinario_id)
        REFERENCES citas (id_cita, mascota_id, veterinario_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Fórmula médica: relación N:M historia <-> medicamento
CREATE TABLE detalle_formula (
    id_detalle_formula  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    historia_id         INT UNSIGNED NOT NULL,
    medicamento_id      INT UNSIGNED NOT NULL,
    cantidad            INT UNSIGNED NOT NULL,
    dosis               VARCHAR(100) NOT NULL,
    frecuencia          VARCHAR(100) NOT NULL,
    duracion_dias       SMALLINT UNSIGNED NULL,
    indicaciones        VARCHAR(255) NULL,
    PRIMARY KEY (id_detalle_formula),
    UNIQUE KEY uq_formula_historia_med (historia_id, medicamento_id),
    KEY idx_formula_medicamento (medicamento_id),
    CONSTRAINT chk_formula_cantidad CHECK (cantidad > 0),
    CONSTRAINT fk_formula_historia FOREIGN KEY (historia_id)
        REFERENCES historias_clinicas (id_historia)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_formula_medicamento FOREIGN KEY (medicamento_id)
        REFERENCES medicamentos (id_medicamento)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 6. CONTROL DE VACUNACIÓN
-- =====================================================================
CREATE TABLE vacunas (
    id_vacuna               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    especie_id              INT UNSIGNED NOT NULL,
    nombre                  VARCHAR(100) NOT NULL,
    descripcion             VARCHAR(255) NULL,
    intervalo_refuerzo_dias SMALLINT UNSIGNED NULL COMMENT 'NULL = dosis única, sin refuerzo',
    activo                  TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_vacuna),
    UNIQUE KEY uq_vacunas_especie_nombre (especie_id, nombre),
    CONSTRAINT chk_vacunas_intervalo CHECK (intervalo_refuerzo_dias IS NULL OR intervalo_refuerzo_dias > 0),
    CONSTRAINT fk_vacunas_especie FOREIGN KEY (especie_id)
        REFERENCES especies (id_especie)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE control_vacunacion (
    id_vacunacion        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    mascota_id           INT UNSIGNED NOT NULL,
    vacuna_id            INT UNSIGNED NOT NULL,
    veterinario_id       INT UNSIGNED NOT NULL,
    historia_id          INT UNSIGNED NULL,
    fecha_aplicacion     DATE NOT NULL,
    lote                 VARCHAR(40) NULL,
    fecha_proxima_dosis  DATE NULL COMMENT 'Calculada por trigger: fecha_aplicacion + intervalo vigente de la vacuna',
    observaciones        VARCHAR(255) NULL,
    PRIMARY KEY (id_vacunacion),
    KEY idx_vacunacion_mascota (mascota_id, fecha_aplicacion),
    KEY idx_vacunacion_proxima (fecha_proxima_dosis),
    KEY idx_vacunacion_vacuna (vacuna_id),
    KEY idx_vacunacion_vet (veterinario_id),
    KEY idx_vacunacion_historia_mascota (historia_id, mascota_id),
    CONSTRAINT fk_vacunacion_mascota FOREIGN KEY (mascota_id)
        REFERENCES mascotas (id_mascota)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_vacunacion_vacuna FOREIGN KEY (vacuna_id)
        REFERENCES vacunas (id_vacuna)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_vacunacion_vet FOREIGN KEY (veterinario_id)
        REFERENCES usuarios (id_usuario)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    -- FK compuesta: si se asocia a una historia, esta debe ser de la misma mascota
    CONSTRAINT fk_vacunacion_historia_coherente FOREIGN KEY (historia_id, mascota_id)
        REFERENCES historias_clinicas (id_historia, mascota_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 7. SERVICIOS, FACTURACIÓN Y PAGOS (ingresos y cuadre de caja)
-- =====================================================================
CREATE TABLE servicios (
    id_servicio  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo       VARCHAR(20)  NOT NULL,
    nombre       VARCHAR(120) NOT NULL,
    descripcion  VARCHAR(255) NULL,
    precio       DECIMAL(10,2) NOT NULL,
    activo       TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_servicio),
    UNIQUE KEY uq_servicios_codigo (codigo),
    CONSTRAINT chk_servicios_precio CHECK (precio >= 0)
) ENGINE=InnoDB;

CREATE TABLE facturas (
    id_factura      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    numero_factura  VARCHAR(20) NOT NULL,
    cita_id         INT UNSIGNED NOT NULL,
    emitida_por     INT UNSIGNED NOT NULL,
    fecha_emision   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado          ENUM('Emitida','Anulada') NOT NULL DEFAULT 'Emitida'
                    COMMENT 'El estado de pago (pendiente/parcial/pagada) se deriva en vista_facturas_resumen',
    observaciones   VARCHAR(255) NULL,
    PRIMARY KEY (id_factura),
    UNIQUE KEY uq_facturas_numero (numero_factura),
    KEY idx_facturas_cita (cita_id),
    KEY idx_facturas_emitida_por (emitida_por),
    KEY idx_facturas_fecha (fecha_emision),
    CONSTRAINT fk_facturas_cita FOREIGN KEY (cita_id)
        REFERENCES citas (id_cita)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_facturas_usuario FOREIGN KEY (emitida_por)
        REFERENCES usuarios (id_usuario)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Cada línea cobra UN servicio O UN medicamento (exclusivo, validado con CHECK).
-- precio_unitario es el precio histórico pactado al facturar (no cambia si el catálogo cambia).
CREATE TABLE detalle_factura (
    id_detalle_factura  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    factura_id          INT UNSIGNED NOT NULL,
    servicio_id         INT UNSIGNED NULL,
    medicamento_id      INT UNSIGNED NULL,
    cantidad            INT UNSIGNED NOT NULL DEFAULT 1,
    precio_unitario     DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id_detalle_factura),
    KEY idx_detfac_factura (factura_id),
    KEY idx_detfac_servicio (servicio_id),
    KEY idx_detfac_medicamento (medicamento_id),
    CONSTRAINT chk_detfac_item CHECK (
        (servicio_id IS NOT NULL AND medicamento_id IS NULL)
        OR (servicio_id IS NULL AND medicamento_id IS NOT NULL)
    ),
    CONSTRAINT chk_detfac_valores CHECK (cantidad > 0 AND precio_unitario >= 0),
    CONSTRAINT fk_detfac_factura FOREIGN KEY (factura_id)
        REFERENCES facturas (id_factura)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    -- Nota: columnas usadas en CHECK no admiten ON UPDATE CASCADE ni SET NULL en MySQL
    CONSTRAINT fk_detfac_servicio FOREIGN KEY (servicio_id)
        REFERENCES servicios (id_servicio)
        ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_detfac_medicamento FOREIGN KEY (medicamento_id)
        REFERENCES medicamentos (id_medicamento)
        ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB;

-- Ahora que existe detalle_factura se puede cerrar la FK del kardex
ALTER TABLE movimientos_inventario
    ADD CONSTRAINT fk_mov_detalle_factura FOREIGN KEY (detalle_factura_id)
        REFERENCES detalle_factura (id_detalle_factura)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- Pagos: permiten abonos parciales y alimentan el cuadre de caja
CREATE TABLE pagos (
    id_pago       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    factura_id    INT UNSIGNED NOT NULL,
    recibido_por  INT UNSIGNED NOT NULL COMMENT 'Cajero / recepcionista que recibió el dinero',
    monto         DECIMAL(10,2) NOT NULL,
    metodo_pago   ENUM('Efectivo','Tarjeta','Transferencia') NOT NULL,
    fecha_pago    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    referencia    VARCHAR(60) NULL COMMENT 'N° de voucher o transferencia',
    PRIMARY KEY (id_pago),
    KEY idx_pagos_factura (factura_id),
    KEY idx_pagos_fecha (fecha_pago),
    KEY idx_pagos_recibido_por (recibido_por, fecha_pago),
    CONSTRAINT chk_pagos_monto CHECK (monto > 0),
    CONSTRAINT fk_pagos_factura FOREIGN KEY (factura_id)
        REFERENCES facturas (id_factura)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pagos_usuario FOREIGN KEY (recibido_por)
        REFERENCES usuarios (id_usuario)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 8. TRIGGERS Y PROCEDIMIENTOS (reglas de negocio)
-- =====================================================================
DELIMITER $$

-- 8.1 Agenda: el asignado debe ser Veterinario y no puede haber cruce de horarios.
--     Las citas canceladas liberan el espacio.
CREATE TRIGGER trg_citas_bi BEFORE INSERT ON citas
FOR EACH ROW
BEGIN
    DECLARE v_rol VARCHAR(20);
    DECLARE v_conflictos INT DEFAULT 0;

    SELECT rol INTO v_rol FROM usuarios WHERE id_usuario = NEW.veterinario_id;
    IF v_rol IS NULL OR v_rol <> 'Veterinario' THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'El usuario asignado a la cita debe tener rol Veterinario';
    END IF;

    IF NEW.estado <> 'Cancelada' THEN
        SELECT COUNT(*) INTO v_conflictos
          FROM citas c
         WHERE c.veterinario_id = NEW.veterinario_id
           AND c.estado <> 'Cancelada'
           AND NEW.fecha_hora < DATE_ADD(c.fecha_hora, INTERVAL c.duracion_min MINUTE)
           AND DATE_ADD(NEW.fecha_hora, INTERVAL NEW.duracion_min MINUTE) > c.fecha_hora;
        IF v_conflictos > 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'El veterinario ya tiene una cita en ese horario';
        END IF;
    END IF;
END$$

CREATE TRIGGER trg_citas_bu BEFORE UPDATE ON citas
FOR EACH ROW
BEGIN
    DECLARE v_rol VARCHAR(20);
    DECLARE v_conflictos INT DEFAULT 0;

    IF NEW.veterinario_id <> OLD.veterinario_id THEN
        SELECT rol INTO v_rol FROM usuarios WHERE id_usuario = NEW.veterinario_id;
        IF v_rol IS NULL OR v_rol <> 'Veterinario' THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'El usuario asignado a la cita debe tener rol Veterinario';
        END IF;
    END IF;

    IF NEW.estado <> 'Cancelada' THEN
        SELECT COUNT(*) INTO v_conflictos
          FROM citas c
         WHERE c.id_cita <> NEW.id_cita
           AND c.veterinario_id = NEW.veterinario_id
           AND c.estado <> 'Cancelada'
           AND NEW.fecha_hora < DATE_ADD(c.fecha_hora, INTERVAL c.duracion_min MINUTE)
           AND DATE_ADD(NEW.fecha_hora, INTERVAL NEW.duracion_min MINUTE) > c.fecha_hora;
        IF v_conflictos > 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'El veterinario ya tiene una cita en ese horario';
        END IF;
    END IF;
END$$

-- 8.2 Vacunación: valida especie y calcula automáticamente el refuerzo.
CREATE TRIGGER trg_vacunacion_bi BEFORE INSERT ON control_vacunacion
FOR EACH ROW
BEGIN
    DECLARE v_intervalo SMALLINT UNSIGNED;
    DECLARE v_esp_vacuna INT UNSIGNED;
    DECLARE v_esp_mascota INT UNSIGNED;

    SELECT intervalo_refuerzo_dias, especie_id
      INTO v_intervalo, v_esp_vacuna
      FROM vacunas WHERE id_vacuna = NEW.vacuna_id;

    SELECT r.especie_id INTO v_esp_mascota
      FROM mascotas m JOIN razas r ON r.id_raza = m.raza_id
     WHERE m.id_mascota = NEW.mascota_id;

    IF v_esp_vacuna <> v_esp_mascota THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'La vacuna no corresponde a la especie de la mascota';
    END IF;

    -- Si no se envía fecha manual, se calcula; si la vacuna es dosis única queda NULL
    IF NEW.fecha_proxima_dosis IS NULL AND v_intervalo IS NOT NULL THEN
        SET NEW.fecha_proxima_dosis = DATE_ADD(NEW.fecha_aplicacion, INTERVAL v_intervalo DAY);
    END IF;
END$$

CREATE TRIGGER trg_vacunacion_bu BEFORE UPDATE ON control_vacunacion
FOR EACH ROW
BEGIN
    DECLARE v_intervalo SMALLINT UNSIGNED;

    IF NEW.fecha_aplicacion <> OLD.fecha_aplicacion OR NEW.vacuna_id <> OLD.vacuna_id THEN
        SELECT intervalo_refuerzo_dias INTO v_intervalo
          FROM vacunas WHERE id_vacuna = NEW.vacuna_id;
        SET NEW.fecha_proxima_dosis =
            IF(v_intervalo IS NULL, NULL, DATE_ADD(NEW.fecha_aplicacion, INTERVAL v_intervalo DAY));
    END IF;
END$$

-- 8.3 Facturación: no se vende más stock del disponible y la venta descuenta el kardex.
CREATE TRIGGER trg_detfac_bi BEFORE INSERT ON detalle_factura
FOR EACH ROW
BEGIN
    DECLARE v_stock INT DEFAULT 0;
    IF NEW.medicamento_id IS NOT NULL THEN
        SELECT COALESCE(SUM(cantidad), 0) INTO v_stock
          FROM movimientos_inventario WHERE medicamento_id = NEW.medicamento_id;
        IF v_stock < NEW.cantidad THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Stock insuficiente para el medicamento solicitado';
        END IF;
    END IF;
END$$

CREATE TRIGGER trg_detfac_ai AFTER INSERT ON detalle_factura
FOR EACH ROW
BEGIN
    DECLARE v_usuario INT UNSIGNED;
    IF NEW.medicamento_id IS NOT NULL THEN
        SELECT emitida_por INTO v_usuario FROM facturas WHERE id_factura = NEW.factura_id;
        INSERT INTO movimientos_inventario
            (medicamento_id, usuario_id, tipo, cantidad, detalle_factura_id, observacion)
        VALUES
            (NEW.medicamento_id, v_usuario, 'Venta', -NEW.cantidad,
             NEW.id_detalle_factura, CONCAT('Venta en factura #', NEW.factura_id));
    END IF;
END$$

-- 8.4 Consulta de disponibilidad para el módulo de agenda
CREATE PROCEDURE sp_verificar_disponibilidad(
    IN  p_veterinario_id INT UNSIGNED,
    IN  p_inicio         DATETIME,
    IN  p_duracion_min   SMALLINT UNSIGNED,
    OUT p_disponible     TINYINT
)
BEGIN
    SELECT COUNT(*) = 0 INTO p_disponible
      FROM citas c
     WHERE c.veterinario_id = p_veterinario_id
       AND c.estado <> 'Cancelada'
       AND p_inicio < DATE_ADD(c.fecha_hora, INTERVAL c.duracion_min MINUTE)
       AND DATE_ADD(p_inicio, INTERVAL p_duracion_min MINUTE) > c.fecha_hora;
END$$

DELIMITER ;

-- =====================================================================
-- 9. VISTAS PARA REPORTES
-- =====================================================================

-- Ficha de pacientes con edad calculada
CREATE VIEW vista_mascotas_detalle AS
SELECT m.id_mascota,
       m.nombre                              AS mascota,
       e.nombre                              AS especie,
       r.nombre                              AS raza,
       m.sexo,
       m.fecha_nacimiento,
       TIMESTAMPDIFF(YEAR,  m.fecha_nacimiento, CURDATE())      AS edad_anios,
       TIMESTAMPDIFF(MONTH, m.fecha_nacimiento, CURDATE()) % 12 AS edad_meses_adicionales,
       p.id_propietario,
       CONCAT(p.nombres, ' ', p.apellidos)   AS propietario,
       p.documento,
       p.telefono
FROM mascotas m
JOIN razas r        ON r.id_raza = m.raza_id
JOIN especies e     ON e.id_especie = r.especie_id
JOIN propietarios p ON p.id_propietario = m.propietario_id;

-- Agenda médica legible
CREATE VIEW vista_agenda AS
SELECT c.id_cita,
       c.fecha_hora,
       DATE_ADD(c.fecha_hora, INTERVAL c.duracion_min MINUTE) AS hora_fin,
       c.estado,
       c.motivo,
       m.nombre                                AS mascota,
       CONCAT(p.nombres, ' ', p.apellidos)     AS propietario,
       p.telefono,
       CONCAT(v.nombre, ' ', v.apellido)       AS veterinario,
       c.veterinario_id
FROM citas c
JOIN mascotas m     ON m.id_mascota = c.mascota_id
JOIN propietarios p ON p.id_propietario = m.propietario_id
JOIN usuarios v     ON v.id_usuario = c.veterinario_id;

-- Stock calculado desde el kardex, con alerta de reposición
CREATE VIEW vista_stock_medicamentos AS
SELECT md.id_medicamento,
       md.codigo,
       md.nombre,
       md.presentacion,
       COALESCE(SUM(mv.cantidad), 0) AS stock_actual,
       md.stock_minimo,
       CASE WHEN COALESCE(SUM(mv.cantidad), 0) <= md.stock_minimo
            THEN 'REPONER' ELSE 'OK' END AS alerta
FROM medicamentos md
LEFT JOIN movimientos_inventario mv ON mv.medicamento_id = md.id_medicamento
WHERE md.activo = 1
GROUP BY md.id_medicamento, md.codigo, md.nombre, md.presentacion, md.stock_minimo;

-- Total facturado, pagado, saldo y estado de pago por factura
CREATE VIEW vista_facturas_resumen AS
SELECT f.id_factura,
       f.numero_factura,
       f.cita_id,
       f.fecha_emision,
       f.estado,
       COALESCE(d.total, 0)                       AS total_facturado,
       COALESCE(p.pagado, 0)                      AS total_pagado,
       COALESCE(d.total, 0) - COALESCE(p.pagado, 0) AS saldo,
       CASE
           WHEN f.estado = 'Anulada' THEN 'Anulada'
           WHEN COALESCE(d.total, 0) > 0 AND COALESCE(p.pagado, 0) >= COALESCE(d.total, 0) THEN 'Pagada'
           WHEN COALESCE(p.pagado, 0) > 0 THEN 'Parcial'
           ELSE 'Pendiente'
       END                                        AS estado_pago
FROM facturas f
LEFT JOIN (SELECT factura_id, SUM(cantidad * precio_unitario) AS total
             FROM detalle_factura GROUP BY factura_id) d ON d.factura_id = f.id_factura
LEFT JOIN (SELECT factura_id, SUM(monto) AS pagado
             FROM pagos GROUP BY factura_id) p ON p.factura_id = f.id_factura;

-- Ingresos por día (solo facturas vigentes), desglosados por método de pago
CREATE VIEW vista_ingresos_diarios AS
SELECT DATE(p.fecha_pago) AS fecha,
       COUNT(*)           AS num_pagos,
       SUM(CASE WHEN p.metodo_pago = 'Efectivo'      THEN p.monto ELSE 0 END) AS efectivo,
       SUM(CASE WHEN p.metodo_pago = 'Tarjeta'       THEN p.monto ELSE 0 END) AS tarjeta,
       SUM(CASE WHEN p.metodo_pago = 'Transferencia' THEN p.monto ELSE 0 END) AS transferencia,
       SUM(p.monto)       AS total_ingresos
FROM pagos p
JOIN facturas f ON f.id_factura = p.factura_id
WHERE f.estado = 'Emitida'
GROUP BY DATE(p.fecha_pago);

-- Ingresos por mes
CREATE VIEW vista_ingresos_mensuales AS
SELECT YEAR(p.fecha_pago)  AS anio,
       MONTH(p.fecha_pago) AS mes,
       COUNT(*)            AS num_pagos,
       SUM(CASE WHEN p.metodo_pago = 'Efectivo'      THEN p.monto ELSE 0 END) AS efectivo,
       SUM(CASE WHEN p.metodo_pago = 'Tarjeta'       THEN p.monto ELSE 0 END) AS tarjeta,
       SUM(CASE WHEN p.metodo_pago = 'Transferencia' THEN p.monto ELSE 0 END) AS transferencia,
       SUM(p.monto)        AS total_ingresos
FROM pagos p
JOIN facturas f ON f.id_factura = p.factura_id
WHERE f.estado = 'Emitida'
GROUP BY YEAR(p.fecha_pago), MONTH(p.fecha_pago);

-- Cuadre de caja: dinero recibido por cada usuario, por día y método
CREATE VIEW vista_cuadre_caja AS
SELECT DATE(p.fecha_pago)               AS fecha,
       p.recibido_por,
       CONCAT(u.nombre, ' ', u.apellido) AS cajero,
       p.metodo_pago,
       COUNT(*)                         AS num_pagos,
       SUM(p.monto)                     AS total_recibido
FROM pagos p
JOIN facturas f ON f.id_factura = p.factura_id
JOIN usuarios u ON u.id_usuario = p.recibido_por
WHERE f.estado = 'Emitida'
GROUP BY DATE(p.fecha_pago), p.recibido_por, u.nombre, u.apellido, p.metodo_pago;

-- Ingresos por concepto (servicio o medicamento) y periodo
CREATE VIEW vista_ingresos_por_concepto AS
SELECT DATE_FORMAT(f.fecha_emision, '%Y-%m') AS periodo,
       CASE WHEN d.servicio_id IS NOT NULL THEN 'Servicio' ELSE 'Medicamento' END AS tipo,
       COALESCE(s.nombre, md.nombre)         AS concepto,
       SUM(d.cantidad)                       AS unidades,
       SUM(d.cantidad * d.precio_unitario)   AS total_facturado
FROM detalle_factura d
JOIN facturas f          ON f.id_factura = d.factura_id
LEFT JOIN servicios s    ON s.id_servicio = d.servicio_id
LEFT JOIN medicamentos md ON md.id_medicamento = d.medicamento_id
WHERE f.estado = 'Emitida'
GROUP BY periodo, tipo, concepto;

-- Refuerzos de vacunas vencidos o próximos (30 días), solo la última aplicación de cada vacuna
CREATE VIEW vista_vacunas_pendientes AS
SELECT cv.id_vacunacion,
       m.id_mascota,
       m.nombre                             AS mascota,
       CONCAT(p.nombres, ' ', p.apellidos)  AS propietario,
       p.telefono,
       v.nombre                             AS vacuna,
       cv.fecha_aplicacion,
       cv.fecha_proxima_dosis,
       DATEDIFF(cv.fecha_proxima_dosis, CURDATE()) AS dias_restantes,
       CASE WHEN cv.fecha_proxima_dosis < CURDATE() THEN 'Vencida' ELSE 'Próxima' END AS estado
FROM control_vacunacion cv
JOIN mascotas m     ON m.id_mascota = cv.mascota_id
JOIN propietarios p ON p.id_propietario = m.propietario_id
JOIN vacunas v      ON v.id_vacuna = cv.vacuna_id
WHERE cv.fecha_proxima_dosis IS NOT NULL
  AND cv.fecha_proxima_dosis <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
  AND NOT EXISTS (
        SELECT 1 FROM control_vacunacion x
         WHERE x.mascota_id = cv.mascota_id
           AND x.vacuna_id  = cv.vacuna_id
           AND x.fecha_aplicacion > cv.fecha_aplicacion);

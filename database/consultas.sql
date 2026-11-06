-- Consultas de la Sesion 6 para clinica_veterinaria.
-- Ejecutar despues de database/schema.sql y database/datos_prueba.sql.
-- Los ejemplos de insercion y eliminacion crean y limpian un propietario,
-- una mascota y una cita de prueba dentro de la misma transaccion.

USE clinica_veterinaria;

START TRANSACTION;

-- 1. INSERT: registrar un propietario de prueba.
-- Cambie el documento si ya existe en propietarios.
INSERT INTO propietarios
    (tipo_documento, documento, nombres, apellidos, telefono, direccion, correo)
VALUES
    ('CC', 'S6-DEMO-0001', 'Prueba', 'Sesion Seis',
     '3000000000', 'Direccion de prueba', 'sesion6@example.test');

SET @propietario_demo = LAST_INSERT_ID();

-- 2. INSERT: registrar una mascota vinculada al propietario y a una raza existente.
-- Requiere que el catalogo tenga al menos una raza (datos_prueba.sql lo incluye).
SET @raza_demo = (
    SELECT id_raza
    FROM razas
    ORDER BY id_raza
    LIMIT 1
);

INSERT INTO mascotas
    (propietario_id, raza_id, nombre, fecha_nacimiento, sexo, color)
VALUES
    (@propietario_demo, @raza_demo, 'Paciente de Prueba',
     '2024-01-15', 'Macho', 'Cafe');

SET @mascota_demo = LAST_INSERT_ID();

-- 3. INSERT: agendar una cita con un veterinario y usuario creador existentes.
-- El trigger valida que el veterinario tenga el rol Veterinario y no tenga
-- otra cita que se cruce con el horario de prueba.
SET @veterinario_demo = (
    SELECT id_usuario
    FROM usuarios
    WHERE rol = 'Veterinario'
      AND activo = 1
    ORDER BY id_usuario
    LIMIT 1
);

SET @creador_demo = (
    SELECT id_usuario
    FROM usuarios
    WHERE activo = 1
    ORDER BY id_usuario
    LIMIT 1
);

INSERT INTO citas
    (mascota_id, veterinario_id, creada_por, fecha_hora, duracion_min, motivo, estado)
VALUES
    (@mascota_demo, @veterinario_demo, @creador_demo,
     DATE_ADD(NOW(), INTERVAL 30 DAY), 30, 'Cita de prueba Sesion 6', 'Programada');

SET @cita_demo = LAST_INSERT_ID();

-- 4. SELECT con WHERE, LIKE y ORDER BY: buscar propietarios por nombre o apellido.
SELECT id_propietario, nombres, apellidos, telefono, correo
FROM propietarios
WHERE nombres LIKE '%Sofi%'
   OR apellidos LIKE '%Ramirez%'
ORDER BY apellidos, nombres;

-- 5. SELECT con WHERE, LIKE y ORDER BY: buscar mascotas por nombre.
SELECT id_mascota, nombre, propietario_id, raza_id, sexo
FROM mascotas
WHERE nombre LIKE '%Lun%'
ORDER BY nombre;

-- 6. UPDATE: cambiar el estado de la cita de prueba.
-- La condicion WHERE restringe la modificacion a la cita recien insertada.
UPDATE citas
SET estado = 'Cancelada',
    observaciones = 'Cancelacion de prueba controlada'
WHERE id_cita = @cita_demo
  AND estado = 'Programada';

-- 7. JOIN: mostrar la agenda con nombres de mascota, cliente y veterinario.
SELECT
    c.id_cita,
    c.fecha_hora,
    c.estado,
    c.motivo,
    m.nombre AS mascota,
    CONCAT(p.nombres, ' ', p.apellidos) AS propietario,
    CONCAT(v.nombre, ' ', v.apellido) AS veterinario
FROM citas AS c
INNER JOIN mascotas AS m
    ON m.id_mascota = c.mascota_id
INNER JOIN propietarios AS p
    ON p.id_propietario = m.propietario_id
INNER JOIN usuarios AS v
    ON v.id_usuario = c.veterinario_id
WHERE c.fecha_hora >= NOW()
ORDER BY c.fecha_hora;

-- 8. COUNT y GROUP BY: total de citas por veterinario, incluyendo quienes
-- aun no tienen citas. Se excluyen las citas canceladas.
SELECT
    u.id_usuario AS id_veterinario,
    CONCAT(u.nombre, ' ', u.apellido) AS veterinario,
    COUNT(c.id_cita) AS total_citas
FROM usuarios AS u
LEFT JOIN citas AS c
    ON c.veterinario_id = u.id_usuario
   AND c.estado <> 'Cancelada'
WHERE u.rol = 'Veterinario'
GROUP BY u.id_usuario, u.nombre, u.apellido
ORDER BY total_citas DESC, veterinario;

-- 9. GROUP BY y HAVING: clientes recurrentes con mas de 3 citas no canceladas.
SELECT
    p.id_propietario,
    CONCAT(p.nombres, ' ', p.apellidos) AS propietario,
    COUNT(c.id_cita) AS total_citas
FROM propietarios AS p
INNER JOIN mascotas AS m
    ON m.propietario_id = p.id_propietario
INNER JOIN citas AS c
    ON c.mascota_id = m.id_mascota
WHERE c.estado <> 'Cancelada'
GROUP BY p.id_propietario, p.nombres, p.apellidos
HAVING COUNT(c.id_cita) > 3
ORDER BY total_citas DESC, propietario;

-- 10. SUM, COUNT, GROUP BY y HAVING: ingresos facturados por mes.
-- El total se calcula a partir del detalle de factura para no duplicar
-- importes cuando una factura tenga varios pagos.
SELECT
    YEAR(f.fecha_emision) AS anio,
    MONTH(f.fecha_emision) AS mes,
    COUNT(DISTINCT f.id_factura) AS facturas_emitidas,
    SUM(df.cantidad * df.precio_unitario) AS total_facturado
FROM facturas AS f
INNER JOIN detalle_factura AS df
    ON df.factura_id = f.id_factura
WHERE f.estado = 'Emitida'
GROUP BY YEAR(f.fecha_emision), MONTH(f.fecha_emision)
HAVING SUM(df.cantidad * df.precio_unitario) > 0
ORDER BY anio, mes;

-- 11. SUM, COUNT y GROUP BY: recaudo recibido por mes y metodo de pago.
SELECT
    YEAR(p.fecha_pago) AS anio,
    MONTH(p.fecha_pago) AS mes,
    p.metodo_pago,
    COUNT(p.id_pago) AS numero_pagos,
    SUM(p.monto) AS total_recaudado
FROM pagos AS p
INNER JOIN facturas AS f
    ON f.id_factura = p.factura_id
WHERE f.estado = 'Emitida'
GROUP BY YEAR(p.fecha_pago), MONTH(p.fecha_pago), p.metodo_pago
ORDER BY anio, mes, p.metodo_pago;

-- 12. DELETE controlado: eliminar solo la cita de prueba creada arriba.
-- Se borra antes la mascota y el propietario para mantener la integridad
-- referencial. El resto de los datos de la clinica no se modifica.
DELETE FROM citas
WHERE id_cita = @cita_demo
  AND estado = 'Cancelada';

DELETE FROM mascotas
WHERE id_mascota = @mascota_demo
  AND propietario_id = @propietario_demo;

DELETE FROM propietarios
WHERE id_propietario = @propietario_demo
  AND documento = 'S6-DEMO-0001';

COMMIT;

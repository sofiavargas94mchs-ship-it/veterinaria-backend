-- Datos de prueba para clinica_veterinaria.
-- Ejecutar despues de database/schema.sql.
-- Todos los usuarios comparten un hash bcrypt de demostracion; reemplazarlo
-- por hashes generados por la aplicacion antes de usar datos reales.

USE clinica_veterinaria;

START TRANSACTION;

INSERT INTO usuarios
    (id_usuario, nombre, apellido, correo, password_hash, rol, registro_profesional)
VALUES
    (1, 'Camila', 'Torres', 'admin@veterinaria.local',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'Administrador', NULL),
    (2, 'Laura', 'Mendez', 'recepcion@veterinaria.local',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'Recepcionista', NULL),
    (3, 'Andres', 'Lopez', 'andres.lopez@veterinaria.local',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'Veterinario', 'VET-1001'),
    (4, 'Valentina', 'Gomez', 'valentina.gomez@veterinaria.local',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'Veterinario', 'VET-1002'),
    (5, 'Mateo', 'Ruiz', 'mateo.ruiz@veterinaria.local',
     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.', 'Veterinario', 'VET-1003');

INSERT INTO propietarios
    (id_propietario, tipo_documento, documento, nombres, apellidos, telefono, direccion, correo)
VALUES
    (1, 'CC', '1020304050', 'Sofia', 'Ramirez', '3001112233', 'Calle 10 # 20-30', 'sofia.ramirez@example.com'),
    (2, 'CC', '1020304051', 'Daniel', 'Castro', '3002223344', 'Carrera 15 # 8-21', 'daniel.castro@example.com'),
    (3, 'CC', '1020304052', 'Mariana', 'Vargas', '3003334455', 'Calle 42 # 12-09', 'mariana.vargas@example.com'),
    (4, 'CE', 'CE778899', 'Juan', 'Herrera', '3004445566', 'Avenida 6 # 31-18', 'juan.herrera@example.com'),
    (5, 'CC', '1020304054', 'Isabella', 'Moreno', '3005556677', 'Calle 7 # 44-02', 'isabella.moreno@example.com');

INSERT INTO especies (id_especie, nombre)
VALUES
    (1, 'Canino'),
    (2, 'Felino'),
    (3, 'Conejo'),
    (4, 'Ave'),
    (5, 'Cobayo');

INSERT INTO razas (id_raza, especie_id, nombre)
VALUES
    (1, 1, 'Labrador Retriever'),
    (2, 2, 'Siames'),
    (3, 3, 'Mini Rex'),
    (4, 4, 'Periquito Australiano'),
    (5, 5, 'Americano');

INSERT INTO mascotas
    (id_mascota, propietario_id, raza_id, nombre, fecha_nacimiento, sexo, color, esterilizado, microchip)
VALUES
    (1, 1, 1, 'Max', '2021-03-15', 'Macho', 'Dorado', 1, 'MC-000001'),
    (2, 2, 2, 'Luna', '2022-07-02', 'Hembra', 'Crema', 1, 'MC-000002'),
    (3, 3, 3, 'Nube', '2023-01-20', 'Hembra', 'Blanco', 0, 'MC-000003'),
    (4, 4, 4, 'Kiwi', '2024-05-10', 'Macho', 'Verde', 0, 'MC-000004'),
    (5, 5, 5, 'Canela', '2023-09-08', 'Hembra', 'Marron', 0, 'MC-000005');

-- Las primeras cinco citas respaldan las historias y facturas.
-- Las cinco restantes permiten probar la agenda futura.
INSERT INTO citas
    (id_cita, mascota_id, veterinario_id, creada_por, fecha_hora, duracion_min, motivo, estado, observaciones)
VALUES
    (1, 1, 3, 2, '2026-10-20 09:00:00', 30, 'Control general', 'Completada', 'Consulta de control'),
    (2, 2, 4, 2, '2026-10-20 10:00:00', 30, 'Vacunacion anual', 'Completada', 'Refuerzo anual'),
    (3, 3, 5, 2, '2026-10-20 11:00:00', 30, 'Revision digestiva', 'Completada', 'Evaluacion y tratamiento'),
    (4, 4, 3, 2, '2026-10-20 12:00:00', 30, 'Revision preventiva', 'Completada', 'Paciente estable'),
    (5, 5, 4, 2, '2026-10-20 13:00:00', 30, 'Control de peso', 'Completada', 'Control nutricional'),
    (6, 1, 3, 2, '2026-11-07 09:00:00', 30, 'Seguimiento', 'Programada', NULL),
    (7, 2, 4, 2, '2026-11-07 10:00:00', 30, 'Valoracion dental', 'Programada', NULL),
    (8, 3, 5, 2, '2026-11-07 11:00:00', 30, 'Control de rutina', 'Programada', NULL),
    (9, 4, 3, 2, '2026-11-08 09:00:00', 30, 'Revision preventiva', 'Programada', NULL),
    (10, 5, 4, 2, '2026-11-08 10:00:00', 30, 'Seguimiento nutricional', 'Programada', NULL);

INSERT INTO medicamentos
    (id_medicamento, codigo, nombre, principio_activo, presentacion, stock_minimo, precio_compra, precio_venta)
VALUES
    (1, 'MED-001', 'Antiparasitario canino', 'Fenbendazol', 'Tableta 500 mg', 5, 8000.00, 12000.00),
    (2, 'MED-002', 'Antibiotico veterinario', 'Amoxicilina', 'Capsula 250 mg', 5, 5000.00, 8500.00),
    (3, 'MED-003', 'Analgesico veterinario', 'Meloxicam', 'Frasco 10 ml', 3, 12000.00, 18000.00),
    (4, 'MED-004', 'Suplemento para aves', 'Vitaminas A-D-E', 'Frasco 20 ml', 3, 9000.00, 14000.00),
    (5, 'MED-005', 'Probiótico veterinario', 'Lactobacillus', 'Sobre 2 g', 5, 3000.00, 5000.00);

-- Entradas iniciales de inventario; el stock se deriva del kardex.
INSERT INTO movimientos_inventario
    (id_movimiento, medicamento_id, usuario_id, tipo, cantidad, lote, fecha_vencimiento, fecha_movimiento, observacion)
VALUES
    (1, 1, 1, 'Compra', 30, 'L-ANT-001', '2027-12-31', '2026-10-01 08:00:00', 'Inventario inicial'),
    (2, 2, 1, 'Compra', 25, 'L-AMX-002', '2027-09-30', '2026-10-01 08:10:00', 'Inventario inicial'),
    (3, 3, 1, 'Compra', 20, 'L-MEL-003', '2027-06-30', '2026-10-01 08:20:00', 'Inventario inicial'),
    (4, 4, 1, 'Compra', 15, 'L-VIT-004', '2027-11-30', '2026-10-01 08:30:00', 'Inventario inicial'),
    (5, 5, 1, 'Compra', 40, 'L-PRO-005', '2027-08-31', '2026-10-01 08:40:00', 'Inventario inicial');

INSERT INTO historias_clinicas
    (id_historia, mascota_id, veterinario_id, cita_id, fecha_atencion, peso_kg, temperatura_c, sintomas, diagnostico, tratamiento, observaciones, proxima_revision)
VALUES
    (1, 1, 3, 1, '2026-10-20 09:30:00', 28.40, 38.5, 'Buen apetito y actividad normal', 'Paciente sano', 'Continuar plan preventivo', 'Sin hallazgos relevantes', '2027-04-20'),
    (2, 2, 4, 2, '2026-10-20 10:30:00', 4.20, 38.7, 'Sin sintomas; acude por refuerzo', 'Paciente sano', 'Aplicar vacuna triple felina', 'Buena respuesta a vacunacion previa', '2027-10-20'),
    (3, 3, 5, 3, '2026-10-20 11:30:00', 2.10, 39.0, 'Disminucion leve del apetito', 'Irritacion digestiva leve', 'Dieta indicada y probiotico', 'Revisar evolucion en una semana', '2026-10-27'),
    (4, 4, 3, 4, '2026-10-20 12:30:00', 0.04, 41.0, 'Sin cambios de comportamiento', 'Paciente estable', 'Continuar suplementacion', 'Examen general sin alteraciones', '2027-04-20'),
    (5, 5, 4, 5, '2026-10-20 13:30:00', 0.85, 38.2, 'Peso estable y actividad normal', 'Paciente sano', 'Mantener dieta actual', 'Control de rutina', '2027-04-20');

INSERT INTO detalle_formula
    (id_detalle_formula, historia_id, medicamento_id, cantidad, dosis, frecuencia, duracion_dias, indicaciones)
VALUES
    (1, 1, 1, 1, '1 tableta', 'Una vez', 1, 'Administrar con alimento'),
    (2, 2, 2, 5, '1 capsula', 'Cada 12 horas', 5, 'Administrar segun indicacion veterinaria'),
    (3, 3, 5, 7, '1 sobre', 'Una vez al dia', 7, 'Mezclar con el alimento'),
    (4, 4, 4, 1, '5 gotas', 'Una vez al dia', 10, 'Diluir en agua fresca'),
    (5, 5, 3, 1, '0.2 ml', 'Una vez al dia', 3, 'Administrar por via oral');

INSERT INTO vacunas
    (id_vacuna, especie_id, nombre, descripcion, intervalo_refuerzo_dias)
VALUES
    (1, 1, 'Rabia canina', 'Vacuna antirrabica para perros', 365),
    (2, 2, 'Triple felina', 'Proteccion para enfermedades felinas comunes', 365),
    (3, 3, 'Mixomatosis', 'Vacuna preventiva para conejos', 365),
    (4, 4, 'Poliomavirus aviar', 'Vacuna preventiva para aves', 365),
    (5, 5, 'Bordetella', 'Vacuna preventiva para cobayos', 365);

INSERT INTO control_vacunacion
    (id_vacunacion, mascota_id, vacuna_id, veterinario_id, historia_id, fecha_aplicacion, lote, observaciones)
VALUES
    (1, 1, 1, 3, 1, '2026-10-20', 'VAC-CAN-101', 'Aplicacion sin reaccion adversa'),
    (2, 2, 2, 4, 2, '2026-10-20', 'VAC-FEL-102', 'Refuerzo anual aplicado'),
    (3, 3, 3, 5, 3, '2026-10-20', 'VAC-CON-103', 'Paciente tolero bien la dosis'),
    (4, 4, 4, 3, 4, '2026-10-20', 'VAC-AVE-104', 'Aplicacion preventiva'),
    (5, 5, 5, 4, 5, '2026-10-20', 'VAC-COB-105', 'Proximo control segun calendario');

INSERT INTO servicios (id_servicio, codigo, nombre, descripcion, precio)
VALUES
    (1, 'SER-001', 'Consulta general', 'Evaluacion clinica general', 45000.00),
    (2, 'SER-002', 'Vacunacion', 'Aplicacion de vacuna', 30000.00),
    (3, 'SER-003', 'Control de laboratorio', 'Toma y revision de muestra', 55000.00),
    (4, 'SER-004', 'Desparasitacion', 'Servicio de desparasitacion', 25000.00),
    (5, 'SER-005', 'Consulta de seguimiento', 'Revision de evolucion clinica', 35000.00);

INSERT INTO facturas
    (id_factura, numero_factura, cita_id, emitida_por, fecha_emision, estado, observaciones)
VALUES
    (1, 'FV-2026-0001', 1, 2, '2026-10-20 09:45:00', 'Emitida', 'Consulta general'),
    (2, 'FV-2026-0002', 2, 2, '2026-10-20 10:45:00', 'Emitida', 'Vacunacion anual'),
    (3, 'FV-2026-0003', 3, 2, '2026-10-20 11:45:00', 'Emitida', 'Consulta y seguimiento'),
    (4, 'FV-2026-0004', 4, 2, '2026-10-20 12:45:00', 'Emitida', 'Revision preventiva'),
    (5, 'FV-2026-0005', 5, 2, '2026-10-20 13:45:00', 'Emitida', 'Control nutricional');

INSERT INTO detalle_factura
    (id_detalle_factura, factura_id, servicio_id, medicamento_id, cantidad, precio_unitario)
VALUES
    (1, 1, 1, NULL, 1, 45000.00),
    (2, 2, 2, NULL, 1, 30000.00),
    (3, 3, 3, NULL, 1, 55000.00),
    (4, 4, 4, NULL, 1, 25000.00),
    (5, 5, 5, NULL, 1, 35000.00);

INSERT INTO pagos
    (id_pago, factura_id, recibido_por, monto, metodo_pago, fecha_pago, referencia)
VALUES
    (1, 1, 2, 45000.00, 'Efectivo', '2026-10-20 09:50:00', NULL),
    (2, 2, 2, 30000.00, 'Tarjeta', '2026-10-20 10:50:00', 'POS-2002'),
    (3, 3, 2, 55000.00, 'Transferencia', '2026-10-20 11:50:00', 'TRF-3003'),
    (4, 4, 2, 25000.00, 'Efectivo', '2026-10-20 12:50:00', NULL),
    (5, 5, 2, 35000.00, 'Tarjeta', '2026-10-20 13:50:00', 'POS-2005');

COMMIT;

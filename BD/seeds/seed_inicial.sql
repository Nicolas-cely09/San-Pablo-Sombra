-- Seed inicial para San Pablo (roles + admin + profesional + paciente)
-- Contrasena en texto plano de cuentas semilla: 123456789

-- Este seed asume que el catalogo de roles ya existe con:
-- 1 = ADMINISTRADOR
-- 2 = PROFESIONAL SOMBRA

ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS documento_identidad VARCHAR(30) UNIQUE AFTER rol_id;

INSERT INTO usuarios (rol_id, documento_identidad, nombre, apellido, email, password_hash, telefono, estado)
SELECT 1, '1069769579', 'Nicolas', 'Cely', 'admin@sanpablo.local', '$2y$10$rZlDYDDcemzkKr3oPVH5d.BHwimydunho4CFqL5vfZu/PzVaR3eDi', '3001234567', 'activo'
WHERE EXISTS (SELECT 1 FROM roles r WHERE r.id = 1 AND r.nombre = 'ADMINISTRADOR')
ON DUPLICATE KEY UPDATE
    rol_id = VALUES(rol_id),
    documento_identidad = VALUES(documento_identidad),
    nombre = VALUES(nombre),
    apellido = VALUES(apellido),
    password_hash = VALUES(password_hash),
    telefono = VALUES(telefono),
    estado = VALUES(estado);

INSERT INTO usuarios (rol_id, documento_identidad, nombre, apellido, email, password_hash, telefono, estado)
SELECT 2, '1098765432', 'Laura', 'Perez', 'laura.perez@sanpablo.local', '$2y$10$rZlDYDDcemzkKr3oPVH5d.BHwimydunho4CFqL5vfZu/PzVaR3eDi', '3009876543', 'activo'
WHERE EXISTS (SELECT 1 FROM roles r WHERE r.id = 2 AND r.nombre = 'PROFESIONAL SOMBRA')
ON DUPLICATE KEY UPDATE
    rol_id = VALUES(rol_id),
    documento_identidad = VALUES(documento_identidad),
    nombre = VALUES(nombre),
    apellido = VALUES(apellido),
    password_hash = VALUES(password_hash),
    telefono = VALUES(telefono),
    estado = VALUES(estado);

INSERT INTO pacientes (
    documento_identidad,
    nombre,
    apellido,
    fecha_nacimiento,
    nombre_acudiente,
    contacto_acudiente,
    observaciones_iniciales,
    estado
)
SELECT 'PAC-PRUEBA-001', 'Samuel', 'Prueba', '2013-05-10', 'Marta Rojas', '3002223344', 'Paciente de prueba inicial para validar flujo Plan Sombra.', 'activo'
WHERE NOT EXISTS (
    SELECT 1 FROM pacientes p WHERE p.documento_identidad = 'PAC-PRUEBA-001'
);

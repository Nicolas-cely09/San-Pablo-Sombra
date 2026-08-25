-- Migracion: agregar documento_identidad a usuarios existentes
-- Ejecutar sobre la base sanpablo_bd

USE sanpablo_bd;

ALTER TABLE usuarios
ADD COLUMN IF NOT EXISTS documento_identidad VARCHAR(30) UNIQUE AFTER rol_id;

-- Datos iniciales para login por documento
UPDATE usuarios
SET documento_identidad = '1069769579'
WHERE email = 'admin@sanpablo.local'
  AND (documento_identidad IS NULL OR documento_identidad = '');

UPDATE usuarios
SET documento_identidad = '1098765432'
WHERE email = 'laura.perez@sanpablo.local'
  AND (documento_identidad IS NULL OR documento_identidad = '');

-- Verificacion rapida
SELECT id, rol_id, documento_identidad, nombre, apellido, email
FROM usuarios
ORDER BY id;

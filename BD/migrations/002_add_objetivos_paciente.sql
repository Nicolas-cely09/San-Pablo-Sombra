-- Migracion: objetivos de tratamiento por paciente
-- Ejecutar sobre la base sanpablo_bd

USE sanpablo_bd;

ALTER TABLE pacientes
ADD COLUMN IF NOT EXISTS objetivo_general TEXT NULL AFTER observaciones_iniciales;

CREATE TABLE IF NOT EXISTS paciente_objetivos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    codigo VARCHAR(20) NOT NULL,
    descripcion TEXT NOT NULL,
    frecuencia VARCHAR(30) NOT NULL DEFAULT 'Semanal',
    estado ENUM('pendiente', 'cumplido', 'no_cumplido') NOT NULL DEFAULT 'pendiente',
    observacion TEXT NULL,
    fecha_evaluacion DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_paciente_objetivo_codigo (paciente_id, codigo),
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
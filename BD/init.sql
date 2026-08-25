CREATE DATABASE IF NOT EXISTS sanpablo_bd;

USE sanpablo_bd;

-- 1. Roles Table (Simplified)
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- Catalogo base de roles del sistema
INSERT INTO roles (id, nombre)
VALUES
    (1, 'ADMINISTRADOR'),
    (2, 'PROFESIONAL SOMBRA')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- 2. Users Table
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol_id INT NOT NULL,
    documento_identidad VARCHAR(30) UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    telefono VARCHAR(20),
    estado ENUM('activo', 'inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 3. Patients Table
CREATE TABLE IF NOT EXISTS pacientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    documento_identidad VARCHAR(30) UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    nombre_acudiente VARCHAR(150),
    contacto_acudiente VARCHAR(50),
    observaciones_iniciales TEXT,
    objetivo_general TEXT,
    estado ENUM('activo', 'inactivo', 'finalizado') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. Shadow Plan Assignments Table (Linking Professionals with Patients)
CREATE TABLE IF NOT EXISTS asignaciones_plan_sombra (
    id INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id INT NOT NULL,
    profesional_id INT NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NULL,
    estado ENUM('activo', 'pausado', 'finalizado') DEFAULT 'activo',
    objetivos_plan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (profesional_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 5. Daily Reports / Clinical History Table
CREATE TABLE IF NOT EXISTS historias_clinicas_reportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asignacion_id INT NOT NULL,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    resumen_jornada TEXT NOT NULL,         -- Actividades desarrolladas durante el acompañamiento
    comportamiento_observado TEXT,       -- Conducta, crisis, logros o evoluciones del día
    novedades_alertas TEXT,              -- Notas de atención especial
    FOREIGN KEY (asignacion_id) REFERENCES asignaciones_plan_sombra(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 3.1 Treatment objectives by patient
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

-- 2.1 User attachments
CREATE TABLE IF NOT EXISTS usuario_adjuntos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo VARCHAR(80) NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
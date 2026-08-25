-- Migracion: documentos y fotografias de profesionales
-- Ejecutar sobre la base sanpablo_bd

USE sanpablo_bd;

CREATE TABLE IF NOT EXISTS usuario_adjuntos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo VARCHAR(80) NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
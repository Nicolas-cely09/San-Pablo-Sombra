-- Migracion: estructura de bitacora diaria
-- Ejecutar sobre la base sanpablo_bd

USE sanpablo_bd;

ALTER TABLE historias_clinicas_reportes
    ADD COLUMN fecha_bitacora DATE NULL AFTER fecha_registro,
    ADD COLUMN grado VARCHAR(100) NULL AFTER fecha_bitacora,
    ADD COLUMN nivel_participacion VARCHAR(20) NULL AFTER resumen_jornada,
    ADD COLUMN descripcion_participacion TEXT NULL AFTER nivel_participacion,
    ADD COLUMN apoyos_brindados TEXT NULL AFTER descripcion_participacion,
    ADD COLUMN avances_logros TEXT NULL AFTER apoyos_brindados,
    ADD COLUMN dificultades_observadas TEXT NULL AFTER avances_logros,
    ADD COLUMN observaciones TEXT NULL AFTER dificultades_observadas,
    ADD COLUMN firma_digital VARCHAR(255) NULL AFTER observaciones;
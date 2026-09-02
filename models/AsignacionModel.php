<?php

declare(strict_types=1);

final class AsignacionModel
{
    public function __construct(private PDO $db)
    {
    }

    public function listarPorProfesional(int $profesionalId): array
    {
        $sql = '
            SELECT
                aps.id,
                aps.paciente_id,
                aps.profesional_id,
                aps.estado,
                p.documento_identidad AS paciente_documento,
                p.nombre AS paciente_nombre,
                p.apellido AS paciente_apellido,
                p.estado AS paciente_estado,
                p.colegio AS paciente_colegio,
                p.nombre_acudiente AS paciente_acudiente,
                p.contacto_acudiente AS paciente_contacto,
                TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS paciente_edad,
                CONCAT(u.nombre, " ", u.apellido) AS profesional_nombre
            FROM asignaciones_plan_sombra aps
            INNER JOIN pacientes p ON p.id = aps.paciente_id
            INNER JOIN usuarios u ON u.id = aps.profesional_id
            WHERE aps.profesional_id = :profesional_id
            ORDER BY aps.created_at DESC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['profesional_id' => $profesionalId]);

        return $stmt->fetchAll();
    }

    public function crearInforme(int $profesionalId, array $data): bool
    {
        $this->asegurarEstructura();

        $asignacionId = (int) ($data['asignacion_id'] ?? 0);

        if ($asignacionId <= 0) {
            throw new InvalidArgumentException('Asignacion invalida para crear informe.');
        }

        if (!$this->asignacionPerteneceAProfesional($asignacionId, $profesionalId)) {
            throw new RuntimeException('No puedes crear informes sobre asignaciones de otro profesional.');
        }

        $sql = '
            INSERT INTO historias_clinicas_reportes (
                asignacion_id,
                fecha_bitacora,
                grado,
                resumen_jornada,
                nivel_participacion,
                descripcion_participacion,
                apoyos_brindados,
                avances_logros,
                dificultades_observadas,
                observaciones,
                firma_digital,
                comportamiento_observado,
                novedades_alertas,
                manejo_brindado
            ) VALUES (
                :asignacion_id,
                :fecha_bitacora,
                :grado,
                :resumen_jornada,
                :nivel_participacion,
                :descripcion_participacion,
                :apoyos_brindados,
                :avances_logros,
                :dificultades_observadas,
                :observaciones,
                :firma_digital,
                :comportamiento_observado,
                :novedades_alertas,
                :manejo_brindado
            )
        ';

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'asignacion_id' => $asignacionId,
            'fecha_bitacora' => trim((string) ($data['fecha_bitacora'] ?? date('Y-m-d'))),
            'grado' => trim((string) ($data['grado'] ?? '')),
            'resumen_jornada' => trim((string) ($data['resumen_jornada'] ?? '')),
            'nivel_participacion' => trim((string) ($data['nivel_participacion'] ?? '')),
            'descripcion_participacion' => trim((string) ($data['descripcion_participacion'] ?? '')),
            'apoyos_brindados' => trim((string) ($data['apoyos_brindados'] ?? '')),
            'avances_logros' => trim((string) ($data['avances_logros'] ?? '')),
            'dificultades_observadas' => trim((string) ($data['dificultades_observadas'] ?? '')),
            'observaciones' => trim((string) ($data['observaciones'] ?? '')),
            'firma_digital' => trim((string) ($data['firma_digital'] ?? '')),
            'comportamiento_observado' => trim((string) ($data['comportamiento_observado'] ?? '')),
            'novedades_alertas' => trim((string) ($data['novedades_alertas'] ?? '')),
            'manejo_brindado' => trim((string) ($data['manejo_brindado'] ?? '')),
        ]);
    }

    public function listarInformesPorProfesional(int $profesionalId): array
    {
        $this->asegurarEstructura();

        $sql = '
            SELECT
                hcr.id,
                hcr.asignacion_id,
                hcr.fecha_registro,
                hcr.fecha_bitacora,
                hcr.grado,
                hcr.resumen_jornada,
                hcr.nivel_participacion,
                hcr.descripcion_participacion,
                hcr.apoyos_brindados,
                hcr.avances_logros,
                hcr.dificultades_observadas,
                hcr.observaciones,
                hcr.firma_digital,
                hcr.comportamiento_observado,
                hcr.novedades_alertas,
                hcr.manejo_brindado,
                p.documento_identidad AS paciente_documento,
                CONCAT(p.nombre, " ", p.apellido) AS paciente_nombre
            FROM historias_clinicas_reportes hcr
            INNER JOIN asignaciones_plan_sombra aps ON aps.id = hcr.asignacion_id
            INNER JOIN pacientes p ON p.id = aps.paciente_id
            WHERE aps.profesional_id = :profesional_id
            ORDER BY hcr.fecha_registro DESC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['profesional_id' => $profesionalId]);

        return $stmt->fetchAll();
    }

    public function listarPorPaciente(int $pacienteId): array
    {
        $this->asegurarEstructura();

        $sql = '
            SELECT
                hcr.id,
                hcr.fecha_registro,
                hcr.fecha_bitacora,
                hcr.grado,
                hcr.resumen_jornada,
                hcr.nivel_participacion,
                hcr.descripcion_participacion,
                hcr.apoyos_brindados,
                hcr.avances_logros,
                hcr.dificultades_observadas,
                hcr.observaciones,
                hcr.firma_digital,
                hcr.comportamiento_observado,
                hcr.novedades_alertas,
                hcr.manejo_brindado,
                CONCAT(u.nombre, " ", u.apellido) AS profesional_nombre
            FROM historias_clinicas_reportes hcr
            INNER JOIN asignaciones_plan_sombra aps ON aps.id = hcr.asignacion_id
            INNER JOIN usuarios u ON u.id = aps.profesional_id
            WHERE aps.paciente_id = :paciente_id
            ORDER BY hcr.fecha_registro DESC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['paciente_id' => $pacienteId]);

        return $stmt->fetchAll();
    }

    public function crear(array $data): bool
    {
        $this->validarPacienteSinAsignacionActiva((int) $data['paciente_id']);

        $sql = '
            INSERT INTO asignaciones_plan_sombra (
                paciente_id,
                profesional_id,
                fecha_inicio,
                fecha_fin,
                estado,
                objetivos_plan
            ) VALUES (
                :paciente_id,
                :profesional_id,
                :fecha_inicio,
                :fecha_fin,
                :estado,
                :objetivos_plan
            )
        ';

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'paciente_id' => (int) $data['paciente_id'],
            'profesional_id' => (int) $data['profesional_id'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'] !== '' ? $data['fecha_fin'] : null,
            'estado' => $data['estado'] ?? 'activo',
            'objetivos_plan' => trim($data['objetivos_plan'] ?? ''),
        ]);
    }

    public function listar(): array
    {
        $sql = '
            SELECT
                aps.id,
                aps.fecha_inicio,
                aps.fecha_fin,
                aps.estado,
                aps.objetivos_plan,
                p.documento_identidad AS paciente_documento,
                CONCAT(p.nombre, " ", p.apellido) AS paciente_nombre,
                u.documento_identidad AS profesional_documento,
                CONCAT(u.nombre, " ", u.apellido) AS profesional_nombre
            FROM asignaciones_plan_sombra aps
            INNER JOIN pacientes p ON p.id = aps.paciente_id
            INNER JOIN usuarios u ON u.id = aps.profesional_id
            ORDER BY aps.created_at DESC
        ';

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    private function asegurarEstructura(): void
    {
        $this->asegurarColumna('historias_clinicas_reportes', 'fecha_bitacora', 'DATE NULL DEFAULT NULL');
        $this->asegurarColumna('historias_clinicas_reportes', 'grado', 'VARCHAR(100) NULL DEFAULT NULL');
        $this->asegurarColumna('historias_clinicas_reportes', 'nivel_participacion', 'VARCHAR(20) NULL DEFAULT NULL');
        $this->asegurarColumna('historias_clinicas_reportes', 'descripcion_participacion', 'TEXT NULL DEFAULT NULL');
        $this->asegurarColumna('historias_clinicas_reportes', 'apoyos_brindados', 'TEXT NULL DEFAULT NULL');
        $this->asegurarColumna('historias_clinicas_reportes', 'avances_logros', 'TEXT NULL DEFAULT NULL');
        $this->asegurarColumna('historias_clinicas_reportes', 'dificultades_observadas', 'TEXT NULL DEFAULT NULL');
        $this->asegurarColumna('historias_clinicas_reportes', 'observaciones', 'TEXT NULL DEFAULT NULL');
        $this->asegurarColumna('historias_clinicas_reportes', 'firma_digital', 'VARCHAR(255) NULL DEFAULT NULL');
        $this->asegurarColumna('historias_clinicas_reportes', 'manejo_brindado', 'TEXT NULL DEFAULT NULL');
    }

    private function asegurarColumna(string $table, string $column, string $definition): void
    {
        $sqlExiste = '
            SELECT COUNT(*) AS total
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = :table
              AND column_name = :column
        ';

        $stmt = $this->db->prepare($sqlExiste);
        $stmt->execute(['table' => $table, 'column' => $column]);
        $resultado = $stmt->fetch();
        $existe = ((int) ($resultado['total'] ?? 0)) > 0;

        if ($existe) {
            return;
        }

        $this->db->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
    }

    private function validarPacienteSinAsignacionActiva(int $pacienteId): void
    {
        $sql = '
            SELECT COUNT(*) AS total
            FROM asignaciones_plan_sombra
            WHERE paciente_id = :paciente_id
              AND estado = :estado
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'paciente_id' => $pacienteId,
            'estado' => 'activo',
        ]);

        $total = (int) (($stmt->fetch()['total'] ?? 0));

        if ($total > 0) {
            throw new RuntimeException('Este paciente ya tiene una asignacion activa.');
        }
    }

    private function asignacionPerteneceAProfesional(int $asignacionId, int $profesionalId): bool
    {
        $sql = '
            SELECT COUNT(*) AS total
            FROM asignaciones_plan_sombra
            WHERE id = :asignacion_id
              AND profesional_id = :profesional_id
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'asignacion_id' => $asignacionId,
            'profesional_id' => $profesionalId,
        ]);

        return ((int) (($stmt->fetch()['total'] ?? 0))) > 0;
    }

    public function pacienteAsignadoAProfesional(int $pacienteId, int $profesionalId): bool
    {
        $sql = '
            SELECT COUNT(*) AS total
            FROM asignaciones_plan_sombra
            WHERE paciente_id = :paciente_id
              AND profesional_id = :profesional_id
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'paciente_id' => $pacienteId,
            'profesional_id' => $profesionalId,
        ]);

        return ((int) (($stmt->fetch()['total'] ?? 0))) > 0;
    }

    public function obtenerAsignacionPorPacienteYProfesional(int $pacienteId, int $profesionalId): ?array
    {
        $sql = '
            SELECT id, estado, fecha_inicio, fecha_fin
            FROM asignaciones_plan_sombra
            WHERE paciente_id = :paciente_id
              AND profesional_id = :profesional_id
            ORDER BY (estado = "activo") DESC, created_at DESC
            LIMIT 1
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'paciente_id' => $pacienteId,
            'profesional_id' => $profesionalId,
        ]);

        $asignacion = $stmt->fetch();

        return $asignacion ?: null;
    }
}

<?php

declare(strict_types=1);

final class PacienteModel
{
    public static function tiposDocumentosPaciente(): array
    {
        return [
            'Foto',
            'Documento Menor',
            'Documentos padre',
            'Consentimientos informados',
            'Carta de autorización programa sombra',
            'Historia clínica',
            'Contrato laboral',
            'PIAR',
            'Informes de valoración',
            'Protocolos',
            'Material de apoyo',
            'Anexos de seguimiento',
        ];
    }

    public static function tiposDocumentosVisiblesProfesional(): array
    {
        return [
            'PIAR',
            'Informes de valoración',
            'Protocolos',
            'Material de apoyo',
            'Anexos de seguimiento',
        ];
    }

    public static function tiposDocumentosCargablesProfesional(): array
    {
        return ['Anexos de seguimiento'];
    }

    public function __construct(private PDO $db)
    {
    }

    public function crear(array $data): int
    {
        $this->asegurarEstructura();

        $sql = 'INSERT INTO pacientes (documento_identidad, nombre, apellido, fecha_nacimiento, nombre_acudiente, contacto_acudiente, observaciones_iniciales, objetivo_general, estado, tipo_discapacidad, otra_discapacidad, colegio) VALUES (:documento_identidad, :nombre, :apellido, :fecha_nacimiento, :nombre_acudiente, :contacto_acudiente, :observaciones_iniciales, :objetivo_general, :estado, :tipo_discapacidad, :otra_discapacidad, :colegio)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'documento_identidad' => trim($data['documento_identidad']),
            'nombre' => trim($data['nombre']),
            'apellido' => trim($data['apellido']),
            'fecha_nacimiento' => $data['fecha_nacimiento'],
            'nombre_acudiente' => trim($data['nombre_acudiente'] ?? ''),
            'contacto_acudiente' => trim($data['contacto_acudiente'] ?? ''),
            'observaciones_iniciales' => trim($data['observaciones_iniciales'] ?? ''),
            'objetivo_general' => trim($data['objetivo_general'] ?? ''),
            'estado' => $data['estado'] ?? 'activo',
            'tipo_discapacidad' => trim((string) ($data['tipo_discapacidad'] ?? '')),
            'otra_discapacidad' => trim((string) ($data['otra_discapacidad'] ?? '')),
            'colegio' => trim((string) ($data['colegio'] ?? '')),
        ]);

        $id = (int) $this->db->lastInsertId();
        if ($id <= 0) {
            throw new RuntimeException('No fue posible crear el paciente.');
        }

        return $id;
    }

    public function crearPacientePrueba(): int
    {
        $timestamp = date('YmdHis');

        return $this->crear([
            'documento_identidad' => 'TEST-' . $timestamp,
            'nombre' => 'Paciente',
            'apellido' => 'Prueba',
            'fecha_nacimiento' => '2014-01-01',
            'nombre_acudiente' => 'Acudiente Prueba',
            'contacto_acudiente' => '3000000000',
            'observaciones_iniciales' => 'Paciente de prueba creado por administrador.',
            'estado' => 'activo',
            'tipo_discapacidad' => 'Otro',
            'otra_discapacidad' => 'Prueba',
        ]);
    }

    public function listar(): array
    {
        $this->asegurarEstructura();

        $sql = '
            SELECT
                p.id,
                p.documento_identidad,
                p.nombre,
                p.apellido,
                p.fecha_nacimiento,
                p.nombre_acudiente,
                p.contacto_acudiente,
                p.observaciones_iniciales,
                p.estado,
                p.tipo_discapacidad,
                p.otra_discapacidad,
                (
                    SELECT CONCAT(u.nombre, " ", u.apellido)
                    FROM asignaciones_plan_sombra aps
                    INNER JOIN usuarios u ON u.id = aps.profesional_id
                    WHERE aps.paciente_id = p.id
                    ORDER BY aps.created_at DESC
                    LIMIT 1
                ) AS profesional_nombre
            FROM pacientes p
            ORDER BY p.created_at DESC
        ';

        $stmt = $this->db->query($sql);
        $pacientes = $stmt->fetchAll();

        return array_map(function (array $paciente): array {
            $paciente['edad'] = $this->calcularEdad((string) ($paciente['fecha_nacimiento'] ?? ''));
            return $paciente;
        }, $pacientes);
    }

    public function obtenerPorId(int $id): ?array
    {
        $this->asegurarEstructura();

        $sql = '
            SELECT
                p.id,
                p.documento_identidad,
                p.nombre,
                p.apellido,
                p.fecha_nacimiento,
                p.nombre_acudiente,
                p.contacto_acudiente,
                p.observaciones_iniciales,
                p.estado,
                p.tipo_discapacidad,
                p.otra_discapacidad,
                (
                    SELECT CONCAT(u.nombre, " ", u.apellido)
                    FROM asignaciones_plan_sombra aps
                    INNER JOIN usuarios u ON u.id = aps.profesional_id
                    WHERE aps.paciente_id = p.id
                    ORDER BY aps.created_at DESC
                    LIMIT 1
                ) AS profesional_nombre
            FROM pacientes p
            WHERE p.id = :id
            LIMIT 1
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $paciente = $stmt->fetch();

        if (!$paciente) {
            return null;
        }

        $paciente['edad'] = $this->calcularEdad((string) ($paciente['fecha_nacimiento'] ?? ''));

        return $paciente;
    }

    public function actualizar(array $data): bool
    {
        $this->asegurarEstructura();

        $sql = '
            UPDATE pacientes
            SET documento_identidad = :documento_identidad,
                nombre = :nombre,
                apellido = :apellido,
                fecha_nacimiento = :fecha_nacimiento,
                nombre_acudiente = :nombre_acudiente,
                contacto_acudiente = :contacto_acudiente,
                observaciones_iniciales = :observaciones_iniciales,
                estado = :estado,
                tipo_discapacidad = :tipo_discapacidad,
                otra_discapacidad = :otra_discapacidad,
                colegio = :colegio
            WHERE id = :id
        ';

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'documento_identidad' => trim($data['documento_identidad']),
            'nombre' => trim($data['nombre']),
            'apellido' => trim($data['apellido']),
            'fecha_nacimiento' => $data['fecha_nacimiento'],
            'nombre_acudiente' => trim($data['nombre_acudiente'] ?? ''),
            'contacto_acudiente' => trim($data['contacto_acudiente'] ?? ''),
            'observaciones_iniciales' => trim($data['observaciones_iniciales'] ?? ''),
            'estado' => $data['estado'],
            'tipo_discapacidad' => trim((string) ($data['tipo_discapacidad'] ?? '')),
            'otra_discapacidad' => trim((string) ($data['otra_discapacidad'] ?? '')),
            'colegio' => trim((string) ($data['colegio'] ?? '')),
            'id' => (int) $data['id'],
        ]);
    }

    public function eliminar(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Paciente no valido.');
        }

        $stmt = $this->db->prepare('DELETE FROM pacientes WHERE id = :id');
        $stmt->execute(['id' => $id]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('No se encontro el paciente para eliminar.');
        }
    }

    public function listarActivos(): array
    {
        $this->asegurarEstructura();

        $stmt = $this->db->prepare('
            SELECT id, nombre, apellido, documento_identidad
            FROM pacientes
            WHERE estado = :estado
            ORDER BY nombre ASC, apellido ASC
        ');
        $stmt->execute(['estado' => 'activo']);

        return $stmt->fetchAll();
    }

    public function guardarAdjunto(int $pacienteId, array $archivo, string $tipo): void
    {
        if (!isset($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) {
            return;
        }

        $this->asegurarEstructura();
        $this->asegurarTablaAdjuntos();

        $directorio = dirname(__DIR__) . '/public/uploads/pacientes/' . $pacienteId;
        if (!is_dir($directorio) && !mkdir($directorio, 0777, true) && !is_dir($directorio)) {
            throw new RuntimeException('No fue posible crear el directorio de adjuntos.');
        }

        $nombreOriginal = basename((string) $archivo['name']);
        $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
        $nombreArchivo = $this->normalizarNombreArchivo($tipo) . '-' . time() . ($extension !== '' ? '.' . $extension : '');
        $rutaCompleta = $directorio . '/' . $nombreArchivo;
        $rutaPublica = '/Sanpablo/public/uploads/pacientes/' . $pacienteId . '/' . $nombreArchivo;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
            throw new RuntimeException('No fue posible guardar el archivo adjunto.');
        }

        $stmt = $this->db->prepare('
            INSERT INTO paciente_adjuntos (paciente_id, tipo, nombre_original, ruta_archivo)
            VALUES (:paciente_id, :tipo, :nombre_original, :ruta_archivo)
        ');
        $stmt->execute([
            'paciente_id' => $pacienteId,
            'tipo' => $tipo,
            'nombre_original' => $nombreOriginal,
            'ruta_archivo' => $rutaPublica,
        ]);
    }

    public function listarAdjuntos(int $pacienteId): array
    {
        $this->asegurarTablaAdjuntos();

        $stmt = $this->db->prepare('
            SELECT id, tipo, nombre_original, ruta_archivo, created_at
            FROM paciente_adjuntos
            WHERE paciente_id = :paciente_id
            ORDER BY created_at DESC
        ');
        $stmt->execute(['paciente_id' => $pacienteId]);

        return $stmt->fetchAll();
    }

    public function obtenerObjetivoGeneral(int $pacienteId): string
    {
        $this->asegurarEstructura();

        $stmt = $this->db->prepare('SELECT objetivo_general FROM pacientes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $pacienteId]);

        return (string) ($stmt->fetchColumn() ?: '');
    }

    public function guardarObjetivoGeneral(int $pacienteId, string $objetivo): void
    {
        $this->asegurarEstructura();

        $stmt = $this->db->prepare('UPDATE pacientes SET objetivo_general = :objetivo_general WHERE id = :id');
        $stmt->execute([
            'objetivo_general' => trim($objetivo),
            'id' => $pacienteId,
        ]);
    }

    public function listarObjetivosEspecificos(int $pacienteId): array
    {
        $this->asegurarTablaObjetivos();

        $stmt = $this->db->prepare('
            SELECT id, codigo, descripcion, frecuencia, estado, observacion, fecha_evaluacion, created_at, updated_at
            FROM paciente_objetivos
            WHERE paciente_id = :paciente_id
            ORDER BY id ASC
        ');
        $stmt->execute(['paciente_id' => $pacienteId]);

        return $stmt->fetchAll();
    }

    public function crearObjetivoEspecifico(int $pacienteId, string $descripcion, string $frecuencia = 'Semanal'): void
    {
        $this->asegurarTablaObjetivos();

        $stmt = $this->db->prepare('
            INSERT INTO paciente_objetivos (paciente_id, codigo, descripcion, frecuencia)
            VALUES (:paciente_id, :codigo, :descripcion, :frecuencia)
        ');
        $stmt->execute([
            'paciente_id' => $pacienteId,
            'codigo' => $this->siguienteCodigoObjetivo($pacienteId),
            'descripcion' => trim($descripcion),
            'frecuencia' => trim($frecuencia),
        ]);
    }

    public function actualizarObjetivoEspecifico(int $pacienteId, int $objetivoId, string $estado, string $observacion): void
    {
        $this->asegurarTablaObjetivos();

        $stmt = $this->db->prepare('
            UPDATE paciente_objetivos
            SET estado = :estado,
                observacion = :observacion,
                fecha_evaluacion = CURRENT_DATE,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id AND paciente_id = :paciente_id
        ');
        $stmt->execute([
            'estado' => $estado,
            'observacion' => trim($observacion),
            'id' => $objetivoId,
            'paciente_id' => $pacienteId,
        ]);
    }

    private function asegurarEstructura(): void
    {
        $this->asegurarColumna('pacientes', 'tipo_discapacidad', 'VARCHAR(50) NULL DEFAULT NULL');
        $this->asegurarColumna('pacientes', 'otra_discapacidad', 'TEXT NULL DEFAULT NULL');
        $this->asegurarColumna('pacientes', 'colegio', 'VARCHAR(150) NULL DEFAULT NULL');
        $this->asegurarColumna('pacientes', 'objetivo_general', 'TEXT NULL DEFAULT NULL');
        $this->asegurarTablaObjetivos();
    }

    private function asegurarTablaAdjuntos(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS paciente_adjuntos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                paciente_id INT NOT NULL,
                tipo VARCHAR(80) NOT NULL,
                nombre_original VARCHAR(255) NOT NULL,
                ruta_archivo VARCHAR(500) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB
        ');
    }

    private function asegurarTablaObjetivos(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS paciente_objetivos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                paciente_id INT NOT NULL,
                codigo VARCHAR(20) NOT NULL,
                descripcion TEXT NOT NULL,
                frecuencia VARCHAR(30) NOT NULL DEFAULT \'Semanal\',
                estado ENUM(\'pendiente\', \'cumplido\', \'no_cumplido\') NOT NULL DEFAULT \'pendiente\',
                observacion TEXT NULL,
                fecha_evaluacion DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_paciente_objetivo_codigo (paciente_id, codigo),
                FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB
        ');
    }

    private function siguienteCodigoObjetivo(int $pacienteId): string
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM paciente_objetivos WHERE paciente_id = :paciente_id');
        $stmt->execute(['paciente_id' => $pacienteId]);

        return 'OBJ-' . str_pad((string) (((int) $stmt->fetchColumn()) + 1), 3, '0', STR_PAD_LEFT);
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

    private function calcularEdad(string $fecha): int
    {
        if ($fecha === '') {
            return 0;
        }

        $fechaNacimiento = new DateTimeImmutable($fecha);
        $hoy = new DateTimeImmutable('today');
        return (int) $hoy->diff($fechaNacimiento)->y;
    }

    private function normalizarNombreArchivo(string $tipo): string
    {
        $base = preg_replace('/[^A-Za-z0-9]+/', '-', strtolower($tipo)) ?? 'adjunto';
        return trim($base, '-');
    }
}

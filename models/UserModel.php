<?php

declare(strict_types=1);

final class UserModel
{
    private const ROL_ADMINISTRADOR_ID = 1;
    private const ROL_PROFESIONAL_SOMBRA_ID = 2;
    private static ?bool $columnaDocumentoDisponible = null;

    public static function tiposDocumentosProfesional(): array
    {
        return [
            'Foto',
            'Hoja de vida',
            'Cédula',
            'Antecedentes',
            'Tarjeta profesional',
            'Certificados de estudios',
            'Afiliación a seguridad social',
            'Soportes laborales',
            'Soportes profesionales de la salud',
        ];
    }

    public function __construct(private PDO $db)
    {
    }

    public function buscarPorIdentificador(string $identificador): ?array
    {
        $this->asegurarColumnaDocumentoIdentidad();

        $sql = "
            SELECT u.*, r.nombre AS rol_nombre
            FROM usuarios u
            INNER JOIN roles r ON r.id = u.rol_id
            WHERE u.documento_identidad = :documento_identidad
            LIMIT 1
        ";

        $identificador = trim($identificador);
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['documento_identidad' => $identificador]);

        $usuario = $stmt->fetch();
        return $usuario ?: null;
    }

    public function crearProfesional(array $data): bool
    {
        $this->asegurarColumnaDocumentoIdentidad();

        $rolId = self::ROL_PROFESIONAL_SOMBRA_ID;

        $sql = 'INSERT INTO usuarios (rol_id, documento_identidad, nombre, apellido, email, password_hash, telefono, estado) VALUES (:rol_id, :documento_identidad, :nombre, :apellido, :email, :password_hash, :telefono, :estado)';

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'rol_id' => $rolId,
            'documento_identidad' => trim($data['documento_identidad']),
            'nombre' => trim($data['nombre']),
            'apellido' => trim($data['apellido']),
            'email' => strtolower(trim($data['email'])),
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'telefono' => trim($data['telefono'] ?? ''),
            'estado' => 'activo',
        ]);
    }

    public function obtenerProfesionalPorId(int $id): ?array
    {
        $this->asegurarColumnaDocumentoIdentidad();

        $stmt = $this->db->prepare('
            SELECT id, documento_identidad, nombre, apellido, email, telefono, estado
            FROM usuarios
            WHERE id = :id AND rol_id = :rol_id
            LIMIT 1
        ');
        $stmt->execute(['id' => $id, 'rol_id' => self::ROL_PROFESIONAL_SOMBRA_ID]);
        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public function guardarAdjuntoProfesional(int $profesionalId, array $archivo, string $tipo): void
    {
        if (!isset($archivo['tmp_name']) || !is_uploaded_file($archivo['tmp_name'])) {
            throw new InvalidArgumentException('Selecciona un archivo valido.');
        }

        $this->asegurarTablaAdjuntosProfesional();
        $directorio = dirname(__DIR__) . '/public/uploads/profesionales/' . $profesionalId;
        if (!is_dir($directorio) && !mkdir($directorio, 0777, true) && !is_dir($directorio)) {
            throw new RuntimeException('No fue posible crear el directorio del profesional.');
        }

        $nombreOriginal = basename((string) $archivo['name']);
        $extension = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
        $nombreArchivo = $this->normalizarNombreArchivo($tipo) . '-' . time() . ($extension !== '' ? '.' . $extension : '');
        $rutaCompleta = $directorio . '/' . $nombreArchivo;
        $rutaPublica = '/Sanpablo/public/uploads/profesionales/' . $profesionalId . '/' . $nombreArchivo;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaCompleta)) {
            throw new RuntimeException('No fue posible guardar el archivo del profesional.');
        }

        $stmt = $this->db->prepare('
            INSERT INTO usuario_adjuntos (usuario_id, tipo, nombre_original, ruta_archivo)
            VALUES (:usuario_id, :tipo, :nombre_original, :ruta_archivo)
        ');
        $stmt->execute([
            'usuario_id' => $profesionalId,
            'tipo' => $tipo,
            'nombre_original' => $nombreOriginal,
            'ruta_archivo' => $rutaPublica,
        ]);
    }

    public function listarAdjuntosProfesional(int $profesionalId): array
    {
        $this->asegurarTablaAdjuntosProfesional();

        $stmt = $this->db->prepare('
            SELECT id, tipo, nombre_original, ruta_archivo, created_at
            FROM usuario_adjuntos
            WHERE usuario_id = :usuario_id
            ORDER BY created_at DESC
        ');
        $stmt->execute(['usuario_id' => $profesionalId]);

        return $stmt->fetchAll();
    }

    public function listarProfesionales(): array
    {
        $this->asegurarColumnaDocumentoIdentidad();

        $sql = "
            SELECT u.id, u.documento_identidad, u.nombre, u.apellido, u.email, u.telefono, u.estado
            FROM usuarios u
            WHERE u.rol_id = :rol_id
            ORDER BY u.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['rol_id' => self::ROL_PROFESIONAL_SOMBRA_ID]);
        return $stmt->fetchAll();
    }

    public function actualizarProfesional(array $data): bool
    {
        $this->asegurarColumnaDocumentoIdentidad();

        $sql = '
            UPDATE usuarios
            SET documento_identidad = :documento_identidad,
                nombre = :nombre,
                apellido = :apellido,
                email = :email,
                telefono = :telefono,
                estado = :estado
            WHERE id = :id
              AND rol_id = :rol_id
        ';

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            'documento_identidad' => trim($data['documento_identidad']),
            'nombre' => trim($data['nombre']),
            'apellido' => trim($data['apellido']),
            'email' => strtolower(trim($data['email'])),
            'telefono' => trim($data['telefono'] ?? ''),
            'estado' => $data['estado'],
            'id' => (int) $data['id'],
            'rol_id' => self::ROL_PROFESIONAL_SOMBRA_ID,
        ]);
    }

    public function listarProfesionalesActivos(): array
    {
        $this->asegurarColumnaDocumentoIdentidad();

        $sql = '
            SELECT id, nombre, apellido, documento_identidad
            FROM usuarios
            WHERE rol_id = :rol_id
              AND estado = :estado
            ORDER BY nombre ASC, apellido ASC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'rol_id' => self::ROL_PROFESIONAL_SOMBRA_ID,
            'estado' => 'activo',
        ]);

        return $stmt->fetchAll();
    }

    private function asegurarColumnaDocumentoIdentidad(): void
    {
        if (self::$columnaDocumentoDisponible === true) {
            return;
        }

        $sqlExiste = "
            SELECT COUNT(*) AS total
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND table_name = 'usuarios'
              AND column_name = 'documento_identidad'
        ";

        $stmt = $this->db->query($sqlExiste);
        $resultado = $stmt->fetch();
        $existe = ((int) ($resultado['total'] ?? 0)) > 0;

        if (!$existe) {
            $this->db->exec('ALTER TABLE usuarios ADD COLUMN documento_identidad VARCHAR(30) UNIQUE AFTER rol_id');
        }

        self::$columnaDocumentoDisponible = true;
    }

    private function asegurarTablaAdjuntosProfesional(): void
    {
        $this->db->exec('
            CREATE TABLE IF NOT EXISTS usuario_adjuntos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                tipo VARCHAR(80) NOT NULL,
                nombre_original VARCHAR(255) NOT NULL,
                ruta_archivo VARCHAR(500) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB
        ');
    }

    private function normalizarNombreArchivo(string $tipo): string
    {
        $base = preg_replace('/[^A-Za-z0-9]+/', '-', strtolower($tipo)) ?? 'adjunto';
        return trim($base, '-') ?: 'adjunto';
    }
}

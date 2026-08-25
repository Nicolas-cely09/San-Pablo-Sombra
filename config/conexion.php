<?php

declare(strict_types=1);

final class Conexion
{
    private static ?PDO $conexion = null;

    private function __construct()
    {
    }

    /**
     * Retorna una instancia unica de PDO con modo seguro por defecto.
     */
    public static function getConexion(): PDO
    {
        if (self::$conexion instanceof PDO) {
            return self::$conexion;
        }

        $config = require __DIR__ . '/config.php';
        $db = $config['db'];

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['dbname'],
            $db['charset']
        );

        try {
            self::$conexion = new PDO(
                $dsn,
                $db['username'],
                $db['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            self::$conexion->exec('SET NAMES utf8mb4');

            return self::$conexion;
        } catch (PDOException $e) {
            throw new RuntimeException('No fue posible conectar con la base de datos: ' . $e->getMessage());
        }
    }
}

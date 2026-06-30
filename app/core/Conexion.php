<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

class Conexion
{
    private static ?PDO $conexion = null;

    public static function conectar(): PDO
    {
        if (self::$conexion instanceof PDO) {
            return self::$conexion;
        }

        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        try {
            self::$conexion = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            return self::$conexion;
        } catch (PDOException $e) {
            error_log('Error de conexion PDO: ' . $e->getMessage());
            throw new RuntimeException('No se pudo conectar con la base de datos.');
        }
    }

    public static function cerrar(): void
    {
        self::$conexion = null;
    }
}

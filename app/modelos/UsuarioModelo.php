<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Conexion.php';

class UsuarioModelo extends Conexion
{
    public function buscarPorEmail(string $email): ?array
    {
        $sql = 'SELECT * FROM usuarios WHERE email = :email LIMIT 1';
        $stmt = self::conectar()->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public function obtenerPorId(int $idUsuario): ?array
    {
        $sql = 'SELECT id_usuario, nombre, email, rol, activo, creado_en, ultimo_acceso
                FROM usuarios
                WHERE id_usuario = :id
                LIMIT 1';
        $stmt = self::conectar()->prepare($sql);
        $stmt->bindValue(':id', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
        $usuario = $stmt->fetch();

        return $usuario ?: null;
    }

    public function crear(string $nombre, string $email, string $contrasena): int
    {
        $sql = 'INSERT INTO usuarios (nombre, email, contrasena_hash, rol)
                VALUES (:nombre, :email, :contrasena_hash, :rol)';
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $rol = 'estandar';

        $stmt = self::conectar()->prepare($sql);
        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':contrasena_hash', $hash);
        $stmt->bindValue(':rol', $rol);
        $stmt->execute();

        return (int)self::conectar()->lastInsertId();
    }

    public function actualizarUltimoAcceso(int $idUsuario): void
    {
        $sql = 'UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = :id';
        $stmt = self::conectar()->prepare($sql);
        $stmt->bindValue(':id', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function emailExiste(string $email, ?int $exceptoUsuario = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM usuarios WHERE email = :email';
        if ($exceptoUsuario !== null) {
            $sql .= ' AND id_usuario <> :id';
        }

        $stmt = self::conectar()->prepare($sql);
        $stmt->bindValue(':email', $email);
        if ($exceptoUsuario !== null) {
            $stmt->bindValue(':id', $exceptoUsuario, PDO::PARAM_INT);
        }
        $stmt->execute();

        return (int)$stmt->fetchColumn() > 0;
    }

    public function actualizarPerfil(int $idUsuario, string $nombre, string $email, ?string $contrasenaNueva): void
    {
        if ($contrasenaNueva !== null && $contrasenaNueva !== '') {
            $sql = 'UPDATE usuarios
                    SET nombre = :nombre, email = :email, contrasena_hash = :hash
                    WHERE id_usuario = :id';
            $stmt = self::conectar()->prepare($sql);
            $stmt->bindValue(':hash', password_hash($contrasenaNueva, PASSWORD_DEFAULT));
        } else {
            $sql = 'UPDATE usuarios
                    SET nombre = :nombre, email = :email
                    WHERE id_usuario = :id';
            $stmt = self::conectar()->prepare($sql);
        }

        $stmt->bindValue(':nombre', $nombre);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':id', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function obtenerPreferencias(int $idUsuario): array
    {
        $sql = 'SELECT id_genero FROM usuarios_generos WHERE id_usuario = :id';
        $stmt = self::conectar()->prepare($sql);
        $stmt->bindValue(':id', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return array_map('intval', array_column($stmt->fetchAll(), 'id_genero'));
    }

    public function tienePreferencias(int $idUsuario): bool
    {
        $sql = 'SELECT COUNT(*) FROM usuarios_generos WHERE id_usuario = :id';
        $stmt = self::conectar()->prepare($sql);
        $stmt->bindValue(':id', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();

        return (int)$stmt->fetchColumn() > 0;
    }

    public function guardarPreferencias(int $idUsuario, array $generos): void
    {
        $conexion = self::conectar();
        $conexion->beginTransaction();

        try {
            $eliminar = $conexion->prepare('DELETE FROM usuarios_generos WHERE id_usuario = :id');
            $eliminar->bindValue(':id', $idUsuario, PDO::PARAM_INT);
            $eliminar->execute();

            $insertar = $conexion->prepare(
                'INSERT INTO usuarios_generos (id_usuario, id_genero)
                 VALUES (:id_usuario, :id_genero)'
            );

            foreach ($generos as $idGenero) {
                $insertar->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
                $insertar->bindValue(':id_genero', (int)$idGenero, PDO::PARAM_INT);
                $insertar->execute();
            }

            $conexion->commit();
        } catch (Throwable $e) {
            $conexion->rollBack();
            error_log('Error guardando preferencias: ' . $e->getMessage());
            throw $e;
        }
    }

    public function listarUsuarios(): array
    {
        $sql = 'SELECT id_usuario AS id, nombre, email, rol, activo, creado_en
                FROM usuarios
                ORDER BY creado_en DESC';

        return self::conectar()->query($sql)->fetchAll();
    }

    public function toggleEstado(int $idUsuario): void
    {
        $sql = 'UPDATE usuarios
                SET activo = IF(activo = 1, 0, 1)
                WHERE id_usuario = :id AND rol <> "admin"';
        $stmt = self::conectar()->prepare($sql);
        $stmt->bindValue(':id', $idUsuario, PDO::PARAM_INT);
        $stmt->execute();
    }
}

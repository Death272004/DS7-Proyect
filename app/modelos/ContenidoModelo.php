<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/Conexion.php';

class ContenidoModelo extends Conexion
{
    public function obtenerGeneros(bool $soloActivos = true): array
    {
        $sql = 'SELECT id_genero AS id, nombre, emoji
                FROM generos';
        if ($soloActivos) {
            $sql .= ' WHERE activo = 1';
        }
        $sql .= ' ORDER BY nombre ASC';

        return self::conectar()->query($sql)->fetchAll();
    }

    public function listarCatalogo(string $buscar = '', string $tipo = 'todos', int $generoId = 0): array
    {
        $sql = 'SELECT vc.*
                FROM vista_catalogo vc
                WHERE vc.activo = 1';
        $params = [];

        if ($buscar !== '') {
            $sql .= ' AND vc.titulo LIKE :buscar';
            $params[':buscar'] = '%' . $buscar . '%';
        }

        if (in_array($tipo, ['pelicula', 'serie'], true)) {
            $sql .= ' AND vc.tipo = :tipo';
            $params[':tipo'] = $tipo;
        }

        if ($generoId > 0) {
            $sql .= ' AND EXISTS (
                        SELECT 1
                        FROM contenidos_generos cg
                        WHERE cg.id_contenido = vc.id
                          AND cg.id_genero = :genero
                    )';
            $params[':genero'] = $generoId;
        }

        $sql .= ' ORDER BY vc.destacado DESC, vc.calificacion DESC, vc.titulo ASC';
        $stmt = self::conectar()->prepare($sql);

        foreach ($params as $clave => $valor) {
            $tipoDato = is_int($valor) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($clave, $valor, $tipoDato);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenerDestacados(int $limite = 6): array
    {
        $sql = 'SELECT *
                FROM vista_catalogo
                WHERE activo = 1 AND destacado = 1
                ORDER BY calificacion DESC, titulo ASC
                LIMIT :limite';
        $stmt = self::conectar()->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obtenerRecomendados(int $idUsuario, int $limite = 4): array
    {
        $sql = 'SELECT DISTINCT vc.*
                FROM vista_catalogo vc
                INNER JOIN contenidos_generos cg ON cg.id_contenido = vc.id
                INNER JOIN usuarios_generos ug ON ug.id_genero = cg.id_genero
                WHERE ug.id_usuario = :id_usuario
                  AND vc.activo = 1
                ORDER BY vc.calificacion DESC, vc.titulo ASC
                LIMIT :limite';
        $stmt = self::conectar()->prepare($sql);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        $resultados = $stmt->fetchAll();

        return $resultados ?: $this->obtenerDestacados($limite);
    }

    public function obtenerPorId(int $idContenido): ?array
    {
        $sql = 'SELECT *
                FROM vista_catalogo
                WHERE id = :id AND activo = 1
                LIMIT 1';
        $stmt = self::conectar()->prepare($sql);
        $stmt->bindValue(':id', $idContenido, PDO::PARAM_INT);
        $stmt->execute();
        $contenido = $stmt->fetch();

        return $contenido ?: null;
    }

    public function obtenerPorIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT *
                FROM vista_catalogo
                WHERE activo = 1 AND id IN ($placeholders)";
        $stmt = self::conectar()->prepare($sql);
        foreach ($ids as $indice => $id) {
            $stmt->bindValue($indice + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();

        $filas = $stmt->fetchAll();
        $porId = [];
        foreach ($filas as $fila) {
            $porId[(int)$fila['id']] = $fila;
        }

        $ordenadas = [];
        foreach ($ids as $id) {
            if (isset($porId[$id])) {
                $ordenadas[] = $porId[$id];
            }
        }

        return $ordenadas;
    }

    public function obtenerSimilares(int $idContenido, int $limite = 3): array
    {
        $sql = 'SELECT DISTINCT vc.*
                FROM vista_catalogo vc
                INNER JOIN contenidos_generos cg ON cg.id_contenido = vc.id
                WHERE vc.activo = 1
                  AND vc.id <> :id_actual
                  AND cg.id_genero IN (
                      SELECT id_genero
                      FROM contenidos_generos
                      WHERE id_contenido = :id_genero_base
                  )
                ORDER BY vc.calificacion DESC, vc.titulo ASC
                LIMIT :limite';
        $stmt = self::conectar()->prepare($sql);
        $stmt->bindValue(':id_actual', $idContenido, PDO::PARAM_INT);
        $stmt->bindValue(':id_genero_base', $idContenido, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function registrarHistorial(int $idUsuario, int $idContenido): void
    {
        $sql = 'INSERT INTO historial_visualizaciones (id_usuario, id_contenido)
                VALUES (:id_usuario, :id_contenido)';
        $stmt = self::conectar()->prepare($sql);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':id_contenido', $idContenido, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function obtenerHistorialUsuario(int $idUsuario, int $limite = 5): array
    {
        $sql = 'SELECT vc.titulo, vc.imagen_url, h.visto_en
                FROM historial_visualizaciones h
                INNER JOIN vista_catalogo vc ON vc.id = h.id_contenido
                WHERE h.id_usuario = :id_usuario
                ORDER BY h.visto_en DESC
                LIMIT :limite';
        $stmt = self::conectar()->prepare($sql);
        $stmt->bindValue(':id_usuario', $idUsuario, PDO::PARAM_INT);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function listarAdmin(): array
    {
        $sql = 'SELECT vc.*,
                       (
                           SELECT cg.id_genero
                           FROM contenidos_generos cg
                           WHERE cg.id_contenido = vc.id
                           ORDER BY cg.es_principal DESC, cg.id_genero ASC
                           LIMIT 1
                       ) AS genero_id
                FROM vista_catalogo vc
                ORDER BY vc.id DESC';

        return self::conectar()->query($sql)->fetchAll();
    }

    public function crearContenido(array $datos): int
    {
        $conexion = self::conectar();
        $conexion->beginTransaction();

        try {
            $sql = 'INSERT INTO contenidos
                    (titulo, tipo, anio, duracion_minutos, calificacion, sinopsis, imagen_url, trailer_url, activo, destacado)
                    VALUES
                    (:titulo, :tipo, :anio, :duracion, :calificacion, :sinopsis, :imagen_url, :trailer_url, 1, 0)';
            $stmt = $conexion->prepare($sql);
            $stmt->bindValue(':titulo', $datos['titulo']);
            $stmt->bindValue(':tipo', $datos['tipo']);
            $stmt->bindValue(':anio', $datos['anio'], $datos['anio'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':duracion', $datos['duracion'], $datos['duracion'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':calificacion', $datos['calificacion']);
            $stmt->bindValue(':sinopsis', $datos['sinopsis']);
            $stmt->bindValue(':imagen_url', $datos['imagen_url'], $datos['imagen_url'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':trailer_url', $datos['trailer_url'], $datos['trailer_url'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->execute();

            $idContenido = (int)$conexion->lastInsertId();

            $relacion = $conexion->prepare(
                'INSERT INTO contenidos_generos (id_contenido, id_genero, es_principal)
                 VALUES (:id_contenido, :id_genero, 1)'
            );
            $relacion->bindValue(':id_contenido', $idContenido, PDO::PARAM_INT);
            $relacion->bindValue(':id_genero', (int)$datos['genero_id'], PDO::PARAM_INT);
            $relacion->execute();

            $conexion->commit();
            return $idContenido;
        } catch (Throwable $e) {
            $conexion->rollBack();
            error_log('Error creando contenido: ' . $e->getMessage());
            throw $e;
        }
    }

    public function eliminarContenido(int $idContenido): void
    {
        $sql = 'DELETE FROM contenidos WHERE id_contenido = :id';
        $stmt = self::conectar()->prepare($sql);
        $stmt->bindValue(':id', $idContenido, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function actualizarContenido(int $idContenido, array $datos): void
    {
        $conexion = self::conectar();
        $conexion->beginTransaction();

        try {
            $sql = 'UPDATE contenidos
                    SET titulo = :titulo,
                        tipo = :tipo,
                        anio = :anio,
                        duracion_minutos = :duracion,
                        calificacion = :calificacion,
                        sinopsis = :sinopsis,
                        imagen_url = :imagen_url,
                        trailer_url = :trailer_url
                    WHERE id_contenido = :id';
            $stmt = $conexion->prepare($sql);
            $stmt->bindValue(':titulo', $datos['titulo']);
            $stmt->bindValue(':tipo', $datos['tipo']);
            $stmt->bindValue(':anio', $datos['anio'], $datos['anio'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':duracion', $datos['duracion'], $datos['duracion'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $stmt->bindValue(':calificacion', $datos['calificacion']);
            $stmt->bindValue(':sinopsis', $datos['sinopsis']);
            $stmt->bindValue(':imagen_url', $datos['imagen_url'], $datos['imagen_url'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':trailer_url', $datos['trailer_url'], $datos['trailer_url'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $stmt->bindValue(':id', $idContenido, PDO::PARAM_INT);
            $stmt->execute();

            $eliminarGenero = $conexion->prepare('DELETE FROM contenidos_generos WHERE id_contenido = :id');
            $eliminarGenero->bindValue(':id', $idContenido, PDO::PARAM_INT);
            $eliminarGenero->execute();

            $relacion = $conexion->prepare(
                'INSERT INTO contenidos_generos (id_contenido, id_genero, es_principal)
                 VALUES (:id_contenido, :id_genero, 1)'
            );
            $relacion->bindValue(':id_contenido', $idContenido, PDO::PARAM_INT);
            $relacion->bindValue(':id_genero', (int)$datos['genero_id'], PDO::PARAM_INT);
            $relacion->execute();

            $conexion->commit();
        } catch (Throwable $e) {
            $conexion->rollBack();
            error_log('Error actualizando contenido: ' . $e->getMessage());
            throw $e;
        }
    }

    public function estadisticas(): array
    {
        $conexion = self::conectar();
        $totalUsuarios = (int)$conexion->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
        $totalContenidos = (int)$conexion->query('SELECT COUNT(*) FROM contenidos')->fetchColumn();
        $totalCalificaciones = (int)$conexion->query('SELECT COUNT(*) FROM calificaciones_usuarios')->fetchColumn();

        $sqlGenero = 'SELECT g.nombre
                      FROM historial_visualizaciones h
                      INNER JOIN contenidos_generos cg ON cg.id_contenido = h.id_contenido
                      INNER JOIN generos g ON g.id_genero = cg.id_genero
                      GROUP BY g.id_genero, g.nombre
                      ORDER BY COUNT(*) DESC, g.nombre ASC
                      LIMIT 1';
        $generoMasVisto = (string)($conexion->query($sqlGenero)->fetchColumn() ?: 'Sin visitas');

        return compact('totalUsuarios', 'totalContenidos', 'totalCalificaciones', 'generoMasVisto');
    }

    public function generosPopulares(int $limite = 5): array
    {
        $sql = 'SELECT g.nombre, COUNT(h.id_historial) AS visitas
                FROM generos g
                LEFT JOIN contenidos_generos cg ON cg.id_genero = g.id_genero
                LEFT JOIN historial_visualizaciones h ON h.id_contenido = cg.id_contenido
                GROUP BY g.id_genero, g.nombre
                ORDER BY visitas DESC, g.nombre ASC
                LIMIT :limite';
        $stmt = self::conectar()->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        $filas = $stmt->fetchAll();
        $maximo = max(1, ...array_map(static fn ($fila) => (int)$fila['visitas'], $filas));

        foreach ($filas as &$fila) {
            $fila['visitas'] = (int)$fila['visitas'];
            $fila['porcentaje'] = (int)round(($fila['visitas'] / $maximo) * 100);
        }

        return $filas;
    }
}

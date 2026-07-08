<?php
declare(strict_types=1);

final class CatalogoController extends Controlador
{
    private ContenidoModelo $contenidoModelo;
    private UsuarioModelo $usuarioModelo;

    public function __construct()
    {
        $this->contenidoModelo = new ContenidoModelo();
        $this->usuarioModelo = new UsuarioModelo();
    }

    public function index(): void
    {
        $terminoBusqueda = Seguridad::limpiarTexto($_GET['buscar'] ?? '', 100);
        $tipoSolicitado = Seguridad::limpiarTexto($_GET['tipo'] ?? 'todos', 20);
        $filtroTipoActivo = in_array($tipoSolicitado, ['todos', 'pelicula', 'serie'], true) ? $tipoSolicitado : 'todos';
        $filtroGeneroActivo = Seguridad::entero($_GET['genero'] ?? 0, 0);
        $mostrarTodo = Seguridad::limpiarTexto($_GET['ver'] ?? '', 20) === 'todos';
        $generosDisponibles = [];
        $contenidos = [];
        $recomendados = [];
        $ultimasVistas = [];
        $preferenciasNombres = [];

        try {
            $generosDisponibles = $this->contenidoModelo->obtenerGeneros();
            $contenidos = $this->contenidoModelo->listarCatalogo($terminoBusqueda, $filtroTipoActivo, $filtroGeneroActivo);
            if (Seguridad::usuarioId()) {
                $preferenciasIds = $this->usuarioModelo->obtenerPreferencias(Seguridad::usuarioId());
                $preferenciasNombres = $this->nombresPreferencias($generosDisponibles, $preferenciasIds);
                $recomendados = $this->contenidoModelo->obtenerRecomendados(Seguridad::usuarioId(), 5);
                $idsCookie = json_decode((string)($_COOKIE['framefy_ultimas_vistas'] ?? '[]'), true);
                $ultimasVistas = $this->contenidoModelo->obtenerPorIds(is_array($idsCookie) ? $idsCookie : []);
            }
            if ($filtroGeneroActivo > 0) {
                foreach ($generosDisponibles as $genero) {
                    if ((int)$genero['id'] === $filtroGeneroActivo) {
                        $preferenciasNombres[] = $this->normalizarGenero((string)$genero['nombre']);
                        $preferenciasNombres = array_values(array_unique($preferenciasNombres));
                        break;
                    }
                }
            }

            $contenidos = $this->aplicarCoincidencias($contenidos, $preferenciasNombres, true);
            $recomendados = $this->aplicarCoincidencias($recomendados, $preferenciasNombres, true);
            $ultimasVistas = $this->aplicarCoincidencias($ultimasVistas, $preferenciasNombres, false);

            if (!empty($preferenciasNombres)) {
                $recomendados = array_slice($contenidos, 0, 5);
            }
        } catch (Throwable $e) {
            error_log('Error catalogo: ' . $e->getMessage());
        }

        $this->vista('catalog/catalogo', [
            'tituloPagina' => 'Catalogo',
            'paginaActiva' => 'catalogo',
            'terminoBusqueda' => $terminoBusqueda,
            'filtroTipoActivo' => $filtroTipoActivo,
            'filtroGeneroActivo' => $filtroGeneroActivo,
            'mostrarTodo' => $mostrarTodo,
            'generosDisponibles' => $generosDisponibles,
            'contenidos' => $contenidos,
            'recomendados' => $recomendados,
            'ultimasVistas' => $ultimasVistas,
        ]);
    }

    public function detalle(): void
    {
        $idContenido = Seguridad::entero($_GET['id'] ?? 0, 0);
        $contenido = null;
        $similares = [];

        try {
            $contenido = $idContenido > 0 ? $this->contenidoModelo->obtenerPorId($idContenido) : null;
            if ($contenido && Seguridad::usuarioId()) {
                $this->contenidoModelo->registrarHistorial(Seguridad::usuarioId(), (int)$contenido['id']);
                $this->guardarUltimaVistaCookie((int)$contenido['id']);
            }

            $similares = $contenido ? $this->contenidoModelo->obtenerSimilares((int)$contenido['id'], 3) : [];

        } catch (Throwable $e) {
            error_log('Error detalle: ' . $e->getMessage());
            $contenido = null;
            $similares = [];
        }

        $this->vista('catalog/detalle', [
            'tituloPagina' => 'Detalle',
            'paginaActiva' => 'catalogo',
            'contenido' => $contenido,
            'similares' => $similares,
            'trailerEmbed' => $contenido ? app_trailer_embed((string)$contenido['titulo'], $contenido['trailer_url'] ?? null) : null,
        ]);
    }

    private function guardarUltimaVistaCookie(int $idContenido): void
    {
        $ids = json_decode((string)($_COOKIE['framefy_ultimas_vistas'] ?? '[]'), true);
        if (!is_array($ids)) {
            $ids = [];
        }

        $ids = array_values(array_unique(array_merge([$idContenido], array_map('intval', $ids))));
        $ids = array_slice($ids, 0, 5);
        Seguridad::guardarCookie('framefy_ultimas_vistas', json_encode($ids), 2592000);
    }

    private function nombresPreferencias(array $generosDisponibles, array $preferenciasIds): array
    {
        $ids = array_map('intval', $preferenciasIds);
        $nombres = [];

        foreach ($generosDisponibles as $genero) {
            if (in_array((int)$genero['id'], $ids, true)) {
                $nombres[] = $this->normalizarGenero((string)$genero['nombre']);
            }
        }

        return array_values(array_unique(array_filter($nombres)));
    }

    private function aplicarCoincidencias(array $contenidos, array $preferenciasNombres, bool $ordenar): array
    {
        foreach ($contenidos as &$contenido) {
            $contenido['coincidencia'] = $this->calcularCoincidencia($contenido, $preferenciasNombres);
        }
        unset($contenido);

        if ($ordenar) {
            usort($contenidos, static function (array $a, array $b): int {
                $porCoincidencia = ((int)($b['coincidencia'] ?? 0)) <=> ((int)($a['coincidencia'] ?? 0));
                if ($porCoincidencia !== 0) {
                    return $porCoincidencia;
                }

                $porCalificacion = ((float)($b['calificacion'] ?? 0)) <=> ((float)($a['calificacion'] ?? 0));
                if ($porCalificacion !== 0) {
                    return $porCalificacion;
                }

                return strcmp((string)($a['titulo'] ?? ''), (string)($b['titulo'] ?? ''));
            });
        }

        return $contenidos;
    }

    private function calcularCoincidencia(array $contenido, array $preferenciasNombres): int
    {
        $generosContenido = array_map(
            fn (string $genero): string => $this->normalizarGenero($genero),
            array_filter(array_map('trim', explode(',', (string)($contenido['generos'] ?? ''))))
        );

        $calificacion = max(0.0, min(10.0, (float)($contenido['calificacion'] ?? 0)));
        $bonusCalificacion = (int)round(($calificacion - 5.0) * 2.2);
        $variacionEstable = ((int)crc32((string)($contenido['titulo'] ?? '')) % 9) - 4;

        if (empty($preferenciasNombres)) {
            return max(58, min(86, 66 + $bonusCalificacion + $variacionEstable));
        }

        $coincidenciasDirectas = count(array_intersect($generosContenido, $preferenciasNombres));
        if ($coincidenciasDirectas > 0) {
            return max(76, min(98, 76 + ($coincidenciasDirectas * 8) + $bonusCalificacion + $variacionEstable));
        }

        $relacionadas = $this->contarGenerosRelacionados($generosContenido, $preferenciasNombres);
        if ($relacionadas > 0) {
            return max(62, min(84, 58 + ($relacionadas * 7) + $bonusCalificacion + $variacionEstable));
        }

        return max(42, min(68, 50 + $bonusCalificacion + $variacionEstable));
    }

    private function contarGenerosRelacionados(array $generosContenido, array $preferenciasNombres): int
    {
        $relaciones = [
            'accion' => ['aventura', 'thriller', 'ciencia ficcion'],
            'animacion' => ['comedia', 'aventura', 'fantasia', 'familiar'],
            'aventura' => ['accion', 'fantasia', 'ciencia ficcion'],
            'ciencia ficcion' => ['aventura', 'fantasia', 'thriller', 'misterio'],
            'comedia' => ['animacion', 'romance', 'familiar'],
            'documental' => ['drama', 'misterio'],
            'drama' => ['romance', 'misterio', 'thriller'],
            'fantasia' => ['aventura', 'animacion', 'ciencia ficcion'],
            'misterio' => ['thriller', 'terror', 'drama', 'ciencia ficcion'],
            'romance' => ['drama', 'comedia'],
            'suspenso' => ['thriller', 'misterio', 'terror'],
            'terror' => ['suspenso', 'thriller', 'misterio'],
            'thriller' => ['suspenso', 'misterio', 'accion', 'terror'],
        ];

        $total = 0;
        foreach ($preferenciasNombres as $preferencia) {
            $cercanos = $relaciones[$preferencia] ?? [];
            if (array_intersect($generosContenido, $cercanos)) {
                $total++;
            }
        }

        return $total;
    }

    private function normalizarGenero(string $genero): string
    {
        $normalizado = iconv('UTF-8', 'ASCII//TRANSLIT', mb_strtolower(trim($genero)));
        $normalizado = preg_replace('/[^a-z0-9]+/', ' ', (string)$normalizado) ?? '';
        $normalizado = trim($normalizado);

        return match ($normalizado) {
            'sci fi', 'scifi', 'ciencia ficcion' => 'ciencia ficcion',
            default => $normalizado,
        };
    }
}

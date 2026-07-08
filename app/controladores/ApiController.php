<?php
declare(strict_types=1);

final class ApiController extends Controlador
{
    private ContenidoModelo $contenidoModelo;
    private WebserviceLocal $webserviceLocal;

    public function __construct()
    {
        $this->contenidoModelo = new ContenidoModelo();
        $this->webserviceLocal = new WebserviceLocal();
    }

    public function catalogo(): void
    {
        $formato = strtolower(Seguridad::limpiarTexto($_GET['formato'] ?? 'json', 10));
        $buscar = Seguridad::limpiarTexto($_GET['buscar'] ?? '', 100);
        $tipo = Seguridad::limpiarTexto($_GET['tipo'] ?? 'todos', 20);
        $genero = Seguridad::entero($_GET['genero'] ?? 0, 0);
        $tipo = in_array($tipo, ['todos', 'pelicula', 'serie'], true) ? $tipo : 'todos';

        try {
            $datos = [
                'servicio' => 'Framefy Catalogo',
                'formato' => $formato === 'xml' ? 'xml' : 'json',
                'generado_en' => date(DATE_ATOM),
                'filtros' => [
                    'buscar' => $buscar,
                    'tipo' => $tipo,
                    'genero' => $genero,
                ],
                'catalogo' => $this->contenidoModelo->listarCatalogo($buscar, $tipo, $genero),
                'recomendaciones' => Seguridad::usuarioId()
                    ? $this->contenidoModelo->obtenerRecomendados(Seguridad::usuarioId(), 4)
                    : [],
                'fuentes_intercambio' => $this->webserviceLocal->obtenerIntercambio(),
            ];
        } catch (Throwable $e) {
            error_log('Error API catalogo: ' . $e->getMessage());
            http_response_code(500);
            $datos = ['error' => 'No se pudo generar la respuesta.'];
        }

        if ($formato === 'xml') {
            header('Content-Type: application/xml; charset=UTF-8');
            echo $this->arrayXml($datos, 'framefy');
            return;
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function intercambio(): void
    {
        $formato = strtolower(Seguridad::limpiarTexto($_GET['formato'] ?? 'json', 10));
        $datos = [
            'servicio' => 'Framefy Intercambio',
            'formato' => $formato === 'xml' ? 'xml' : 'json',
            'generado_en' => date(DATE_ATOM),
            'archivos_consumidos' => [
                'app/data/intercambio_catalogo.json',
                'app/data/intercambio_generos.xml',
            ],
            'datos' => $this->webserviceLocal->obtenerIntercambio(),
        ];

        if ($formato === 'xml') {
            header('Content-Type: application/xml; charset=UTF-8');
            echo $this->arrayXml($datos, 'framefy_intercambio');
            return;
        }

        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function arrayXml(array $datos, string $raiz): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<' . $raiz . '>' . PHP_EOL;
        $xml .= $this->nodosXml($datos, 1);
        $xml .= '</' . $raiz . '>' . PHP_EOL;
        return $xml;
    }

    private function nodosXml(array $datos, int $nivel): string
    {
        $xml = '';
        $indentacion = str_repeat('  ', $nivel);

        foreach ($datos as $clave => $valor) {
            $etiqueta = is_int($clave) ? 'item' : preg_replace('/[^a-zA-Z0-9_:-]/', '_', (string)$clave);

            if (is_array($valor)) {
                $xml .= $indentacion . '<' . $etiqueta . '>' . PHP_EOL;
                $xml .= $this->nodosXml($valor, $nivel + 1);
                $xml .= $indentacion . '</' . $etiqueta . '>' . PHP_EOL;
                continue;
            }

            $xml .= $indentacion . '<' . $etiqueta . '>'
                . htmlspecialchars((string)$valor, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                . '</' . $etiqueta . '>' . PHP_EOL;
        }

        return $xml;
    }
}

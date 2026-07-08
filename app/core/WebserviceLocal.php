<?php
declare(strict_types=1);

final class WebserviceLocal
{
    private string $directorio;

    public function __construct(?string $directorio = null)
    {
        $this->directorio = $directorio ?? APP_BASE_PATH . '/app/data';
    }

    public function obtenerIntercambio(): array
    {
        return [
            'json' => $this->leerJsonCatalogo(),
            'xml' => $this->leerXmlGeneros(),
        ];
    }

    private function leerJsonCatalogo(): array
    {
        $archivo = $this->directorio . '/intercambio_catalogo.json';
        if (!is_file($archivo)) {
            return ['error' => 'Archivo JSON no disponible.'];
        }

        $datos = json_decode((string)file_get_contents($archivo), true);
        if (!is_array($datos)) {
            return ['error' => 'Archivo JSON invalido.'];
        }

        return $datos;
    }

    private function leerXmlGeneros(): array
    {
        $archivo = $this->directorio . '/intercambio_generos.xml';
        if (!is_file($archivo)) {
            return ['error' => 'Archivo XML no disponible.'];
        }

        if (!function_exists('simplexml_load_file')) {
            return ['error' => 'La extension SimpleXML no esta disponible.'];
        }

        $xml = simplexml_load_file($archivo);
        if (!$xml) {
            return ['error' => 'Archivo XML invalido.'];
        }

        $generos = [];
        foreach ($xml->generos->genero ?? [] as $genero) {
            $generos[] = [
                'nombre' => (string)$genero->nombre,
                'prioridad' => (string)$genero->prioridad,
            ];
        }

        return [
            'fuente' => (string)$xml->fuente,
            'descripcion' => (string)$xml->descripcion,
            'actualizado_en' => (string)$xml->actualizado_en,
            'generos' => $generos,
        ];
    }
}

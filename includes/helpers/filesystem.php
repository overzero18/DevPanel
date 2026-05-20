<?php

if (!function_exists('obtenerRutaBase')) {
    function obtenerRutaBase()
    {
        return '/opt/lampp/htdocs';
    }
}

if (!function_exists('normalizarRuta')) {
    function normalizarRuta($ruta)
    {
        return rtrim(str_replace('\\', '/', $ruta), '/');
    }
}

if (!function_exists('esRutaPermitida')) {
    function esRutaPermitida($ruta)
    {
        $base = normalizarRuta(obtenerRutaBase());

        $rutaReal = realpath($ruta);

        if (!$rutaReal)
        {
            return false;
        }

        $rutaReal = normalizarRuta($rutaReal);

        return strpos($rutaReal, $base) === 0;
    }
}

if (!function_exists('listarArchivosDirectorio')) {
    function listarArchivosDirectorio($ruta)
    {
        if (!is_dir($ruta))
        {
            return [];
        }

        $resultado = [];

        $items = scandir($ruta);

        foreach ($items as $item)
        {
            if ($item === '.' || $item === '..')
            {
                continue;
            }

            $rutaCompleta =
                $ruta . DIRECTORY_SEPARATOR . $item;

            $esDirectorio = is_dir($rutaCompleta);

            $resultado[] = [

                'name' => $item,

                'type' => $esDirectorio
                    ? 'folder'
                    : 'file',

                'path' => $rutaCompleta,

                'size' => $esDirectorio
                    ? null
                    : filesize($rutaCompleta),

                'modified' =>
                    date(
                        'Y-m-d H:i:s',
                        filemtime($rutaCompleta)
                    )

            ];
        }

        usort($resultado, function($a, $b)
        {
            if ($a['type'] === $b['type'])
            {
                return strcasecmp(
                    $a['name'],
                    $b['name']
                );
            }

            return $a['type'] === 'folder'
                ? -1
                : 1;
        });

        return $resultado;
    }
}
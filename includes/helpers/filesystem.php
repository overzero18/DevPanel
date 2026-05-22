<?php

if (!function_exists('obtenerRutaBase')) {
    function obtenerRutaBase()
    {
        require_once __DIR__ . '/config.php';

        return devpanelConfig('HTDOCS_PATH', '/opt/lampp/htdocs');
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

        return $rutaReal === $base || str_starts_with($rutaReal, $base . '/');
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

                'sizeLabel' => $esDirectorio
                    ? '--'
                    : formatearTamanoArchivo(filesize($rutaCompleta)),

                'modified' =>
                    date(
                        'Y-m-d H:i:s',
                        filemtime($rutaCompleta)
                    ),

                'writable' => is_writable($rutaCompleta),

                'parentWritable' => is_writable($ruta)

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

if (!function_exists('formatearTamanoArchivo')) {
    function formatearTamanoArchivo($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = max(0, (float) $bytes);
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1)
        {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, $unitIndex === 0 ? 0 : 1) . ' ' . $units[$unitIndex];
    }
}

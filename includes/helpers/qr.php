<?php

function devpanelQrSvg(string $text, int $scale = 5, int $border = 4): ?string
{
    $version = 5;
    $size = 17 + (4 * $version);
    $dataCodewords = 108;
    $eccCodewords = 26;
    $bytes = array_values(unpack('C*', $text) ?: []);

    if (count($bytes) > 100)
    {
        return null;
    }

    $bits = [0, 1, 0, 0];
    for ($i = 7; $i >= 0; $i--)
    {
        $bits[] = (count($bytes) >> $i) & 1;
    }

    foreach ($bytes as $byte)
    {
        for ($i = 7; $i >= 0; $i--)
        {
            $bits[] = ($byte >> $i) & 1;
        }
    }

    for ($i = 0; $i < 4 && count($bits) < ($dataCodewords * 8); $i++)
    {
        $bits[] = 0;
    }

    while (count($bits) % 8 !== 0)
    {
        $bits[] = 0;
    }

    $data = [];
    for ($i = 0; $i < count($bits); $i += 8)
    {
        $byte = 0;
        for ($j = 0; $j < 8; $j++)
        {
            $byte = ($byte << 1) | $bits[$i + $j];
        }
        $data[] = $byte;
    }

    for ($pad = 0; count($data) < $dataCodewords; $pad ^= 1)
    {
        $data[] = $pad ? 0x11 : 0xec;
    }

    $codewords = array_merge($data, devpanelQrReedSolomonRemainder($data, $eccCodewords));
    $modules = array_fill(0, $size, array_fill(0, $size, false));
    $reserved = array_fill(0, $size, array_fill(0, $size, false));
    $set = static function (int $x, int $y, bool $dark, bool $reserve = true) use (&$modules, &$reserved): void {
        $modules[$y][$x] = $dark;
        if ($reserve)
        {
            $reserved[$y][$x] = true;
        }
    };

    devpanelQrFinder($set, 3, 3);
    devpanelQrFinder($set, $size - 4, 3);
    devpanelQrFinder($set, 3, $size - 4);
    devpanelQrAlignment($set, 30, 30);

    for ($i = 0; $i < $size; $i++)
    {
        if (!$reserved[6][$i])
        {
            $set($i, 6, $i % 2 === 0);
        }
        if (!$reserved[$i][6])
        {
            $set(6, $i, $i % 2 === 0);
        }
    }

    $set(8, (4 * $version) + 9, true);

    for ($i = 0; $i < 9; $i++)
    {
        $reserved[8][$i] = true;
        $reserved[$i][8] = true;
        $reserved[8][$size - 1 - $i] = true;
        $reserved[$size - 1 - $i][8] = true;
    }

    $bitIndex = 0;
    $totalBits = count($codewords) * 8;
    $upward = true;

    for ($right = $size - 1; $right >= 1; $right -= 2)
    {
        if ($right === 6)
        {
            $right--;
        }

        for ($vert = 0; $vert < $size; $vert++)
        {
            $y = $upward ? ($size - 1 - $vert) : $vert;
            for ($x = $right; $x >= $right - 1; $x--)
            {
                if ($reserved[$y][$x])
                {
                    continue;
                }

                $dark = false;
                if ($bitIndex < $totalBits)
                {
                    $dark = (($codewords[intdiv($bitIndex, 8)] >> (7 - ($bitIndex % 8))) & 1) === 1;
                    $bitIndex++;
                }

                if (($x + $y) % 2 === 0)
                {
                    $dark = !$dark;
                }

                $set($x, $y, $dark, false);
            }
        }

        $upward = !$upward;
    }

    devpanelQrFormatBits($set, $size, 0);

    $svgSize = ($size + ($border * 2)) * $scale;
    $rects = '';

    for ($y = 0; $y < $size; $y++)
    {
        for ($x = 0; $x < $size; $x++)
        {
            if ($modules[$y][$x])
            {
                $rects .= '<rect x="' . (($x + $border) * $scale) . '" y="' . (($y + $border) * $scale) . '" width="' . $scale . '" height="' . $scale . '"/>';
            }
        }
    }

    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $svgSize . '" height="' . $svgSize . '" viewBox="0 0 ' . $svgSize . ' ' . $svgSize . '"><rect width="100%" height="100%" fill="#fff"/><g fill="#111827">' . $rects . '</g></svg>';
}

function devpanelQrFinder(callable $set, int $cx, int $cy): void
{
    for ($y = -4; $y <= 4; $y++)
    {
        for ($x = -4; $x <= 4; $x++)
        {
            $dist = max(abs($x), abs($y));
            if ($cx + $x >= 0 && $cy + $y >= 0)
            {
                $set($cx + $x, $cy + $y, $dist !== 2 && $dist !== 4);
            }
        }
    }
}

function devpanelQrAlignment(callable $set, int $cx, int $cy): void
{
    for ($y = -2; $y <= 2; $y++)
    {
        for ($x = -2; $x <= 2; $x++)
        {
            $set($cx + $x, $cy + $y, max(abs($x), abs($y)) !== 1);
        }
    }
}

function devpanelQrFormatBits(callable $set, int $size, int $mask): void
{
    $data = (1 << 3) | $mask;
    $bits = $data << 10;
    for ($i = 14; $i >= 10; $i--)
    {
        if ((($bits >> $i) & 1) !== 0)
        {
            $bits ^= 0x537 << ($i - 10);
        }
    }
    $format = (($data << 10) | $bits) ^ 0x5412;

    for ($i = 0; $i <= 5; $i++) $set(8, $i, (($format >> $i) & 1) !== 0);
    $set(8, 7, (($format >> 6) & 1) !== 0);
    $set(8, 8, (($format >> 7) & 1) !== 0);
    $set(7, 8, (($format >> 8) & 1) !== 0);
    for ($i = 9; $i < 15; $i++) $set(14 - $i, 8, (($format >> $i) & 1) !== 0);
    for ($i = 0; $i < 8; $i++) $set($size - 1 - $i, 8, (($format >> $i) & 1) !== 0);
    for ($i = 8; $i < 15; $i++) $set(8, $size - 15 + $i, (($format >> $i) & 1) !== 0);
}

function devpanelQrReedSolomonRemainder(array $data, int $degree): array
{
    $generator = [1];
    for ($i = 0; $i < $degree; $i++)
    {
        $next = array_fill(0, count($generator) + 1, 0);
        foreach ($generator as $j => $coefficient)
        {
            $next[$j] ^= devpanelQrGfMultiply($coefficient, devpanelQrGfPow($i));
            $next[$j + 1] ^= $coefficient;
        }
        $generator = $next;
    }

    $result = array_fill(0, $degree, 0);
    foreach ($data as $byte)
    {
        $factor = $byte ^ $result[0];
        array_shift($result);
        $result[] = 0;
        for ($i = 0; $i < $degree; $i++)
        {
            $result[$i] ^= devpanelQrGfMultiply($generator[$i], $factor);
        }
    }

    return $result;
}

function devpanelQrGfPow(int $power): int
{
    $value = 1;
    for ($i = 0; $i < $power; $i++)
    {
        $value = devpanelQrGfMultiply($value, 2);
    }
    return $value;
}

function devpanelQrGfMultiply(int $x, int $y): int
{
    $result = 0;
    while ($y > 0)
    {
        if (($y & 1) !== 0)
        {
            $result ^= $x;
        }
        $x <<= 1;
        if (($x & 0x100) !== 0)
        {
            $x ^= 0x11d;
        }
        $y >>= 1;
    }
    return $result & 0xff;
}

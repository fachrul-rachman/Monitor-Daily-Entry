<?php

declare(strict_types=1);

function fail(string $message): never
{
    fwrite(STDERR, $message.PHP_EOL);
    exit(1);
}

function loadPng(string $path): GdImage
{
    if (!is_file($path)) {
        fail("Missing file: {$path}");
    }

    $image = @imagecreatefrompng($path);
    if ($image === false) {
        fail("Failed to read PNG: {$path}");
    }

    imagealphablending($image, true);
    imagesavealpha($image, true);

    return $image;
}

function containToSquare(GdImage $src, int $size): GdImage
{
    $srcW = imagesx($src);
    $srcH = imagesy($src);

    if ($srcW <= 0 || $srcH <= 0) {
        fail('Invalid source image dimensions.');
    }

    $dst = imagecreatetruecolor($size, $size);
    if ($dst === false) {
        fail('Failed to create destination image.');
    }

    imagealphablending($dst, false);
    imagesavealpha($dst, true);

    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    if ($transparent === false) {
        fail('Failed to allocate transparent color.');
    }
    imagefilledrectangle($dst, 0, 0, $size - 1, $size - 1, $transparent);

    $scale = min($size / $srcW, $size / $srcH);
    $newW = max(1, (int) round($srcW * $scale));
    $newH = max(1, (int) round($srcH * $scale));

    $dstX = (int) floor(($size - $newW) / 2);
    $dstY = (int) floor(($size - $newH) / 2);

    $ok = imagecopyresampled(
        $dst,
        $src,
        $dstX,
        $dstY,
        0,
        0,
        $newW,
        $newH,
        $srcW,
        $srcH
    );

    if (!$ok) {
        fail('Failed to resample image.');
    }

    return $dst;
}

function pngBytes(GdImage $img): string
{
    ob_start();
    imagesavealpha($img, true);
    imagepng($img, null, 9);
    return (string) ob_get_clean();
}

function writePng(string $path, GdImage $img): void
{
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
        fail("Failed to create directory: {$dir}");
    }

    imagesavealpha($img, true);
    if (!imagepng($img, $path, 9)) {
        fail("Failed to write PNG: {$path}");
    }
}

function writeIco(string $path, array $imagesPngBytes): void
{
    // ICO container with PNG images (supported by modern browsers/OS).
    $count = count($imagesPngBytes);
    if ($count <= 0) {
        fail('No images provided for ICO.');
    }

    $header = pack('vvv', 0, 1, $count);
    $dirEntries = '';
    $data = '';
    $offset = 6 + (16 * $count);

    foreach ($imagesPngBytes as $size => $png) {
        if (!is_int($size) || $size <= 0 || $size > 256) {
            fail('Invalid ICO size key.');
        }

        $widthByte = $size === 256 ? 0 : $size;
        $heightByte = $size === 256 ? 0 : $size;
        $bytesInRes = strlen($png);

        // ICONDIRENTRY:
        // width, height, colorCount, reserved, planes, bitCount, bytesInRes, imageOffset
        $dirEntries .= pack(
            'CCCCvvVV',
            $widthByte,
            $heightByte,
            0,
            0,
            1,
            32,
            $bytesInRes,
            $offset
        );

        $data .= $png;
        $offset += $bytesInRes;
    }

    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
        fail("Failed to create directory: {$dir}");
    }

    $bytes = $header.$dirEntries.$data;
    if (file_put_contents($path, $bytes) === false) {
        fail("Failed to write ICO: {$path}");
    }
}

function writeSvgWrapper(string $path, string $pngHref): void
{
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0777, true) && !is_dir($dir)) {
        fail("Failed to create directory: {$dir}");
    }

    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
  <image href="{$pngHref}" x="0" y="0" width="64" height="64" preserveAspectRatio="xMidYMid meet" />
</svg>
SVG;

    if (file_put_contents($path, $svg.PHP_EOL) === false) {
        fail("Failed to write SVG: {$path}");
    }
}

if (!extension_loaded('gd')) {
    fail('GD extension is required.');
}

$root = dirname(__DIR__);
$public = $root.DIRECTORY_SEPARATOR.'public';

$sourcePng = $public.DIRECTORY_SEPARATOR.'logo dayta.png';
$faviconPng = $public.DIRECTORY_SEPARATOR.'favicon.png';
$appleTouch = $public.DIRECTORY_SEPARATOR.'apple-touch-icon.png';
$faviconIco = $public.DIRECTORY_SEPARATOR.'favicon.ico';
$faviconSvg = $public.DIRECTORY_SEPARATOR.'favicon.svg';

$src = loadPng($sourcePng);

$icon16 = containToSquare($src, 16);
$icon32 = containToSquare($src, 32);
$icon180 = containToSquare($src, 180);

writePng($faviconPng, $icon32);
writePng($appleTouch, $icon180);

writeIco($faviconIco, [
    16 => pngBytes($icon16),
    32 => pngBytes($icon32),
]);

writeSvgWrapper($faviconSvg, '/favicon.png');

fwrite(STDOUT, "Generated: public/favicon.png, public/favicon.ico, public/favicon.svg, public/apple-touch-icon.png\n");

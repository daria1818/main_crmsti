<?php

namespace Pwd\Helpers;

final class File
{
    public static function lazyPreviewByDimension(int $width, int $height, bool $proportionalCompression = true, bool $asBase64 = false): ?string
    {
        if ($width <= 0 || $height <= 0) {
            return null;
        }

        $compressionSize = 60;

        if ($proportionalCompression && $width > $compressionSize && $height > $compressionSize) {
            $compression = $compressionSize / \max($width, $height);

            $width  = (int) ($width * $compression);
            $height = (int) ($height * $compression);
        }

        $uri  = "/upload/lazy-load-preview/{$width}x{$height}.png";
        $file = $_SERVER['DOCUMENT_ROOT'] . $uri;

        if (\file_exists($file) === false) {
            @\mkdir(\dirname($file), BX_DIR_PERMISSIONS, true);

            $img = \imagecreatetruecolor($width, $height);

            \imagesavealpha($img, true);

            $color = \imagecolorallocatealpha($img, 200, 200, 200, 127);

            \imagefill($img, 0, 0, $color);
            \imagepng($img, $file);
        }

        if ($asBase64) {
            return 'data:image/png;base64,' . \base64_encode(\file_get_contents($file));
        }

        return $uri;
    }

    public static function lazyPreviewByFile(string $file, bool $proportionalCompression = true, bool $asBase64 = false): ?string
    {
        $sizes = \getimagesize($_SERVER['DOCUMENT_ROOT'] . '/' . \str_replace($_SERVER['DOCUMENT_ROOT'], '', $file));

        if ($sizes === false) {
            return null;
        }

        return self::lazyPreviewByDimension($sizes[0], $sizes[1], $proportionalCompression, $asBase64);
    }

    public static function generateWebpImage($path): void
    {
        $file = \file_exists($path) ? $path : $_SERVER['DOCUMENT_ROOT'] . $path;

        if (\file_exists($file) === false) {
            return;
        }

        $ext      = \mb_strtolower(\pathinfo($file, PATHINFO_EXTENSION));
        $webpFile = $file . '.webp';

        if (\file_exists($webpFile)) {
            return;
        }

        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $img = @\imagecreatefromjpeg($file);

                if ($img !== false) {
                    @\imagewebp($img, $webpFile, 80);
                    @\imagedestroy($img);
                }
                break;

            case 'png':
                $img = @\imagecreatefrompng($file);

                if ($img !== false) {
                    @\imagewebp($img, $webpFile, 80);
                    @\imagedestroy($img);
                }
                break;
        }

        unset($file, $ext, $webpFile, $img);
    }
}

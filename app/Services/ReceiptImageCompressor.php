<?php

namespace App\Services;

use GdImage;
use Throwable;

class ReceiptImageCompressor
{
    public function __construct(
        private readonly int $maxWidth = 800,
        private readonly int $jpegQuality = 85,
    ) {}

    /**
     * Resize (max width, aspect ratio preserved) and re-encode as JPEG for Gemini.
     *
     * @return array{binary: string, mime: string}
     */
    public function compress(string $binary, ?string $mime = null): array
    {
        if ($binary === '' || ! function_exists('imagecreatefromstring') || ! $this->isSupportedRasterImage($binary)) {
            return ['binary' => $binary, 'mime' => $mime ?? 'application/octet-stream'];
        }

        $source = imagecreatefromstring($binary);
        if ($source === false) {
            return ['binary' => $binary, 'mime' => $mime ?? 'application/octet-stream'];
        }

        try {
            $width = imagesx($source);
            $height = imagesy($source);

            if ($width <= 0 || $height <= 0) {
                imagedestroy($source);

                return ['binary' => $binary, 'mime' => $mime ?? 'application/octet-stream'];
            }

            [$targetWidth, $targetHeight] = $this->targetDimensions($width, $height);

            $resized = $this->resizeImage($source, $width, $height, $targetWidth, $targetHeight);
            imagedestroy($source);

            if ($resized === null) {
                return ['binary' => $binary, 'mime' => $mime ?? 'application/octet-stream'];
            }

            $encoded = $this->encodeJpeg($resized);
            imagedestroy($resized);

            if ($encoded === null || $encoded === '') {
                return ['binary' => $binary, 'mime' => $mime ?? 'application/octet-stream'];
            }

            return ['binary' => $encoded, 'mime' => 'image/jpeg'];
        } catch (Throwable) {
            if ($source instanceof GdImage) {
                imagedestroy($source);
            }

            return ['binary' => $binary, 'mime' => $mime ?? 'application/octet-stream'];
        }
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function targetDimensions(int $width, int $height): array
    {
        if ($width <= $this->maxWidth) {
            return [$width, $height];
        }

        $targetWidth = $this->maxWidth;
        $targetHeight = (int) round($height * ($this->maxWidth / $width));

        return [$targetWidth, max(1, $targetHeight)];
    }

    private function resizeImage(
        GdImage $source,
        int $sourceWidth,
        int $sourceHeight,
        int $targetWidth,
        int $targetHeight,
    ): ?GdImage {
        if ($targetWidth === $sourceWidth && $targetHeight === $sourceHeight) {
            return $this->flattenForJpeg($source, $sourceWidth, $sourceHeight);
        }

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($canvas === false) {
            return null;
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        if ($white === false) {
            imagedestroy($canvas);

            return null;
        }

        imagefill($canvas, 0, 0, $white);

        if (! imagecopyresampled(
            $canvas,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight,
        )) {
            imagedestroy($canvas);

            return null;
        }

        return $canvas;
    }

    private function flattenForJpeg(GdImage $source, int $width, int $height): ?GdImage
    {
        $canvas = imagecreatetruecolor($width, $height);
        if ($canvas === false) {
            return null;
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        if ($white === false) {
            imagedestroy($canvas);

            return null;
        }

        imagefill($canvas, 0, 0, $white);

        if (! imagecopy($canvas, $source, 0, 0, 0, 0, $width, $height)) {
            imagedestroy($canvas);

            return null;
        }

        return $canvas;
    }

    private function isSupportedRasterImage(string $binary): bool
    {
        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return true;
        }

        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return true;
        }

        if (str_starts_with($binary, 'GIF87a') || str_starts_with($binary, 'GIF89a')) {
            return true;
        }

        return strlen($binary) >= 12
            && str_starts_with($binary, 'RIFF')
            && substr($binary, 8, 4) === 'WEBP';
    }

    private function encodeJpeg(GdImage $image): ?string
    {
        ob_start();
        $ok = imagejpeg($image, null, $this->jpegQuality);
        $encoded = ob_get_clean();

        if (! $ok || $encoded === false) {
            return null;
        }

        return $encoded;
    }
}

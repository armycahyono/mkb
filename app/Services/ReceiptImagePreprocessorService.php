<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ReceiptImagePreprocessorService
{
    /**
     * Process an image (UploadedFile, file path, or base64 string) through the OCR pre-processing pipeline:
     * 1. EXIF Auto-Orientation (Correct upside-down / sideways photos from mobile cameras)
     * 2. Auto-Crop / Background Removal (Focus on the receipt paper, trim table/desk edges)
     * 3. Contrast Stretching (Make faint thermal receipt text crisp & dark)
     * 4. Sharpening Filter / Unsharp Mask (Repair blurry / out-of-focus small characters)
     *
     * @param  UploadedFile|string  $image  UploadedFile, file path, or data URI / base64 string
     * @param  array  $options  Customizable parameters (crop, contrast, sharpen, grayscale)
     * @return array Array with processed base64 data, raw bytes, and dimensions
     */
    public function process(UploadedFile|string $image, array $options = []): array
    {
        $crop = $options['crop'] ?? true;
        $contrast = $options['contrast'] ?? true;
        $sharpen = $options['sharpen'] ?? true;
        $grayscale = $options['grayscale'] ?? false;

        $sourceData = $this->loadSourceImage($image);
        if (! $sourceData || ! $sourceData['resource']) {
            return [
                'success' => false,
                'message' => 'Gagal memuat berkas gambar.',
                'image' => $image,
                'base64' => is_string($image) ? $image : '',
            ];
        }

        $gdImg = $sourceData['resource'];
        $mimeType = $sourceData['mime'];
        $originalWidth = imagesx($gdImg);
        $originalHeight = imagesy($gdImg);

        // 1. Auto-Orientation from EXIF
        if (! empty($sourceData['filePath']) && function_exists('exif_read_data')) {
            $gdImg = $this->correctExifOrientation($gdImg, $sourceData['filePath']);
        }

        // 2. Auto-Crop Background (Bounding Box Detection)
        if ($crop && $originalWidth > 200 && $originalHeight > 200) {
            $gdImg = $this->autoCropReceiptBoundary($gdImg);
        }

        // 3. Contrast & Lighting Enhancement (Dynamic Range Expansion)
        if ($contrast) {
            $gdImg = $this->enhanceContrastAndTone($gdImg);
        }

        // 4. Sharpen Text (Unsharp Mask via 3x3 Convolution Matrix)
        if ($sharpen) {
            $gdImg = $this->sharpenReceiptText($gdImg);
        }

        // 5. Optional Grayscale
        if ($grayscale) {
            imagefilter($gdImg, IMG_FILTER_GRAYSCALE);
        }

        $finalWidth = imagesx($gdImg);
        $finalHeight = imagesy($gdImg);

        // Export processed image to base64
        ob_start();
        imagejpeg($gdImg, null, 92);
        $processedBytes = ob_get_clean();
        imagedestroy($gdImg);

        $processedBase64 = 'data:image/jpeg;base64,'.base64_encode($processedBytes);

        return [
            'success' => true,
            'base64' => $processedBase64,
            'raw_bytes' => $processedBytes,
            'mime' => 'image/jpeg',
            'width' => $finalWidth,
            'height' => $finalHeight,
        ];
    }

    /**
     * Load image from UploadedFile, file path, or base64 string into a GD resource
     */
    protected function loadSourceImage(UploadedFile|string $image): ?array
    {
        try {
            $filePath = null;
            $rawBytes = null;
            $mime = 'image/jpeg';

            if ($image instanceof UploadedFile) {
                $filePath = $image->getRealPath();
                $mime = $image->getMimeType() ?: 'image/jpeg';
                $rawBytes = file_get_contents($filePath);
            } elseif (is_string($image) && file_exists($image)) {
                $filePath = $image;
                $mime = mime_content_type($image) ?: 'image/jpeg';
                $rawBytes = file_get_contents($image);
            } elseif (is_string($image) && str_starts_with($image, 'data:image')) {
                [$meta, $encoded] = explode(',', $image, 2);
                if (preg_match('/data:(image\/[a-zA-Z+]+);base64/', $meta, $m)) {
                    $mime = $m[1];
                }
                $rawBytes = base64_decode($encoded);
            } elseif (is_string($image)) {
                $rawBytes = base64_decode($image);
            }

            if (! $rawBytes) {
                return null;
            }

            $gdImg = imagecreatefromstring($rawBytes);
            if (! $gdImg) {
                return null;
            }

            return [
                'resource' => $gdImg,
                'filePath' => $filePath,
                'mime' => $mime,
            ];
        } catch (Exception $e) {
            Log::warning('ReceiptImagePreprocessor loadSourceImage exception: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Correct orientation using EXIF tags (often 3, 6, or 8 from smartphone cameras)
     */
    public function correctExifOrientation(\GdImage $gdImg, string $filePath): \GdImage
    {
        try {
            $exif = @exif_read_data($filePath);
            if (! empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3:
                        // 180 degrees
                        $rotated = imagerotate($gdImg, 180, 0);
                        if ($rotated) {
                            imagedestroy($gdImg);
                            $gdImg = $rotated;
                        }
                        break;
                    case 6:
                        // 90 degrees CW (needs 270 deg counter-clockwise in GD)
                        $rotated = imagerotate($gdImg, -90, 0);
                        if ($rotated) {
                            imagedestroy($gdImg);
                            $gdImg = $rotated;
                        }
                        break;
                    case 8:
                        // 90 degrees CCW (needs 90 deg in GD)
                        $rotated = imagerotate($gdImg, 90, 0);
                        if ($rotated) {
                            imagedestroy($gdImg);
                            $gdImg = $rotated;
                        }
                        break;
                }
            }
        } catch (Exception $e) {
            // Ignore EXIF errors on unreadable metadata
        }

        return $gdImg;
    }

    /**
     * Auto-crop surrounding background (e.g. dark wood table, floor) to focus on the paper receipt
     */
    public function autoCropReceiptBoundary(\GdImage $gdImg): \GdImage
    {
        $width = imagesx($gdImg);
        $height = imagesy($gdImg);

        $stepX = max(1, (int) ($width / 100));
        $stepY = max(1, (int) ($height / 100));

        $minX = 0;
        $maxX = $width - 1;
        $minY = 0;
        $maxY = $height - 1;

        // Sample corners to estimate background average luminance
        $cornerSamples = [
            imagecolorat($gdImg, 5, 5),
            imagecolorat($gdImg, $width - 6, 5),
            imagecolorat($gdImg, 5, $height - 6),
            imagecolorat($gdImg, $width - 6, $height - 6),
        ];
        $cornerLuminance = 0;
        foreach ($cornerSamples as $col) {
            $r = ($col >> 16) & 0xFF;
            $g = ($col >> 8) & 0xFF;
            $b = $col & 0xFF;
            $cornerLuminance += ($r * 0.299 + $g * 0.587 + $b * 0.114);
        }
        $bgLum = $cornerLuminance / 4;

        // Find top bound
        for ($y = 0; $y < $height * 0.35; $y += $stepY) {
            $rowLum = 0;
            $samples = 0;
            for ($x = (int) ($width * 0.2); $x < $width * 0.8; $x += $stepX) {
                $c = imagecolorat($gdImg, $x, $y);
                $rowLum += ((($c >> 16) & 0xFF) * 0.299 + (($c >> 8) & 0xFF) * 0.587 + ($c & 0xFF) * 0.114);
                $samples++;
            }
            $avg = $samples > 0 ? ($rowLum / $samples) : $bgLum;
            if (abs($avg - $bgLum) > 28) {
                $minY = max(0, $y - 10);
                break;
            }
        }

        // Find bottom bound
        for ($y = $height - 1; $y > $height * 0.65; $y -= $stepY) {
            $rowLum = 0;
            $samples = 0;
            for ($x = (int) ($width * 0.2); $x < $width * 0.8; $x += $stepX) {
                $c = imagecolorat($gdImg, $x, $y);
                $rowLum += ((($c >> 16) & 0xFF) * 0.299 + (($c >> 8) & 0xFF) * 0.587 + ($c & 0xFF) * 0.114);
                $samples++;
            }
            $avg = $samples > 0 ? ($rowLum / $samples) : $bgLum;
            if (abs($avg - $bgLum) > 28) {
                $maxY = min($height - 1, $y + 10);
                break;
            }
        }

        // Find left bound
        for ($x = 0; $x < $width * 0.35; $x += $stepX) {
            $colLum = 0;
            $samples = 0;
            for ($y = (int) ($height * 0.2); $y < $height * 0.8; $y += $stepY) {
                $c = imagecolorat($gdImg, $x, $y);
                $colLum += ((($c >> 16) & 0xFF) * 0.299 + (($c >> 8) & 0xFF) * 0.587 + ($c & 0xFF) * 0.114);
                $samples++;
            }
            $avg = $samples > 0 ? ($colLum / $samples) : $bgLum;
            if (abs($avg - $bgLum) > 28) {
                $minX = max(0, $x - 10);
                break;
            }
        }

        // Find right bound
        for ($x = $width - 1; $x > $width * 0.65; $x -= $stepX) {
            $colLum = 0;
            $samples = 0;
            for ($y = (int) ($height * 0.2); $y < $height * 0.8; $y += $stepY) {
                $c = imagecolorat($gdImg, $x, $y);
                $colLum += ((($c >> 16) & 0xFF) * 0.299 + (($c >> 8) & 0xFF) * 0.587 + ($c & 0xFF) * 0.114);
                $samples++;
            }
            $avg = $samples > 0 ? ($colLum / $samples) : $bgLum;
            if (abs($avg - $bgLum) > 28) {
                $maxX = min($width - 1, $x + 10);
                break;
            }
        }

        $cropW = $maxX - $minX;
        $cropH = $maxY - $minY;

        // Only crop if document is at least 40% of original area to avoid over-cropping
        if ($cropW > ($width * 0.4) && $cropH > ($height * 0.4) && ($cropW < $width * 0.98 || $cropH < $height * 0.98)) {
            $cropped = imagecrop($gdImg, [
                'x' => $minX,
                'y' => $minY,
                'width' => $cropW,
                'height' => $cropH,
            ]);
            if ($cropped) {
                imagedestroy($gdImg);

                return $cropped;
            }
        }

        return $gdImg;
    }

    /**
     * Enhance contrast, flatten uneven shadows and boost thermal printer text darkness
     */
    public function enhanceContrastAndTone(\GdImage $gdImg): \GdImage
    {
        // Increase contrast by 18% (negative value in GD increases contrast)
        imagefilter($gdImg, IMG_FILTER_CONTRAST, -18);

        // Slight brightness normalization
        imagefilter($gdImg, IMG_FILTER_BRIGHTNESS, 5);

        return $gdImg;
    }

    /**
     * Sharpen blurry/out-of-focus text using a 3x3 unsharp convolution kernel
     */
    public function sharpenReceiptText(\GdImage $gdImg): \GdImage
    {
        // High-pass sharpening matrix (Laplacian / Unsharp mask kernel)
        $matrix = [
            [-1.0, -1.0, -1.0],
            [-1.0,  9.0, -1.0],
            [-1.0, -1.0, -1.0],
        ];
        $divisor = 1.0;
        $offset = 0.0;

        imageconvolution($gdImg, $matrix, $divisor, $offset);

        return $gdImg;
    }
}

<?php

namespace Tests\Feature;

use App\Services\ReceiptImagePreprocessorService;
use App\Services\UtilityReceiptOcrService;
use Database\Seeders\UtilityProfileSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReceiptImagePreprocessorTest extends TestCase
{
    protected ReceiptImagePreprocessorService $preprocessor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureTablesExist();
        $this->seed(UtilityProfileSeeder::class);
        $this->preprocessor = app(ReceiptImagePreprocessorService::class);
    }

    private function ensureTablesExist(): void
    {
        if (! Schema::hasTable('utility_profiles')) {
            Schema::create('utility_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name');
                $table->string('category', 50);
                $table->string('customer_name');
                $table->string('customer_number')->nullable();
                $table->string('meter_number')->nullable();
                $table->string('idpel')->nullable();
                $table->string('tariff_power')->nullable();
                $table->string('provider')->nullable();
                $table->string('payment_method')->nullable();
                $table->text('description')->nullable();
                $table->integer('sort_order')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Test processing a synthetic image from base64 string
     */
    public function test_preprocessor_processes_base64_image_successfully(): void
    {
        // Create a 300x400 synthetic receipt image with GD
        $img = imagecreatetruecolor(300, 400);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefilledrectangle($img, 0, 0, 300, 400, $white);
        imagestring($img, 5, 20, 50, 'STRUK PEMBAYARAN PLN', $black);

        ob_start();
        imagejpeg($img);
        $bytes = ob_get_clean();
        imagedestroy($img);

        $base64 = 'data:image/jpeg;base64,'.base64_encode($bytes);

        $result = $this->preprocessor->process($base64);

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('data:image/jpeg;base64,', $result['base64']);
        $this->assertNotEmpty($result['raw_bytes']);
        $this->assertGreaterThan(0, $result['width']);
        $this->assertGreaterThan(0, $result['height']);
    }

    /**
     * Test processing an UploadedFile instance
     */
    public function test_preprocessor_processes_uploaded_file(): void
    {
        $file = UploadedFile::fake()->image('receipt.jpg', 350, 500);

        $result = $this->preprocessor->process($file);

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('data:image/jpeg;base64,', $result['base64']);
        $this->assertEquals(350, $result['width']);
        $this->assertEquals(500, $result['height']);
    }

    /**
     * Test contrast enhancement filter
     */
    public function test_contrast_enhancement_filter(): void
    {
        $img = imagecreatetruecolor(100, 100);
        $gray = imagecolorallocate($img, 128, 128, 128);
        imagefilledrectangle($img, 0, 0, 100, 100, $gray);

        $enhanced = $this->preprocessor->enhanceContrastAndTone($img);

        $this->assertInstanceOf(\GdImage::class, $enhanced);
        imagedestroy($enhanced);
    }

    /**
     * Test sharpening convolution kernel
     */
    public function test_sharpening_convolution_filter(): void
    {
        $img = imagecreatetruecolor(100, 100);
        $white = imagecolorallocate($img, 240, 240, 240);
        imagefilledrectangle($img, 0, 0, 100, 100, $white);

        $sharpened = $this->preprocessor->sharpenReceiptText($img);

        $this->assertInstanceOf(\GdImage::class, $sharpened);
        imagedestroy($sharpened);
    }

    /**
     * Test auto-crop boundary on image with dark border
     */
    public function test_autocrop_boundary_detection(): void
    {
        // Image 400x500 with dark background (table) and bright center (receipt)
        $img = imagecreatetruecolor(400, 500);
        $darkTable = imagecolorallocate($img, 40, 30, 25);
        $whitePaper = imagecolorallocate($img, 245, 245, 245);
        imagefilledrectangle($img, 0, 0, 400, 500, $darkTable);
        imagefilledrectangle($img, 60, 50, 340, 450, $whitePaper);

        $cropped = $this->preprocessor->autoCropReceiptBoundary($img);

        $this->assertInstanceOf(\GdImage::class, $cropped);
        imagedestroy($cropped);
    }

    /**
     * Test preprocessor gracefully handles invalid/corrupted input
     */
    public function test_preprocessor_handles_invalid_image(): void
    {
        $result = $this->preprocessor->process('not-a-valid-image-data-string');

        $this->assertFalse($result['success']);
        $this->assertEquals('Gagal memuat berkas gambar.', $result['message']);
    }

    /**
     * Test OCR Service integration provides enhanced_image
     */
    public function test_ocr_service_includes_enhanced_image(): void
    {
        $file = UploadedFile::fake()->image('test_receipt.jpg', 300, 400);

        $ocrService = app(UtilityReceiptOcrService::class);
        $rawText = "STRUK PEMBAYARAN TAGIHAN LISTRIK\nIDPEL : 513123456789\nNAMA : SMK PGRI KRAS\nTOTAL BAYAR : RP. 50.000";

        $result = $ocrService->scan($file, $rawText, 'local');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('enhanced_image', $result);
        $this->assertStringStartsWith('data:image/jpeg;base64,', $result['enhanced_image']);
    }
}

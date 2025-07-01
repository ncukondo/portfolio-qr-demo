<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\QRCodeService;

class QRCodeServiceTest extends TestCase
{
    private QRCodeService $qrCodeService;

    protected function setUp(): void
    {
        $this->qrCodeService = new QRCodeService();
    }

    public function testGenerateQRCodeReturnsBase64String(): void
    {
        $url = 'https://example.com/test';
        $result = $this->qrCodeService->generateQRCode($url);
        
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertTrue(base64_encode(base64_decode($result, true)) === $result);
    }

    public function testGenerateQRCodeDataUrlReturnsDataUrl(): void
    {
        $url = 'https://example.com/test';
        $result = $this->qrCodeService->generateQRCodeDataUrl($url);
        
        $this->assertIsString($result);
        $this->assertStringStartsWith('data:image/png;base64,', $result);
    }

    public function testGenerateQRCodeWithLabel(): void
    {
        $url = 'https://example.com/test';
        $label = 'Test Label';
        $result = $this->qrCodeService->generateQRCodeDataUrl($url, $label);
        
        $this->assertIsString($result);
        $this->assertStringStartsWith('data:image/png;base64,', $result);
    }

    public function testGenerateQRCodeWithCustomSize(): void
    {
        $url = 'https://example.com/test';
        $size = 400;
        $result = $this->qrCodeService->generateQRCode($url, null, $size);
        
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGenerateMultipleQRCodes(): void
    {
        $urls = [
            'https://example.com/test1',
            ['url' => 'https://example.com/test2', 'label' => 'Test 2'],
            ['url' => 'https://example.com/test3', 'label' => 'Test 3']
        ];
        
        $result = $this->qrCodeService->generateMultipleQRCodes($urls);
        
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        
        foreach ($result as $index => $qrData) {
            $this->assertArrayHasKey('index', $qrData);
            $this->assertArrayHasKey('url', $qrData);
            $this->assertArrayHasKey('qr_code', $qrData);
            $this->assertEquals($index, $qrData['index']);
            $this->assertStringStartsWith('data:image/png;base64,', $qrData['qr_code']);
        }
    }

    public function testSaveQRCodeToFileWithValidPath(): void
    {
        $url = 'https://example.com/test';
        $tempFile = tempnam(sys_get_temp_dir(), 'qr_test_');
        
        $result = $this->qrCodeService->saveQRCodeToFile($url, $tempFile);
        
        $this->assertTrue($result);
        $this->assertFileExists($tempFile);
        $this->assertGreaterThan(0, filesize($tempFile));
        
        // Clean up
        unlink($tempFile);
    }

    public function testSaveQRCodeToFileWithInvalidPath(): void
    {
        $url = 'https://example.com/test';
        $invalidPath = '/invalid/path/that/does/not/exist/qr.png';
        
        $result = $this->qrCodeService->saveQRCodeToFile($url, $invalidPath);
        
        $this->assertFalse($result);
    }

    public function testGenerateStyledQRCode(): void
    {
        $url = 'https://example.com/test';
        $options = ['color' => '#000000'];
        $result = $this->qrCodeService->generateStyledQRCode($url, $options);
        
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertTrue(base64_encode(base64_decode($result, true)) === $result);
    }
}
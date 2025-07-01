<?php

namespace Tests\Unit\Api;

use PHPUnit\Framework\TestCase;

class GenerateQRCodeTest extends TestCase
{
    public function testQRCodeApiEndpointExists(): void
    {
        $filePath = __DIR__ . '/../../../public/generate-qr-code.php';
        $this->assertFileExists($filePath);
    }

    public function testQRCodePageHasCorrectStructure(): void
    {
        $filePath = __DIR__ . '/../../../public/generate-qr-code.php';
        $content = file_get_contents($filePath);
        
        // Check that it has proper security checks
        $this->assertStringContainsString('Auth::check()', $content);
        $this->assertStringContainsString('Auth::hasRole(\'administrator\')', $content);
        $this->assertStringContainsString('Auth::hasRole(\'class-owner\')', $content);
        
        // Check that it handles GET requests (URL parameters)
        $this->assertStringContainsString('$_GET[\'class_id\']', $content);
        
        // Check that it validates input
        $this->assertStringContainsString('class_id', $content);
        $this->assertStringContainsString('ClassModel', $content);
        $this->assertStringContainsString('QRCodeService', $content);
        $this->assertStringContainsString('ClassCompletionTokenService', $content);
        
        // Check that it returns HTML
        $this->assertStringContainsString('<!DOCTYPE html>', $content);
        $this->assertStringContainsString('QRコード生成', $content);
    }

    public function testQRCodePageHasProperErrorHandling(): void
    {
        $filePath = __DIR__ . '/../../../public/generate-qr-code.php';
        $content = file_get_contents($filePath);
        
        // Check for proper error handling
        $this->assertStringContainsString('try {', $content);
        $this->assertStringContainsString('} catch (Exception $e) {', $content);
        $this->assertStringContainsString('$error', $content);
        $this->assertStringContainsString('error-message', $content);
    }
}
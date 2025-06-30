<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * QRCodeService
 * QRコード生成サービス
 */
class QRCodeService
{
    /**
     * Generate QR code for URL
     * URL用のQRコードを生成
     * 
     * @param string $url URL to encode
     * @param string|null $label Optional label text
     * @param int $size QR code size in pixels
     * @return string Base64 encoded PNG image
     */
    public function generateQRCode(string $url, ?string $label = null, int $size = 300): string
    {
        $qrCode = new QrCode($url);
        $writer = new PngWriter();
        
        $result = $writer->write($qrCode);
        
        return base64_encode($result->getString());
    }

    /**
     * Generate QR code data URL for HTML embedding
     * HTML埋め込み用のQRコードデータURLを生成
     * 
     * @param string $url URL to encode
     * @param string|null $label Optional label text
     * @param int $size QR code size in pixels
     * @return string Data URL for HTML img src
     */
    public function generateQRCodeDataUrl(string $url, ?string $label = null, int $size = 300): string
    {
        $base64 = $this->generateQRCode($url, $label, $size);
        return 'data:image/png;base64,' . $base64;
    }

    /**
     * Generate QR code and save to file
     * QRコードを生成してファイルに保存
     * 
     * @param string $url URL to encode
     * @param string $filePath Path to save the QR code image
     * @param string|null $label Optional label text
     * @param int $size QR code size in pixels
     * @return bool Success status
     */
    public function saveQRCodeToFile(string $url, string $filePath, ?string $label = null, int $size = 300): bool
    {
        try {
            $base64 = $this->generateQRCode($url, $label, $size);
            $imageData = base64_decode($base64);
            
            return file_put_contents($filePath, $imageData) !== false;
        } catch (\Exception $e) {
            error_log("QR Code save error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate multiple QR codes for different URLs
     * 複数URL用のQRコードを生成
     * 
     * @param array $urls Array of URLs with optional labels
     * @param int $size QR code size in pixels
     * @return array Array of QR code data URLs
     */
    public function generateMultipleQRCodes(array $urls, int $size = 300): array
    {
        $qrCodes = [];
        
        foreach ($urls as $index => $urlData) {
            $url = is_array($urlData) ? $urlData['url'] : $urlData;
            $label = is_array($urlData) ? ($urlData['label'] ?? null) : null;
            
            $qrCodes[] = [
                'index' => $index,
                'url' => $url,
                'label' => $label,
                'qr_code' => $this->generateQRCodeDataUrl($url, $label, $size)
            ];
        }
        
        return $qrCodes;
    }

    /**
     * Generate QR code with custom styling
     * カスタムスタイリング付きQRコードを生成
     * 
     * @param string $url URL to encode
     * @param array $options Styling options
     * @return string Base64 encoded PNG image
     */
    public function generateStyledQRCode(string $url, array $options = []): string
    {
        $qrCode = new QrCode($url);
        $writer = new PngWriter();
        
        $result = $writer->write($qrCode);
        
        return base64_encode($result->getString());
    }
}
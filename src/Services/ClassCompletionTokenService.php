<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

/**
 * ClassCompletionTokenService
 * クラス受講完了用JWTトークン管理サービス
 */
class ClassCompletionTokenService
{
    private string $secretKey;
    private string $algorithm = 'HS256';
    private int $expirationTime = 86400; // 24 hours in seconds

    public function __construct()
    {
        // In production, this should be loaded from environment variables
        $this->secretKey = $_ENV['JWT_SECRET'] ?? 'portfolio-system-jwt-secret-key-2025';
    }

    /**
     * Generate JWT token for class completion URL
     * クラス受講完了URL用JWTトークンを生成
     * 
     * @param array $classIds Array of class IDs to complete
     * @param int|null $expirationHours Token expiration in hours (default: 24)
     * @return string JWT token
     */
    public function generateCompletionToken(array $classIds, ?int $expirationHours = null): string
    {
        $expiration = $expirationHours ? $expirationHours * 3600 : $this->expirationTime;
        
        $payload = [
            'iss' => 'portfolio-system',
            'iat' => time(),
            'exp' => time() + $expiration,
            'purpose' => 'class_completion',
            'class_ids' => array_map('intval', $classIds), // Ensure integers
            'created_at' => date('Y-m-d H:i:s')
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Decode and validate completion token
     * 受講完了トークンをデコード・検証
     * 
     * @param string $token JWT token
     * @return array|null Decoded payload or null if invalid
     */
    public function decodeCompletionToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            $payload = (array) $decoded;

            // Validate token purpose
            if (!isset($payload['purpose']) || $payload['purpose'] !== 'class_completion') {
                return null;
            }

            // Validate class_ids
            if (!isset($payload['class_ids']) || !is_array($payload['class_ids'])) {
                return null;
            }

            return $payload;
        } catch (Exception $e) {
            error_log("JWT decode error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate completion URL with JWT token
     * JWT トークンを含む受講完了URLを生成
     * 
     * @param array $classIds Array of class IDs
     * @param string $baseUrl Base URL of the application
     * @param int|null $expirationHours Token expiration in hours
     * @return string Complete URL with JWT token
     */
    public function generateCompletionUrl(array $classIds, string $baseUrl, ?int $expirationHours = null): string
    {
        $token = $this->generateCompletionToken($classIds, $expirationHours);
        $baseUrl = rtrim($baseUrl, '/');
        
        return $baseUrl . '/complete-classes?token=' . urlencode($token);
    }

    /**
     * Validate if token is expired
     * トークンの有効期限をチェック
     * 
     * @param array $payload Decoded JWT payload
     * @return bool True if token is valid (not expired)
     */
    public function isTokenValid(array $payload): bool
    {
        if (!isset($payload['exp'])) {
            return false;
        }

        return time() < $payload['exp'];
    }

    /**
     * Get token information for display
     * 表示用のトークン情報を取得
     * 
     * @param string $token JWT token
     * @return array Token information
     */
    public function getTokenInfo(string $token): array
    {
        $payload = $this->decodeCompletionToken($token);
        
        if (!$payload) {
            return [
                'valid' => false,
                'error' => 'Invalid token'
            ];
        }

        return [
            'valid' => true,
            'class_ids' => $payload['class_ids'],
            'created_at' => $payload['created_at'] ?? 'Unknown',
            'expires_at' => isset($payload['exp']) ? date('Y-m-d H:i:s', $payload['exp']) : 'Unknown',
            'is_expired' => !$this->isTokenValid($payload),
            'remaining_time' => isset($payload['exp']) ? max(0, $payload['exp'] - time()) : 0
        ];
    }

    /**
     * Create multiple completion URLs for different class combinations
     * 異なるクラス組み合わせ用の複数完了URLを作成
     * 
     * @param array $classIdGroups Array of class ID arrays
     * @param string $baseUrl Base URL of the application
     * @param int|null $expirationHours Token expiration in hours
     * @return array Array of URLs
     */
    public function generateMultipleCompletionUrls(array $classIdGroups, string $baseUrl, ?int $expirationHours = null): array
    {
        $urls = [];
        
        foreach ($classIdGroups as $index => $classIds) {
            $urls[] = [
                'group_index' => $index,
                'class_ids' => $classIds,
                'url' => $this->generateCompletionUrl($classIds, $baseUrl, $expirationHours)
            ];
        }

        return $urls;
    }
}
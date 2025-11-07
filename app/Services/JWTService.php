<?php
namespace App\Services;
use Firebase\JWT\JWT;
use Exception;
use Firebase\JWT\Key;

class JWTService
{
    private string $secretKey;
    private string $refreshSecretKey;
    private string $algorithm = 'HS256';

    public function __construct()
    {
        $this->secretKey = config('app.key');
        $this->refreshSecretKey = config('app.key') . '_refresh';
    }

    // Generar access token
    public function generateAccessToken(array $payload, int $expiryMinutes = 100): string
    {
        $issuedAt = time();
        $expiry = $issuedAt + ($expiryMinutes * 60);

        $tokenPayload = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $expiry,
            'type' => 'access_token',
        ]);

        return JWT::encode($tokenPayload, $this->secretKey, $this->algorithm);
    }

    // Generar refresh token
    public function generateRefreshToken(array $payload, int $expiryDays = 7): string
    {
        $issuedAt = time();
        $expiry = $issuedAt + ($expiryDays * 24 * 60 * 60);

        $tokenPayload = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $expiry,
            'type' => 'refresh_token',
        ]);

        return JWT::encode($tokenPayload, $this->secretKey, $this->algorithm);
    }

    // Generar ambos tokens
    public function generateTokenPair(array $payload): array
    {
        return [
            'access_token' => $this->generateAccessToken($payload),
            'refresh_token' => $this->generateRefreshToken($payload),
        ];
    }

    public function decodeAccessToken(string $token)
    {
        try {
            return JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            
        } catch (Exception $e) {
            throw new Exception('Invalid or expired access token: ' . $e->getMessage());
        }
    }

    public function decodeRefreshToken(string $token)
    {
        try {
            return JWT::decode($token, new Key($this->refreshSecretKey, $this->algorithm));
        } catch (Exception $e) {
            throw new Exception('Invalid or expired refresh token: ' . $e->getMessage());
        }
    }
}
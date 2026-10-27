<?php

namespace App\Services;

class SecurityQrService
{
    private string $secretKey;

    public function __construct()
    {
        // Use application encryption key or fallback to secure government salt
        $this->secretKey = config('Encryption')->key ?? 'PIA_GOV_FMS_HMAC_SECRET_2026';
    }

    /**
     * Generate HMAC-SHA256 digital security token
     */
    public function generateToken(string $serialNo, string $plateNumber, string $driverCode, string $departureTime): string
    {
        $payload = \sprintf("%s|%s|%s|%s", $serialNo, strtoupper($plateNumber), $driverCode, $departureTime);
        $signature = hash_hmac('sha256', $payload, $this->secretKey);

        // Format: Base64URL(Payload) . "." . Signature
        return rtrim(strtr(base64_encode($payload), '+/', '-_'), '=') . '.' . substr($signature, 0, 32);
    }

    /**
     * Verify token authenticity and extract verified payload
     */
    public function verifyToken(string $token): ?array
    {
        if (!str_contains($token, '.')) {
            return null;
        }

        [$encodedPayload, $givenSig] = explode('.', $token, 2);
        $payload = base64_decode(strtr($encodedPayload, '-_', '+/'));
        if (!$payload) {
            return null;
        }

        $expectedSig = substr(hash_hmac('sha256', $payload, $this->secretKey), 0, 32);

        if (!hash_equals($expectedSig, $givenSig)) {
            return null; // Tampered or invalid token
        }

        $parts = explode('|', $payload);
        if (\count($parts) < 4) {
            return null;
        }

        return [
            'valid'          => true,
            'serial_no'      => $parts[0],
            'plate_number'   => $parts[1],
            'driver_code'    => $parts[2],
            'departure_time' => $parts[3],
        ];
    }
}

<?php

namespace App\Libraries;

use CodeIgniter\Session\Handlers\FileHandler;

/**
 * Serverless-resilient Session Handler for Vercel PHP runtime.
 *
 * Stores session files in `/tmp/pia-afms-writable/session` (via FileHandler)
 * AND mirrors the serialized session payload into an HMAC-SHA256 signed
 * HTTP-only cookie (`pia_vercel_sess`). If a subsequent request is routed
 * to a warm or cold container instance that does not have the local `/tmp`
 * session file, `read()` transparently verifies and restores the session
 * state from the signed cookie.
 */
class ServerlessSessionHandler extends FileHandler
{
    private const BACKUP_COOKIE = 'pia_vercel_sess';

    private function signingKey(): string
    {
        $key = getenv('ENCRYPTION_KEY') ?: ($_ENV['encryption.key'] ?? '');
        return hash('sha256', 'pia-afms-session-hmac:' . $key);
    }

    public function read($id): false|string
    {
        $data = parent::read($id);

        if (is_string($data) && $data !== '') {
            return $data;
        }

        $rawCookie = $_COOKIE[self::BACKUP_COOKIE] ?? '';
        if (! is_string($rawCookie) || ! str_contains($rawCookie, '.')) {
            return is_string($data) ? $data : '';
        }

        [$b64, $sig] = explode('.', $rawCookie, 2);
        $expectedSig = hash_hmac('sha256', $b64, $this->signingKey());
        if (! hash_equals($expectedSig, $sig)) {
            return is_string($data) ? $data : '';
        }

        $decoded = base64_decode($b64, true);
        if (! is_string($decoded) || $decoded === '') {
            return is_string($data) ? $data : '';
        }

        return $decoded;
    }

    public function write($id, $data): bool
    {
        $ok = parent::write($id, $data);

        if (! headers_sent()) {
            $isSecure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

            if ($data === '') {
                setcookie(self::BACKUP_COOKIE, '', [
                    'expires'  => time() - 3600,
                    'path'     => '/',
                    'secure'   => $isSecure,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            } elseif (strlen($data) < 3000) {
                $b64 = base64_encode($data);
                $sig = hash_hmac('sha256', $b64, $this->signingKey());
                setcookie(self::BACKUP_COOKIE, $b64 . '.' . $sig, [
                    'expires'  => time() + 7200,
                    'path'     => '/',
                    'secure'   => $isSecure,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
        }

        return $ok;
    }

    public function destroy($id): bool
    {
        if (! headers_sent()) {
            $isSecure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
                || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

            setcookie(self::BACKUP_COOKIE, '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        return parent::destroy($id);
    }
}

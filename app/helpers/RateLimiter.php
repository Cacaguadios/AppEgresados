<?php

require_once __DIR__ . '/../../config/environment.php';

class RateLimiter {
    private static function storageDirectory() {
        $configured = (string) app_env('RATE_LIMIT_STORAGE', '');
        if ($configured !== '' && !app_is_production()) {
            return rtrim($configured, '/\\');
        }

        return dirname(__DIR__, 2) . '/storage/cache/rate_limits';
    }

    private static function filePath($bucket, $identity) {
        $key = (string) app_env('APP_KEY', 'development-only-rate-limit-key');
        $digest = hash_hmac('sha256', (string) $bucket . '|' . (string) $identity, $key);
        return self::storageDirectory() . '/' . $digest . '.json';
    }

    private static function withRecord($bucket, $identity, callable $callback) {
        $directory = self::storageDirectory();
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo crear el almacenamiento de rate limiting.');
        }

        $path = self::filePath($bucket, $identity);
        $handle = fopen($path, 'c+');
        if ($handle === false) {
            throw new RuntimeException('No se pudo abrir el almacenamiento de rate limiting.');
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new RuntimeException('No se pudo bloquear el almacenamiento de rate limiting.');
            }

            rewind($handle);
            $raw = stream_get_contents($handle);
            $record = $raw ? json_decode($raw, true) : [];
            if (!is_array($record)) {
                $record = [];
            }

            $result = $callback($record);
            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($record));
            fflush($handle);
            flock($handle, LOCK_UN);
            return $result;
        } finally {
            fclose($handle);
        }
    }

    public static function tooManyAttempts($bucket, $identity, $maxAttempts, $windowSeconds) {
        return self::withRecord($bucket, $identity, function (&$record) use ($maxAttempts, $windowSeconds) {
            $now = time();
            $resetAt = (int) ($record['reset_at'] ?? 0);
            if ($resetAt <= $now) {
                $record = ['attempts' => 0, 'reset_at' => $now + (int) $windowSeconds];
            }

            return (int) ($record['attempts'] ?? 0) >= (int) $maxAttempts;
        });
    }

    public static function hit($bucket, $identity, $windowSeconds) {
        return self::withRecord($bucket, $identity, function (&$record) use ($windowSeconds) {
            $now = time();
            if ((int) ($record['reset_at'] ?? 0) <= $now) {
                $record = ['attempts' => 0, 'reset_at' => $now + (int) $windowSeconds];
            }

            $record['attempts'] = (int) ($record['attempts'] ?? 0) + 1;
            return $record['attempts'];
        });
    }

    public static function clear($bucket, $identity) {
        $path = self::filePath($bucket, $identity);
        if (is_file($path)) {
            unlink($path);
        }
    }
}

<?php

require_once __DIR__ . '/../../config/environment.php';

class ErrorHandler {
    private static $requestId = '';
    private static $handling = false;

    public static function register() {
        if (self::$requestId !== '') {
            return;
        }

        try {
            self::$requestId = bin2hex(random_bytes(12));
        } catch (Throwable $exception) {
            self::$requestId = str_replace('.', '', uniqid('req', true));
        }

        $debug = !app_is_production() && app_env_bool('APP_DEBUG', false);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('display_startup_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        error_reporting(E_ALL);

        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            header('X-Request-ID: ' . self::$requestId);
        }

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function requestId() {
        return self::$requestId;
    }

    public static function handleError($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        $exception = new ErrorException($message, 0, $severity, $file, $line);
        if (in_array($severity, [E_USER_ERROR, E_RECOVERABLE_ERROR], true)) {
            throw $exception;
        }

        self::logException($exception, 'php_error');
        return app_is_production();
    }

    public static function handleException($exception) {
        if (self::$handling) {
            return;
        }
        self::$handling = true;

        self::logException($exception, 'uncaught_exception');
        self::render(500, 'internal_error', 'Ocurrio un error inesperado.', $exception);
    }

    public static function handleShutdown() {
        $error = error_get_last();
        if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            return;
        }

        $exception = new ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        );
        self::logException($exception, 'fatal_error');
        self::render(500, 'fatal_error', 'Ocurrio un error inesperado.', $exception);
    }

    public static function renderHttpError($status, $code, $message) {
        self::render((int) $status, (string) $code, (string) $message, null);
    }

    public static function logEvent($level, $event, array $context = []) {
        $record = [
            'timestamp' => gmdate('c'),
            'level' => (string) $level,
            'event' => (string) $event,
            'request_id' => self::$requestId,
            'method' => (string) ($_SERVER['REQUEST_METHOD'] ?? 'CLI'),
            'path' => self::safePath(),
            'context' => self::redact($context),
        ];

        self::writeLog($record);
    }

    private static function logException($exception, $event) {
        $context = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        if (app_env_bool('APP_DEBUG', false) && !app_is_production()) {
            $context['trace'] = $exception->getTraceAsString();
        }

        self::logEvent('error', $event, $context);
    }

    private static function render($status, $code, $message, $exception = null) {
        http_response_code((int) $status);
        $debug = !app_is_production() && app_env_bool('APP_DEBUG', false);
        $publicMessage = $debug && $exception ? $exception->getMessage() : $message;

        if (!headers_sent()) {
            header('X-Request-ID: ' . self::$requestId);
        }

        if (self::wantsJson()) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'success' => false,
                'code' => $code,
                'error' => $publicMessage,
                'request_id' => self::$requestId,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }

        $errorStatus = (int) $status;
        $errorMessage = (string) $publicMessage;
        $requestId = self::$requestId;
        $template = dirname(__DIR__, 2) . '/views/errors/error.php';
        if (file_exists($template)) {
            require $template;
            return;
        }

        echo '<h1>Error</h1><p>No fue posible completar la solicitud.</p>';
    }

    private static function wantsJson() {
        $path = self::safePath();
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        return strpos($path, '/api/') !== false || strpos($accept, 'application/json') !== false;
    }

    private static function safePath() {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $path = parse_url($uri, PHP_URL_PATH);
        return is_string($path) ? $path : '';
    }

    private static function redact($value) {
        $secrets = [
            (string) app_env('APP_KEY', ''),
            (string) app_env('APP_DB_PASS', ''),
            (string) app_env('MAIL_PASS', ''),
        ];
        $secrets = array_filter($secrets, function ($secret) {
            return strlen($secret) >= 4;
        });

        if (is_array($value)) {
            $redacted = [];
            foreach ($value as $key => $item) {
                if (preg_match('/pass|password|secret|token|authorization|cookie/i', (string) $key)) {
                    $redacted[$key] = '[REDACTED]';
                } else {
                    $redacted[$key] = self::redact($item);
                }
            }
            return $redacted;
        }

        if (!is_string($value)) {
            return $value;
        }

        $redacted = $value;
        foreach ($secrets as $secret) {
            $redacted = str_replace($secret, '[REDACTED]', $redacted);
        }
        $redacted = preg_replace('#(://[^:/\s]+:)[^@/\s]+@#', '$1[REDACTED]@', $redacted);
        $redacted = preg_replace('/(pass(?:word)?|secret|token)\s*[=:]\s*[^\s;,]+/i', '$1=[REDACTED]', $redacted);
        return $redacted;
    }

    private static function writeLog(array $record) {
        $directory = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            error_log('AppEgresados: no se pudo crear storage/logs');
            return;
        }

        $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        $path = $directory . '/application.log';
        file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
        @chmod($path, 0640);
    }
}

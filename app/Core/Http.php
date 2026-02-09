<?php
declare(strict_types=1);

namespace App\Core;

use Throwable;
use ErrorException;

final class Http
{
    public static function respond(int $code, array $payload): never
    {
        $start = $GLOBALS['__REQUEST_START__'] ?? null;
        if ($start !== null) {
            $durationMs = (microtime(true) - $start) * 1000;
            header('X-Response-Time: ' . number_format($durationMs, 2) . ' ms');
        }

        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
        );
        exit;
    }

    public static function getBearerToken(): string
    {
        $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if ($h === '' && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $h = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        if (preg_match('/^\s*Bearer\s+(.+)\s*$/i', $h, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    public static function requireToken(string $expectedToken): void
    {
        if ($expectedToken === '') {
            self::respond(500, ['error' => 'Server token not configured']);
        }

        $token = self::getBearerToken();
        if ($token === '' || !hash_equals($expectedToken, $token)) {
            header('WWW-Authenticate: Bearer');
            self::respond(401, ['error' => 'Unauthorized']);
        }
    }

    public static function parseAndValidateDate(string $date): ?\DateTimeImmutable
    {
        $date = trim($date);
        $formats = ['Y-m-d', 'Y-m-d\TH:i', 'Y-m-d\TH:i:s'];

        foreach ($formats as $format) {
            $dt = \DateTimeImmutable::createFromFormat($format, $date);
            if (!$dt) continue;

            $errors = \DateTimeImmutable::getLastErrors();
            if ($errors === false) return $dt;

            if (($errors['warning_count'] ?? 0) === 0 && ($errors['error_count'] ?? 0) === 0) {
                return $dt;
            }
        }
        return null;
    }

    /**
     * Read and decode a JSON request body.
     *
     * - Returns an associative array.
     * - Responds with 400 on invalid JSON / empty body.
     * - Responds with 413 if body is too large.
     */
    public static function readJsonBody(int $maxBytes = 1048576): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false) {
            self::respond(400, ['error' => 'Unable to read request body']);
        }

        if (strlen($raw) > $maxBytes) {
            self::respond(413, ['error' => 'Request body too large']);
        }

        if (trim($raw) === '') {
            self::respond(400, ['error' => 'Empty JSON body']);
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            self::respond(400, ['error' => 'Invalid JSON body']);
        }

        if (!is_array($data)) {
            self::respond(400, ['error' => 'Invalid JSON body']);
        }

        return $data;
    }

    public static function installErrorHandlers(bool $debug): void
    {
        set_error_handler(function (int $severity, string $message, string $file, int $line) {
            if (!(error_reporting() & $severity)) return false;
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(function (Throwable $e) use ($debug) {
            error_log((string)$e);

            if ($debug) {
                self::renderHtmlException($e);
            } else {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Internal server error'], JSON_UNESCAPED_UNICODE);
            }
            exit;
        });

        register_shutdown_function(function () use ($debug) {
            $err = error_get_last();
            if (!$err) return;

            $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
            if (!in_array($err['type'], $fatalTypes, true)) return;

            $e = new ErrorException(
                $err['message'] ?? 'Fatal error',
                0,
                $err['type'] ?? E_ERROR,
                $err['file'] ?? 'unknown',
                $err['line'] ?? 0
            );

            if ($debug) {
                self::renderHtmlException($e);
            } else {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Internal server error'], JSON_UNESCAPED_UNICODE);
            }
            exit;
        });
    }

    private static function renderHtmlException(Throwable $e): void
    {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');

        $msg   = htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $file  = htmlspecialchars($e->getFile(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $line  = (int)$e->getLine();
        $trace = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        echo "<!doctype html><html lang='fr'><head><meta charset='utf-8'>";
        echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";
        echo "<title>Erreur PHP</title>";
        echo "<style>
                body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:20px;line-height:1.4}
                .box{padding:12px;border:1px solid #ddd;border-radius:10px;background:#fafafa}
                pre{white-space:pre-wrap;background:#111;color:#eee;padding:12px;border-radius:10px;overflow:auto}
                h1{margin-top:0}
              </style></head><body>";
        echo "<h1>Erreur PHP</h1>";
        echo "<div class='box'>";
        echo "<p><b>Message :</b> {$msg}</p>";
        echo "<p><b>Fichier :</b> {$file} : {$line}</p>";
        echo "</div>";
        echo "<h2>Stack trace</h2><pre>{$trace}</pre>";
        echo "</body></html>";
    }

    /**
     * Build a standardized exception payload for Http::respond.
     *
     * Caller should pass __METHOD__ from the catching scope to identify location.
     */
    public static function exceptionPayload(Throwable $e, ?string $from = null): array
    {
        $from = $from ?? '';

        // Attempt to capture the caller arguments (values only).
        $args = [];
        $bt = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
        if (isset($bt[1]['args']) && is_array($bt[1]['args'])) {
            // Limit depth and stringify non-scalars to keep payload compact
            $args = array_map(function ($v) {
                if (is_null($v)) return null;
                if (is_scalar($v)) return $v;
                if (is_array($v)) return $v;
                return is_object($v) ? sprintf('object(%s)', get_class($v)) : gettype($v);
            }, $bt[1]['args']);
        }

        $fromWithArgs = $from;
        if (!empty($args)) {
            try {
                $jsonArgs = json_encode($args, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            } catch (Throwable $t) {
                $jsonArgs = '[]';
            }
            $fromWithArgs .= ' ' . $jsonArgs;
        }

        return [
            'error' => 'Internal server error',
            'from'  => $fromWithArgs,
            'data'  => $e->getMessage(),
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ];
    }
}
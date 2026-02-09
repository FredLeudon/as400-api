<?php
declare(strict_types=1);

namespace App\Core;

final class Debug
{
    public static bool $traceEnabled = true;

    public static function init(bool $debug = false): void
    {
        // Basic runtime settings
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('display_startup_errors', $debug ? '1' : '0');
        error_reporting(E_ALL);

        // Convert warnings/notices to exceptions when debugging (helps surface issues)
        set_error_handler(function (int $severity, string $message, string $file, int $line) {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        // Nice JSON error by default (HTML if debug)
        set_exception_handler(function (\Throwable $e) use ($debug) {
            error_log((string)$e);
            http_response_code(500);

            if ($debug) {
                header('Content-Type: text/html; charset=utf-8');
                $msg   = htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $file  = htmlspecialchars($e->getFile(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $line  = (int)$e->getLine();
                $trace = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                echo "<!doctype html><html lang='fr'><head><meta charset='utf-8'>";
                echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";
                echo "<title>Erreur PHP</title>";
                echo "<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:20px;line-height:1.4}.box{padding:12px;border:1px solid #ddd;border-radius:10px;background:#fafafa}pre{white-space:pre-wrap;background:#111;color:#eee;padding:12px;border-radius:10px;overflow:auto}h1{margin-top:0}</style></head><body>";
                echo "<h1>Erreur PHP</h1>";
                echo "<div class='box'><p><b>Message :</b> {$msg}</p><p><b>Fichier :</b> {$file} : {$line}</p></div>";
                echo "<h2>Stack trace</h2><pre>{$trace}</pre>";
                echo self::traceHtmlBlock();
                echo "</body></html>";
            } else {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'error' => 'Internal server error',
                ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            }
            exit;
        });

        // Capture fatal errors (parse, core, etc.)
        register_shutdown_function(function () use ($debug) {
            $err = error_get_last();
            if (!$err) {
                return;
            }
            $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
            if (!in_array($err['type'] ?? E_ERROR, $fatalTypes, true)) {
                return;
            }

            http_response_code(500);
            if ($debug) {
                header('Content-Type: text/html; charset=utf-8');
                $msg  = htmlspecialchars((string)($err['message'] ?? 'Fatal error'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $file = htmlspecialchars((string)($err['file'] ?? 'unknown'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $line = (int)($err['line'] ?? 0);
                echo "<!doctype html><html lang='fr'><head><meta charset='utf-8'><title>Fatal error</title></head><body>";
                echo "<h1>Fatal error</h1><p><b>Message :</b> {$msg}</p><p><b>Fichier :</b> {$file} : {$line}</p>";
                echo self::traceHtmlBlock();
                echo "</body></html>";
            } else {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Internal server error'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            }
            exit;
        });
    }

    public static function trace(string $label, mixed $data = null): void
    {
        if (!self::$traceEnabled) return;

        static $buf = '';
        $time = date('H:i:s');

        if ($data !== null) {
            if (is_array($data) || is_object($data)) {
                $data = print_r($data, true);
            }
            $line = "[$time] $label: $data";
        } else {
            $line = "[$time] $label";
        }

        error_log($line);

        $buf .= htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";
        $GLOBALS['__TRACE_HTML__'] = $buf;
    }

    public static function traceHtmlBlock(): string
    {
        $t = $GLOBALS['__TRACE_HTML__'] ?? '';
        if ($t === '') return '';
        return "<h2>Traces</h2><pre>{$t}</pre>";
    }
}
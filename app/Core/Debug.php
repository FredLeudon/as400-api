<?php
declare(strict_types=1);

namespace App\Core;

final class Debug
{
    public static bool $traceEnabled = true;

    public static function init(bool $debug = false): void
    {
        self::configureProjectErrorLog();

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

            $sqlContext = self::lastSqlContextForFatal();
            if ($sqlContext !== null) {
                self::logFatalSqlContext($sqlContext);
            }

            http_response_code(500);
            if ($debug) {
                header('Content-Type: text/html; charset=utf-8');
                $msg  = htmlspecialchars((string)($err['message'] ?? 'Fatal error'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $file = htmlspecialchars((string)($err['file'] ?? 'unknown'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $line = (int)($err['line'] ?? 0);
                $sqlHtml = '';
                if ($sqlContext !== null) {
                    $json = json_encode($sqlContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                    $sqlHtml = "<h2>Derniere requete SQL</h2><pre>" .
                        htmlspecialchars((string)($json !== false ? $json : 'json_encode_failed'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                        "</pre>";
                }
                echo "<!doctype html><html lang='fr'><head><meta charset='utf-8'><title>Fatal error</title></head><body>";
                echo "<h1>Fatal error</h1><p><b>Message :</b> {$msg}</p><p><b>Fichier :</b> {$file} : {$line}</p>";
                echo $sqlHtml;
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

    public static function logVarDump(mixed $value, ?string $label = null, ?string $targetPath = null): void
    {
        $logPath = self::resolveLogPath($targetPath, 'APP_VAR_DUMP_LOG', 'logs/var-dump.log');
        if ($logPath === null) {
            return;
        }

        $dir = dirname($logPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        if (!is_dir($dir) || !is_writable($dir)) {
            return;
        }

        ob_start();
        var_dump($value);
        $dump = trim((string)ob_get_clean());

        $time = date('Y-m-d H:i:s');
        $labelValue = is_string($label) ? trim($label) : '';
        $labelPart = $labelValue !== '' ? ' ' . $labelValue : '';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? '-';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '-';

        $line = "[{$time}]{$labelPart} [{$requestMethod} {$requestUri}]\n{$dump}\n\n";
        @file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX);
    }

    public static function traceHtmlBlock(): string
    {
        $t = $GLOBALS['__TRACE_HTML__'] ?? '';
        if ($t === '') return '';
        return "<h2>Traces</h2><pre>{$t}</pre>";
    }

    /** @return ?array<string,mixed> */
    private static function lastSqlContextForFatal(): ?array
    {
        $ctx = $GLOBALS['__DB_ACTIVE_QUERY__'] ?? ($GLOBALS['__DB_LAST_QUERY__'] ?? null);
        if (!is_array($ctx)) return null;

        if (!isset($ctx['duration_ms']) && isset($ctx['started_at_ts']) && is_numeric($ctx['started_at_ts'])) {
            $ctx['duration_ms'] = round((microtime(true) - (float)$ctx['started_at_ts']) * 1000, 2);
        }

        unset($ctx['started_at_ts']);
        return $ctx;
    }

    /** @param array<string,mixed> $ctx */
    private static function logFatalSqlContext(array $ctx): void
    {
        $json = json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        error_log('[FATAL SQL CONTEXT] ' . ($json !== false ? $json : 'json_encode_failed'));
    }

    private static function configureProjectErrorLog(): void
    {
        ini_set('log_errors', '1');

        $logPath = self::resolveLogPath(null, 'APP_ERROR_LOG', 'logs/php-error.log');
        if ($logPath === null) {
            return;
        }

        $dir = dirname($logPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        if (!is_dir($dir) || !is_writable($dir)) {
            return;
        }

        if (!file_exists($logPath)) {
            @touch($logPath);
        }

        if (file_exists($logPath) && !is_writable($logPath)) {
            return;
        }

        ini_set('error_log', $logPath);
        $GLOBALS['__APP_ERROR_LOG_FILE__'] = $logPath;
    }

    private static function resolveLogPath(?string $pathOverride, string $envVar, string $defaultRelativePath): ?string
    {
        $projectRoot = dirname(__DIR__, 2);

        $candidate = is_string($pathOverride) ? trim($pathOverride) : '';
        if ($candidate === '') {
            $envPath = getenv($envVar);
            $candidate = is_string($envPath) ? trim($envPath) : '';
        }
        if ($candidate === '') {
            $candidate = $defaultRelativePath;
        }

        if (!self::isAbsolutePath($candidate)) {
            $candidate = $projectRoot . '/' . ltrim($candidate, '/');
        }

        return $candidate;
    }

    private static function isAbsolutePath(string $path): bool
    {
        if ($path === '') return false;
        if ($path[0] === '/' || $path[0] === '\\') return true;
        return (bool)preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }
}

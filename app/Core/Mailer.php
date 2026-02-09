<?php
declare(strict_types=1);

namespace App\Core;

final class Mailer
{
    private static string $lastError = '';

    public static function lastError(): string
    {
        return self::$lastError;
    }

    /**
     * Send an HTML email using SMTP settings from .env.
     *
     * Required env: MAIL_HOST, MAIL_PORT, MAIL_USER, MAIL_PASS
     * Optional env: MAIL_SECURE (tls|ssl|none), MAIL_FROM_EMAIL, MAIL_FROM_NAME,
     *               MAIL_REPLY_TO, MAIL_TIMEOUT, MAIL_HELO
     */
    public static function sendHtml(string|array $to, string $subject, string $html): bool
    {
        self::$lastError = '';

        $host = trim((string)getenv('MAIL_HOST'));
        $port = (int)(getenv('MAIL_PORT') ?: 587);
        $user = (string)getenv('MAIL_USER');
        $pass = (string)getenv('MAIL_PASS');
        $secure = strtolower((string)(getenv('MAIL_SECURE') ?: 'tls'));
        $fromEmail = (string)(getenv('MAIL_FROM_EMAIL') ?: $user);
        $fromName = (string)getenv('MAIL_FROM_NAME');
        $replyTo = (string)getenv('MAIL_REPLY_TO');
        $timeout = (int)(getenv('MAIL_TIMEOUT') ?: 10);
        $helo = (string)(getenv('MAIL_HELO') ?: gethostname());

        if ($host === '' || $port <= 0) {
            self::$lastError = 'MAIL_HOST/MAIL_PORT not configured';
            return false;
        }
        if ($fromEmail === '') {
            self::$lastError = 'MAIL_FROM_EMAIL or MAIL_USER not configured';
            return false;
        }

        $recipients = self::normalizeRecipients($to);
        if ($recipients === []) {
            self::$lastError = 'No recipient provided';
            return false;
        }

        $socket = self::connect($host, $port, $secure, $helo, $timeout);
        if (!$socket) {
            return false;
        }

        if ($user !== '') {
            if (!self::command($socket, 'AUTH LOGIN', [334])) {
                self::close($socket);
                return false;
            }
            if (!self::command($socket, base64_encode($user), [334])) {
                self::close($socket);
                return false;
            }
            if (!self::command($socket, base64_encode($pass), [235])) {
                self::close($socket);
                return false;
            }
        }

        if (!self::command($socket, 'MAIL FROM:<' . $fromEmail . '>', [250])) {
            self::close($socket);
            return false;
        }

        foreach ($recipients as $addr) {
            if (!self::command($socket, 'RCPT TO:<' . $addr . '>', [250, 251])) {
                self::close($socket);
                return false;
            }
        }

        if (!self::command($socket, 'DATA', [354])) {
            self::close($socket);
            return false;
        }

        $headers = self::buildHeaders($recipients, $fromEmail, $fromName, $replyTo, $subject);
        $body = quoted_printable_encode($html);
        $body = str_replace(["\r\n", "\r"], "\n", $body);
        $body = str_replace("\n", "\r\n", $body);
        $payload = $headers . "\r\n\r\n" . $body;
        $payload = preg_replace('/^\./m', '..', $payload);

        fwrite($socket, $payload . "\r\n.\r\n");
        if (!self::expect($socket, [250])) {
            self::close($socket);
            return false;
        }

        self::command($socket, 'QUIT', [221, 250]);
        self::close($socket);
        return true;
    }

    private static function connect(string $host, int $port, string $secure, string $helo, int $timeout)
    {
        $target = $secure === 'ssl' ? 'ssl://' . $host : $host;
        $socket = @fsockopen($target, $port, $errno, $errstr, $timeout);
        if (!$socket) {
            self::$lastError = 'Connection failed: ' . $errstr . ' (' . $errno . ')';
            return false;
        }

        stream_set_timeout($socket, $timeout);
        if (!self::expect($socket, [220])) {
            self::close($socket);
            return false;
        }

        $ehlo = self::command($socket, 'EHLO ' . $helo, [250]);
        if ($ehlo === false) {
            self::close($socket);
            return false;
        }

        if ($secure === 'tls') {
            if (!self::hasCapability($ehlo, 'STARTTLS')) {
                self::$lastError = 'SMTP server does not support STARTTLS';
                self::close($socket);
                return false;
            }
            if (!self::command($socket, 'STARTTLS', [220])) {
                self::close($socket);
                return false;
            }
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                self::$lastError = 'STARTTLS negotiation failed';
                self::close($socket);
                return false;
            }
            if (!self::command($socket, 'EHLO ' . $helo, [250])) {
                self::close($socket);
                return false;
            }
        }

        return $socket;
    }

    private static function close($socket): void
    {
        if (is_resource($socket)) {
            fclose($socket);
        }
    }

    private static function buildHeaders(
        array $recipients,
        string $fromEmail,
        string $fromName,
        string $replyTo,
        string $subject
    ): string {
        $headers = [];
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'From: ' . self::formatAddress($fromEmail, $fromName);
        $headers[] = 'To: ' . implode(', ', array_map(fn ($a) => '<' . $a . '>', $recipients));
        $headers[] = 'Subject: ' . self::encodeHeader($subject);
        $headers[] = 'Message-ID: <' . uniqid('mail_', true) . '@' . self::safeDomain() . '>';
        if ($replyTo !== '') {
            $headers[] = 'Reply-To: ' . self::formatAddress($replyTo, '');
        }
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: quoted-printable';
        $headers[] = 'X-Mailer: as400-apis';

        return implode("\r\n", $headers);
    }

    private static function encodeHeader(string $value): string
    {
        if (function_exists('mb_encode_mimeheader')) {
            return mb_encode_mimeheader($value, 'UTF-8', 'Q', "\r\n");
        }
        return $value;
    }

    private static function formatAddress(string $email, string $name): string
    {
        if ($name === '') {
            return '<' . $email . '>';
        }
        return self::encodeHeader($name) . ' <' . $email . '>';
    }

    private static function safeDomain(): string
    {
        $host = (string)getenv('MAIL_HELO');
        if ($host !== '') {
            return preg_replace('/[^a-z0-9\.\-]/i', '', $host) ?: 'localhost';
        }
        $hn = gethostname();
        return $hn !== false ? $hn : 'localhost';
    }

    private static function normalizeRecipients(string|array $to): array
    {
        $list = [];
        if (is_array($to)) {
            $list = $to;
        } else {
            $list = preg_split('/[,;]/', $to) ?: [];
        }

        $clean = [];
        foreach ($list as $addr) {
            $addr = trim((string)$addr);
            if ($addr !== '') {
                $clean[] = $addr;
            }
        }
        return $clean;
    }

    private static function hasCapability(array $response, string $capability): bool
    {
        $capability = strtoupper($capability);
        foreach ($response as $line) {
            if (str_contains(strtoupper($line), $capability)) {
                return true;
            }
        }
        return false;
    }

    private static function command($socket, string $command, array $expectCodes)
    {
        fwrite($socket, $command . "\r\n");
        return self::expect($socket, $expectCodes);
    }

    private static function expect($socket, array $expectCodes)
    {
        $lines = self::readResponse($socket);
        if ($lines === []) {
            self::$lastError = 'Empty SMTP response';
            return false;
        }
        $code = (int)substr($lines[0], 0, 3);
        if (!in_array($code, $expectCodes, true)) {
            self::$lastError = 'SMTP error ' . $code . ': ' . implode(' | ', $lines);
            return false;
        }
        return $lines;
    }

    private static function readResponse($socket): array
    {
        $lines = [];
        while (!feof($socket)) {
            $line = fgets($socket, 515);
            if ($line === false) {
                break;
            }
            $line = rtrim($line, "\r\n");
            $lines[] = $line;
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }
        return $lines;
    }
}

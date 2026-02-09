<?php
declare(strict_types=1);

namespace App\Phone;

use App\Core\Http;
use App\Core\Debug;

final class Phone
{
    public static function check(string $phone, string $country): array
    {
        if ($phone === '' || $country === '') {
            Http::respond(400, ['error' => 'Missing parameters', 'expected' => '?phone=XXXXXXXX&country=FR']);
        }
        if (!preg_match('/^[0-9+\.\-\s]+$/', $phone)) {
            Http::respond(400, ['error' => 'Invalid phone format']);
        }
        if (!preg_match('/^[A-Z]{2}$/', $country)) {
            Http::respond(400, ['error' => 'Invalid country code']);
        }

        $python  = '/QOpenSys/pkgs/bin/python3';
        $script  = '/www/apis/python/phoneNumber.py';

        $cmd = sprintf(
            '%s %s %s %s 2>&1',
            escapeshellcmd($python),
            escapeshellarg($script),
            escapeshellarg($phone),
            escapeshellarg($country)
        );

        Debug::trace('python cmd', $cmd);

        $output = shell_exec($cmd);
        if ($output === null) {
            Http::respond(500, ['error' => 'Python execution failed']);
        }

        $result = json_decode($output, true);
        if ($result === null) {
            Http::respond(500, ['error' => 'Invalid JSON from Python', 'output' => $output]);
        }

        return $result;
    }
}

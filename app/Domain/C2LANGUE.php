<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use Throwable;

use App\Core\Http;
use App\Core\clFichier;

final class C2LANGUE extends clFichier
{
    protected static string $table = 'C2LANGUE';
    protected static array $primaryKey = ['C2LANG'];

    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'C2LANG'  => ['label' => 'Code langue', 'type' => 'CHAR'],
        'C2LIB'   => ['label' => 'Libellé de la langue', 'type' => 'CHAR'],
        'C2SOC'   => ['label' => 'N° de la société', 'type' => 'CHAR'],
        'C2ACT'   => ['label' => 'Actif', 'type' => 'CHAR'],
        'C2HOACT' => ['label' => 'Horodatage action', 'type' => 'TIMESTMP'],
        'C2UTIL'  => ['label' => 'Utilisateur', 'type' => 'CHAR'],
        'C2PGM'   => ['label' => 'Programme', 'type' => 'CHAR'],
    ];

    /**
     * Cache par bibliothèque AS/400.
     *
     * @var array<string,array{rows:array<int,array<string,mixed>>, byId:array<string,array<string,mixed>>}>
     */
    private static array $cacheByLibrary = [];

    private static function loadCache(PDO $pdo, string $library): void
    {
        if (array_key_exists($library, self::$cacheByLibrary)) {
            return;
        }

        $fic = new self($pdo, $library);

        // Avoid "Empty criteria" by using a harmless criteria (C2LANG is NOT NULL).
        $rows = $fic
            ->where('C2LANG', '!=', '')
            ->orderBy('C2LANG')
            ->get();

        if (!is_array($rows)) {
            $rows = [];
        }

        // Move FRA first, then alphabetical.
        usort($rows, static function ($a, $b): int {
            $la = strtoupper((string)($a['C2LANG'] ?? ''));
            $lb = strtoupper((string)($b['C2LANG'] ?? ''));
            if ($la === 'FRA' && $lb !== 'FRA') return -1;
            if ($lb === 'FRA' && $la !== 'FRA') return 1;
            return $la <=> $lb;
        });

        $byId = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (string)($row['C2LANG'] ?? '');
            if ($id === '') {
                continue;
            }
            $byId[$id] = $row;
        }

        self::$cacheByLibrary[$library] = [
            'rows' => $rows,
            'byId' => $byId,
        ];
    }

    public static function getById(PDO $pdo, string $companyCode, string $Id): array
    {
        try {
            $company = Company::get($companyCode);
            if (!$company) return [];

            $library = (string)($company['main_library'] ?? '');
            if ($library === '') return [];

            self::loadCache($pdo, $library);
            $row = self::$cacheByLibrary[$library]['byId'][$Id] ?? null;
            return is_array($row) ? $row : [];

        } catch (Throwable $e) {
            $debug = (getenv('APP_DEBUG') === '1');
            $payload = [
                'error' => 'Internal server error',
                'from'  => 'C2LANGUE::getById()',
                'data'  => $e->getMessage(),
            ];
            if ($debug) {
                $payload['file']  = $e->getFile();
                $payload['line']  = $e->getLine();
                $payload['trace'] = $e->getTraceAsString();
            }
            Http::respond(500, $payload);
        }
    }

    public static function all(PDO $pdo, string $companyCode): array
    {
        try {
            $company = Company::get($companyCode);
            if (!$company) return [];

            $library = (string)($company['main_library'] ?? '');
            if ($library === '') return [];

            self::loadCache($pdo, $library);
            return self::$cacheByLibrary[$library]['rows'] ?? [];

        } catch (Throwable $e) {
            $debug = (getenv('APP_DEBUG') === '1');
            $payload = [
                'error' => 'Internal server error',
                'from'  => 'C2LANGUE::all()',
                'data'  => $e->getMessage(),
            ];
            if ($debug) {
                $payload['file']  = $e->getFile();
                $payload['line']  = $e->getLine();
                $payload['trace'] = $e->getTraceAsString();
            }
            Http::respond(500, $payload);
        }
    }

}

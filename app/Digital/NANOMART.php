<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

/**
 * MATIS.NANOMART
 *
 * Unique key: NA_AS00001 => [NAART]
 * Indexes: NANOL001, NANOL002
 */
final class NANOMART extends clFichier
{
    protected static string $table = 'NANOMART';
    protected static array $primaryKey = ['NAART'];

    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'NAART'    => ['label' => 'NAART',    'type' => 'CHAR',      'nullable' => false],
        'NACODSEG' => ['label' => 'NACODSEG', 'type' => 'SMALLINT',  'nullable' => true],
        'NACODFAM' => ['label' => 'NACODFAM', 'type' => 'SMALLINT',  'nullable' => true],
        'NACODSSF' => ['label' => 'NACODSSF', 'type' => 'SMALLINT',  'nullable' => true],
        'NACODGAM' => ['label' => 'NACODGAM', 'type' => 'SMALLINT',  'nullable' => true],
        'NACODSER' => ['label' => 'NACODSER', 'type' => 'SMALLINT',  'nullable' => true],
        'NACODMOD' => ['label' => 'NACODMOD', 'type' => 'SMALLINT',  'nullable' => true],
    ];

        /**
     * Get rows for an article code (NAART).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function getByArticle(PDO $pdo, string $naart): array
    {
        try {
            $library = 'MATIS';
            $naart = trim($naart);
            if ($naart === '') return [];

            return self::for($pdo, $library)
                ->select(array_keys(self::$columns))
                ->whereEq('NAART', $naart)
                ->orderBy('NAART','ASC')
                ->getModels();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * Get hydrated model for an article (first match).
     */
    public static function getModelByArticle(PDO $pdo,  string $naart): ?static
    {
        try {
            $library = 'MATIS';
            $naart = trim($naart);
            if ($naart === '') return null;

            return self::for($pdo, $library)
                ->select(array_keys(self::$columns))
                ->whereEq('NAART', $naart)
                ->firstModel();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

 /**
     * Get hydrated model for an article (first match).
     */
    public static function getModelsByArticle(PDO $pdo,  string $naart): ?array
    {
        try {
            $library = 'MATIS';
            $naart = trim($naart);
            if ($naart === '') return [];

            return self::for($pdo, $library)
                ->select(array_keys(self::$columns))
                ->whereEq('NAART', $naart)
                ->getModels();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * List models filtered by optional attributes (segment/fam/subfam/gam/ser/mod).
     *
     * @param array<string,int|string|null> $filters
     * @return array<int,static>
     */
    public static function listByAttributes(PDO $pdo,  array $filters = []): array
    {
        try {
            $library = 'MATIS';
            $qb = self::for($pdo, $library)->select(array_keys(self::$columns));
            $map = [
                'segment' => 'NACODSEG',
                'famille' => 'NACODFAM',
                'sousfam' => 'NACODSSF',
                'gamme'   => 'NACODGAM',
                'serie'   => 'NACODSER',
                'modele'  => 'NACODMOD',
            ];
            foreach ($map as $k => $col) {
                if (isset($filters[$k]) && $filters[$k] !== null && trim((string)$filters[$k]) !== '') {
                    $qb->whereEq($col, trim((string)$filters[$k]));
                }
            }
            $qb->orderBy('NAART','ASC');
            return $qb->getModels();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

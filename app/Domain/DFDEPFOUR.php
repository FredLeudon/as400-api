<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

/**
 * MATIS.DFDEPFOUR
 *
 * Keys:
 *  - DFIDX00001 => [DFDEP]
 *  - DFIDX00002 => [DFFOUR]
 */
final class DFDEPFOUR extends clFichier
{
    protected static string $table = 'DFDEPFOUR';
    protected static array $primaryKey = ['DFDEP'];

    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'DFDEP'     => ['label' => 'DFDEP',     'type' => 'CHAR',     'nullable' => false],
        'DFFOUR'    => ['label' => 'DFFOUR',    'type' => 'CHAR',     'nullable' => false],
        'DFSOCGFOU' => ['label' => 'DFSOCGFOU', 'type' => 'CHAR',     'nullable' => false],
    ];

    /**
     * Get rows by depot code (DFDEP).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function getByDepot(PDO $pdo, string $dfdep): array
    {
        try {
            $library = 'MATIS';
            $dfdep = trim($dfdep);
            if ($dfdep === '') return [];

            return self::for($pdo, $library)
                ->select(array_keys(self::$columns))
                ->whereEq('DFDEP', $dfdep)
                ->orderBy('DFDEP','ASC')
                ->getModels();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * Get single hydrated model by depot code.
     */
    public static function getModelByDepot(PDO $pdo, string $dfdep): ?static
    {
        try {
            $library = 'MATIS';
            $dfdep = trim($dfdep);
            if ($dfdep === '') return null;

            return self::for($pdo, $library)
                ->select(array_keys(self::$columns))
                ->whereEq('DFDEP', $dfdep)
                ->firstModel();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * Get single hydrated model by depot code.
     */
    public static function getModelByFour(PDO $pdo, string $dffour): ?static
    {
        try {
            $library = 'MATIS';
            $dffour = trim($dffour);
            if ($dffour === '') return null;

            return self::for($pdo, $library)
                ->select(array_keys(self::$columns))
                ->whereEq('DFFOUR', $dffour)
                ->firstModel();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
    /**
     * List rows by fournisseur code (DFFOUR).
     *
     * @return array<int,static>
     */
    public static function listByFourn(PDO $pdo, ?string $dffour = null): array
    {
        try {
            $library = 'MATIS';
            $qb = self::for($pdo, $library)->select(array_keys(self::$columns));
            if ($dffour !== null && trim($dffour) !== '') {
                $qb->whereEq('DFFOUR', trim($dffour));
            }
            $qb->orderBy('DFFOUR','ASC');
            return $qb->getModels();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

/**
 * MATIS.FIFOUINT
 *
 * Unique key: [FIFOUR]
 * Index: FIL1 => [FISOC]
 */
final class FIFOUINT extends clFichier
{
    protected static string $table = 'FIFOUINT';
    protected static array $primaryKey = ['FIFOUR'];

    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'FIFOUR'  => ['label' => 'FIFOUR',  'type' => 'CHAR',     'nullable' => false],
        'FISOC'   => ['label' => 'FISOC',   'type' => 'CHAR',     'nullable' => false],
        'FIACT'   => ['label' => 'FIACT',   'type' => 'CHAR',     'nullable' => false],
        'FIHOACT' => ['label' => 'FIHOACT', 'type' => 'TIMESTMP', 'nullable' => false],
        'FIUTIL'  => ['label' => 'FIUTIL',  'type' => 'CHAR',     'nullable' => false],
        'FIPGM'   => ['label' => 'FIPGM',   'type' => 'CHAR',     'nullable' => false],
    ];

     /**
     * Get a FIFOUINT row by FIFOUR.
     *
     * @return array<string,mixed> Empty array if not found.
     */
    public static function getByFour(PDO $pdo, string $fifour): array
    {
        try {
            $library = 'MATIS';
            $fifour = trim($fifour);
            if ($fifour === '') return [];

            $row = self::for($pdo, $library)
                ->select(array_keys(self::$columns))
                ->whereEq('FIFOUR', $fifour)
                ->first();

            return is_array($row) ? $row : [];
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * Get hydrated model by id.
     */
    public static function getModelByFour(PDO $pdo, string $fifour): ?static
    {
        try {
            $library = 'MATIS';
            $fifour = trim($fifour);
            if ($fifour === '') return null;
            return self::for($pdo, $library)
                ->select(array_keys(self::$columns))
                ->whereEq('FIFOUR', $fifour)
                ->firstModel();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    public static function getModelBySoc(PDO $pdo, string $fifsoc): ?static
    {
        try {
            $library = 'MATIS';
            $fifsoc = trim($fifsoc);
            if ($fifsoc === '') return null;
            return self::for($pdo, $library)
                ->select(array_keys(self::$columns))
                ->whereEq('FISOC', $fifsoc)
                ->firstModel();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
    
}

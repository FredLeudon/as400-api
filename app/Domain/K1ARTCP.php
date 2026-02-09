<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

/**
 * Domain model for AS/400 file K1ARCPT
 */
final class K1ARCPT extends clFichier
{
    protected static string $table = 'K1ARTCP';
    // Primary key guessed — adjust if different in your AS/400 schema
    protected static array $primaryKey = ['K1ART','K1CPT'];

    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'K1ART'   => ['label' => 'Article',       'type' => 'CHAR',     'nullable' => false],
        'K1SOC'   => ['label' => 'Société',       'type' => 'CHAR',     'nullable' => false],
        'K1CPT'   => ['label' => 'Compte',        'type' => 'CHAR',     'nullable' => false],
        'K1LIB'   => ['label' => 'Libellé',       'type' => 'CHAR',     'nullable' => false],
        'K1ACT'   => ['label' => 'Actif',         'type' => 'CHAR',     'nullable' => false],
        'K1HOACT' => ['label' => 'Horodatage',    'type' => 'TIMESTMP', 'nullable' => false],
        'K1UTIL'  => ['label' => 'Utilisateur',   'type' => 'CHAR',     'nullable' => false],
        'K1PGM'   => ['label' => 'Programme',     'type' => 'CHAR',     'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;

        $library = (string)($company['main_library'] ?? '');
        return $library !== '' ? $library : null;
    }

    /**
     * Get one record by K1ART + K1CPT
     *
     * @return array<string,mixed> Empty array if not found.
     */
    public static function getById(PDO $pdo, string $companyCode, string $k1art): array
    {
        try {
            $library = self::libraryOf($companyCode);
            if ($library === null) return [];
            $k1art = trim($k1art);            
            if ($k1art === '') return [];
            $row = self::for($pdo, $library)               
                ->whereEq('K1ART', $k1art)
                ->whereEq('K1SOC', $companyCode)
                ->first();
            return is_array($row) ? $row : [];

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * Get one hydrated model by id
     */
    public static function getModelById(PDO $pdo, string $companyCode, string $k1art): ?static
    {
        try {
            $library = self::libraryOf($companyCode);
            if ($library === null) return null;
            $k1art = trim($k1art);            
            if ($k1art === '') return null;
            return self::for($pdo, $library)                
                ->whereEq('K1ART', $k1art)
                ->whereEq('K1SOC', $companyCode)
                ->firstModel();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * List all records for an article
     *
     * @return array<int,static>
     */
    public static function listByArticle(PDO $pdo, string $companyCode, string $k1art): array
    {
        try {
            $library = self::libraryOf($companyCode);
            if ($library === null) return [];

            $k1art = trim($k1art);
            if ($k1art === '') return [];

            return self::for($pdo, $library)
                ->select(['K1ART','K1CPT','K1LIB','K1ACT'])
                ->whereEq('K1ART', $k1art)
                ->orderBy('K1CPT','ASC')
                ->getModels();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

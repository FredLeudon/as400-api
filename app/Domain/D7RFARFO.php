<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class D7RFARFO extends clFichier
{
    protected static string $table = 'D7RFARFO';
    protected static array $primaryKey = ['D7BASE'];
    /** @var array<int,array<int,string>> */
    protected static array $uniqueKeys = [
        ['D7BASE'], // D7L99
    ];
    /** @var array<string,array<int,string>> */
    protected static array $indexes = [
        // Source mentions A1ART; using D7ART to match D7RFARFO columns.
        'A1C0L0' => ['D7ART'],
        'A1C0L1' => ['D7ART'],
        'D7L0'   => ['D7ART', 'D7FOUR'],
        'D7L1'   => ['D7ART', 'D7NORD', 'D7FOUR'],
        'D7L10'  => ['D7ART', 'D7NORD', 'D7FOUR'],
        'D7L100001' => ['D7ART', 'D7SOC'],
        'D7L11'  => ['D7ART', 'D7SOC', 'D7NORD', 'D7FOUR'],
        'D7L12'  => ['D7ART', 'D7SOC', 'D7FOUR', 'D7NORD'],
        'D7L2'   => ['D7FOUR', 'D7ART', 'D7NORD'],
        'D7L3'   => ['D7ART', 'D7FOUR', 'D7NORD'],
        'D7L4'   => ['D7ART', 'D7NORD'],
        'D7L5'   => ['D7FOUR', 'D7ART'],
        'D7L8'   => ['D7BARE'],
        'D7L9'   => ['D7FOUR', 'D7REFF'],
        'D7L99'  => ['D7BASE'],
    ];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'D7ART'   => ['label' => 'Code article',                 'type' => 'CHAR',     'nullable' => false],
        'D7FOUR'  => ['label' => 'Code fournisseur',             'type' => 'CHAR',     'nullable' => false],
        'D7NORD'  => ['label' => 'Numero ordre',                 'type' => 'NUMERIC',  'nullable' => false],
        'D7REFF'  => ['label' => 'Reference fournisseur',        'type' => 'CHAR',     'nullable' => false],
        'D7SOC'   => ['label' => 'Numero de societe',            'type' => 'CHAR',     'nullable' => false],
        'D7QTMF'  => ['label' => 'Quantite mini commande',       'type' => 'DECIMAL',  'nullable' => false],
        'D7DELA'  => ['label' => 'Delais appro en sem',          'type' => 'DECIMAL',  'nullable' => false],
        'D7COND'  => ['label' => 'Quantite de conditionnement',  'type' => 'DECIMAL',  'nullable' => false],
        'D7DATR'  => ['label' => 'Date de reference',            'type' => 'NUMERIC',  'nullable' => false],
        'D7BASE'  => ['label' => 'Numero unique',               'type' => 'NUMERIC',  'nullable' => false],
        'D7BARE'  => ['label' => 'Code barre',                   'type' => 'NUMERIC',  'nullable' => false],
        'D7ACT'   => ['label' => 'Actif',                        'type' => 'CHAR',     'nullable' => false],
        'D7HOACT' => ['label' => 'Horodatage action',            'type' => 'TIMESTMP', 'nullable' => false],
        'D7UTIL'  => ['label' => 'Utilisateur',                  'type' => 'CHAR',     'nullable' => false],
        'D7PGM'   => ['label' => 'Programme',                    'type' => 'CHAR',     'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = \App\Domain\Company::get($companyCode);
        if (!$company) return null;

        $library = (string)($company['main_library'] ?? '');
        return $library !== '' ? $library : null;
    }

    /**
     * Get one row by base id (D7BASE).
     *
     * @return array<string,mixed> Empty array if not found.
     */
    public static function getByBase(
        PDO $pdo,
        string $companyCode,
        string $baseId
    ): array {
        try {
            $companyKey = trim($companyCode);
            if ($companyKey === '') return [];

            $library = self::libraryOf($companyKey);
            if ($library === null) return [];

            $baseId = trim($baseId);
            if ($baseId === '') return [];

            $row = self::for($pdo, $library)
                ->whereEq('D7BASE', $baseId)
                ->first();

            return is_array($row) ? $row : [];

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    public static function getByCompanyProduct( PDO $pdo, string $companyCode, string $productId ): array 
    {
        try {
            $companyKey = trim($companyCode);
            if ($companyKey === '') return [];

            $library = self::libraryOf($companyKey);
            if ($library === null) return [];

            $productId = trim($productId);
            if ($productId === '') return [];

            $rows = self::for($pdo, $library)
                ->whereEq('D7ART', $productId)
                ->whereEq('D7SOC', $companyKey)
                ->orderBy('D7NORD', 'ASC')
                ->get();

            return $rows;

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
    public static function getModelsByCompanyProduct( PDO $pdo, string $companyCode, string $productId ): array 
    {
        try {
            $companyKey = trim($companyCode);
            if ($companyKey === '') return [];
            $library = self::libraryOf($companyKey);
            if ($library === null) return [];
            $productId = trim($productId);
            if ($productId === '') return [];
            $rows = self::for($pdo, $library)
                ->whereEq('D7ART', $productId)
                ->whereEq('D7SOC', $companyKey)
                ->orderBy('D7NORD', 'ASC')
                ->getModels();
            return $rows;

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
    
    public static function getMBIMainSupplier( PDO $pdo, string $productId ): ?static
    {
        try {
            $library = 'MATIS';
            $productId = trim($productId);
            if ($productId === '') return null;
            $row = self::for($pdo, $library)
                ->whereEq('D7ART', $productId)
                ->whereIn('D7SOC', ['06','38','40'])
                ->whereNotIn('D7FOUR', ['63840','63841'])
                ->orderBy('D7NORD', 'ASC')
                ->firstModel();
            return $row;

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

}

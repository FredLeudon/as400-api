<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class R5GESPRA extends clFichier
{
    protected static string $table = 'R5GESPRA';
    protected static array $primaryKey = ['R5BASE'];
    /** @var array<int,array<int,string>> */
    protected static array $uniqueKeys = [];
    /** @var array<string,array<int,string>> */
    protected static array $indexes = [
        'R5L0' => ['R5ART', 'R5DAPL', 'R5DMPL', 'R5DJPL'],
        'R5L1' => ['R5ART', 'R5DMAJ'],
        'R5L2' => ['R5ART', 'R5BASE'],
        'R5L3' => ['R5ART', 'R5DMAJ'],
    ];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'R5ART'  => ['label' => 'Article',                             'type' => 'CHAR',    'nullable' => false],
        'R5FOU'  => ['label' => 'Fournisseur',                         'type' => 'CHAR',    'nullable' => false],
        'R5PNBA' => ['label' => 'Tarif de base',                       'type' => 'DECIMAL', 'nullable' => false],
        'R5RBAS' => ['label' => 'Remise de base',                      'type' => 'DECIMAL', 'nullable' => false],
        'R5R1CO' => ['label' => 'Remise 1 complémentaire',             'type' => 'DECIMAL', 'nullable' => false],
        'R5R2CO' => ['label' => 'Remise 2 complémentaire',             'type' => 'DECIMAL', 'nullable' => false],
        'R5PNRE' => ['label' => 'Prix d achat aprés remise',           'type' => 'DECIMAL', 'nullable' => false],
        'R5DOUA' => ['label' => '% de frais de douane',                'type' => 'DECIMAL', 'nullable' => false],
        'R5CAS'  => ['label' => '% de frais de casse',                 'type' => 'DECIMAL', 'nullable' => false],
        'R5PORT' => ['label' => '% de frais de port',                  'type' => 'DECIMAL', 'nullable' => false],
        'R5EMB'  => ['label' => '% de frais d emballage',              'type' => 'DECIMAL', 'nullable' => false],
        'R5RECO' => ['label' => '% de frais de reconditionnement',     'type' => 'DECIMAL', 'nullable' => false],
        'R5PNFR' => ['label' => 'Prix d achat aprés frais',            'type' => 'DECIMAL', 'nullable' => false],
        'R5CDEV' => ['label' => 'Code de la devise',                   'type' => 'CHAR',    'nullable' => false],
        'R5TDEV' => ['label' => 'Taux de la devise',                   'type' => 'DECIMAL', 'nullable' => false],
        'R5PAFF' => ['label' => 'Prix d achat en FF',                  'type' => 'DECIMAL', 'nullable' => false],
        'R5DAPL' => ['label' => 'Date d application du PRA',           'type' => 'CHAR',    'nullable' => false],
        'R5DMPL' => ['label' => 'Date d application du PRA',           'type' => 'CHAR',    'nullable' => false],
        'R5DJPL' => ['label' => 'Date d application du PRA',           'type' => 'CHAR',    'nullable' => false],
        'R5DMAJ' => ['label' => 'Date de M.A.J.',                      'type' => 'NUMERIC', 'nullable' => false],
        'R5BASE' => ['label' => 'No unique base de donnée',            'type' => 'NUMERIC', 'nullable' => false],
        'R5SOC'  => ['label' => 'N° de société',                       'type' => 'CHAR',    'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = \App\Domain\Company::get($companyCode);
        if (!$company) return null;

        $library = (string)($company['library'] ?? '');
        return $library !== '' ? $library : null;
    }

     /**
     * Get one dépot by id (A1ART).
     *
     * @return ?array Empty array if not found.
     */
    public static function getModelsByCompanySupplierProduct( \PDO $pdo, string $companyCode, string $supplierCode, string $productCode ): ?array {
        try {
            $companyKey = trim($companyCode);
            if ($companyKey === '') return null;
            $library = self::libraryOf($companyKey);
            if ($library === null) return null;           
            $productCode = trim($productCode);
            if ($productCode === '') return null;
            $supplierCode = trim($supplierCode);
            if ($supplierCode === '') return null;
            return self::for($pdo, $library)                
                ->whereEq('R5ART', $productCode)
                ->whereEq('R5FOU', $supplierCode)
                ->orderBy('R5DAPL','DESC')->orderBy('R5DMPL', 'DESC')->orderBy('R5DJPL', 'DESC')
                ->getModels();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

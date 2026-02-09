<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class ADARTDEP extends clFichier
{
    protected static string $table = 'ADARTDEP';
    protected static array $primaryKey = ['ADDEP', 'ADART'];
    /** @var array<int,array<int,string>> */
    protected static array $uniqueKeys = [
        ['ADDEP', 'ADART'], // ADL1
    ];
    /** @var array<string,array<int,string>> */
    protected static array $indexes = [
        'ADL0' => ['ADART', 'ADPRIN'],
        'ADL1' => ['ADDEP', 'ADART'],
    ];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'ADART'   => ['label' => 'Article',                         'type' => 'CHAR',     'nullable' => false],
        'ADDEP'   => ['label' => 'Depot',                           'type' => 'CHAR',     'nullable' => false],
        'ADPRIN'  => ['label' => 'Principal',                       'type' => 'CHAR',     'nullable' => false],
        'ADACT'   => ['label' => 'Actif',                           'type' => 'CHAR',     'nullable' => false],
        'ADHOACT' => ['label' => 'Horodatage action',               'type' => 'TIMESTMP', 'nullable' => false],
        'ADUTIL'  => ['label' => 'Utilisateur',                     'type' => 'CHAR',     'nullable' => false],
        'ADPGM'   => ['label' => 'Programme',                       'type' => 'CHAR',     'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = \App\Domain\Company::get($companyCode);
        if (!$company) return null;

        $library = (string)($company['main_library'] ?? '');
        return $library !== '' ? $library : null;
    }

    /**
     * Get one row by depot + article (ADDEP, ADART).
     *
     * @return array<string,mixed> Empty array if not found.
     */
    public static function getDepotsArrayByProduct( PDO $pdo, string $productCode ): array {
        try {
            $depots['drop'] = ['is_drop' => false];
            $depots['06'] = ['is_depot' => false];
            $depots['38'] = ['is_depot' => false];
            $depots['40'] = ['is_depot' => false];
            $library = 'MATIS';            
            $productCode = trim($productCode);
            if ( $productCode === '') return [];
            $models = self::for($pdo, $library)->whereEq('ADART', $productCode)->getModels();        
            foreach($models as $model) {                
                if(in_array($model->addep,['06','38','40'],true)) {
                    $depots[$model->addep] = [ 'is_depot' => true, 'is_main' => ($model->adprin === '*') ];
                } else {
                    $depots['drop'] = [ 'is_drop' => true, 'code' => $model->addep];
                }
            }            
            return $depots;
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * Get one row by depot + article (ADDEP, ADART).
     *
     * @return array<string,mixed> Empty array if not found.
     */
    public static function getByDepotArticle(
        PDO $pdo,
        string $companyCode,
        string $depotId,
        string $articleId
    ): array {
        try {
            $companyKey = trim($companyCode);
            if ($companyKey === '') return [];

            $library = self::libraryOf($companyKey);
            if ($library === null) return [];

            $depotId = trim($depotId);
            $articleId = trim($articleId);
            if ($depotId === '' || $articleId === '') return [];

            $row = self::for($pdo, $library)
                ->whereEq('ADDEP', $depotId)
                ->whereEq('ADART', $articleId)
                ->first();

            return is_array($row) ? $row : [];

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * Get one row by depot + article (ADDEP, ADART).
     *
     * @return string Empty array if not found.
     */
    public static function getMainDepotByArticle(PDO $pdo, string $articleId ): string {
        try {
            $library = 'MATIS';
            $articleId = trim($articleId);
            if ($articleId === '') return '';
            $row = self::for($pdo, $library)
                ->whereEq('ADART', $articleId)
                ->whereEq('ADPRIN', "*")
                ->first();
            return is_array($row) ? $row['ADDEP'] : '';
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
    /**
     * Get one dépot by id (A1ART).
     *
     * @return ?array Empty array if not found.
     */
    public static function getModelsByArticle( PDO $pdo, string $companyCode, string $articleId ): ?array {
        try {
            $companyKey = trim($companyCode);
            if ($companyKey === '') return null;

            $library = self::libraryOf($companyKey);
            if ($library === null) return null;
           
            $articleId = trim($articleId);
            if ($articleId === '') return null;
            return self::for($pdo, $library)                
                ->whereEq('ADART', $articleId)
                ->getModels();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
    /**
     * Get one row by depot + article (ADDEP, ADART).
     *
     * @return ?static Empty array if not found.
     */
    public static function getModelMainDepotByArticle(PDO $pdo, string $companyCode, string $articleId ): ?static {
        try {
            $companyKey = trim($companyCode);
            if ($companyKey === '') return null;
            $library = self::libraryOf($companyKey);
            if ($library === null) return null;            
            $articleId = trim($articleId);
            if ($articleId === '') return null;
            return self::for($pdo, $library)
                ->whereEq('ADART', $articleId)
                ->whereEq('ADPRIN', "*")
                ->firstModel();            
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

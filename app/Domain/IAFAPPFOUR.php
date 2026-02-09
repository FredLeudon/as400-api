<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

/**
 * MATIS.IAFAPPFOUR
 *
 * Unique key: [IAFSOC, IAFFOUR, IAFART]
 */
final class IAFAPPFOUR extends clFichier
{
    protected static string $table = 'IAFAPPFOUR';
    protected static array $primaryKey = ['IAFSOC','IAFFOUR','IAFART'];

    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'IAFSOC'     => ['label' => 'Code societe',                      'type' => 'CHAR',    'nullable' => false],
        'IAFFOUR'    => ['label' => 'code fournisseur',                  'type' => 'CHAR',    'nullable' => false],
        'IAFART'     => ['label' => 'Code article',                      'type' => 'CHAR',    'nullable' => false],
        'IAFDELFOUS' => ['label' => 'Delai du fournisseur en semaine',   'type' => 'DECIMAL', 'nullable' => false],
        'IAFDELFOUJ' => ['label' => 'Delai fournisseur supplementaire',  'type' => 'DECIMAL', 'nullable' => false],
        'IAFRENMAD'  => ['label' => 'Delai rendu ou mise à dispo R/M',   'type' => 'CHAR',    'nullable' => false],
        'IAFDELATPT' => ['label' => 'Delai transport en semaine',        'type' => 'DECIMAL', 'nullable' => false],
        'IAFDELCOUS' => ['label' => 'Delai couverture de stock semaine', 'type' => 'DECIMAL', 'nullable' => false],
    ];
    
    /**
     * Get a single record by company/supplier/article (composite key).
     *
     * @return array<string,mixed> Empty array if not found.
     */
    public static function getByKey(PDO $pdo, string $companyCode, string $supplierCode, string $articleCode): array
    {
        try {
            $library = 'MATIS';
            $supplierCode = trim($supplierCode);
            $articleCode  = trim($articleCode);
            if ($supplierCode === '' || $articleCode === '') return [];

            $row = self::for($pdo, $library)                
                ->whereEq('IAFSOC', $companyCode)
                ->whereEq('IAFFOUR', $supplierCode)
                ->whereEq('IAFART', $articleCode)
                ->first();

            return is_array($row) ? $row : [];
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * Return hydrated model instance or null.
     */
    public static function getModelByKey(PDO $pdo, string $companyCode, string $supplierCode, string $articleCode): ?static
    {
        try {
            $library = 'MATIS';
            $supplierCode = trim($supplierCode);
            $articleCode  = trim($articleCode);
            if ($supplierCode === '' || $articleCode === '') return null;

            return self::for($pdo, $library)                
                ->whereEq('IAFSOC', $companyCode)
                ->whereEq('IAFFOUR', $supplierCode)
                ->whereEq('IAFART', $articleCode)
                ->firstModel();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * List records for a given supplier and/or article.
     * If $supplierCode is empty, returns records for the article across suppliers (or all if both empty).
     *
     * @return array<int,static>
     */
    public static function list(PDO $pdo, string $companyCode, ?string $supplierCode = null, ?string $articleCode = null): array
    {
        try {
            $library = 'MATIS';
            $qb = self::for($pdo, $library)->select(array_keys(self::$columns));
            if ($supplierCode !== null && trim($supplierCode) !== '') $qb->whereEq('IAFFOUR', trim($supplierCode));
            if ($articleCode !== null && trim($articleCode) !== '') $qb->whereEq('IAFART', trim($articleCode));
            $qb->whereEq('IAFSOC', $companyCode)->orderBy('IAFFOUR','ASC')->orderBy('IAFART','ASC');

            return $qb->getModels();
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

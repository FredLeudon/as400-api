<?php
declare(strict_types=1);

namespace App\Products;

use PDO;
use Throwable;
use App\Core\Http;
use App\Core\clFichier;

/**
 * WDOUTILS.VUE_API_ARICLE
 *
 * Vue exposant la configuration des attributs et leurs liaisons fichiers.
 */
final class VUE_API_ARTICLE extends clFichier
{
    protected static string $table = 'VUE_API_ARTICLE';
    protected static array $primaryKey = [];

    protected static array $columns = [
        'PRODUCT'    => ['label' => 'PRODUCT',            'type' => 'CHAR',    'nullable' => false],
        'JSDATAS'    => ['label' => 'JSDATAS',            'type' => 'CHAR',    'nullable' => true],
    ];

   public static function getProduct(PDO $pdo, string $productCode) : static
    {
        try {
            $library = 'WDOUTILS';            
            return self::for($pdo, $library)->select(['*'])->whereEq('PRODUCT',$productCode)->firstModel();
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    } 
}

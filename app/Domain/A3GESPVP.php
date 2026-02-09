<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class A3GESPVP extends clFichier
{
    protected static string $table = 'A3GESPVP';
    protected static array $primaryKey = [];
    /** @var array<int,array<int,string>> */
    protected static array $uniqueKeys = [];
    /** @var array<string,array<int,string>> */
    protected static array $indexes = [
        'A3GESPVPIX' => ['A3SOC', 'A3ART', 'A3NTAR'],
        'A3GESPVPI2' => ['A3SOC', 'A3NTAR', 'A3ART'],
        'A3GES00001' => ['A3SOC', 'A3NTAR', 'A3ART', 'A3DTEAPL'],
        'A3GP_IDX01' => ['A3SOC', 'A3ART', 'A3NTAR', 'A3DTEAPL'],
    ];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'A3SOC'    => ['label' => 'Code société (  ) = MBI',              'type' => 'CHAR',    'nullable' => true],
        'A3ART'    => ['label' => 'Article',                              'type' => 'CHAR',    'nullable' => false],
        'A3NTAR'   => ['label' => 'Numéro du tarif',                      'type' => 'CHAR',    'nullable' => false],
        'A3PRIV'   => ['label' => 'Prix de vente en FF assossié au n° de tarif', 'type' => 'DECIMAL', 'nullable' => false],
        'A3DTEAPL' => ['label' => "Date d'application du Prix",           'type' => 'DATE',    'nullable' => false],
        'PGM'      => ['label' => 'Programme',                            'type' => 'VARCHAR', 'nullable' => false],
    ];

    public static function getModelsByCompanyTarifProduct( \PDO $pdo, string $companyCode, string $tarifCode, string $productCode ): ?array {
        try {
            $library = 'MATIS';            
            $productCode = trim($productCode);
            if ($productCode === '') return null;
            $tarifCode = trim($tarifCode);
            if ($tarifCode === '') return null;
            return self::for($pdo, $library)  
                ->whereEq('A3SOC', $companyCode)              
                ->whereEq('A3ART', $productCode)
                ->whereEq('A3NTAR', $tarifCode)
                ->orderBy('A3DTEAPL','DESC')
                ->getModels();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}



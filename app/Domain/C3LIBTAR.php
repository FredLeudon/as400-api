<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class C3LIBTAR extends clFichier
{
    protected static string $table = 'C3LIBTAR';
    protected static array $primaryKey = [];
    /** @var array<int,array<int,string>> */
    protected static array $uniqueKeys = [];
    /** @var array<string,array<int,string>> */
    protected static array $indexes = [
        'C3L0' => ['C3INDI'],
        'C3L1' => ['C3LIB'],
    ];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'C3INDI' => ['label' => 'Indice du tarif',                       'type' => 'CHAR',    'nullable' => false],
        'C3LIB'  => ['label' => 'Libellé associé à l indice du tarif',   'type' => 'CHAR',    'nullable' => false],
        'C3SOC'  => ['label' => 'n° de la société',                      'type' => 'CHAR',    'nullable' => false],
        'C3DATE' => ['label' => 'date butoir de validite du tarif',      'type' => 'NUMERIC', 'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;

        $library = (string)($company['common_library'] ?? '');        
        return $library !== '' ? $library : null;
    }

    public static function allModels( PDO $pdo, string $companyCode ): array 
    {
        try {
            $companyKey = trim($companyCode);            
            $library = self::libraryOf($companyKey);
            if ($library === null) return [];
            $rows = self::for($pdo, $library)                
                ->orderBy('C3INDI', 'ASC')
                ->getModels();
            return $rows;

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

}

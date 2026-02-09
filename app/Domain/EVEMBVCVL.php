<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class EVEMBVCVL extends clFichier
{
    protected static string $table = 'EVEMBVCVL';
    protected static array $primaryKey = ['EVART','EVVARNUM','EVNORD'];

    protected static array $columns = [
        'EVART'   => ['label'=>'EVART','type'=>'CHAR','nullable'=>false],
        'EVVARNUM'=> ['label'=>'EVVARNUM','type'=>'SMALLINT','nullable'=>false],
        'EVVAR'   => ['label'=>'EVVAR','type'=>'CHAR','nullable'=>false],
        'EVNORD'  => ['label'=>'EVNORD','type'=>'SMALLINT','nullable'=>false],
        'EVMAT'   => ['label'=>'EVMAT','type'=>'CHAR','nullable'=>false],
        'EVPOIDS' => ['label'=>'EVPOIDS','type'=>'DECIMAL','nullable'=>false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;
        $library = (string)($company['main_library'] ?? '');
        return $library !== '' ? $library : null;
    }
}

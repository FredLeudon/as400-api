<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class AEATTETE extends clFichier
{
    protected static string $table = 'AEATTETE';
    protected static array $primaryKey = ['AEART','AEATT','AENORD'];

    protected static array $columns = [
        'AEART'   => ['label'=>'AEART','type'=>'CHAR','nullable'=>false],
        'AEATT'   => ['label'=>'AEATT','type'=>'CHAR','nullable'=>false],
        'AENORD'  => ['label'=>'AENORD','type'=>'SMALLINT','nullable'=>true],
        'AEDATA'  => ['label'=>'AEDATA','type'=>'VARCHAR','nullable'=>true],
        'AEUNTSTK'=> ['label'=>'AEUNTSTK','type'=>'CHAR','nullable'=>true],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;
        $library = (string)($company['main_library'] ?? '');
        return $library !== '' ? $library : null;
    }
}

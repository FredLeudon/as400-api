<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class AAACCART extends clFichier
{
    protected static string $table = 'AAACCART';
    protected static array $primaryKey = ['AAART','AATYPE','AACODACC'];

    protected static array $columns = [
        'AAART'   => ['label'=>'AAART','type'=>'CHAR','nullable'=>false],
        'AATYPE'  => ['label'=>'AATYPE','type'=>'CHAR','nullable'=>false],
        'AACODACC'=> ['label'=>'AACODACC','type'=>'CHAR','nullable'=>false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;
        $library = (string)($company['main_library'] ?? '');
        return $library !== '' ? $library : null;
    }
}

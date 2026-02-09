<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class CACOMARTVC extends clFichier
{
    protected static string $table = 'CACOMARTVC';
    protected static array $primaryKey = ['CAART','CAVC'];

    protected static array $columns = [
        'CAART'   => ['label'=>'CAART','type'=>'CHAR','nullable'=>false],
        'CAVC'    => ['label'=>'CAVC','type'=>'CHAR','nullable'=>false],
        'CACAB'   => ['label'=>'CACAB','type'=>'CHAR','nullable'=>false],
        'CAHAUTEUR'=> ['label'=>'CAHAUTEUR','type'=>'NUMERIC','nullable'=>false],
        'CALARGEUR'=> ['label'=>'CALARGEUR','type'=>'NUMERIC','nullable'=>false],
        'CALONGUEUR'=> ['label'=>'CALONGUEUR','type'=>'NUMERIC','nullable'=>false],
        'CAVOLUME'=> ['label'=>'CAVOLUME','type'=>'NUMERIC','nullable'=>false],
        'CAPOIDSB'=> ['label'=>'CAPOIDSB','type'=>'NUMERIC','nullable'=>false],
        'CAPOIDSN'=> ['label'=>'CAPOIDSN','type'=>'NUMERIC','nullable'=>false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;
        $library = (string)($company['main_library'] ?? '');
        return $library !== '' ? $library : null;
    }
}

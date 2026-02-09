<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class CACOMART extends clFichier
{
    protected static string $table = 'CACOMART';
    protected static array $primaryKey = ['CAART'];

    protected static array $columns = [
        'CAART'    => ['label'=>'CAART','type'=>'CHAR','nullable'=>false],
        'CANIVFON' => ['label'=>'CANIVFON','type'=>'SMALLINT','nullable'=>false],
        'CATOPCAT' => ['label'=>'CATOPCAT','type'=>'SMALLINT','nullable'=>false],
        'CAMARCIB' => ['label'=>'CAMARCIB','type'=>'CHAR','nullable'=>true],
        'CATYPDOUA'=> ['label'=>'CATYPDOUA','type'=>'CHAR','nullable'=>false],
        'CAGLNMARQ'=> ['label'=>'CAGLNMARQ','type'=>'CHAR','nullable'=>true],
        'CACLASGPC'=> ['label'=>'CACLASGPC','type'=>'CHAR','nullable'=>true],
        'CADATEXP' => ['label'=>'CADATEXP','type'=>'DATE','nullable'=>true],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;
        $library = (string)($company['main_library'] ?? '');
        return $library !== '' ? $library : null;
    }
}

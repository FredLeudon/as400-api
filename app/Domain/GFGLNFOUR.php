<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class GFGLNFOUR extends clFichier
{
    protected static string $table = 'GFGLNFOUR';
    protected static array $primaryKey = ['GFCODFOU'];

    protected static array $columns = [
        'GFCODFOU' => ['label'=>'GFCODFOU','type'=>'CHAR','nullable'=>false],
        'GFCODGLN' => ['label'=>'GFCODGLN','type'=>'CHAR','nullable'=>false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;
        $library = (string)($company['main_library'] ?? '');
        return $library !== '' ? $library : null;
    }
}

<?php
declare(strict_types=1);

namespace App\Digital;

use App\Core\clFichier;

final class MARQUES extends clFichier
{
    protected static string $table = 'MARQUES';
    protected static array $primaryKey = ['IDMARQ'];

    protected static array $columns = [
        'IDMARQ' => ['label'=>'IDMARQ','type'=>'INTEGER','nullable'=>false],
        'NOMMARQ' => ['label'=>'NOMMARQ','type'=>'VARCHAR','nullable'=>true],
        'AUDIGITAL' => ['label'=>'AUDIGITAL','type'=>'CHAR','nullable'=>true],
        'AUPRINT' => ['label'=>'AUPRINT','type'=>'CHAR','nullable'=>true],
        'UTIL' => ['label'=>'UTIL','type'=>'CHAR','nullable'=>true],
    ];
}

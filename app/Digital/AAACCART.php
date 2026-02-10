<?php
declare(strict_types=1);

namespace App\Digital;

use App\Core\clFichier;

final class AAACCART extends clFichier
{
    protected static string $table = 'AAACCART';
    protected static array $primaryKey = ['AAART','AATYPE','AACODACC'];

    protected static array $columns = [
        'AAART' => ['label'=>'AAART','type'=>'CHAR','nullable'=>false],
        'AATYPE' => ['label'=>'AATYPE','type'=>'CHAR','nullable'=>false],
        'AACODACC' => ['label'=>'AACODACC','type'=>'CHAR','nullable'=>false],
    ];
}

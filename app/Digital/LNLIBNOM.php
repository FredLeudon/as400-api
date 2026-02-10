<?php
declare(strict_types=1);

namespace App\Digital;

use App\Core\clFichier;

final class LNLIBNOM extends clFichier
{
    protected static string $table = 'LNLIBNOM';
    protected static array $primaryKey = ['LNCODFAM','LNLANG'];

    protected static array $columns = [
        'LNCODSEG' => ['label'=>'LNCODSEG','type'=>'SMALLINT','nullable'=>true],
        'LNCODFAM' => ['label'=>'LNCODFAM','type'=>'SMALLINT','nullable'=>true],
        'LNCODSSF' => ['label'=>'LNCODSSF','type'=>'SMALLINT','nullable'=>true],
        'LNCODGAM' => ['label'=>'LNCODGAM','type'=>'SMALLINT','nullable'=>true],
        'LNCODSER' => ['label'=>'LNCODSER','type'=>'SMALLINT','nullable'=>true],
        'LNCODMOD' => ['label'=>'LNCODMOD','type'=>'SMALLINT','nullable'=>true],
        'LNLANG' => ['label'=>'LNLANG','type'=>'CHAR','nullable'=>true],
        'LNLIB' => ['label'=>'LNLIB','type'=>'VARCHAR','nullable'=>true],
    ];
}

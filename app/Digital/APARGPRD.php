<?php
declare(strict_types=1);

namespace App\Digital;

use App\Core\clFichier;

final class APARGPRD extends clFichier
{
    protected static string $table = 'APARGPRD';
    protected static array $primaryKey = ['APTYPE','APNORD','APNIV','APCODSEG','APCODFAM','APCODSSF','APCODGAM','APCODSER'];

    protected static array $columns = [
        'APCODSEG' => ['label'=>'APCODSEG','type'=>'SMALLINT','nullable'=>true],
        'APCODFAM' => ['label'=>'APCODFAM','type'=>'SMALLINT','nullable'=>true],
        'APCODSSF' => ['label'=>'APCODSSF','type'=>'SMALLINT','nullable'=>true],
        'APCODGAM' => ['label'=>'APCODGAM','type'=>'SMALLINT','nullable'=>true],
        'APCODSER' => ['label'=>'APCODSER','type'=>'SMALLINT','nullable'=>true],
        'APCODMOD' => ['label'=>'APCODMOD','type'=>'SMALLINT','nullable'=>true],
        'APNIV' => ['label'=>'APNIV','type'=>'NUMERIC','nullable'=>false],
        'APTYPE' => ['label'=>'APTYPE','type'=>'CHAR','nullable'=>true],
        'APNORD' => ['label'=>'APNORD','type'=>'INTEGER','nullable'=>false],
        'APIDARG' => ['label'=>'APIDARG','type'=>'INTEGER','nullable'=>false],
    ];
}

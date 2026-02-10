<?php
declare(strict_types=1);

namespace App\Digital;

use App\Core\clFichier;

final class ANATTNOM extends clFichier
{
    protected static string $table = 'ANATTNOM';
    protected static array $primaryKey = ['ANCODSEG','ANCODFAM','ANCODSSF','ANCODGAM','ANCODSER','ANCODMOD','ANCODATT'];

    protected static array $columns = [
        'ANCODSEG' => ['label'=>'ANCODSEG','type'=>'SMALLINT','nullable'=>true],
        'ANCODFAM' => ['label'=>'ANCODFAM','type'=>'SMALLINT','nullable'=>true],
        'ANCODSSF' => ['label'=>'ANCODSSF','type'=>'SMALLINT','nullable'=>true],
        'ANCODGAM' => ['label'=>'ANCODGAM','type'=>'SMALLINT','nullable'=>true],
        'ANCODSER' => ['label'=>'ANCODSER','type'=>'SMALLINT','nullable'=>true],
        'ANCODMOD' => ['label'=>'ANCODMOD','type'=>'SMALLINT','nullable'=>true],
        'ANCODATT' => ['label'=>'ANCODATT','type'=>'CHAR','nullable'=>false],
        'ANUNTAFF' => ['label'=>'ANUNTAFF','type'=>'CHAR','nullable'=>false],
    ];
}

<?php
declare(strict_types=1);

namespace App\Digital;

use App\Core\clFichier;

final class ASATTSERAP extends clFichier
{
    protected static string $table = 'ASATTSERAP';
    protected static array $primaryKey = ['ASCODAPP','ASCODSEG','ASCODFAM','ASCODSSF','ASCODGAM','ASCODSER','ASCODATT'];

    protected static array $columns = [
        'ASCODAPP' => ['label'=>'ASCODAPP','type'=>'CHAR','nullable'=>false],
        'ASCODSEG' => ['label'=>'ASCODSEG','type'=>'SMALLINT','nullable'=>true],
        'ASCODFAM' => ['label'=>'ASCODFAM','type'=>'SMALLINT','nullable'=>true],
        'ASCODSSF' => ['label'=>'ASCODSSF','type'=>'SMALLINT','nullable'=>true],
        'ASCODGAM' => ['label'=>'ASCODGAM','type'=>'SMALLINT','nullable'=>true],
        'ASCODSER' => ['label'=>'ASCODSER','type'=>'SMALLINT','nullable'=>true],
        'ASCODATT' => ['label'=>'ASCODATT','type'=>'CHAR','nullable'=>false],
        'ASNORDRE' => ['label'=>'ASNORDRE','type'=>'SMALLINT','nullable'=>true],
        'ASUNTAFF' => ['label'=>'ASUNTAFF','type'=>'CHAR','nullable'=>true],
    ];
}

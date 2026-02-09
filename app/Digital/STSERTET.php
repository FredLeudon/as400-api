<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class STSERTET extends clFichier
{
    protected static string $table = 'STSERTET';
    protected static array $primaryKey = ['STCODSEG'];

    protected static array $columns = [
        'STCODSEG' => ['label'=>'STCODSEG','type'=>'SMALLINT','nullable'=>true],
        'STCODFAM' => ['label'=>'STCODFAM','type'=>'SMALLINT','nullable'=>true],
        'STCODSSF' => ['label'=>'STCODSSF','type'=>'SMALLINT','nullable'=>true],
        'STCODGAM' => ['label'=>'STCODGAM','type'=>'SMALLINT','nullable'=>true],
        'STCODSER' => ['label'=>'STCODSER','type'=>'SMALLINT','nullable'=>true],
        'STTETESER'=> ['label'=>'STTETESER','type'=>'CHAR','nullable'=>false],
    ];

}

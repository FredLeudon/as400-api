<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class MAMATART extends clFichier
{
    protected static string $table = 'MAMATART';
    protected static array $primaryKey = ['MAART','MANORD'];

    protected static array $columns = [
        'MAART'   => ['label'=>'MAART','type'=>'CHAR','nullable'=>false],
        'MANORD'  => ['label'=>'MANORD','type'=>'SMALLINT','nullable'=>false],
        'MAMAT'   => ['label'=>'MAMAT','type'=>'CHAR','nullable'=>false],
        'MADIFINT'=> ['label'=>'MADIFINT','type'=>'CHAR','nullable'=>true],
        'MAEPAISS'=> ['label'=>'MAEPAISS','type'=>'NUMERIC','nullable'=>true],
        'MADENSITE'=>['label'=>'MADENSITE','type'=>'NUMERIC','nullable'=>true],
    ];

    
}

<?php
declare(strict_types=1);

namespace App\Digital;

use App\Core\clFichier;

final class TMTABMAT extends clFichier
{
    protected static string $table = 'TMTABMAT';
    protected static array $primaryKey = ['TMMAT'];

    protected static array $columns = [
        'TMMAT' => ['label'=>'TMMAT','type'=>'CHAR','nullable'=>false],
        'TMTYPE' => ['label'=>'TMTYPE','type'=>'CHAR','nullable'=>true],
        'TMUNITE' => ['label'=>'TMUNITE','type'=>'CHAR','nullable'=>true],
        'TMUNITD' => ['label'=>'TMUNITD','type'=>'CHAR','nullable'=>true],
    ];
}

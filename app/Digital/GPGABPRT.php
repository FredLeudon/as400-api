<?php
declare(strict_types=1);

namespace App\Digital;

use App\Core\clFichier;

final class GPGABPRT extends clFichier
{
    protected static string $table = 'GPGABPRT';
    protected static array $primaryKey = [];

    protected static array $columns = [
        'GPIDNOM' => ['label'=>'GPIDNOM','type'=>'INTEGER','nullable'=>false],
        'GPTXT' => ['label'=>'GPTXT','type'=>'VARCHAR','nullable'=>false],
    ];
}

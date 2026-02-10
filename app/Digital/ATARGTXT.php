<?php
declare(strict_types=1);

namespace App\Digital;

use App\Core\clFichier;

final class ATARGTXT extends clFichier
{
    protected static string $table = 'ATARGTXT';
    protected static array $primaryKey = ['ATIDARG','ATLANG'];

    protected static array $columns = [
        'ATIDARG' => ['label'=>'ATIDARG','type'=>'INTEGER','nullable'=>true],
        'ATLANG' => ['label'=>'ATLANG','type'=>'CHAR','nullable'=>true],
        'ATTXT' => ['label'=>'ATTXT','type'=>'VARCHAR','nullable'=>true],
    ];
}

<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class TPTITPRT extends clFichier
{
    protected static string $table = 'TPTITPRT';
    protected static array $primaryKey = ['TPIDNOM'];

    protected static array $columns = [
        'TPIDNOM'=> ['label'=>'TPIDNOM','type'=>'INTEGER','nullable'=>false],
        'TPTYPE' => ['label'=>'TPTYPE','type'=>'CHAR','nullable'=>false],
        'TPLANG' => ['label'=>'TPLANG','type'=>'CHAR','nullable'=>false],
        'TPTITRE'=> ['label'=>'TPTITRE','type'=>'VARCHAR','nullable'=>false],
    ];

    
}

<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class LVLIBVCART extends clFichier
{
    protected static string $table = 'LVLIBVCART';
    protected static array $primaryKey = ['LVART','LVVC','LVLANG'];

    protected static array $columns = [
        'LVART'  => ['label'=>'LVART','type'=>'CHAR','nullable'=>false],
        'LVVC'   => ['label'=>'LVVC','type'=>'CHAR','nullable'=>false],
        'LVLANG' => ['label'=>'LVLANG','type'=>'CHAR','nullable'=>false],
        'LVLIB'  => ['label'=>'LVLIB','type'=>'VARCHAR','nullable'=>false],
    ];
    
}

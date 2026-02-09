<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class NNNIVNOMCA extends clFichier
{
    protected static string $table = 'NNNIVNOMCA';
    protected static array $primaryKey = ['NNNIV'];

    protected static array $columns = [
        'NNNIV' => ['label'=>'NNNIV','type'=>'SMALLINT','nullable'=>false],
        'NNLANG'=> ['label'=>'NNLANG','type'=>'CHAR','nullable'=>false],
        'NNLIB' => ['label'=>'NNLIB','type'=>'CHAR','nullable'=>false],
    ];

}

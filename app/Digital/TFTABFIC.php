<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class TFTABFIC extends clFichier
{
    protected static string $table = 'TFTABFIC';
    protected static array $primaryKey = ['TFIDFIC'];

    protected static array $columns = [
        'TFIDFIC' => ['label'=>'TFIDFIC','type'=>'INTEGER','nullable'=>false],
        'TFCODETYP'=> ['label'=>'TFCODETYP','type'=>'CHAR','nullable'=>true],
        'TFCODESTYP'=> ['label'=>'TFCODESTYP','type'=>'CHAR','nullable'=>true],
        'TFLIBFIC' => ['label'=>'TFLIBFIC','type'=>'VARCHAR','nullable'=>true],
        'TFURL'    => ['label'=>'TFURL','type'=>'VARCHAR','nullable'=>true],
    ];

    
}

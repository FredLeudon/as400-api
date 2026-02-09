<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class NFNOMFIC extends clFichier
{
    protected static string $table = 'NFNOMFIC';
    protected static array $primaryKey = ['NFCODSEG'];

    protected static array $columns = [
        'NFCODSEG' => ['label'=>'NFCODSEG','type'=>'SMALLINT','nullable'=>true],
        'NFCODFAM' => ['label'=>'NFCODFAM','type'=>'SMALLINT','nullable'=>true],
        'NFCODSSF' => ['label'=>'NFCODSSF','type'=>'SMALLINT','nullable'=>true],
        'NFCODGAM' => ['label'=>'NFCODGAM','type'=>'SMALLINT','nullable'=>true],
        'NFCODSER' => ['label'=>'NFCODSER','type'=>'SMALLINT','nullable'=>true],
        'NFCODMOD' => ['label'=>'NFCODMOD','type'=>'SMALLINT','nullable'=>true],
        'NFTYPFIC' => ['label'=>'NFTYPFIC','type'=>'CHAR','nullable'=>true],
        'NFSOUTYPFI'=> ['label'=>'NFSOUTYPFI','type'=>'CHAR','nullable'=>true],
        'NFNUMORD' => ['label'=>'NFNUMORD','type'=>'SMALLINT','nullable'=>true],
        'NFIDFIC'  => ['label'=>'NFIDFIC','type'=>'INTEGER','nullable'=>true],
    ];

}

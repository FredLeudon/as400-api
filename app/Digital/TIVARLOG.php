<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class TIVARLOG extends clFichier
{
    protected static string $table = 'TIVARLOG';
    protected static array $primaryKey = ['TIVLCODART','TIVLCODVL'];

    protected static array $columns = [
        'TIVLCODART' => ['label'=>'TIVLCODART','type'=>'CHAR','nullable'=>false],
        'TIVLCODVL'  => ['label'=>'TIVLCODVL','type'=>'CHAR','nullable'=>false],
        'TIVLTCAB'   => ['label'=>'TIVLTCAB','type'=>'CHAR','nullable'=>false],
        'TIVLCAB'    => ['label'=>'TIVLCAB','type'=>'CHAR','nullable'=>false],
        'TIVLHAUT'   => ['label'=>'TIVLHAUT','type'=>'DECIMAL','nullable'=>false],
        'TIVLLARG'   => ['label'=>'TIVLLARG','type'=>'DECIMAL','nullable'=>false],
        'TIVLLONG'   => ['label'=>'TIVLLONG','type'=>'DECIMAL','nullable'=>false],
        'TIVLNBCPC'  => ['label'=>'TIVLNBCPC','type'=>'DECIMAL','nullable'=>false],
        'TIVLPOIB'   => ['label'=>'TIVLPOIB','type'=>'DECIMAL','nullable'=>false],
        'TIVLPOIN'   => ['label'=>'TIVLPOIN','type'=>'DECIMAL','nullable'=>false],
        'TIVLQTE'    => ['label'=>'TIVLQTE','type'=>'DECIMAL','nullable'=>false],
        'TIVLVOL'    => ['label'=>'TIVLVOL','type'=>'DECIMAL','nullable'=>false],
        'TI_TRAITE'  => ['label'=>'TI_TRAITE','type'=>'CHAR','nullable'=>true],
    ];

  
}

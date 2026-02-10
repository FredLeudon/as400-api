<?php
declare(strict_types=1);

namespace App\Digital;

use App\Core\clFichier;

final class CACOMART extends clFichier
{
    protected static string $table = 'CACOMART';
    protected static array $primaryKey = ['CAART'];

    protected static array $columns = [
        'CAART' => ['label'=>'CAART','type'=>'CHAR','nullable'=>false],
        'CANIVFON' => ['label'=>'CANIVFON','type'=>'SMALLINT','nullable'=>false],
        'CATOPCAT' => ['label'=>'CATOPCAT','type'=>'SMALLINT','nullable'=>false],
        'CAMARCIB' => ['label'=>'CAMARCIB','type'=>'CHAR','nullable'=>true],
        'CATYPDOUA' => ['label'=>'CATYPDOUA','type'=>'CHAR','nullable'=>false],
        'CAGLNMARQ' => ['label'=>'CAGLNMARQ','type'=>'CHAR','nullable'=>true],
        'CACLASGPC' => ['label'=>'CACLASGPC','type'=>'CHAR','nullable'=>true],
        'CADATEXP' => ['label'=>'CADATEXP','type'=>'DATE','nullable'=>true],
        'CADATCOM' => ['label'=>'CADATCOM','type'=>'DATE','nullable'=>true],
        'CADATMAJ' => ['label'=>'CADATMAJ','type'=>'DATE','nullable'=>true],
        'CADATVAL' => ['label'=>'CADATVAL','type'=>'DATE','nullable'=>true],
        'CADATPUB' => ['label'=>'CADATPUB','type'=>'DATE','nullable'=>true],
        'CAIDMARQ' => ['label'=>'CAIDMARQ','type'=>'INTEGER','nullable'=>true],
        'CAPERSON' => ['label'=>'CAPERSON','type'=>'CHAR','nullable'=>true],
        'CASERVICE' => ['label'=>'CASERVICE','type'=>'CHAR','nullable'=>true],
        'CAMESVAR' => ['label'=>'CAMESVAR','type'=>'CHAR','nullable'=>true],
        'CACONFEMB' => ['label'=>'CACONFEMB','type'=>'CHAR','nullable'=>true],
        'CAQTEEAM1' => ['label'=>'CAQTEEAM1','type'=>'DECIMAL','nullable'=>true],
        'CAQTEEAM2' => ['label'=>'CAQTEEAM2','type'=>'DECIMAL','nullable'=>true],
    ];
}

<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class AEATTETE extends clFichier
{
    protected static string $table = 'AEATTETE';
    protected static array $primaryKey = ['AEART', 'AEATT', 'AENORD'];

    protected static array $columns = [
        'AEART'    => ['label'=>'AE_CODE_ART',     'type'=>'CHAR',    'nullable'=>false],
        'AEATT'    => ['label'=>'AE_CODE_ATTRIBU', 'type'=>'CHAR',    'nullable'=>false],
        'AENORD'   => ['label'=>'AE_NUM_ORDRE',    'type'=>'SMALLINT','nullable'=>true],
        'AEDATA'   => ['label'=>'AE_DATA',         'type'=>'VARCHAR', 'nullable'=>true],
        'AEUNTSTK' => ['label'=>'AE_UNITE_STOCKA', 'type'=>'CHAR',    'nullable'=>true],
    ];
}


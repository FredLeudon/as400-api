<?php
declare(strict_types=1);

namespace App\Digital;

use App\Core\clFichier;

final class BPBCLPRT extends clFichier
{
    protected static string $table = 'BPBCLPRT';
    protected static array $primaryKey = [];

    protected static array $columns = [
        'BPIDNOM' => ['label'=>'BPIDNOM','type'=>'INTEGER','nullable'=>false],
        'BPLANG' => ['label'=>'BPLANG','type'=>'CHAR','nullable'=>false],
        'BPBCLLIG' => ['label'=>'BPBCLLIG','type'=>'VARCHAR','nullable'=>false],
        'BPGRPBCLLI' => ['label'=>'BPGRPBCLLI','type'=>'VARCHAR','nullable'=>false],
        'BPBCLCOL' => ['label'=>'BPBCLCOL','type'=>'VARCHAR','nullable'=>false],
        'BPGRPBCLCO' => ['label'=>'BPGRPBCLCO','type'=>'VARCHAR','nullable'=>false],
    ];
}

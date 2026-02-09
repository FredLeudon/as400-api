<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class TAFAM extends clFichier
{
    protected static string $table = 'TAFAM';
    protected static array $primaryKey = ['TAGROUP'];

    protected static array $columns = [
        'TAGROUP' => ['label'=>'TAGROUP','type'=>'SMALLINT','nullable'=>false],
        'TACODFAM'=> ['label'=>'TACODFAM','type'=>'SMALLINT','nullable'=>false],
        'TACODSFAM'=>['label'=>'TACODSFAM','type'=>'SMALLINT','nullable'=>false],
        'TAORDRE' => ['label'=>'TAORDRE','type'=>'SMALLINT','nullable'=>false],
        'TALIB'   => ['label'=>'TALIB','type'=>'VARCHAR','nullable'=>true],
        'TAWRKFLW'=> ['label'=>'TAWRKFLW','type'=>'SMALLINT','nullable'=>false],
        'TAVERROU'=> ['label'=>'TAVERROU','type'=>'SMALLINT','nullable'=>false],
    ];

    public static function getElement(PDO $pdo, int $groupe, ?int $famille = 0 , ?int $sousfamille = 0, bool $debug = true ): ?static
    {        
        if ($groupe === 0) return null;            
        $qb = self::for($pdo, 'MATIS')->whereEq('TAGROUP', $groupe);
        $qb->whereEq('TACODFAM', $famille);
        $qb->whereEq('TACODSFAM', $sousfamille);
        $tafam = $qb->firstModel();
        return $tafam;
    }
    
}

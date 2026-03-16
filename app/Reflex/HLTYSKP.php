<?php
declare(strict_types=1);

namespace App\Reflex;

use App\Core\clFichier;

final class HLTYSKP extends clFichier
{
    protected static string $table = 'HLTYSKP';
    protected static array $primaryKey = ['TKCTST'];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'TKCTST' => ['label' => 'Code type de stock',                           'type' => 'CHAR',    'nullable' => false],
        'TKL3TS' => ['label' => 'Libelle (3) type de stock',                    'type' => 'CHAR',    'nullable' => false],
        'TKLTST' => ['label' => 'Libelle type de stock',                        'type' => 'CHAR',    'nullable' => false],
        'TKRTST' => ['label' => 'Libelle reduit type de stock',                 'type' => 'CHAR',    'nullable' => false],
        'TKNSTS' => ['label' => 'Numero de sequence type de stock',             'type' => 'DECIMAL', 'nullable' => false],
        'TKTACA' => ['label' => 'Top type de stock a calculer',                 'type' => 'CHAR',    'nullable' => false],
        'TKCPGM' => ['label' => 'Code programme calcul type de stock',          'type' => 'CHAR',    'nullable' => false],
        'TKCBIC' => ['label' => 'Code bibliotheque programme calcul type stock','type' => 'CHAR',    'nullable' => false],
    ];

    public static function readModel(\PDO $pdo, string $TypeStock) : ? static
    {
        $row = self::for($pdo,'HLOFRA70')->whereEq('TKCTST',$TypeStock)->firstModel();
        //echo 'Type de stock : '.$TypeStock;
        //var_dump($row);
        return $row;
    }
}

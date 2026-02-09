<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use Throwable;
use App\Core\Http;
use App\Core\clFichier;

final class TATXTATT extends clFichier
{
    protected static string $table = 'TATXTATT';
    protected static array $primaryKey = ['TACODATT','TALANG'];

    protected static array $columns = [
        'TACODATT'=> ['label'=>'TACODATT','type'=>'VARCHAR','nullable'=>false],
        'TALANG'  => ['label'=>'TALANG','type'=>'CHAR','nullable'=>false],
        'TALIB'   => ['label'=>'TALIB','type'=>'VARCHAR','nullable'=>false],
        'TATEXTE' => ['label'=>'TATEXTE','type'=>'VARCHAR','nullable'=>false],
        'TABULLE' => ['label'=>'TABULLE','type'=>'VARCHAR','nullable'=>false],
    ];

    public static function getLibelle(PDO $pdo, string $attribut): ?array
    {
        $datas = [];
        try {
            $library = 'MATIS';
            $rows = self::for($pdo, $library) 
                ->whereEq('TX_CODE_ATTRIBUT', $attribut)
                ->orderBy('TX_CODE_LANGUE','ASC')
                ->getModels();
            foreach($rows as $row) {
                $datas[$row->tx_code_langue] = $row->toArrayLower();
            }
                
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
        return $datas;
    }
}

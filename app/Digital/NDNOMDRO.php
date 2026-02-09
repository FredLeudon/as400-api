<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class NDNOMDRO extends clFichier
{
    protected static string $table = 'NDNOMDRO';
    protected static array $primaryKey = ['NDNIV'];

    protected static array $columns = [
        'NDNIV'    => ['label'=>'NDNIV','type'=>'NUMERIC','nullable'=>false],
        'NDCODSEG' => ['label'=>'NDCODSEG','type'=>'SMALLINT','nullable'=>true],
        'NDCODFAM' => ['label'=>'NDCODFAM','type'=>'SMALLINT','nullable'=>true],
        'NDCODSSF' => ['label'=>'NDCODSSF','type'=>'SMALLINT','nullable'=>true],
        'NDCODGAM' => ['label'=>'NDCODGAM','type'=>'SMALLINT','nullable'=>true],
        'NDCODSER' => ['label'=>'NDCODSER','type'=>'SMALLINT','nullable'=>true],
        'NDCODMOD' => ['label'=>'NDCODMOD','type'=>'SMALLINT','nullable'=>true],
        'NDPROFIL' => ['label'=>'NDPROFIL','type'=>'CHAR','nullable'=>false],
        'NDPROPAGE'=> ['label'=>'NDPROPAGE','type'=>'SMALLINT','nullable'=>true],
    ];

    public static function getById(PDO $pdo, string $companyCode, $id): array
    {
        try {
            $library = 'MATIS';
            return self::for($pdo,$library)
                ->select(array_keys(self::$columns))
                ->whereEq('NDNIV',(string)$id)
                ->first() ?: [];
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

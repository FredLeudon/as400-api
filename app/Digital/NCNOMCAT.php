<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class NCNOMCAT extends clFichier
{
    protected static string $table = 'NCNOMCAT';
    protected static array $primaryKey = ['NCNIV'];

    protected static array $columns = [
        'NCNIV'    => ['label'=>'NCNIV','type'=>'NUMERIC','nullable'=>false],
        'NCCODSEG' => ['label'=>'NCCODSEG','type'=>'SMALLINT','nullable'=>true],
        'NCCODFAM' => ['label'=>'NCCODFAM','type'=>'SMALLINT','nullable'=>true],
        'NCCODSSF' => ['label'=>'NCCODSSF','type'=>'SMALLINT','nullable'=>true],
        'NCCODGAM' => ['label'=>'NCCODGAM','type'=>'SMALLINT','nullable'=>true],
        'NCCODSER' => ['label'=>'NCCODSER','type'=>'SMALLINT','nullable'=>true],
        'NCCODMOD' => ['label'=>'NCCODMOD','type'=>'SMALLINT','nullable'=>true],
        'NCID'     => ['label'=>'NCID','type'=>'INTEGER','nullable'=>false],
        'NCCAT'    => ['label'=>'NCCAT','type'=>'NUMERIC','nullable'=>true],
        'NCNUMORD' => ['label'=>'NCNUMORD','type'=>'SMALLINT','nullable'=>true],
        'NCTOPDIG' => ['label'=>'NCTOPDIG','type'=>'CHAR','nullable'=>true],
    ];

       public static function getById(PDO $pdo, string $companyCode, $id): array
    {
        try {
            $library = 'MATIS';
            return self::for($pdo,$library)
                ->select(array_keys(self::$columns))
                ->whereEq('NCNIV',(string)$id)
                ->first() ?: [];
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    public static function getModelById(PDO $pdo, string $companyCode, $id): ?static
    {
        try {
            $library = 'MATIS';
            return self::for($pdo,$library)
                ->select(array_keys(self::$columns))
                ->whereEq('NCNIV',(string)$id)
                ->firstModel();
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

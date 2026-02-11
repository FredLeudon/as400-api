<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use App\Core\cst;
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

    public static function DonneLibelléCatégorie(PDO $pdo, array $codes): ?array
    {
        try {
            $library = 'MATIS';
            $map = [
                0 => 'NCCODSEG',
                1 => 'NCCODFAM',
                2 => 'NCCODSSF',                
            ];
            $aliases = [
                'NCCODESEG' => 'NCCODSEG',
                'NCCODEFAM' => 'NCCODFAM',
                'NCCODESSF' => 'NCCODSSF'
            ];

            $namedCodes = [];
            foreach ($codes as $k => $v) {
                if (is_string($k)) {
                    $key = strtoupper(trim($k));
                    $key = $aliases[$key] ?? $key;
                    $namedCodes[$key] = $v;
                }
            }

            $qb = self::for($pdo, $library)->select(['NCCAT']);
            $hasAtLeastOneCode = false;
            foreach ($map as $index => $column) {
                $value = $codes[$index] ?? ($namedCodes[$column] ?? null);
                if (is_string($value)) {
                    $value = trim($value);
                    if ($value === '') {
                        $value = null;
                    }
                }
                $qb->whereEq($column, (int)$value);                
            }
            $qb->whereNull('NCCODGAM');
            $row = $qb->firstModel();
            if($row) {
                $data['valeur'] = $row->nc_categorie;
                switch($row->nc_categorie) {
                    case 1:
                        $data['FRA'] = cst::cstCatPrincipal;
                        break;
                    case 2:
                        $data['FRA'] = cst::cstCatAccessoire;
                        break;
                    case 3:
                        $data['FRA'] = cst::cstCatFonction;
                        break;
                }
                return $data;
            }
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
        return null;
    }

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

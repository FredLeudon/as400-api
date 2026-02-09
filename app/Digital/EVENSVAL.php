<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use App\Core\clFichier;
use App\Core\Http;

/**
 * MATIS.EVENSVAL
 *
 * Unique key: EVENL002 => [EVCODE, EVLANG, EVNORD]
 * Indexes: EV_TA00001, EVENL001, EVENL002, EVENL003, EVENL004
 */
final class EVENSVAL extends clFichier
{
    protected static string $table = 'EVENSVAL';
    protected static array $primaryKey = ['EVCODE', 'EVLANG', 'EVNORD'];

    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'EVCODE'   => ['label' => 'EV_CODE_EV',      'type' => 'CHAR',     'nullable' => false],
        'EVNORD'   => ['label' => 'EV_NUM_ORDRE',    'type' => 'SMALLINT', 'nullable' => false],
        'EVLANG'   => ['label' => 'EV_CODE_LANGUE',  'type' => 'CHAR',     'nullable' => false],
        'EVNUM'    => ['label' => 'EV_NUMERIQUE',    'type' => 'CHAR',     'nullable' => false],
        'EVVALAFF' => ['label' => 'EV_VAL_AFFICHEE', 'type' => 'VARCHAR',  'nullable' => false],
        'EVVALSTK' => ['label' => 'EV_VAL_STK_FMT',  'type' => 'CHAR',     'nullable' => false],
        'EVUNIT'   => ['label' => 'EV_UNITE',        'type' => 'CHAR',     'nullable' => false],
    ];

    public static function getEVENSVAL(PDO $pdo, string $evCode) :? array
    {
        $datas[$evCode] = [];
        try {
            
            $library = 'MATIS';
            $evensval = self::for($pdo, $library)
            ->whereEq('EVCODE', $evCode)            
            ->orderBy('EVNORD')
            ->orderBy('EVLANG')
            ->getModels();                        
            if (!empty($evensval)) {
                foreach ($evensval as $model) {                    
                    $index = ( (int)$model->ev_num_ordre ) - 1;                    
                    if($index < 0 ) $index = 0;                       
                    if($model->ev_code_langue !== '') {
                        $datas[$evCode][$index][$model->ev_code_langue] = $model->toArrayLower();
                    } else {
                        $datas[$evCode][$index] = $model->toArrayLower();
                    }                    
                }
            }
            return $datas;
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    public static function getNbEnsVal(PDO $pdo) :? array
    {
        try {
            $rows = self::for($pdo, 'MATIS')
                ->select(['EVCODE', '#COUNT(*) AS NOMBRE'])
                ->whereIn('EVLANG', ['', 'FRA'])
                ->groupBy('EVCODE')
                ->orderBy('EVCODE')
                ->get();

            $datas = [];
            foreach ($rows as $row) {
                $code = $row['EVCODE'] ?? ($row['evcode'] ?? null);
                if ($code === null) continue;
                $count = $row['NOMBRE'] ?? ($row['nombre'] ?? 0);
                $datas[$code] = (int) $count;
            }
            return $datas;
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }

    }
}

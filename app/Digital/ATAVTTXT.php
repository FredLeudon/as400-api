<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class ATAVTTXT extends clFichier
{
    protected static string $table = 'ATAVTTXT';
    protected static array $primaryKey = ['ATIDAVT','ATLANG'];

    protected static array $columns = [
        'ATIDAVT' => ['label'=>'ATIDAVT','type'=>'INTEGER','nullable'=>true],
        'ATLANG'  => ['label'=>'ATLANG','type'=>'CHAR','nullable'=>true],
        'ATTXT'   => ['label'=>'ATTXT','type'=>'VARCHAR','nullable'=>true],
    ];

    /**
     * Get hydrated model for an article (first match).
     */
    public static function getModelsByID(PDO $pdo, int $idavt, ?string $lang = ''): ?array
    {
        $datas = [];        
        try {
            $library = 'MATIS';
            $qb = self::for($pdo, $library)->whereEq('ATIDAVT', $idavt);
            if ($lang !== null && trim((string)$lang) !== '') {
                $qb->whereEq('ATLANG', trim((string)$lang));
            } else {
               $qb->orderBy('ATLANG');
            }
            $models = $qb->getModels();
            if (is_array($models) && count($models) > 0) {
                foreach ($models as $m) {
                    if (is_object($m)) {
                        $datas[$m->at_code_langue] = $m->toArrayLower();
                    }
                }
            }
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
        return $datas === [] ? null : $datas;
    }


}

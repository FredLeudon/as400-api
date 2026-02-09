<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use App\Core\Http;
use App\Core\clFichier;
use App\Digital\ATAVTTXT;

final class APAVTPRD extends clFichier
{
    protected static string $table = 'APAVTPRD';
    protected static array $primaryKey = ['APTYPE','APART','APNORD'];

    protected static array $columns = [
        'APART'   => ['label'=>'APART','type'=>'CHAR','nullable'=>false],
        'APTYPE'  => ['label'=>'APTYPE','type'=>'CHAR','nullable'=>true],
        'APNORD'  => ['label'=>'APNORD','type'=>'INTEGER','nullable'=>true],
        'APIDAVT' => ['label'=>'APIDAVT','type'=>'INTEGER','nullable'=>false],
    ];


    /**
     * Get hydrated model for an article (first match).
     */
    public static function getModelsByID(PDO $pdo,  string $aptype, string $apart, ? int $numOrdre = 0): ?array
    {
        $datas = [];
        try {
            $library = 'MATIS';
            $apart = trim($apart);
            $aptype = trim($aptype);
            if($apart === '' ) return null;
            if($aptype === '') return null;
            $models = self::for($pdo, $library)->whereEq('APART', $apart);
            $models->whereEq('APTYPE', $aptype);
            if ($numOrdre != 0 ) $models->whereEq('APNORD',$numOrdre);
            $models->orderBy('APNORD');
            $models = $models->getModels();
            if (!empty($models)) {
                foreach ($models as $model) {
                    $data = [];
                    $data['AVTPRD'] = $model->toArrayLower();                    
                    $data['AVTTXT'] = ATAVTTXT::getModelsByID($pdo, (int)$model->apidavt);
                    $datas[] = $data;
                }
            }
            return $datas;
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

}

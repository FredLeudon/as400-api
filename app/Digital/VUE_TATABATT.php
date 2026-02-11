<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use Throwable;
use App\Core\Http;
use App\Core\clFichier;

/**
 * MATIS.VUE_TATABATT
 *
 * Vue exposant la configuration des attributs et leurs liaisons fichiers.
 */
final class VUE_TATABATT extends clFichier
{
    protected static string $table = 'VUE_TATABATT';
    protected static array $primaryKey = [];

    protected static array $columns = [
        'TAMODGES'   => ['label' => 'TA_MODE_GESTION',    'type' => 'CHAR',    'nullable' => false],
        'TABIBL'     => ['label' => 'TA_BIBLIOTHEQUE',    'type' => 'CHAR',    'nullable' => true],
        'TAFIC'      => ['label' => 'TA_FICHIER',         'type' => 'CHAR',    'nullable' => false],
        'TALOG'      => ['label' => 'TA_LOGIQUE',         'type' => 'CHAR',    'nullable' => false],
        'TACLE'      => ['label' => 'TA_CLES',            'type' => 'CHAR',    'nullable' => false],
        'TAMULTI'    => ['label' => 'TA_MULTI_VALEUR',    'type' => 'SMALLINT','nullable' => false],
        'TANBMAXVAL' => ['label' => 'TA_NB_MAX_VAL',      'type' => 'CHAR',    'nullable' => false],
        'MAXVALTYP'  => ['label' => 'TA_NB_MAX_VAL_TYPE', 'type' => 'CHAR',    'nullable' => false],
        'ATTRIBUTS'  => ['label' => 'TA_LISTE_ATTRIBUTS', 'type' => 'VARCHAR', 'nullable' => false],
        'RUBRIQUES'  => ['label' => 'TA_LISTE_RUBRIQUES', 'type' => 'VARCHAR', 'nullable' => false],
    ];

   public static function getAttributes(PDO $pdo, ? string $mode = '', ? string $codeAttribut = '') : array
    {
        try {
            $library = 'MATIS';            
            $models = self::for($pdo, $library)->select(['*']) ;            
            $models->whereEq('TA_MODE_GESTION', $mode);
            if ($codeAttribut != '' ) {
                $models->where('TA_LISTE_ATTRIBUTS','LIKE', "%$codeAttribut%");
            }            
            return $models->orderBy('TA_BIBLIOTHEQUE','ASC')                
                ->orderBy('TA_FICHIER','ASC')                
                ->orderBy('TA_LOGIQUE','ASC')                
                ->orderBy('TA_CLES','ASC')                
                ->orderBy('TA_LISTE_ATTRIBUTS','ASC')                
                ->getModels();                                 
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    } 
}

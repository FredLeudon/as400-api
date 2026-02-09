<?php
declare(strict_types=1);

namespace App\Digital;

use PDO;
use Throwable;
use App\Core\Http;
use App\Core\clFichier;

/**
 * MATIS.TATABATT
 *
 * Unique key: TATAL001 => [TACODATT]
 * Indexes: TATAL002, TATAL003, TATAL004, TATAL005, TATAL006
 */
final class TATABATT extends clFichier
{
    protected static string $table = 'TATABATT';
    protected static array $primaryKey = ['TACODATT'];

    protected static array $columns = [
        'TACODATT'   => ['label'=>'TA_CODE_ATTRIBUT',                 'type'=>'CHAR',     'nullable'=>false],
        'TAMODGES'   => ['label'=>'TA_MODE_GESTION',                  'type'=>'CHAR',     'nullable'=>false],
        'TAGROUP'    => ['label'=>'TA_GROUPE',                        'type'=>'SMALLINT', 'nullable'=>false],
        'TAFAM'      => ['label'=>'TA_FAMILLE',                       'type'=>'SMALLINT', 'nullable'=>false],
        'TASFAM'     => ['label'=>'TA_SOUS_FAMILLE',                  'type'=>'SMALLINT', 'nullable'=>false],
        'TAORDRE'    => ['label'=>'TA_ORDRE',                         'type'=>'INTEGER',  'nullable'=>false],
        'TA_MODIF'   => ['label'=>'TA_MODIF',                         'type'=>'CHAR',     'nullable'=>true],
        'TAISNUM'    => ['label'=>'TA_EST_NUMERIQUE',                 'type'=>'CHAR',     'nullable'=>false],
        'TATYPE'     => ['label'=>'TA_TYPE_ATTRIBUT',                 'type'=>'CHAR',     'nullable'=>false],
        'TAMULV'     => ['label'=>'TA_EST_MULTIVALEUR',               'type'=>'CHAR',     'nullable'=>false],
        'TA_NB00001' => ['label'=>'TA_NB_MAX_VAL',                    'type'=>'SMALLINT', 'nullable'=>true],
        'TA_NB00002' => ['label'=>'TA_NB_MAX_VAL_TYPE',               'type'=>'VARCHAR',  'nullable'=>true],
        'TAPIM'      => ['label'=>'TA_PIM_FOURNISSEUR',               'type'=>'CHAR',     'nullable'=>false],
        'FAFT'       => ['label'=>'TA_SUR_FICHE_TECHNIQUE',           'type'=>'CHAR',     'nullable'=>true],
        'TAUNTAFF'   => ['label'=>'TA_UNITE_AFFICHAGE',               'type'=>'CHAR',     'nullable'=>false],
        'TANBRCAR'   => ['label'=>'TA_NOMBRE_CARACTERES',             'type'=>'INTEGER',  'nullable'=>false],
        'TACATFON'   => ['label'=>'TA_CODE_CATEGORIE_FONCTIONNELLE',  'type'=>'SMALLINT', 'nullable'=>false],
        'TACASER'    => ['label'=>'TA_CODE_CATEGORIE_SERVICE',        'type'=>'SMALLINT', 'nullable'=>false],
        'TATYPSUP'   => ['label'=>'TA_TYPE_SUPPRESSION',              'type'=>'CHAR',     'nullable'=>false],
        'TAFICLIEN'  => ['label'=>'TA_FICHIER_LIE',                   'type'=>'CHAR',     'nullable'=>true],
        'TATYPLIEN'  => ['label'=>'TA_TYPE_LIEN',                     'type'=>'CHAR',     'nullable'=>true],
        'TACBOBIB'   => ['label'=>'TA_COMBO_BIBLIOTHEQUE',            'type'=>'CHAR',     'nullable'=>true],
        'TACBOFIC'   => ['label'=>'TA_COMBO_FICHIER',                 'type'=>'CHAR',     'nullable'=>true],
        'TACBOLOG'   => ['label'=>'TA_COMBO_LOGIQUE',                 'type'=>'CHAR',     'nullable'=>true],
        'TACBOCLE'   => ['label'=>'TA_COMBO_CLES',                    'type'=>'CHAR',     'nullable'=>true],
        'TACBOLIB'   => ['label'=>'TA_COMBO_LIBELLE',                 'type'=>'CHAR',     'nullable'=>true],
        'TACBOVAL'   => ['label'=>'TA_COMBO_VALEUR',                  'type'=>'CHAR',     'nullable'=>true],
        'TACBOSQL'   => ['label'=>'TA_SQL',                           'type'=>'VARCHAR',  'nullable'=>true],
        'TABIBL'     => ['label'=>'TA_BIBLIOTHEQUE',                  'type'=>'CHAR',     'nullable'=>true],
        'TAFIC'      => ['label'=>'TA_FICHIER',                       'type'=>'CHAR',     'nullable'=>false],
        'TALOG'      => ['label'=>'TA_LOGIQUE',                       'type'=>'CHAR',     'nullable'=>false],
        'TACLE'      => ['label'=>'TA_CLES',                          'type'=>'CHAR',     'nullable'=>false],
        'TAZONE'     => ['label'=>'TA_ZONE',                          'type'=>'CHAR',     'nullable'=>false],
        'TA_CO00001' => ['label'=>'TA_CODE_INIT',                     'type'=>'VARCHAR',  'nullable'=>true],
    ];

    /**
     * Get rows by attribute code (TACODATT).
     *
     */
    public static function getByAttribute(PDO $pdo, string $tamodges, string $tacodatt): array
    {
        try {
            $library = 'MATIS';
            $tacodatt = trim($tacodatt);
            $rows = self::for($pdo, $library);
            if($tamodges !== '') {
                $rows->whereEq('TAMODGES', $tamodges);
            } 
            if($tacodatt !== '') {
                $rows->whereEq('TACODATT', $tacodatt);
            } 
            return $rows
                ->orderBy('TAGROUP','ASC')
                ->orderBy('TAFAM','ASC')
                ->orderBy('TASFAM','ASC')
                ->orderBy('TAORDRE','ASC')
                ->getModels();

        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * Get rows by file name (TAFIC).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function getByFile(PDO $pdo, string $tafic): array
    {
        try {
            $library = 'MATIS';
            $tafic = trim($tafic);
            if ($tafic === '') return [];

            return self::for($pdo, $library)
                ->select(array_keys(self::$columns))
                ->whereEq('TAFIC', $tafic)
                ->orderBy('TAGROUP','ASC')
                ->orderBy('TAFAM','ASC')
                ->orderBy('TASFAM','ASC')
                ->orderBy('TAORDRE','ASC')
                ->getModels();

        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

}

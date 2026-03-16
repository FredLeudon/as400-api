<?php
declare(strict_types=1);

namespace App\Reflex;

final class Dépot
{

    private const TYPE_STOCK_old = [
        '200'           => ['code' => '200' , 'libellé' => 'Physique'],
        '210'           => ['code' => '210' , 'libellé' => 'Physique bloqué'],
        '240'           => ['code' => '240' , 'libellé' => 'Physique non bloqué'],
        '270'           => ['code' => '270' , 'libellé' => 'Disponible ODP'],
        '260'           => ['code' => '260' , 'libellé' => 'Disponible PRP'],
        'ENC'           => ['code' => 'ENC' , 'libellé' => 'Prévu réception [progescom]'],
        '110'           => ['code' => '110' , 'libellé' => 'ODP'],
        '130'           => ['code' => '130' , 'libellé' => 'À mettre en lancement'],
        '150'           => ['code' => '150' , 'libellé' => 'Mis en lancement'],
        '160'           => ['code' => '160' , 'libellé' => 'À prélever PRP'],
        '170'           => ['code' => '170' , 'libellé' => 'Préparé'],
        '180'           => ['code' => '180' , 'libellé' => 'En instance de consommation Picking'],
            'SAI'           => ['code' => 'SAI' , 'libellé' => 'En saisie [progescom'],
            'RFLX'          => ['code' => 'RFLX', 'libellé' => '"Reflex [progescom]'],
            'RAL'           => ['code' => 'RAL' , 'libellé' => 'En RAL [progescom]']
    ];
    private const TYPE_STOCK = [
        '200'           => ['code' =>'200', 'libellé' => "Physique"                                 , 'affichage' => true ],
        '240'           => ['code' =>'240', 'libellé' => "Physique non bloqué"                      , 'affichage' => true ],
        '270'           => ['code' =>'270', 'libellé' => "Disponible pour odp"                      , 'affichage' => true ],
        '260'           => ['code' =>'260', 'libellé' => "Disponible pour préparation"              , 'affichage' => true ],
        '210'           => ['code' =>'210', 'libellé' => "Physique bloqué"                          , 'affichage' => true ],
        '211'           => ['code' =>'211', 'libellé' => "Bloqué pour motif particulier"            , 'affichage' => false ],
        '212'           => ['code' =>'212', 'libellé' => "Bloqué sous douane"                       , 'affichage' => false ],
        '213'           => ['code' =>'213', 'libellé' => "Bloqué pour stabilisation"                , 'affichage' => false ],
        '214'           => ['code' =>'214', 'libellé' => "Bloqué pour contrôle"                     , 'affichage' => false ],
        '215'           => ['code' =>'215', 'libellé' => "Bloqué pour reconditionnement"            , 'affichage' => false ],
        '216'           => ['code' =>'216', 'libellé' => "Sur emplacement bloqué"                   , 'affichage' => false ],
        '217'           => ['code' =>'217', 'libellé' => "Bloqué pour inventaire"                   , 'affichage' => false ],
        '320'           => ['code' =>'320', 'libellé' => "Arrivée ODP transfert"                    , 'affichage' => false ],
        '330'           => ['code' =>'330', 'libellé' => "Arrivée préparation transfert"            , 'affichage' => false ],
        '410'           => ['code' =>'410', 'libellé' => "Prévu conditionnement"                    , 'affichage' => false ],
        '010'           => ['code' =>'010', 'libellé' => "Prévu réception"                          , 'affichage' => false ],
        'ENC'           => ['code' =>'ENC', 'libellé' => 'Prévu réception [progescom]'              , 'affichage' => false ],
        '015'           => ['code' =>'015', 'libellé' => "Prévu retour"                             , 'affichage' => false ],
        '018'           => ['code' =>'018', 'libellé' => "Prévu transfert"                          , 'affichage' => false ],
        '050'           => ['code' =>'050', 'libellé' => "Avis d'expédition réception"              , 'affichage' => false ],
        '060'           => ['code' =>'060', 'libellé' => "Avis d'expédition retour"                 , 'affichage' => false ],
        '070'           => ['code' =>'070', 'libellé' => "Avis d'expédition transfert"              , 'affichage' => false ],
        '020'           => ['code' =>'020', 'libellé' => "Réception"                                , 'affichage' => false ],
        '030'           => ['code' =>'030', 'libellé' => "Retour"                                   , 'affichage' => false ],
        '040'           => ['code' =>'040', 'libellé' => "Transfert"                                , 'affichage' => false ],
        '110'           => ['code' =>'110', 'libellé' => "Ordres de préparation"                    , 'affichage' => true ],
        '120'           => ['code' =>'120', 'libellé' => "Réservé ordre de préparation"             , 'affichage' => false ],
        '130'           => ['code' =>'130', 'libellé' => "A mettre en lancement"                    , 'affichage' => true ],
        '140'           => ['code' =>'140', 'libellé' => "Réservé à mettre en lancement"            , 'affichage' => false ],
        '150'           => ['code' =>'150', 'libellé' => "Mis en lancement"                         , 'affichage' => true ],
        '155'           => ['code' =>'155', 'libellé' => "Réservé mis en lancement"                 , 'affichage' => false ],
        '160'           => ['code' =>'160', 'libellé' => "A prélever préparation"                   , 'affichage' => true ],
        '170'           => ['code' =>'170', 'libellé' => "Préparé"                                  , 'affichage' => true ],
        '180'           => ['code' =>'180', 'libellé' => "Instance consommation picking"            , 'affichage' => true ],
        '340'           => ['code' =>'340', 'libellé' => "Départ ODP transfert"                     , 'affichage' => false ],
        '350'           => ['code' =>'350', 'libellé' => "Départ préparation transfert"             , 'affichage' => false ],
        '250'           => ['code' =>'250', 'libellé' => "Comptable"                                , 'affichage' => true ],
        '220'           => ['code' =>'220', 'libellé' => "Ecart"                                    , 'affichage' => false ],
        '225'           => ['code' =>'225', 'libellé' => "Ecart après inventaire"                   , 'affichage' => false ],
        '510'           => ['code' =>'510', 'libellé' => "Consigne plein"                           , 'affichage' => false ],
        '520'           => ['code' =>'520', 'libellé' => "Ecart consigne plein"                     , 'affichage' => false ],
        '525'           => ['code' =>'525', 'libellé' => "Ecart consigne plein après inv"           , 'affichage' => false ],
        '610'           => ['code' =>'610', 'libellé' => "Consigne vide"                            , 'affichage' => false ],
        '620'           => ['code' =>'620', 'libellé' => "Ecart consigne vide"                      , 'affichage' => false ],
        'SAI'           => ['code' =>'SAI', 'libellé' => 'En saisie [progescom]'                    , 'affichage' => true ],
        'RFLX'          => ['code' =>'RFLX','libellé' => "Reflex [progescom]"                       , 'affichage' => true ],
        'RAL'           => ['code' =>'RAL', 'libellé' => "En RAL [progescom]"                       , 'affichage' => true ]
    ];

    private const BY_NAME = [
        'matfer'        => '06',
        'insitu'        => '40',
        'bourgeat'      => '38',
        'flovending'    => '15'        
    ];
    private const BY_ACTIVITY = [
        'mat'           => '06',
        'bgt'           => '38',
        'smo'           => '40',
        'flv'           => '15'
    ];
    // [Dpo physique, DPO Logique, Activité, Bibliothèque, Bibliothèque Spé]
    private const BY_CODE = [
        '06'            => ['code' => '06', 'DPOPhysique' => 'MAT', 'DPOLogique' => 'MAT', 'Activité' => 'MAT', 'library' => 'MATR', 'library_spé' => 'SJOSPE1'],
        '38'            => ['code' => '38', 'DPOPhysique' => 'AB2', 'DPOLogique' => 'BGT', 'Activité' => 'BGT', 'library' => 'BGTR', 'library_spé' => 'SJOSPE3'],
        '40'            => ['code' => '40', 'DPOPhysique' => 'SEV', 'DPOLogique' => 'SEV', 'Activité' => 'SMO', 'library' => 'SJOR', 'library_spé' => 'SJOSPE2'],
        '15'            => ['code' => '15', 'DPOPhysique' => 'SEV', 'DPOLogique' => 'SEV', 'Activité' => 'FLV', 'library' => 'FLVR', 'library_spé' => 'SJOSPE4']
    ];
    
    public static function codeOf(string $depot): ?string
    {
        $key = strtolower(trim($depot));

        if (isset(self::BY_NAME[$key])) return self::BY_NAME[$key];
        if (isset(self::BY_ACTIVITY[$key])) return self::BY_ACTIVITY[$key];
        if (isset(self::BY_CODE[$key])) return self::BY_CODE[$key]['code'];

        return null;
    }

    public static function get(string $depot): ?array
    {
        $key = strtolower(trim($depot));
        
        if (isset(self::BY_ACTIVITY[$key])) {
            $code = self::BY_ACTIVITY[$key];
            return self::BY_CODE[$code] ?? null;
        }

        if (isset(self::BY_NAME[$key])) {
            $code = self::BY_NAME[$key];
            return self::BY_CODE[$code] ?? null;
        }

        if (isset(self::BY_CODE[$key])) {
            return self::BY_CODE[$key];
        }
        return null;
    }

    public static function all(): array
    {
        return array_values(self::BY_CODE);
    }

    public static function TypesStocks() : array
    {
         return array_values(self::TYPE_STOCK);
    }
}

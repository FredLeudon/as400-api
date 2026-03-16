<?php
declare(strict_types=1);

namespace App\Reflex;

use App\Reflex\Dépot;
use App\Core\clFichier;

final class HLACTIP extends clFichier
{
    protected static string $table = 'HLACTIP';
    protected static array $primaryKey = ['ACCACT'];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'ACCACT' => ['label' => 'Code activite',                                    'type' => 'CHAR',    'nullable' => false],
        'ACLACT' => ['label' => 'Libelle activite',                                 'type' => 'CHAR',    'nullable' => false],
        'ACRACT' => ['label' => 'Libelle reduit activite',                          'type' => 'CHAR',    'nullable' => false],
        'ACCCLI' => ['label' => 'Code client',                                      'type' => 'CHAR',    'nullable' => false],
        'ACTGPR' => ['label' => 'Top gestion des prix a la reception',              'type' => 'CHAR',    'nullable' => false],
        'ACTDMA' => ['label' => "Top gestion date d'ordo mini possible",            'type' => 'CHAR',    'nullable' => false],
        'ACTDSA' => ['label' => "Top gestion date d'ordo sup. aux preced. possible",'type' => 'CHAR',    'nullable' => false],
        'ACLNI1' => ['label' => 'Libelle du niveau de type de VL 1',                'type' => 'CHAR',    'nullable' => false],
        'ACLNI2' => ['label' => 'Libelle du niveau de type de VL 2',                'type' => 'CHAR',    'nullable' => false],
        'ACLNI3' => ['label' => 'Libelle du niveau de type de VL 3',                'type' => 'CHAR',    'nullable' => false],
        'ACLCOU' => ['label' => 'Libelle de la couche',                             'type' => 'CHAR',    'nullable' => false],
        'ACCTVS' => ['label' => 'Code type de valorisation stock',                  'type' => 'CHAR',    'nullable' => false],
        'ACPGBL' => ['label' => "Code programme edition BL pour l'activite",        'type' => 'CHAR',    'nullable' => false],
        'ACBIBL' => ['label' => "Code bibliotheque pgm edition BL pour l'activite", 'type' => 'CHAR',    'nullable' => false],
        'ACNCOM' => ['label' => 'Numero de commentaire',                            'type' => 'DECIMAL', 'nullable' => false],
        'ACSCRE' => ['label' => 'Date de creation - siecle',                        'type' => 'DECIMAL', 'nullable' => false],
        'ACACRE' => ['label' => 'Date de creation - annee',                         'type' => 'DECIMAL', 'nullable' => false],
        'ACMCRE' => ['label' => 'Date de creation - mois',                          'type' => 'DECIMAL', 'nullable' => false],
        'ACJCRE' => ['label' => 'Date de creation - jour',                          'type' => 'DECIMAL', 'nullable' => false],
        'ACHCRE' => ['label' => 'Heure de creation',                                'type' => 'DECIMAL', 'nullable' => false],
        'ACCUCR' => ['label' => 'Code utilisateur creation',                        'type' => 'CHAR',    'nullable' => false],
        'ACSMAJ' => ['label' => 'Date de derniere mise a jour - siecle',            'type' => 'DECIMAL', 'nullable' => false],
        'ACAMAJ' => ['label' => 'Date de derniere mise a jour - annee',             'type' => 'DECIMAL', 'nullable' => false],
        'ACMMAJ' => ['label' => 'Date de derniere mise a jour - mois',              'type' => 'DECIMAL', 'nullable' => false],
        'ACJMAJ' => ['label' => 'Date de derniere mise a jour - jour',              'type' => 'DECIMAL', 'nullable' => false],
        'ACHMAJ' => ['label' => 'Heure de derniere mise a jour',                    'type' => 'DECIMAL', 'nullable' => false],
        'ACCUMJ' => ['label' => 'Code utilisateur derniere mise a jour',            'type' => 'CHAR',    'nullable' => false],
        'ACTOPD' => ['label' => 'Top desactivation',                                'type' => 'CHAR',    'nullable' => false],
    ];

    private static function libraryOf(string $CodeActivité): ?string
    {
        $company = Dépot::get($CodeActivité);
        if (!$company) return null;

        $library = (string)($company['library'] ?? '');
        return $library !== '' ? $library : null;
    }
}

<?php
declare(strict_types=1);

namespace App\Reflex;

use App\Reflex\Dépot;
use App\Core\clFichier;

final class HLDEPLP extends clFichier
{
    protected static string $table = 'HLDEPLP';
    protected static array $primaryKey = ['DLCDPL'];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'DLCDPL' => ['label' => 'Code depot logique',                              'type' => 'CHAR',    'nullable' => false],
        'DLLDPL' => ['label' => 'Libelle depot logique',                           'type' => 'CHAR',    'nullable' => false],
        'DLRDPL' => ['label' => 'Libelle reduit depot logique',                    'type' => 'CHAR',    'nullable' => false],
        'DLITDL' => ['label' => 'Interlocuteur depot logique',                     'type' => 'CHAR',    'nullable' => false],
        'DLTLI3' => ['label' => 'Telephone interlocuteur depot logique',           'type' => 'CHAR',    'nullable' => false],
        'DLTCI3' => ['label' => 'Telecopie interlocuteur depot logique',           'type' => 'CHAR',    'nullable' => false],
        'DLCADR' => ['label' => 'Code adresse',                                    'type' => 'CHAR',    'nullable' => false],
        'DLNCOM' => ['label' => 'Numero de commentaire',                           'type' => 'DECIMAL', 'nullable' => false],
        'DLSCRE' => ['label' => 'Date de creation - siecle',                       'type' => 'DECIMAL', 'nullable' => false],
        'DLACRE' => ['label' => 'Date de creation - annee',                        'type' => 'DECIMAL', 'nullable' => false],
        'DLMCRE' => ['label' => 'Date de creation - mois',                         'type' => 'DECIMAL', 'nullable' => false],
        'DLJCRE' => ['label' => 'Date de creation - jour',                         'type' => 'DECIMAL', 'nullable' => false],
        'DLHCRE' => ['label' => 'Heure de creation',                               'type' => 'DECIMAL', 'nullable' => false],
        'DLCUCR' => ['label' => 'Code utilisateur creation',                       'type' => 'CHAR',    'nullable' => false],
        'DLSMAJ' => ['label' => 'Date de derniere mise a jour - siecle',           'type' => 'DECIMAL', 'nullable' => false],
        'DLAMAJ' => ['label' => 'Date de derniere mise a jour - annee',            'type' => 'DECIMAL', 'nullable' => false],
        'DLMMAJ' => ['label' => 'Date de derniere mise a jour - mois',             'type' => 'DECIMAL', 'nullable' => false],
        'DLJMAJ' => ['label' => 'Date de derniere mise a jour - jour',             'type' => 'DECIMAL', 'nullable' => false],
        'DLHMAJ' => ['label' => 'Heure de derniere mise a jour',                   'type' => 'DECIMAL', 'nullable' => false],
        'DLCUMJ' => ['label' => 'Code utilisateur derniere mise a jour',           'type' => 'CHAR',    'nullable' => false],
        'DLTOPD' => ['label' => 'Top desactivation',                               'type' => 'CHAR',    'nullable' => false],
    ];

   private static function libraryOf(string $CodeActivité): ?string
    {
        $company = Dépot::get($CodeActivité);
        if (!$company) return null;

        $library = (string)($company['library'] ?? '');
        return $library !== '' ? $library : null;
    }
}

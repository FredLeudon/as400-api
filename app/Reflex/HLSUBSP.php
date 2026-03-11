<?php
declare(strict_types=1);

namespace App\Reflex;

use App\Domain\Company;
use App\Core\clFichier;

final class HLSUBSP extends clFichier
{
    protected static string $table = 'HLSUBSP';
    protected static array $primaryKey = ['SBCACT', 'SBCART', 'SBCVLA', 'SBCPRP', 'SBCQAL', 'SBCSUB', 'SBNSQS'];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'SBCACT' => ['label' => 'Code activite',                                  'type' => 'CHAR',    'nullable' => false],
        'SBCART' => ['label' => 'Code article',                                   'type' => 'CHAR',    'nullable' => false],
        'SBCVLA' => ['label' => 'Code variante logistique article',               'type' => 'CHAR',    'nullable' => false],
        'SBCPRP' => ['label' => 'Code proprietaire',                              'type' => 'CHAR',    'nullable' => false],
        'SBCQAL' => ['label' => 'Code qualite',                                   'type' => 'CHAR',    'nullable' => false],
        'SBCSUB' => ['label' => 'Code substitution',                              'type' => 'CHAR',    'nullable' => false],
        'SBNSQS' => ['label' => 'Numero de sequence substitution',                'type' => 'DECIMAL', 'nullable' => false],
        'SBNORS' => ['label' => "Numero ordre d'affichage substitution",          'type' => 'DECIMAL', 'nullable' => false],
        'SBCARS' => ['label' => 'Code article substitution',                      'type' => 'CHAR',    'nullable' => false],
        'SBCVLS' => ['label' => 'Code variante logistique article substitution',  'type' => 'CHAR',    'nullable' => false],
        'SBCPRS' => ['label' => 'Code proprietaire substitution',                 'type' => 'CHAR',    'nullable' => false],
        'SBCQUS' => ['label' => 'Code qualite substitution',                      'type' => 'CHAR',    'nullable' => false],
        'SBNQSU' => ['label' => 'Numerateur quantite substitution',               'type' => 'DECIMAL', 'nullable' => false],
        'SBDQSU' => ['label' => 'Denominateur quantite substitution',             'type' => 'DECIMAL', 'nullable' => false],
        'SBSDSU' => ['label' => 'Date debut validite substitution - siecle',      'type' => 'DECIMAL', 'nullable' => false],
        'SBADSU' => ['label' => 'Date debut validite substitution - annee',       'type' => 'DECIMAL', 'nullable' => false],
        'SBMDSU' => ['label' => 'Date debut validite substitution - mois',        'type' => 'DECIMAL', 'nullable' => false],
        'SBJDSU' => ['label' => 'Date debut validite substitution - jour',        'type' => 'DECIMAL', 'nullable' => false],
        'SBSFSU' => ['label' => 'Date fin validite substitution - siecle',        'type' => 'DECIMAL', 'nullable' => false],
        'SBAFSU' => ['label' => 'Date fin validite substitution - annee',         'type' => 'DECIMAL', 'nullable' => false],
        'SBMFSU' => ['label' => 'Date fin validite substitution - mois',          'type' => 'DECIMAL', 'nullable' => false],
        'SBJFSU' => ['label' => 'Date fin validite substitution - jour',          'type' => 'DECIMAL', 'nullable' => false],
        'SBNCOM' => ['label' => 'Numero de commentaire',                          'type' => 'DECIMAL', 'nullable' => false],
        'SBSCRE' => ['label' => 'Date de creation - siecle',                      'type' => 'DECIMAL', 'nullable' => false],
        'SBACRE' => ['label' => 'Date de creation - annee',                       'type' => 'DECIMAL', 'nullable' => false],
        'SBMCRE' => ['label' => 'Date de creation - mois',                        'type' => 'DECIMAL', 'nullable' => false],
        'SBJCRE' => ['label' => 'Date de creation - jour',                        'type' => 'DECIMAL', 'nullable' => false],
        'SBHCRE' => ['label' => 'Heure de creation',                              'type' => 'DECIMAL', 'nullable' => false],
        'SBCUCR' => ['label' => 'Code utilisateur creation',                      'type' => 'CHAR',    'nullable' => false],
        'SBSMAJ' => ['label' => 'Date de derniere mise a jour - siecle',          'type' => 'DECIMAL', 'nullable' => false],
        'SBAMAJ' => ['label' => 'Date de derniere mise a jour - annee',           'type' => 'DECIMAL', 'nullable' => false],
        'SBMMAJ' => ['label' => 'Date de derniere mise a jour - mois',            'type' => 'DECIMAL', 'nullable' => false],
        'SBJMAJ' => ['label' => 'Date de derniere mise a jour - jour',            'type' => 'DECIMAL', 'nullable' => false],
        'SBHMAJ' => ['label' => 'Heure de derniere mise a jour',                  'type' => 'DECIMAL', 'nullable' => false],
        'SBCUMJ' => ['label' => 'Code utilisateur derniere mise a jour',          'type' => 'CHAR',    'nullable' => false],
        'SBTOPD' => ['label' => 'Top desactivation',                              'type' => 'CHAR',    'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;

        $library = (string)($company['reflex_library'] ?? '');
        return $library !== '' ? $library : null;
    }
}

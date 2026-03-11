<?php
declare(strict_types=1);

namespace App\Reflex;

use App\Domain\Company;
use App\Core\clFichier;

final class HLARTIP extends clFichier
{
    protected static string $table = 'HLARTIP';
    protected static array $primaryKey = ['ARCACT', 'ARCART'];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'ARCACT' => ['label' => 'Code activite',                                               'type' => 'CHAR',    'nullable' => false],
        'ARCART' => ['label' => 'Code article',                                                'type' => 'CHAR',    'nullable' => false],
        'ARLART' => ['label' => 'Libelle article',                                             'type' => 'CHAR',    'nullable' => false],
        'ARLRAR' => ['label' => 'Libelle reduit article',                                      'type' => 'CHAR',    'nullable' => false],
        'ARMDAR' => ['label' => 'Mot directeur article',                                       'type' => 'CHAR',    'nullable' => false],
        'ARUAR1' => ['label' => "Code d'usage article",                                        'type' => 'CHAR',    'nullable' => false],
        'ARMRQA' => ['label' => 'Marquage article',                                            'type' => 'CHAR',    'nullable' => false],
        'ARTPDV' => ['label' => 'Top article a poids variable',                                'type' => 'CHAR',    'nullable' => false],
        'ARTPDR' => ['label' => 'Top poids detaille a la reception',                           'type' => 'CHAR',    'nullable' => false],
        'ARTPDP' => ['label' => 'Top poids detaille a la preparation',                         'type' => 'CHAR',    'nullable' => false],
        'ARTARC' => ['label' => 'Top article consigne',                                        'type' => 'CHAR',    'nullable' => false],
        'ARTARA' => ['label' => 'Top article alcool',                                          'type' => 'CHAR',    'nullable' => false],
        'ARTARD' => ['label' => 'Top article dangereux',                                       'type' => 'CHAR',    'nullable' => false],
        'ARNBJS' => ['label' => 'Delai de stabilisation - Nombre de jours',                   'type' => 'DECIMAL', 'nullable' => false],
        'ARNBHS' => ['label' => "Delai de stabilisation - Nombre d'heures minutes",           'type' => 'DECIMAL', 'nullable' => false],
        'ARNBJO' => ['label' => "Nombre de jours mini date d'ordonnancement",                 'type' => 'DECIMAL', 'nullable' => false],
        'ARFBDS' => ['label' => "Fourchette banalisation date d'ordo pour stockage",          'type' => 'DECIMAL', 'nullable' => false],
        'ARFBDP' => ['label' => "Fourchette banalisation date d'ordo pour preparat.",         'type' => 'DECIMAL', 'nullable' => false],
        'ARCFPM' => ['label' => 'Code famille de peremption',                                  'type' => 'CHAR',    'nullable' => false],
        'ARCRBA' => ['label' => 'Code reference de base article',                              'type' => 'CHAR',    'nullable' => false],
        'ARCTVL' => ['label' => 'Code type de variante logistique',                            'type' => 'CHAR',    'nullable' => false],
        'ARNOBJ' => ['label' => "Numero d'objet",                                              'type' => 'DECIMAL', 'nullable' => false],
        'ARNCOM' => ['label' => 'Numero de commentaire',                                       'type' => 'DECIMAL', 'nullable' => false],
        'ARSCRE' => ['label' => 'Date de creation - siecle',                                   'type' => 'DECIMAL', 'nullable' => false],
        'ARACRE' => ['label' => 'Date de creation - annee',                                    'type' => 'DECIMAL', 'nullable' => false],
        'ARMCRE' => ['label' => 'Date de creation - mois',                                     'type' => 'DECIMAL', 'nullable' => false],
        'ARJCRE' => ['label' => 'Date de creation - jour',                                     'type' => 'DECIMAL', 'nullable' => false],
        'ARHCRE' => ['label' => 'Heure de creation',                                           'type' => 'DECIMAL', 'nullable' => false],
        'ARCUCR' => ['label' => 'Code utilisateur creation',                                   'type' => 'CHAR',    'nullable' => false],
        'ARSMAJ' => ['label' => 'Date de derniere mise a jour - siecle',                       'type' => 'DECIMAL', 'nullable' => false],
        'ARAMAJ' => ['label' => 'Date de derniere mise a jour - annee',                        'type' => 'DECIMAL', 'nullable' => false],
        'ARMMAJ' => ['label' => 'Date de derniere mise a jour - mois',                         'type' => 'DECIMAL', 'nullable' => false],
        'ARJMAJ' => ['label' => 'Date de derniere mise a jour - jour',                         'type' => 'DECIMAL', 'nullable' => false],
        'ARHMAJ' => ['label' => 'Heure de derniere mise a jour',                               'type' => 'DECIMAL', 'nullable' => false],
        'ARCUMJ' => ['label' => 'Code utilisateur derniere mise a jour',                       'type' => 'CHAR',    'nullable' => false],
        'ARTOPD' => ['label' => 'Top desactivation',                                           'type' => 'CHAR',    'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;

        $library = (string)($company['reflex_library'] ?? '');
        return $library !== '' ? $library : null;
    }
}

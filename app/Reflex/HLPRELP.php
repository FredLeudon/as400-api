<?php
declare(strict_types=1);

namespace App\Reflex;

use App\Domain\Company;
use App\Core\clFichier;

final class HLPRELP extends clFichier
{
    protected static string $table = 'HLPRELP';
    protected static array $primaryKey = ['PVCACT', 'PVCDPO', 'PVNANN', 'PVNPRL'];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'PVCACT' => ['label' => 'Code activite',                                            'type' => 'CHAR',    'nullable' => false],
        'PVCDPO' => ['label' => 'Code depot physique',                                       'type' => 'CHAR',    'nullable' => false],
        'PVNANN' => ['label' => "Numero d'annee",                                            'type' => 'DECIMAL', 'nullable' => false],
        'PVNPRL' => ['label' => 'Numero de prelevement',                                     'type' => 'DECIMAL', 'nullable' => false],
        'PVNANP' => ['label' => "Numero d'annee preparation",                                'type' => 'DECIMAL', 'nullable' => false],
        'PVNPRE' => ['label' => 'Numero de preparation',                                     'type' => 'DECIMAL', 'nullable' => false],
        'PVSSCA' => ['label' => 'Siecle lancement',                                          'type' => 'DECIMAL', 'nullable' => false],
        'PVANCA' => ['label' => 'Annee lancement',                                           'type' => 'DECIMAL', 'nullable' => false],
        'PVMOCA' => ['label' => 'Mois lancement',                                            'type' => 'DECIMAL', 'nullable' => false],
        'PVJOCA' => ['label' => 'Jour lancement',                                            'type' => 'DECIMAL', 'nullable' => false],
        'PVCDLA' => ['label' => 'Code lancement',                                            'type' => 'CHAR',    'nullable' => false],
        'PVCT03' => ['label' => 'Code type de prelevement',                                  'type' => 'CHAR',    'nullable' => false],
        'PVCZPR' => ['label' => 'Code zone de preparation',                                  'type' => 'CHAR',    'nullable' => false],
        'PVCFPL' => ['label' => 'Code famille de prelevement',                               'type' => 'CHAR',    'nullable' => false],
        'PVCATL' => ['label' => 'Code atelier de prelevement',                               'type' => 'CHAR',    'nullable' => false],
        'PVCCPL' => ['label' => 'Code circuit de prelevement',                               'type' => 'CHAR',    'nullable' => false],
        'PVQUPB' => ['label' => 'Quantite en VL de base unite de prelevement pick',          'type' => 'DECIMAL', 'nullable' => false],
        'PVCATA' => ['label' => 'Code atelier origine',                                      'type' => 'CHAR',    'nullable' => false],
        'PVCTPA' => ['label' => 'Code type de prelevement affecte',                          'type' => 'CHAR',    'nullable' => false],
        'PVNSPL' => ['label' => 'Numero de seq. de prelevement atelier/circuit',             'type' => 'DECIMAL', 'nullable' => false],
        'PVNSQM' => ['label' => 'Numero de sequence mission du prelevement',                 'type' => 'DECIMAL', 'nullable' => false],
        'PVNANM' => ['label' => "Numero d'annee mission de prelevement",                     'type' => 'DECIMAL', 'nullable' => false],
        'PVNMIS' => ['label' => 'Numero de mission de prelevement',                          'type' => 'DECIMAL', 'nullable' => false],
        'PVNLPL' => ['label' => 'Numero de ligne sur la mission',                            'type' => 'DECIMAL', 'nullable' => false],
        'PVNEPP' => ['label' => 'Numero emplacement de prelevement',                         'type' => 'DECIMAL', 'nullable' => false],
        'PVC1EM' => ['label' => 'Code 1 emplacement de prelevement',                         'type' => 'CHAR',    'nullable' => false],
        'PVC2EM' => ['label' => 'Code 2 emplacement de prelevement',                         'type' => 'CHAR',    'nullable' => false],
        'PVC3EM' => ['label' => 'Code 3 emplacement de prelevement',                         'type' => 'CHAR',    'nullable' => false],
        'PVC4EM' => ['label' => 'Code 4 emplacement de prelevement',                         'type' => 'CHAR',    'nullable' => false],
        'PVC5EM' => ['label' => 'Code 5 emplacement de prelevement',                         'type' => 'CHAR',    'nullable' => false],
        'PVNEMD' => ['label' => 'Numero emplacement de destination',                         'type' => 'DECIMAL', 'nullable' => false],
        'PVNEMR' => ['label' => 'Numero emplacement reappro picking',                        'type' => 'DECIMAL', 'nullable' => false],
        'PVCATD' => ['label' => 'Code atelier destination reappro picking',                  'type' => 'CHAR',    'nullable' => false],
        'PVSDPL' => ['label' => 'Date disponibilite prelevement - siecle',                   'type' => 'DECIMAL', 'nullable' => false],
        'PVADPL' => ['label' => 'Date disponibilite prelevement - annee',                    'type' => 'DECIMAL', 'nullable' => false],
        'PVMDPL' => ['label' => 'Date disponibilite prelevement - mois',                     'type' => 'DECIMAL', 'nullable' => false],
        'PVJDPL' => ['label' => 'Date disponibilite prelevement - jour',                     'type' => 'DECIMAL', 'nullable' => false],
        'PVHDPL' => ['label' => 'Heure disponibilite prelevement',                           'type' => 'DECIMAL', 'nullable' => false],
        'PVNSUP' => ['label' => 'Numero du support de prelevement',                          'type' => 'DECIMAL', 'nullable' => false],
        'PVNGEI' => ['label' => 'Numero du GEI de prelevement',                              'type' => 'DECIMAL', 'nullable' => false],
        'PVNSUG' => ['label' => 'Numero du support apres prelevement',                       'type' => 'DECIMAL', 'nullable' => false],
        'PVCRSU' => ['label' => 'Critere reference support',                                 'type' => 'CHAR',    'nullable' => false],
        'PVRFSP' => ['label' => 'Reference support apres prelevement',                       'type' => 'CHAR',    'nullable' => false],
        'PVCTSU' => ['label' => 'Code type de support',                                      'type' => 'CHAR',    'nullable' => false],
        'PVNGEG' => ['label' => 'Numero du GEI apres prelevement',                           'type' => 'DECIMAL', 'nullable' => false],
        'PVNCOL' => ['label' => 'Numero de colis',                                           'type' => 'DECIMAL', 'nullable' => false],
        'PVCRCO' => ['label' => 'Critere reference colis',                                   'type' => 'CHAR',    'nullable' => false],
        'PVRCOL' => ['label' => 'Reference colis',                                           'type' => 'CHAR',    'nullable' => false],
        'PVCTCO' => ['label' => 'Code type de colis',                                        'type' => 'CHAR',    'nullable' => false],
        'PVCART' => ['label' => 'Code article',                                              'type' => 'CHAR',    'nullable' => false],
        'PVCVLA' => ['label' => 'Code variante logistique article',                          'type' => 'CHAR',    'nullable' => false],
        'PVCPRP' => ['label' => 'Code proprietaire',                                         'type' => 'CHAR',    'nullable' => false],
        'PVCQAL' => ['label' => 'Code qualite',                                              'type' => 'CHAR',    'nullable' => false],
        'PVQAP1' => ['label' => 'Quantite niveau 1 a prelever prelevement',                  'type' => 'DECIMAL', 'nullable' => false],
        'PVQAP2' => ['label' => 'Quantite niveau 2 a prelever prelevement',                  'type' => 'DECIMAL', 'nullable' => false],
        'PVQAP3' => ['label' => 'Quantite niveau 3 a prelever prelevement',                  'type' => 'DECIMAL', 'nullable' => false],
        'PVQAPB' => ['label' => 'Quantite en VL de base a prelever prelevement',             'type' => 'DECIMAL', 'nullable' => false],
        'PVPAPP' => ['label' => 'Poids net a prelever prelevement',                          'type' => 'DECIMAL', 'nullable' => false],
        'PVPBRA' => ['label' => 'Poids brut a prelever prelevement',                         'type' => 'DECIMAL', 'nullable' => false],
        'PVVORA' => ['label' => 'Volume a prelever prelevement',                             'type' => 'DECIMAL', 'nullable' => false],
        'PVTPRL' => ['label' => 'Temps prevu prelevement',                                   'type' => 'DECIMAL', 'nullable' => false],
        'PVQPR1' => ['label' => 'Quantite niveau 1 prelevee prelevement',                    'type' => 'DECIMAL', 'nullable' => false],
        'PVQPR2' => ['label' => 'Quantite niveau 2 prelevee prelevement',                    'type' => 'DECIMAL', 'nullable' => false],
        'PVQPR3' => ['label' => 'Quantite niveau 3 prelevee prelevement',                    'type' => 'DECIMAL', 'nullable' => false],
        'PVQPRB' => ['label' => 'Quantite en VL de base prelevee prelevement',               'type' => 'DECIMAL', 'nullable' => false],
        'PVPNPL' => ['label' => 'Poids net preleve prelevement',                             'type' => 'DECIMAL', 'nullable' => false],
        'PVPBPL' => ['label' => 'Poids brut preleve prelevement',                            'type' => 'DECIMAL', 'nullable' => false],
        'PVVOPL' => ['label' => 'Volume preleve prelevement',                                'type' => 'DECIMAL', 'nullable' => false],
        'PVSCHG' => ['label' => 'Date chargement - siecle - a creation prelevement',         'type' => 'DECIMAL', 'nullable' => false],
        'PVACHG' => ['label' => 'Date chargement - annee - a creation prelevement',          'type' => 'DECIMAL', 'nullable' => false],
        'CGMCHG' => ['label' => 'Date chargement - mois - a creation prelevement',           'type' => 'DECIMAL', 'nullable' => false],
        'PVJCHG' => ['label' => 'Date chargement - jour - a creation prelevement',           'type' => 'DECIMAL', 'nullable' => false],
        'PVCCHA' => ['label' => 'Code chargement - a creation prelevement',                  'type' => 'CHAR',    'nullable' => false],
        'PVSDCH' => ['label' => 'Date prevue chargement - siecle - a creation prel',         'type' => 'DECIMAL', 'nullable' => false],
        'PVADCH' => ['label' => 'Date prevue chargement - annee - a creation prel',          'type' => 'DECIMAL', 'nullable' => false],
        'PVMDCH' => ['label' => 'Date prevue chargement - mois - a creation prel',           'type' => 'DECIMAL', 'nullable' => false],
        'PVJDCH' => ['label' => 'Date prevue chargement - jour - a creation prel',           'type' => 'DECIMAL', 'nullable' => false],
        'PVHDCH' => ['label' => 'Heure prevue chargement - a creation prel',                 'type' => 'DECIMAL', 'nullable' => false],
        'PVCTRP' => ['label' => 'Code transporteur - a creation prelevement',                'type' => 'CHAR',    'nullable' => false],
        'PVCDES' => ['label' => 'Code destinataire preparation',                             'type' => 'CHAR',    'nullable' => false],
        'PVSQTR' => ['label' => 'Sequence de tri du service',                                'type' => 'CHAR',    'nullable' => false],
        'PVSVPR' => ['label' => 'Date validation prelevement - siecle',                      'type' => 'DECIMAL', 'nullable' => false],
        'PVAVPR' => ['label' => 'Date validation prelevement - annee',                       'type' => 'DECIMAL', 'nullable' => false],
        'PVMVPR' => ['label' => 'Date validation prelevement - mois',                        'type' => 'DECIMAL', 'nullable' => false],
        'PVJVPR' => ['label' => 'Date validation prelevement - jour',                        'type' => 'DECIMAL', 'nullable' => false],
        'PVHVPR' => ['label' => 'Heure validation prelevement',                              'type' => 'DECIMAL', 'nullable' => false],
        'PVCUVP' => ['label' => 'Code utilisateur validation prelevement',                   'type' => 'CHAR',    'nullable' => false],
        'PVTVPR' => ['label' => 'Top prelevement valide',                                    'type' => 'CHAR',    'nullable' => false],
        'PVTNPR' => ['label' => 'Top quantite non prelevee pour le prelevement',             'type' => 'CHAR',    'nullable' => false],
        'PVNOBJ' => ['label' => "Numero d'objet",                                            'type' => 'DECIMAL', 'nullable' => false],
        'PVSCRE' => ['label' => 'Date de creation - siecle',                                 'type' => 'DECIMAL', 'nullable' => false],
        'PVACRE' => ['label' => 'Date de creation - annee',                                  'type' => 'DECIMAL', 'nullable' => false],
        'PVMCRE' => ['label' => 'Date de creation - mois',                                   'type' => 'DECIMAL', 'nullable' => false],
        'PVJCRE' => ['label' => 'Date de creation - jour',                                   'type' => 'DECIMAL', 'nullable' => false],
        'PVHCRE' => ['label' => 'Heure de creation',                                         'type' => 'DECIMAL', 'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;

        $library = (string)($company['reflex_library'] ?? '');
        return $library !== '' ? $library : null;
    }
}

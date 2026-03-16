<?php
declare(strict_types=1);

namespace App\Reflex;

use App\Reflex\Dépot;
use App\Core\clFichier;

final class HLGEINP extends clFichier
{
    protected static string $table = 'HLGEINP';
    protected static array $primaryKey = ['GECACT', 'GENGEI'];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'GECACT' => ['label' => 'Code activite',                                              'type' => 'CHAR',    'nullable' => false],
        'GENGEI' => ['label' => 'Numero du GEI',                                              'type' => 'DECIMAL', 'nullable' => false],
        'GENIGE' => ['label' => 'Numero inverse du GEI',                                      'type' => 'DECIMAL', 'nullable' => false],
        'GECDPO' => ['label' => 'Code depot physique',                                         'type' => 'CHAR',    'nullable' => false],
        'GENSUP' => ['label' => 'Numero du support',                                           'type' => 'DECIMAL', 'nullable' => false],
        'GENCOL' => ['label' => 'Numero du colis',                                             'type' => 'DECIMAL', 'nullable' => false],
        'GECART' => ['label' => 'Code article',                                                'type' => 'CHAR',    'nullable' => false],
        'GECVLA' => ['label' => 'Code variante logistique article',                            'type' => 'CHAR',    'nullable' => false],
        'GECPRP' => ['label' => 'Code proprietaire',                                           'type' => 'CHAR',    'nullable' => false],
        'GECQAL' => ['label' => 'Code qualite',                                                'type' => 'CHAR',    'nullable' => false],
        'GECTST' => ['label' => 'Code type de stock',                                          'type' => 'CHAR',    'nullable' => false],
        'GEQGEI' => ['label' => 'Quantite du GEI en VL de base',                               'type' => 'DECIMAL', 'nullable' => false],
        'GEPNGE' => ['label' => 'Poids net du GEI',                                            'type' => 'DECIMAL', 'nullable' => false],
        'GEPBGE' => ['label' => 'Poids brut du GEI',                                           'type' => 'DECIMAL', 'nullable' => false],
        'GEPRGE' => ['label' => 'Prix du GEI',                                                 'type' => 'DECIMAL', 'nullable' => false],
        'GEMTGE' => ['label' => 'Montant du GEI',                                              'type' => 'DECIMAL', 'nullable' => false],
        'GECDES' => ['label' => 'Code destinataire reservation',                               'type' => 'CHAR',    'nullable' => false],
        'GECFDS' => ['label' => 'Code famille destinataire reservation',                       'type' => 'CHAR',    'nullable' => false],
        'GERRSO' => ['label' => 'Reference reservation odp',                                   'type' => 'CHAR',    'nullable' => false],
        'GECDOR' => ['label' => 'Code DO reference odp allotissement',                         'type' => 'CHAR',    'nullable' => false],
        'GERODP' => ['label' => 'Reference DO odp allotissement',                              'type' => 'CHAR',    'nullable' => false],
        'GETDJG' => ['label' => 'Top GEI deja prepare (allotissement)',                        'type' => 'CHAR',    'nullable' => false],
        'GECFOU' => ['label' => 'Code fournisseur',                                            'type' => 'CHAR',    'nullable' => false],
        'GECDPR' => ['label' => 'Code depot physique reception origine',                       'type' => 'CHAR',    'nullable' => false],
        'GENANN' => ['label' => "Numero d'annee reception origine",                            'type' => 'DECIMAL', 'nullable' => false],
        'GENREC' => ['label' => 'Numero de reception origine',                                 'type' => 'DECIMAL', 'nullable' => false],
        'GELOTF' => ['label' => 'Lot 1 du GEI',                                                'type' => 'CHAR',    'nullable' => false],
        'GELOT2' => ['label' => 'Lot 2 du GEI',                                                'type' => 'CHAR',    'nullable' => false],
        'GELOT3' => ['label' => 'Lot 3 du GEI',                                                'type' => 'CHAR',    'nullable' => false],
        'GESFAB' => ['label' => 'Date de fabrication du GEI - siecle',                         'type' => 'DECIMAL', 'nullable' => false],
        'GEAFAB' => ['label' => 'Date de fabrication du GEI - annee',                          'type' => 'DECIMAL', 'nullable' => false],
        'GEMFAB' => ['label' => 'Date de fabrication du GEI - mois',                           'type' => 'DECIMAL', 'nullable' => false],
        'GEJFAB' => ['label' => 'Date de fabrication du GEI - jour',                           'type' => 'DECIMAL', 'nullable' => false],
        'GEHFAB' => ['label' => 'Heure de fabrication du GEI',                                 'type' => 'DECIMAL', 'nullable' => false],
        'GESREG' => ['label' => 'Date de reception du GEI - siecle',                           'type' => 'DECIMAL', 'nullable' => false],
        'GEAREG' => ['label' => 'Date de reception du GEI - annee',                            'type' => 'DECIMAL', 'nullable' => false],
        'GEMREG' => ['label' => 'Date de reception du GEI - mois',                             'type' => 'DECIMAL', 'nullable' => false],
        'GEJREG' => ['label' => 'Date de reception du GEI - jour',                             'type' => 'DECIMAL', 'nullable' => false],
        'GEHERE' => ['label' => 'Heure de reception du GEI',                                   'type' => 'DECIMAL', 'nullable' => false],
        'GESDLU' => ['label' => "Date limite d'utilisation optimum du GEI - siecle",          'type' => 'DECIMAL', 'nullable' => false],
        'GEADLU' => ['label' => "Date limite d'utilisation optimum du GEI - annee",           'type' => 'DECIMAL', 'nullable' => false],
        'GEMDLU' => ['label' => "Date limite d'utilisation optimum du GEI - mois",            'type' => 'DECIMAL', 'nullable' => false],
        'GEJDLU' => ['label' => "Date limite d'utilisation optimum du GEI - jour",            'type' => 'DECIMAL', 'nullable' => false],
        'GESDLV' => ['label' => 'Date limite de vente du GEI - siecle',                        'type' => 'DECIMAL', 'nullable' => false],
        'GEADLV' => ['label' => 'Date limite de vente du GEI - annee',                         'type' => 'DECIMAL', 'nullable' => false],
        'GEMDLV' => ['label' => 'Date limite de vente du GEI - mois',                          'type' => 'DECIMAL', 'nullable' => false],
        'GEJDLV' => ['label' => 'Date limite de vente du GEI - jour',                          'type' => 'DECIMAL', 'nullable' => false],
        'GESDLC' => ['label' => 'Date limite de consommation du GEI - siecle',                 'type' => 'DECIMAL', 'nullable' => false],
        'GEADLC' => ['label' => 'Date limite de consommation du GEI - annee',                  'type' => 'DECIMAL', 'nullable' => false],
        'GEMDLC' => ['label' => 'Date limite de consommation du GEI - mois',                   'type' => 'DECIMAL', 'nullable' => false],
        'GEJDLC' => ['label' => 'Date limite de consommation du GEI - jour',                   'type' => 'DECIMAL', 'nullable' => false],
        'GESDOR' => ['label' => "Date d'ordonnancement du GEI - siecle",                       'type' => 'DECIMAL', 'nullable' => false],
        'GEADOR' => ['label' => "Date d'ordonnancement du GEI - annee",                        'type' => 'DECIMAL', 'nullable' => false],
        'GEMDOR' => ['label' => "Date d'ordonnancement du GEI - mois",                         'type' => 'DECIMAL', 'nullable' => false],
        'GEJDOR' => ['label' => "Date d'ordonnancement du GEI - jour",                         'type' => 'DECIMAL', 'nullable' => false],
        'GETGDI' => ['label' => 'Top GEI disponible pour preparation',                          'type' => 'CHAR',    'nullable' => false],
        'GETGBL' => ['label' => 'Top GEI bloque pour motif particulier',                        'type' => 'CHAR',    'nullable' => false],
        'GECMBG' => ['label' => 'Code motif de blocage pour les GEI',                           'type' => 'CHAR',    'nullable' => false],
        'GETBDO' => ['label' => 'Top GEI bloque "sous douane"',                                 'type' => 'CHAR',    'nullable' => false],
        'GESDOU' => ['label' => 'Date deblocage douane du GEI - siecle',                        'type' => 'DECIMAL', 'nullable' => false],
        'GEADOU' => ['label' => 'Date deblocage douane du GEI - annee',                         'type' => 'DECIMAL', 'nullable' => false],
        'GEMDOU' => ['label' => 'Date deblocage douane du GEI - mois',                          'type' => 'DECIMAL', 'nullable' => false],
        'GEJDOU' => ['label' => 'Date deblocage douane du GEI - jour',                          'type' => 'DECIMAL', 'nullable' => false],
        'GEHDOU' => ['label' => 'Heure deblocage douane du GEI',                                'type' => 'DECIMAL', 'nullable' => false],
        'GETBST' => ['label' => 'Top GEI bloque "stabilisation"',                               'type' => 'CHAR',    'nullable' => false],
        'GESSTA' => ['label' => 'Date deblocage stabilisation du GEI - siecle',                 'type' => 'DECIMAL', 'nullable' => false],
        'GEASTA' => ['label' => 'Date deblocage stabilisation du GEI - annee',                  'type' => 'DECIMAL', 'nullable' => false],
        'GEMSTA' => ['label' => 'Date deblocage stabilisation du GEI - mois',                   'type' => 'DECIMAL', 'nullable' => false],
        'GEJSTA' => ['label' => 'Date deblocage stabilisation du GEI - jour',                   'type' => 'DECIMAL', 'nullable' => false],
        'GEHSTA' => ['label' => 'Heure deblocage stabilisation du GEI',                         'type' => 'DECIMAL', 'nullable' => false],
        'GETBCT' => ['label' => 'Top GEI bloque "controle"',                                    'type' => 'CHAR',    'nullable' => false],
        'GETBRC' => ['label' => 'Top GEI bloque "reconditionnement"',                           'type' => 'CHAR',    'nullable' => false],
        'GETBEM' => ['label' => 'Top GEI bloque "emplacement bloque en sortie"',                'type' => 'CHAR',    'nullable' => false],
        'GETGIN' => ['label' => 'Top GEI en inventaire',                                        'type' => 'CHAR',    'nullable' => false],
        'GECDP1' => ['label' => 'Code depot physique reception',                                'type' => 'CHAR',    'nullable' => false],
        'GENANR' => ['label' => "Numero d'annee reception",                                     'type' => 'DECIMAL', 'nullable' => false],
        'GENUMR' => ['label' => 'Numero de reception',                                          'type' => 'DECIMAL', 'nullable' => false],
        'GENLIR' => ['label' => 'Numero de ligne reception',                                    'type' => 'DECIMAL', 'nullable' => false],
        'GENAPL' => ['label' => "Numero d'annee prelevement pour reprise",                     'type' => 'DECIMAL', 'nullable' => false],
        'GENPRL' => ['label' => 'Numero de prelevement pour reprise',                           'type' => 'DECIMAL', 'nullable' => false],
        'GENOBJ' => ['label' => "Numero d'objet",                                               'type' => 'DECIMAL', 'nullable' => false],
        'GENCOM' => ['label' => 'Numero de commentaire',                                        'type' => 'DECIMAL', 'nullable' => false],
        'GESCRE' => ['label' => 'Date de creation - siecle',                                    'type' => 'DECIMAL', 'nullable' => false],
        'GEACRE' => ['label' => 'Date de creation - annee',                                     'type' => 'DECIMAL', 'nullable' => false],
        'GEMCRE' => ['label' => 'Date de creation - mois',                                      'type' => 'DECIMAL', 'nullable' => false],
        'GEJCRE' => ['label' => 'Date de creation - jour',                                      'type' => 'DECIMAL', 'nullable' => false],
        'GEHCRE' => ['label' => 'Heure de creation',                                            'type' => 'DECIMAL', 'nullable' => false],
        'GECPCR' => ['label' => 'Code programme creation',                                      'type' => 'CHAR',    'nullable' => false],
    ];

   private static function libraryOf(string $CodeActivité): ?string
    {
        $company = Dépot::get($CodeActivité);
        if (!$company) return null;

        $library = (string)($company['library'] ?? '');
        return $library !== '' ? $library : null;
    }

    public static function readModels(\PDO $pdo, string $DPOPhysique, string $CodeActivité, string $CodeArticle, string $CodeConditionnement, string $Propriétaire, string $Qualité) : ? array
    {
        $library = self::libraryOf($CodeActivité);
        if (!$library) return null;
        return self::for($pdo,$library)
            ->whereEq("GECDPO",$DPOPhysique)
            ->whereEq("GECACT",$CodeActivité)
            ->whereEq("GECART",$CodeArticle)
            ->whereEq("GECVLA",$CodeConditionnement)
            ->whereEq("GECPRP",$Propriétaire)
            ->whereEq("GECQAL",$Qualité)
            ->whereEq("GECTST","200")
            ->whereEq("GETGDI","0")
            ->getModels();

    }
}

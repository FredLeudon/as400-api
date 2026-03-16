<?php
declare(strict_types=1);

namespace App\Reflex;

use App\Reflex\Dépot;
use App\Core\clFichier;

final class HLARVLP extends clFichier
{
    protected static string $table = 'HLARVLP';
    protected static array $primaryKey = ['VLCACT', 'VLCART', 'VLCVLA'];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'VLCACT' => ['label' => 'Code activite',                                                'type' => 'CHAR',    'nullable' => false],
        'VLCART' => ['label' => 'Code article',                                                 'type' => 'CHAR',    'nullable' => false],
        'VLCVLA' => ['label' => 'Code variante logistique article',                             'type' => 'CHAR',    'nullable' => false],
        'VLMVLA' => ['label' => 'Mot directeur variante logistique article',                    'type' => 'CHAR',    'nullable' => false],
        'VLUVLA' => ['label' => "Code d'usage variante logistique article",                     'type' => 'CHAR',    'nullable' => false],
        'VLCTVL' => ['label' => 'Code type de variante logistique',                             'type' => 'CHAR',    'nullable' => false],
        'VLTVLB' => ['label' => "Top VL de base de l'article",                                  'type' => 'CHAR',    'nullable' => false],
        'VLTVLC' => ['label' => "Top VL conditionnement de l'article",                          'type' => 'CHAR',    'nullable' => false],
        'VLTVLG' => ['label' => 'Top VL de gestion de la VL de conditionnement',                'type' => 'CHAR',    'nullable' => false],
        'VLTVLK' => ['label' => 'Top VL kit',                                                   'type' => 'CHAR',    'nullable' => false],
        'VLCVLS' => ['label' => 'Code VL sous-conditionnement filiere courte',                  'type' => 'CHAR',    'nullable' => false],
        'VLQSCD' => ['label' => 'Quantite en VL de sous-condit. filiere courte',                'type' => 'DECIMAL', 'nullable' => false],
        'VLCVL2' => ['label' => 'Code VL sous-conditionnement filiere longue',                  'type' => 'CHAR',    'nullable' => false],
        'VLQVL2' => ['label' => 'Quantite en VL de sous-condit. filiere longue',                'type' => 'DECIMAL', 'nullable' => false],
        'VLQVLB' => ['label' => 'Quantite en VL de base',                                       'type' => 'DECIMAL', 'nullable' => false],
        'VLNUUS' => ['label' => 'Numerateur conversion VL de base en unite standard',           'type' => 'DECIMAL', 'nullable' => false],
        'VLDEUS' => ['label' => 'Denominateur conversion VL de base en unite std',              'type' => 'DECIMAL', 'nullable' => false],
        'VLRCDV' => ['label' => 'Reference de commande de la VL',                               'type' => 'CHAR',    'nullable' => false],
        'VLPNVL' => ['label' => 'Poids net de la variante logistique article',                  'type' => 'DECIMAL', 'nullable' => false],
        'VLPBVL' => ['label' => 'Poids brut de la variante logistique article',                 'type' => 'DECIMAL', 'nullable' => false],
        'VLHTVL' => ['label' => 'Hauteur de la variante logistique article',                    'type' => 'DECIMAL', 'nullable' => false],
        'VLLAVL' => ['label' => 'Largeur de la variante logistique article',                    'type' => 'DECIMAL', 'nullable' => false],
        'VLPRVL' => ['label' => 'Profondeur de la variante logistique article',                 'type' => 'DECIMAL', 'nullable' => false],
        'VLVOVL' => ['label' => 'Volume de la variante logistique article',                     'type' => 'DECIMAL', 'nullable' => false],
        'VLPSVL' => ['label' => 'Prix standard VL',                                             'type' => 'DECIMAL', 'nullable' => false],
        'VLNSAV' => ['label' => 'Affichage VL : numero de sequence',                            'type' => 'DECIMAL', 'nullable' => false],
        'VLDAVL' => ['label' => 'Affichage VL : decalage (1 2 3)',                              'type' => 'CHAR',    'nullable' => false],
        'VLTCTC' => ['label' => 'Top controle systematique a reception',                        'type' => 'CHAR',    'nullable' => false],
        'VLTREC' => ['label' => 'Top reconditionnement systematique a reception',               'type' => 'CHAR',    'nullable' => false],
        'VLCTSU' => ['label' => 'Code type de support',                                         'type' => 'CHAR',    'nullable' => false],
        'VLCTAI' => ['label' => 'Code taille emplacement',                                      'type' => 'CHAR',    'nullable' => false],
        'VLNBCD' => ['label' => 'Stockage standard : nombre de conditionnements',               'type' => 'DECIMAL', 'nullable' => false],
        'VLTASA' => ['label' => 'Top association automatique supports',                         'type' => 'CHAR',    'nullable' => false],
        'VLCFAS' => ['label' => 'Code famille de stockage',                                     'type' => 'CHAR',    'nullable' => false],
        'VLCFMA' => ['label' => 'Code famille de stockage masse',                               'type' => 'CHAR',    'nullable' => false],
        'VLVRAC' => ['label' => 'Top VL vrac',                                                  'type' => 'CHAR',    'nullable' => false],
        'VLNBSC' => ['label' => 'Nombre de VL sscd (filiere courte) dans une couche',          'type' => 'DECIMAL', 'nullable' => false],
        'VLHACH' => ['label' => "Hauteur d'une couche",                                         'type' => 'DECIMAL', 'nullable' => false],
        'VLCFPR' => ['label' => 'Code famille de preparation',                                  'type' => 'CHAR',    'nullable' => false],
        'VLSDSC' => ['label' => 'Date de debut de service conditionnement - siecle',            'type' => 'DECIMAL', 'nullable' => false],
        'VLADSC' => ['label' => 'Date de debut de service conditionnement - annee',             'type' => 'DECIMAL', 'nullable' => false],
        'VLMDSC' => ['label' => 'Date de debut de service conditionnement - mois',              'type' => 'DECIMAL', 'nullable' => false],
        'VLJDSC' => ['label' => 'Date de debut de service conditionnement - jour',              'type' => 'DECIMAL', 'nullable' => false],
        'VLSFSC' => ['label' => 'Date de fin de service conditionnement - siecle',              'type' => 'DECIMAL', 'nullable' => false],
        'VLAFSC' => ['label' => 'Date de fin de service conditionnement - annee',               'type' => 'DECIMAL', 'nullable' => false],
        'VLMFSC' => ['label' => 'Date de fin de service conditionnement - mois',                'type' => 'DECIMAL', 'nullable' => false],
        'VLJFSC' => ['label' => 'Date de fin de service conditionnement - jour',                'type' => 'DECIMAL', 'nullable' => false],
        'VLNOBJ' => ['label' => "Numero d'objet",                                               'type' => 'DECIMAL', 'nullable' => false],
        'VLNCOM' => ['label' => 'Numero de commentaire',                                        'type' => 'DECIMAL', 'nullable' => false],
        'VLSCRE' => ['label' => 'Date de creation - siecle',                                    'type' => 'DECIMAL', 'nullable' => false],
        'VLACRE' => ['label' => 'Date de creation - annee',                                     'type' => 'DECIMAL', 'nullable' => false],
        'VLMCRE' => ['label' => 'Date de creation - mois',                                      'type' => 'DECIMAL', 'nullable' => false],
        'VLJCRE' => ['label' => 'Date de creation - jour',                                      'type' => 'DECIMAL', 'nullable' => false],
        'VLHCRE' => ['label' => 'Heure de creation',                                            'type' => 'DECIMAL', 'nullable' => false],
        'VLCUCR' => ['label' => 'Code utilisateur creation',                                    'type' => 'CHAR',    'nullable' => false],
        'VLSMAJ' => ['label' => 'Date de derniere mise a jour - siecle',                        'type' => 'DECIMAL', 'nullable' => false],
        'VLAMAJ' => ['label' => 'Date de derniere mise a jour - annee',                         'type' => 'DECIMAL', 'nullable' => false],
        'VLMMAJ' => ['label' => 'Date de derniere mise a jour - mois',                          'type' => 'DECIMAL', 'nullable' => false],
        'VLJMAJ' => ['label' => 'Date de derniere mise a jour - jour',                          'type' => 'DECIMAL', 'nullable' => false],
        'VLHMAJ' => ['label' => 'Heure de derniere mise a jour',                                'type' => 'DECIMAL', 'nullable' => false],
        'VLCUMJ' => ['label' => 'Code utilisateur derniere mise a jour',                        'type' => 'CHAR',    'nullable' => false],
        'VLTOPD' => ['label' => 'Top desactivation',                                            'type' => 'CHAR',    'nullable' => false],
    ];

    private static function libraryOf(string $CodeActivité): ?string
    {
        $company = Dépot::get($CodeActivité);
        if (!$company) return null;

        $library = (string)($company['library'] ?? '');
        return $library !== '' ? $library : null;
    }
}

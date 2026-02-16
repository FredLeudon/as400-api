<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

/**
 * FCMBI.B3CLIENT - customer master.
 *
 * Unique key: [B3CLI, B3SOC]
 */
final class B3CLIENT extends clFichier
{
    protected static string $table = 'B3CLIENT';
    protected static array $primaryKey = ['B3CLI', 'B3SOC'];
    /** @var array<int,array<int,string>> */
    protected static array $uniqueKeys = [
        ['B3CLI', 'B3SOC'], // B3L4
    ];
    /** @var array<string,array<int,string>> */
    protected static array $indexes = [
        'B3C0L0' => ['B3CLI'],
        'B3L0'   => ['B3CLI'],
        'B3L1'   => ['B3RAIS'],
        'B3L10'  => ['B3CMER', 'B3CPF', 'B3CLI'],
        'B3L12'  => ['B3SOC', 'B3PAYF', 'B3CLI'],
        // Source index uses B3DEP; table column is B3DEPP.
        'B3L13'  => ['B3SOC', 'B3DEPP', 'B3CLI'],
        'B3L14'  => ['B3SOC', 'B3CMER', 'B3CLI'],
        'B3L15'  => ['B3CPF', 'B3CLI'],
        'B3L16'  => ['B3SIRT', 'B3SOC'],
        'B3L2'   => ['B3SIGL'],
        // Source index uses B3DEP; table column is B3DEPP.
        'B3L20'  => ['B3SOC', 'B3ZECO', 'B3DEPP', 'B3CLI'],
        'B3L21'  => ['B3SSCP', 'B3CLI'],
        'B3L3'   => ['B3ANCN'],
        // Kept from AS/400 description (field not listed in B3CLIENT columns).
        'B3L32'  => ['B3SICO'],
        'B3L34'  => ['B3PAYF', 'B3CLI'],
        'B3L4'   => ['B3CLI', 'B3SOC'],
        'B3L5'   => ['B3SOC', 'B3CLI'],
        'B3L6'   => ['B3SOC', 'B3ANCN'],
        'B3L65'  => ['B3INPC'],
        'B3L7'   => ['B3SECR', 'B3CLI'],
        'B3L70'  => ['B3SECR', 'B3GRP'],
        'B3L8'   => ['B3CLI'],
        'B3L9'   => ['B3SOC', 'B3GRP', 'B3CLI'],
        'B5L6'   => ['B3CLI'],
        // Logical references from description.
        'C2L999' => ['C2LANG'],
        'D6L999' => ['D6COND'],
        'G0L999' => ['G0PAY', 'G0PAY1'],
        'H3L999' => ['H3SECR'],
        'N5L999' => ['N5CMER'],
    ];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'B3CLI'  => ['label' => 'Code client',                                 'type' => 'CHAR',    'nullable' => false],
        'B3TARI' => ['label' => 'Code tarif utilise',                           'type' => 'CHAR',    'nullable' => false],
        'B3RAL'  => ['label' => 'Tag ral gere',                                 'type' => 'CHAR',    'nullable' => false],
        'B3DUPL' => ['label' => 'Nombre de duplicatas a editer',                'type' => 'DECIMAL', 'nullable' => false],
        'B3ESCP' => ['label' => 'Pourcentage escompte',                         'type' => 'DECIMAL', 'nullable' => false],
        'B3REM1' => ['label' => 'Remise principale',                            'type' => 'DECIMAL', 'nullable' => false],
        'B3REM2' => ['label' => 'Remise complementaire',                        'type' => 'DECIMAL', 'nullable' => false],
        'B3ACTI' => ['label' => 'Code activite client',                         'type' => 'CHAR',    'nullable' => false],
        'B3ZECO' => ['label' => 'Zone economique',                              'type' => 'DECIMAL', 'nullable' => false],
        'B3DEPP' => ['label' => 'Departement ou pays',                          'type' => 'DECIMAL', 'nullable' => false],
        'B3GRP'  => ['label' => 'Code groupement',                              'type' => 'CHAR',    'nullable' => false],
        'B3TELE' => ['label' => 'Telephone',                                    'type' => 'CHAR',    'nullable' => false],
        'B3TELX' => ['label' => 'Telex',                                        'type' => 'CHAR',    'nullable' => false],
        'B3FAX'  => ['label' => 'Fax',                                          'type' => 'CHAR',    'nullable' => false],
        'B3DMAJ' => ['label' => 'Derniere date de mise a jour',                 'type' => 'NUMERIC', 'nullable' => false],
        'B3RAIS' => ['label' => 'Raison sociale de facturation',                'type' => 'CHAR',    'nullable' => false],
        'B3SIGL' => ['label' => 'Sigle ou nom abrege',                          'type' => 'CHAR',    'nullable' => false],
        'B3ADF1' => ['label' => 'Adresse facturation ligne 1',                  'type' => 'CHAR',    'nullable' => false],
        'B3ADF2' => ['label' => 'Adresse facturation ligne 2',                  'type' => 'CHAR',    'nullable' => false],
        'B3ADF3' => ['label' => 'Adresse facturation ligne 3',                  'type' => 'CHAR',    'nullable' => false],
        'B3CPF'  => ['label' => 'Code postal facturation',                      'type' => 'CHAR',    'nullable' => false],
        'B3VILF' => ['label' => 'Ville facturation',                            'type' => 'CHAR',    'nullable' => false],
        'B3PAYF' => ['label' => 'Code pays facturation',                        'type' => 'CHAR',    'nullable' => false],
        'B3RAIL' => ['label' => 'Raison sociale livraison',                     'type' => 'CHAR',    'nullable' => false],
        'B3ADL1' => ['label' => 'Adresse livraison ligne 1',                    'type' => 'CHAR',    'nullable' => false],
        'B3ADL2' => ['label' => 'Adresse livraison ligne 2',                    'type' => 'CHAR',    'nullable' => false],
        'B3ADL3' => ['label' => 'Adresse livraison ligne 3',                    'type' => 'CHAR',    'nullable' => false],
        'B3CPL'  => ['label' => 'Code postal livraison',                        'type' => 'CHAR',    'nullable' => false],
        'B3VILL' => ['label' => 'Ville livraison',                              'type' => 'CHAR',    'nullable' => false],
        'B3PAYL' => ['label' => 'Code pays livraison',                          'type' => 'CHAR',    'nullable' => false],
        'B3REGL' => ['label' => 'Code de reglement',                            'type' => 'CHAR',    'nullable' => false],
        'B3BQE'  => ['label' => 'Nom banque',                                   'type' => 'CHAR',    'nullable' => false],
        'B3CPB'  => ['label' => 'Code postal banque',                           'type' => 'DECIMAL', 'nullable' => false],
        'B3VIBQ' => ['label' => 'Ville banque',                                 'type' => 'CHAR',    'nullable' => false],
        'B3RIB1' => ['label' => 'Code banque',                                  'type' => 'NUMERIC', 'nullable' => false],
        'B3RIB2' => ['label' => 'Code guichet',                                 'type' => 'NUMERIC', 'nullable' => false],
        'B3RIB3' => ['label' => 'Numero de compte',                             'type' => 'CHAR',    'nullable' => false],
        'B3RIB4' => ['label' => 'Cle RIB',                                      'type' => 'NUMERIC', 'nullable' => false],
        'B3EXP'  => ['label' => 'Mode expedition',                              'type' => 'CHAR',    'nullable' => false],
        'B3CREA' => ['label' => 'Credit autorise en cours',                     'type' => 'DECIMAL', 'nullable' => false],
        'B3CA'   => ['label' => 'Chiffre affaire en cours',                     'type' => 'DECIMAL', 'nullable' => false],
        'B3AGEN' => ['label' => 'Gestion agenda contact O/N',                   'type' => 'CHAR',    'nullable' => false],
        'B3CMER' => ['label' => 'Attache commercial',                           'type' => 'CHAR',    'nullable' => false],
        'B3SOC'  => ['label' => 'Numero societe',                               'type' => 'CHAR',    'nullable' => false],
        'B3INPC' => ['label' => 'Indice P ou C',                                'type' => 'CHAR',    'nullable' => false],
        'B3CLAN' => ['label' => 'Code langue libelles article',                 'type' => 'CHAR',    'nullable' => false],
        'B3SLIB' => ['label' => 'Substitution libelle article O/N',             'type' => 'CHAR',    'nullable' => false],
        'B3TVAE' => ['label' => 'Code TVA europeen',                            'type' => 'CHAR',    'nullable' => false],
        'B3PRIN' => ['label' => 'Gestion prix net O/N',                         'type' => 'CHAR',    'nullable' => false],
        'B3CACL' => ['label' => 'Chiffre affaire entreprise KF',                'type' => 'DECIMAL', 'nullable' => false],
        'B3FRVT' => ['label' => 'Force de vente',                               'type' => 'DECIMAL', 'nullable' => false],
        'B3SECR' => ['label' => 'Code assistante client',                       'type' => 'CHAR',    'nullable' => false],
        'B3CRAP' => ['label' => 'Credit autorise precedent',                    'type' => 'DECIMAL', 'nullable' => false],
        'B3DCDE' => ['label' => 'Derniere date commande',                       'type' => 'NUMERIC', 'nullable' => false],
        'B3CTRP' => ['label' => 'Condition de transport',                       'type' => 'CHAR',    'nullable' => false],
        'B3MFRC' => ['label' => 'Minimum franco',                               'type' => 'DECIMAL', 'nullable' => false],
        'B3SEUF' => ['label' => 'Indice condition transport',                   'type' => 'CHAR',    'nullable' => false],
        'B3CEXP' => ['label' => 'Cote expert SRCL',                             'type' => 'CHAR',    'nullable' => false],
        'B3DCOT' => ['label' => 'Date cotation expert',                         'type' => 'NUMERIC', 'nullable' => false],
        'B3TVAA' => ['label' => 'Assujeti a la TVA O/N',                        'type' => 'CHAR',    'nullable' => false],
        'B3VIST' => ['label' => 'Nombre de visites annee',                      'type' => 'DECIMAL', 'nullable' => false],
        'B3DVIS' => ['label' => 'Derniere visite MM/AAAA',                      'type' => 'NUMERIC', 'nullable' => false],
        'B3ANCN' => ['label' => 'Ancien numero de code',                        'type' => 'DECIMAL', 'nullable' => false],
        'B3DOC'  => ['label' => 'Envoi documentation O/N',                      'type' => 'CHAR',    'nullable' => false],
        'B3GRFT' => ['label' => 'Groupement factures O/N',                      'type' => 'CHAR',    'nullable' => false],
        'B3SIRT' => ['label' => 'SIRET',                                        'type' => 'DECIMAL', 'nullable' => false],
        'B3MODT' => ['label' => 'Mode de transport',                            'type' => 'CHAR',    'nullable' => false],
        'B3CENT' => ['label' => 'Code centralisation 1 a 5',                    'type' => 'NUMERIC', 'nullable' => false],
        'B3TYPS' => ['label' => 'Type support envoi tarif',                     'type' => 'CHAR',    'nullable' => false],
        'B3TRTA' => ['label' => 'Traite acceptee O/N',                          'type' => 'CHAR',    'nullable' => false],
        'B3TRSI' => ['label' => 'Code transitaire',                             'type' => 'CHAR',    'nullable' => false],
        'B3SSCP' => ['label' => 'Sous compte comptable',                        'type' => 'CHAR',    'nullable' => false],
        'B3CMTR' => ['label' => 'Commentaire',                                  'type' => 'CHAR',    'nullable' => false],
        'B3BLOQ' => ['label' => 'Bloque a expedition O/N',                      'type' => 'CHAR',    'nullable' => false],
        'B3EXPO' => ['label' => 'Facturation export O/N',                       'type' => 'CHAR',    'nullable' => false],
        'B3DNS'  => ['label' => 'Gestion DNS O/N',                              'type' => 'CHAR',    'nullable' => false],
        'B3CBAR' => ['label' => 'Edition codes barres O/N',                     'type' => 'CHAR',    'nullable' => false],
        'B3FJOI' => ['label' => 'Facture jointe colis O/N',                     'type' => 'CHAR',    'nullable' => false],
        'B3FIL1' => ['label' => 'Libre 1',                                      'type' => 'CHAR',    'nullable' => false],
        'B3FIL2' => ['label' => 'Libre 2',                                      'type' => 'NUMERIC', 'nullable' => false],
        'B3FIL3' => ['label' => 'Libre 3',                                      'type' => 'CHAR',    'nullable' => false],
        'B3FIL4' => ['label' => 'Libre 4',                                      'type' => 'DECIMAL', 'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;

        // Same library selection strategy as Customers::getCustomer().
        if (!empty($company['mbi'])) {
            $library = (string)($company['common_library'] ?? '');
        } else {
            $library = (string)($company['library'] ?? '');
        }

        if ($library === '') {
            $library = (string)($company['common_library'] ?? '');
        }

        return $library !== '' ? $library : null;
    }

    public static function getNombreParCodeTarif(PDO $pdo, string $companyCode, string $codeTarif): ? int
    {
        try {
            $library = self::libraryOf($companyCode);
            if ($library === null) return null;
            $qb = self::for($pdo, $library)
                ->select(["B3TARI","#count(*) as nombre"])
                ->whereEq('B3TARI', $codeTarif)
                ->groupBy("B3TARI");            
            $row = $qb->first();
            if($row) return (int)$row['NOMBRE'];            
            return null;

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * Get one customer row by code, optionally constrained by society code.
     *
     * @return array<string,mixed> Empty array if not found.
     */
    public static function getById(PDO $pdo, string $companyCode, string $customerCode, ?string $societyCode = null): array
    {
        try {
            $library = self::libraryOf($companyCode);
            if ($library === null) return [];

            $customerCode = trim($customerCode);
            if ($customerCode === '') return [];

            $qb = self::for($pdo, $library)->whereEq('B3CLI', $customerCode);
            if ($societyCode !== null && trim($societyCode) !== '') {
                $qb->whereEq('B3SOC', trim($societyCode));
            }

            $row = $qb->first();
            return is_array($row) ? $row : [];

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * Get one customer model by code, optionally constrained by society code.
     */
    public static function getModelById(PDO $pdo, string $companyCode, string $customerCode, ?string $societyCode = null): ?static
    {
        try {
            $library = self::libraryOf($companyCode);
            if ($library === null) return null;

            $customerCode = trim($customerCode);
            if ($customerCode === '') return null;

            $qb = self::for($pdo, $library)->whereEq('B3CLI', $customerCode);
            if ($societyCode !== null && trim($societyCode) !== '') {
                $qb->whereEq('B3SOC', trim($societyCode));
            }

            return $qb->firstModel();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

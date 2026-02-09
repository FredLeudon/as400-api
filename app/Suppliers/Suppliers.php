<?php
declare(strict_types=1);

namespace App\Suppliers;

use PDO;
use Throwable;
use DateTimeImmutable;

use App\Core\Http;
use App\Domain\Company;

final class Suppliers
{
    
    // ============================================= Publics Methods =============================================   
    public static function confirmSupplierOrder(PDO $pdo, string $companyCode, int $orderId, bool $confirmed, ?DateTimeImmutable $confirmedDate = null): bool
    {
        try {
            $company = Company::get($companyCode);
            if (!$company) return false;

            $sql = "UPDATE {$company['library']}.E6CDEFOC
                    SET E6CONF = :confirmed,
                        E6DATC = :confirmed_date
                    WHERE E6NCDE = :order";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':confirmed', $confirmed ? 'O' : 'N', PDO::PARAM_STR);
            $stmt->bindValue(':confirmed_date', $confirmedDate ? (int)$confirmedDate->format('dmY') : 0, PDO::PARAM_INT);
            $stmt->bindValue(':order', $orderId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    public static function confirmSupplierOrderProductDelay(PDO $pdo, string $companyCode, int $orderId, string $productId, ?int $delay = 0): bool
    {
        try {
            $company = Company::get($companyCode);
            if (!$company) return false;

            $sql = "UPDATE {$company['library']}.A8CDEFOU
                    SET A8DELD = :delay
                    WHERE A8NCDE = :order
                      AND A8ART = :product";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':delay', (int)$delay, PDO::PARAM_INT);
            $stmt->bindValue(':order', $orderId, PDO::PARAM_INT);
            $stmt->bindValue(':product', $productId, PDO::PARAM_STR);
            $stmt->execute();

            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
/*
    public static function get(PDO $pdo, 
                                string $companyCode, 
                                string $supplierId, 
                                bool $withBillingAddress = false,
                                bool $withOrderAddress = false,
                                bool $withAdditionalInformation = false
    ): ?array
    {
        try {
            $supplier =[];
            $company = Company::get($companyCode);
            if (!$company) return null;
            $sql = "SELECT *
                    FROM {$company['library']}.A6FOURN
                    WHERE A6FOUR = :supplierID";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':supplierID', $supplierId, PDO::PARAM_STR);
            $stmt->execute();            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }
            W1REFBAN::get($pdo, $companyCode, $row['A6FOUR']);
            U3FOURN::get($pdo, $companyCode, $row['A6FOUR']);
            FSFOUSOC::get($pdo, $companyCode, $row['A6FOUR']);
            C2LANGUE::getById($pdo, $companyCode, $row['A6CLGU']);
            H7ZONECO::get($pdo, $companyCode, $row['A6ZECO']);
            B8ACTFOU::get($pdo, $companyCode, $row['A6ACTI']);
            D6CONTRN::get($pdo, $companyCode, $row['A6MODT']);
            H6TRANSP::get($pdo, $companyCode, $row['A6TRAN']);
            C7REGLEM::get($pdo, $companyCode, $row['A6CRGL']);
            B6DEVISE::get($pdo, $companyCode, $row['A6DEVI']);

            $supplier['origine'] = ($company['code'] === Company::FLO_VENDING) ? 'flovending' : 'mbi';
            $supplier['code'] = $row['A6FOUR'];
            $supplier['statut'] = $row['A6INAS'];
            $supplier['main_company'] = $row['A6SOC'];
            $supplier['name'] = $row['A6RAIS'];
            if ($withBillingAddress) {
                // Adresse facturation                
                $supplier['billing']['address1'] = $row['A6ADF1'];
                $supplier['billing']['address2'] = $row['A6ADF2'];
                $supplier['billing']['address3'] = $row['A6ADF3'];
                $supplier['billing']['zip'] = $row['A6CPF'];
                $supplier['billing']['city'] = $row['A6VILF'];
                $supplier['billing']['country_code'] = $row['A6PAYF'];
                $supplier['billing']['country'] = G0ISO::get($pdo, $companyCode, $row['A6PAYF'])['country'] ?? null;           
            }
            if ($withAdditionalInformation) {
                $supplier['siret'] = substr((string)$row['A6FIL3'], 0, 14);
            }
            // Informations commande
            if ($withOrderAddress) {
                $supplier['order']['name'] = $row['A6RS'];
                $supplier['order']['address1'] = $row['A6ADR1'];
                $supplier['order']['address2'] = $row['A6ADR2'];
                $supplier['order']['address3'] = $row['A6VIBQ'];
                $supplier['order']['zip'] = $row['A6CPOS'];
                $supplier['order']['city'] = $row['A6VILL'];
                $supplier['order']['country_code'] = $row['A6CPAY'];
                $supplier['order']['country'] = G0ISO::get($pdo, $companyCode, $row['A6CPAY'])['country'] ?? null;       
            }
            if ($withAdditionalInformation) {
                $supplier['order']['chiffre_affaire_en_cours'] = $row['A6CAPR'];
                $supplier['order']['commentaire'] = U3FOURN::get($pdo, $companyCode, $row['A6FOUR']['U3LIB']) ?? '';
                $supplier['billing']['conditions_transport']['code'] = $row['A6TRAN'];
                $supplier['billing']['conditions_transport']['libelle'] = H6TRANSP::get($pdo, $companyCode, $row['A6TRAN']);
                $supplier['billing']['mode_transport']['code'] = $row['A6MODT'];
                $supplier['billing']['mode_transport']['libelle'] = D6CONTRN::get($pdo, $companyCode, $row['A6MODT']);
                $supplier['billing']['franco']['minimum'] = $row['A6MFRC'];
                $supplier['billing']['franco']['en_valeur'] = $row['A6MIQV'];
                $supplier['billing']['reglement']['code'] = $row['A6CRGL'];
                $supplier['billing']['reglement']['libelle'] = C7REGLEM::get($pdo, $companyCode, $row['A6CRGL']);
                $supplier['billing']['devise']['code'] = $row['A6DEVI'];
                $supplier['billing']['devise']['libelle'] = B6DEVISE::get($pdo, $companyCode, $row['A6DEVI']);
                $supplier['billing']['devise']['taux'] = $row['A6TAU'];
                $supplier['billing']['code_tva_europeen'] = $row['A6TVA'];
                $supplier['billing']['cote_expert'] = $row['A6CEXP'];
            }
            if ($withOrderAddress) {
                $supplier['repertoire']['telephone'] = $row['A6TEL'];
                $supplier['repertoire']['fax'] = $row['A6FAX'];
            }
            if ($withBillingAddress) {
                $supplier['banque']['nom'] = $row['A6BQE'];
                $bank = W1REFBAN::get($pdo, $companyCode, $row['A6FOUR']);
                if ($bank) {
                    $supplier['banque']['adresse']['l1'] = $bank['W1ADR1'];
                    $supplier['banque']['adresse']['l2'] = $bank['W1ADR2'];
                    $supplier['banque']['adresse']['code_postal'] = $row['A6CPB'];
                    $supplier['banque']['rib']['bic'] = $bank['W1BIC'];
                    $supplier['banque']['rib']['iban'] = $bank['    W1IBAN'];
                } else {
                    $supplier['banque']['adresse']['l1'] = '';
                    $supplier['banque']['adresse']['l2'] = '';              
                    $supplier['banque']['adresse']['code_postal'] = $row['A6CPB'];
                    $supplier['banque']['rib']['bic'] = '';
                    $supplier['banque']['rib']['iban'] = '';
                }
                $supplier['banque']['rib']['banque'] = $row['A6RIB1'];
                $supplier['banque']['rib']['guichet'] = $row['A6RIB2'];
                $supplier['banque']['rib']['compte'] = $row['A6RIB3'];
                $supplier['banque']['rib']['cle'] = $row['A6RIB4'];
            }
            if ($withBillingAddress) {
                $supplier['comptabilite']['compte'] = $row['A6ADR3'];
                $supplier['comptabilite']['code_centralisation'] = $row['A6CENT'];
                $bank = W1REFBAN::get($pdo, $companyCode, $row['A6FOUR']);
                if ($bank) {
                    $supplier['comptabilite']['compte_charge'] = $bank['W1CHAR'];
                    $supplier['comptabilite']['compte_tva_collectee'] = $bank['W1TVA'];
                    switch ($bank['W1EMBE']) {
                        case 0:
                            $supplier['comptabilite']['beneficiaire'] = true;
                            $supplier['comptabilite']['emmeteur'] = false;
                            break;
                        case 1:
                            $supplier['comptabilite']['beneficiaire'] = true;
                            $supplier['comptabilite']['emmeteur'] = true;
                            break;
                        case 2:
                            $supplier['comptabilite']['beneficiaire'] = false;
                            $supplier['comptabilite']['emmeteur'] = true;
                            break;                  
                    }
                } else {            
                    $supplier['comptabilite']['compte_charge'] = '';
                    $supplier['comptabilite']['compte_tva_collectee'] = '';
                    $supplier['comptabilite']['beneficiaire'] = false;
                    $supplier['comptabilite']['emmeteur'] = false;
                }
            }
            if ($row['A6REPF'] === 'O') {
                $supplier['gestion_des_contacts'] = true;
            } else {
                $supplier['gestion_des_contacts'] = false;
            }
            $supplier['contacts'] = ContactsFournisseurs::toJsonArray($pdo, $companyCode, $row['A6FOUR']);
            if ($withAdditionalInformation) {
                if ($company['code'] !== Company::FLO_VENDING) {
                    $supplier['bourgeat']['baan_code'] = substr((string)$row['A6FIL3'], 14, 6);
                    $supplier['last_action']['horodatage'] = $row['A6HOACT'];
                    $supplier['last_action']['profil'] = $row['A6UTIL'];
                    $supplier['last_action']['acttion'] = $row['A6ACT'];
                    $supplier['last_action']['programme'] = $row['A6PGM'];
                }
            }
            
/*
SI combinaisonRetour[articles] ALORS
	jsFournisseur.articles = []
	sSQL est une chaîne = [
		With adartdep As (
		Select adart,
		addep
		From matis.adartdep
		Where adprin = '*'
		),
		asartsoc as (
		select distinct asart, assusp from matis.asartsoc where assoc in ('06' , '38', '40') and assusp in ( %1 )
		),
		d7rfarfo As (
		Select d7soc,
		d7art,
		d7four
		From matis.d7rfarfo
		Where d7four Not In (Select fifour
		From matis.fifouint) and d7four = '%2'
		),
		c0libart As (
		Select c0art,
		c0lib
		From matis.c0libart
		Where c0soc = '' And
		c0lang = 'FRA'
		)
		Select d7four,
		Json_Object(
		'code' Value trim(adart), 
		'statut' value trim(assusp),
		'libelle' Value trim(c0lib)
		) As jsarticles
		From adartdep
		inner join asartsoc on asart = adart
		Inner Join d7rfarfo
		On d7art = adart And
		d7soc = addep
		Left Outer Join c0libart
		On c0art = adart
		Group By d7four, adart, assusp, c0lib
	]
	sdFournisseurs_Articles	est une Source de Données
	sStatuts				est une chaîne	= ""
	SI (combinaisonRetour[statut_article_N] OU combinaisonRetour[statut_article_T] OU combinaisonRetour[statut_article_C] OU combinaisonRetour[statut_article_S] OU combinaisonRetour[statut_article_A] ) ALORS
		SI combinaisonRetour[statut_article_N] ALORS sStatuts+= [ ", "] + "'N'"
		SI combinaisonRetour[statut_article_T] ALORS sStatuts+= [ ", "] + "'T'"
		SI combinaisonRetour[statut_article_C] ALORS sStatuts+= [ ", "] + "'C'"
		SI combinaisonRetour[statut_article_S] ALORS sStatuts+= [ ", "] + "'S'"
		SI combinaisonRetour[statut_article_A] ALORS sStatuts+= [ ", "] + "'A'"
	SINON
		sStatuts = "'N', 'T', 'S'"
	FIN
	nindice est un entier = 0
	SI HExécuteRequêteSQL(sdFournisseurs_Articles,::mg_pclConnexion:Connexion,hRequêteSansCorrection, ChaîneConstruit(sSQL,sStatuts,a6fourn:a6four)) ALORS
		POUR TOUT sdFournisseurs_Articles
			nindice++
			jsFournisseur.articles[nindice]	=	ChaîneVersJSON(sdFournisseurs_Articles.jsarticles)
		FIN
	FIN

FIN

return $supplier;

} catch (Throwable $e) {
    Http::respond(500, ['error' => 'Internal server error', 'from' => 'getSupplierOrder', 'data' => $e->getMessage()]);
    }
    }
    
    public static function exists(PDO $pdo, 
    string $companyCode, 
    string $supplierId): ? boolean
    {
        try {            
            $company = Company::get($companyCode);
            if (!$company) return false;
            $sql = "SELECT A6FOUR
            FROM {$company['library']}.A6FOURN
            WHERE A6FOUR = :supplierID";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':supplierID', $supplierId, PDO::PARAM_STR);
            $stmt->execute();            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return false;
                }
                return true;
                } catch (Throwable $e) {
                    Http::respond(500, ['error' => 'Internal server error', 'from' => 'getSupplierOrder', 'data' => $e->getMessage()]);
                    }
                    }
                    */
    
}

<?php
declare(strict_types=1);

namespace App\Products;

use PDO;
use Throwable;
use DateTimeInterface;
use DateTimeImmutable;

use App\Core\Http;
use App\Core\cst;

use App\Domain\Company;
use App\Digital\Digital;

use App\Domain\A1ARTICL;
use App\Domain\A3GESPVP;
use App\Domain\A4TVA;
use App\Domain\A9FAMIL;
use App\Domain\ACARTCAT;
use App\Domain\ACNARTCAT;
use App\Domain\ADARTDEP;
use App\Domain\ARTNOWEB;
use App\Domain\ASARTSOC;
use App\Domain\B3CLIENT;
use App\Domain\B9INDUTI;
use App\Domain\CATALOGUE;
use App\Domain\C0LIBART;
use App\Domain\C3LIBTAR;

use App\Domain\D4NOMENC;
use App\Domain\D7RFARFO;
use App\Domain\DFDEPFOUR;

use App\Domain\EAECOART;

use App\Domain\G0ISO;

use App\Domain\IAFAPPFOUR;

use App\Domain\K1ARCPT;

use App\Domain\PXROUGE;
use App\Domain\PXNROUGE;

use App\Domain\R5GESPRA;

use App\Domain\TTTXT;

final class Products
{
	private static array $contexte = [
		'utilisateur' => '',
		'programme' => '',
		'action' => 'ajout',
	];


	// API de création d'article
	// Partie 1 : les contrôles
	public static function contrôle(PDO $pdo, array|string $product) : array
	{
		$errorMessage = null;
		$payload = self::normaliserPayloadControle($product, $errorMessage);
		if ($payload === null) {
			return [
				'error' => true,
				'warning' => false,
				'message' => $errorMessage ?? 'Impossible de lire product',
				'details' => [],
			];
		}

		$details = self::initialiserContexteControle($payload);

		$productData = $payload;
		if (array_key_exists('product', $payload)) {
			if (!is_array($payload['product'])) {
				$hasWarning = false;
				foreach ($details as $detail) {
					$hasWarning = $hasWarning || (bool)($detail['warning'] ?? false);
				}
				return [
					'error' => true,
					'warning' => $hasWarning,
					'message' => 'La clé product doit être un objet JSON',
					'contexte' => self::$contexte,
					'details' => $details,
				];
			}
			$productData = $payload['product'];
		}

		$details[] = self::contrôle_A1ARTICL($pdo, $productData['A1ARTICL'] ?? null);
		$details = array_merge(
			$details,
			self::contrôle_SOCIETE($productData['SOCIETES'] ?? null)
		);

		$hasError = false;
		$hasWarning = false;
		foreach ($details as $detail) {
			$hasError = $hasError || (bool)($detail['error'] ?? false);
			$hasWarning = $hasWarning || (bool)($detail['warning'] ?? false);
		}

		$message = 'Contrôle terminé';
		if ($hasError) {
			$message = 'Contrôle terminé avec erreurs';
		} elseif ($hasWarning) {
			$message = 'Contrôle terminé avec avertissements';
		}

		return [
			'error' => $hasError,
			'warning' => $hasWarning,
			'message' => $message,
			'contexte' => self::$contexte,
			'details' => $details,
		];
	}

	private static function initialiserContexteControle(array $payload): array
	{
		$details = [];
		$utilisateur = trim((string)($payload['utilisateur'] ?? $payload['Utilisateur'] ?? ''));
		$programme = trim((string)($payload['programme'] ?? $payload['Programme'] ?? ''));
		$action = strtolower(trim((string)($payload['action'] ?? $payload['Action'] ?? '')));

		if ($action === '') {
			$action = 'ajout';
			$details[] = [
				'element' => 'action',
				'error' => false,
				'warning' => true,
				'message' => "Action absente ou vide, valeur par défaut appliquée: 'ajout'",
			];
		} elseif (!in_array($action, ['ajout', 'modification'], true)) {
			$details[] = [
				'element' => 'action',
				'error' => true,
				'warning' => false,
				'message' => "Action invalide '{$action}'. Valeurs autorisées: ajout, modification",
			];
		}

		self::$contexte = [
			'utilisateur' => $utilisateur,
			'programme' => $programme,
			'action' => $action,
		];

		return $details;
	}

	private static function normaliserPayloadControle(array|string $product, ?string &$errorMessage = null): ?array
	{
		if (is_array($product)) {
			return $product;
		}
		$payload = trim($product);
		if ($payload === '') {
			$errorMessage = 'Product JSON vide';
			return null;
		}
		try {
			$decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
		} catch (\JsonException $e) {
			$errorMessage = 'JSON invalide: ' . $e->getMessage();
			return null;
		}
		if (!is_array($decoded)) {
			$errorMessage = 'Le JSON doit représenter un objet ou un tableau';
			return null;
		}
		return $decoded;
	}

	private static function contrôle_A1ARTICL(PDO $pdo, mixed $a1articl): array
	{
		if ($a1articl === null) {
			return [
				'element' => 'product.A1ARTICL',
				'error' => true,
				'warning' => false,
				'message' => 'A1ARTICL manquant',
			];
		}
		if (!is_array($a1articl)) {
			return [
				'element' => 'product.A1ARTICL',
				'error' => true,
				'warning' => false,
				'message' => 'A1ARTICL doit être un objet',
			];
		}
		if ($a1articl === []) {
			return [
				'element' => 'product.A1ARTICL',
				'error' => false,
				'warning' => true,
				'message' => 'A1ARTICL est vide',
			];
		}		
		$errors = [];
		$warnings = [];

		// A1ART : Code article [Mandatory]
		if(!array_key_exists('a1art', $a1articl)) {
			$errors[] = ['rubrique' => 'a1art', 'message' => 'code article manquant'];
		}		
		$a1art = trim((string)($a1articl['a1art'] ?? ''));
		if ($a1art === '' || !preg_match('/^[A-Z0-9]{6}$/', $a1art)) {
			$errors[] = ['rubrique' => 'a1art', 'message' => 'code article ('.$a1art.') invalide: attendu 6 caractères [A-Z0-9]'];
			goto fin;
		}
		if ((self::$contexte['action'] ?? '') === 'ajout') {
			if(A1ARTICL::exists($pdo,cst::MBI,$a1art)) {
				$errors[] = ['rubrique' => 'a1art', 'message' => 'code article '.$a1art.' existe déjà !'];
				goto fin;
			}
		}
		// A1CNUF : Code pays d'odigine [Mandatory] Lien : G0ISO
		if(array_key_exists('a1cnuf',$a1articl)) {
			$a1cnuf = sprintf('%03d', (int) $a1articl['a1cnuf']);
			if(!G0ISO::exists($pdo,$a1cnuf)) {
				$errors[] = ['rubrique' => 'a1cnuf', 'message' => 'code pays ('.$a1cnuf.') inconnu dans G0ISO.'];	
			}
		} else {
			 $errors[] = ['rubrique' => 'a1cnuf', 'message' => 'code pays manquant.'];
		}
		// A1SAIS : Indice de saison d'utilisation [Facultatif] Lien : B9INDUTI Valeur par défaut ''
		if(array_key_exists('a1sais',$a1articl)) {
			if(!$a1articl['a1sais']) {
				$warnings[] = ['rubrique' => 'a1sais', 'message' => "Indice de saison d'utilisation : valeur nulle fournie."];	
			} 			
			$a1sais = $a1articl['a1sais'] ?? '';
		} else {
			$warnings[] = ['rubrique' => 'a1sais', 'message' => "Indice de saison d'utilisation non renseigné, valeur par défaut utilisée"];
			$a1sais = '';
		}		
		if($a1sais != '') {
			if(!B9INDUTI::exists($pdo, cst::MBI, $a1sais)) {
				$errors[] = ['rubrique' => 'a1sais', 'message' => "Indice de saison d'utilisation (".$a1sais.") non trouvé dans B9INDUTI."];	
			}			
		}			
		// A1POID : Poids unitaire de l'article en (kg) [Facultatif]
		// A1UNVT : Unite de vente [Facultatif] dft : 1
		$a1unvt = 1;
		if(array_key_exists('a1unvt',$a1articl)) {
			if((int) $a1articl['a1unvt'] != 0 ) {
				$a1unvt = (int) $a1articl['a1unvt'];
			}
		}
		// A1QTMC : Quantite mini de commande client [Facultatif] dft : 1
		$a1qtmc = $a1unvt;
		if(array_key_exists('a1qtmc',$a1articl)) {
			if((int) $a1articl['a1qtmc'] != 0 ) {
				$a1qtmc = (int) $a1articl['a1qtmc'];				
			}
		}
		// A1QTMC doit etre un multiple de A1UNVT
		if (($a1qtmc % $a1unvt) !== 0) {
			$errors[] = [
				'rubrique' => 'a1qtmc',
				'message' => 'A1QTMC ('.$a1qtmc.') doit etre un multiple de A1UNVT ('.$a1unvt.').',
			];
		}		
		// A1DNS  : Demande non satisfaite [Facultatif]
		
		// A1CTVA / A1TVA : Code T.V.A. / T.V.A. appliquee a l'article [Mandatory]
		$a1ctva = '2'; // Code TVA par défaut
		if(array_key_exists('a1ctva',$a1articl)) {
			if($a1articl['a1ctva'] != '') {
				$a1ctva = $a1articl['a1ctva'];
			}		
		}
		if(!A4TVA::exists($pdo,cst::MBI,$a1ctva)) {
			$errors[] = [
				'rubrique' => 'a1ctva',
				'message' => "Code de TVA (".$a1ctva.") non trouvé dans A4TVA."
			];
		}
		// A1FAMI : Code de la famille [Mandatory] Lien : A9FAMI
		if(array_key_exists('a1fami',$a1articl)) {
			$a1fami = $a1articl['a1fami'];
			if($a1fami === '') {
				$errors[] = ['rubrique' => 'a1fami', 'message' => 'Code famille renseigné mais vide.'];
			} else {
				if(!A9FAMIL::exists($pdo,cst::MBI,$a1fami)) {
					$errors[] = [
						'rubrique' => 'a1fami',
						'message' => "Code famille (".$a1fami.") non trouvé dans A9FAMIL."
					];
				}
			}
		} else {
			$errors[] = ['rubrique' => 'a1fami', 'message' => 'Code famille manquant.'];
		}
		// A1QTST : Quantite de conditionnement stockage [Mandatory]
		// A1MTRO : Reference article chez le client (O/N) [Mandatory]
		// A1MATI : Code matiere [Mandatory] Lien : C6MATIER
		// A1DATC : Date de creation MM/AAAA [Mandatory]
		// A1TYPE : Type ou niveau de finition de l'article" [Mandatory]
		$a1type = 'PF';
		if(array_key_exists('a1type',$a1articl)) {
			if(in_array($a1articl['a1type'], ['PF','CO','SF','MP',''],true )) {
				$a1type = $a1articl['a1type'];
				if($a1type === '') $a1type = 'PF';
			} else {
				$errors[] = ['rubrique' => 'a1type', 'message' => 'Code type de produit ('.$a1articl['a1type'].') invalide [ PF / CO / SF / MP ].'];
			}
		} else {
			// Warning ?
		}
		// A1DOUA : No de tarif douanier [Mandatory] Lien : COMEX (simple vérif)
		// A1EMPL : Tag conservation dernier emplacement (O/N) [Mandatory]
		// A1DI10 : Dimension 1 longueur [Mandatory]
		// A1DI11 : Dimension 1 largeur [Mandatory]
		// A1DI12 : Dimension 1 hauteur [Mandatory]
		// A1DI20 : Dimension 2 longueur [Mandatory]
		// A1DI21 : Dimension 2 largeur [Mandatory]
		// A1DI22 : Dimension 2 hauteur [Mandatory]
		// A1ECAV : Dernier P.R.A. [Mandatory]
		// A1MOYV : Filler [Mandatory]
		// A1TEND : Type acces (Libre/Fixe) [Mandatory]
		// A1SASO : H = Supprimer Article en historique REDA09 [Mandatory]
		// A1PRIV : Prix Vente Cata creation [Mandatory]
		fin:
		return [
			'element' => 'product.A1ARTICL',
			'error' => (count($errors) > 0),
			'warning' => (count($warnings) > 0),
			'message' => 'A1ARTICL valide',
			'erreurs' => $errors,
			'warnings' => $warnings
		];
	}

	private static function contrôle_SOCIETE(mixed $societes): array
	{
		if ($societes === null) {
			return [[
				'element' => 'product.SOCIETES',
				'code_societe' => null,
				'error' => true,
				'warning' => false,
				'message' => 'SOCIETES manquant',
			]];
		}
		if (!is_array($societes)) {
			return [[
				'element' => 'product.SOCIETES',
				'code_societe' => null,
				'error' => true,
				'warning' => false,
				'message' => 'SOCIETES doit être un tableau',
			]];
		}
		if ($societes === []) {
			return [[
				'element' => 'product.SOCIETES',
				'code_societe' => null,
				'error' => false,
				'warning' => true,
				'message' => 'SOCIETES est vide',
			]];
		}

		$results = [];
		foreach ($societes as $index => $societe) {
			$elementPath = 'product.SOCIETES[' . $index . ']';
			$codeSociete = null;
			if (is_array($societe) && array_key_exists('SOCIETE', $societe)) {
				$codeSocieteValue = trim((string)$societe['SOCIETE']);
				if ($codeSocieteValue !== '') {
					$codeSociete = $codeSocieteValue;
				}
			}

			if (!is_array($societe)) {
				$results[] = [
					'element' => $elementPath,
					'code_societe' => $codeSociete,
					'error' => true,
					'warning' => false,
					'message' => 'La société doit être un objet',
				];
				continue;
			}
			if (!array_key_exists('SOCIETE', $societe) || trim((string)$societe['SOCIETE']) === '') {
				$results[] = [
					'element' => $elementPath . '.SOCIETE',
					'code_societe' => $codeSociete,
					'error' => true,
					'warning' => false,
					'message' => 'Code société manquant',
				];
			} else {
				$results[] = [
					'element' => $elementPath . '.SOCIETE',
					'code_societe' => $codeSociete,
					'error' => false,
					'warning' => false,
					'message' => 'Code société valide',
				];
			}
			if (!array_key_exists('DATAS', $societe) || !is_array($societe['DATAS'])) {
				$results[] = [
					'element' => $elementPath . '.DATAS',
					'code_societe' => $codeSociete,
					'error' => true,
					'warning' => false,
					'message' => 'DATAS manquant ou invalide',
				];
			} elseif ($societe['DATAS'] === []) {
				$results[] = [
					'element' => $elementPath . '.DATAS',
					'code_societe' => $codeSociete,
					'error' => false,
					'warning' => true,
					'message' => 'DATAS vide',
				];
			} else {
				$results[] = [
					'element' => $elementPath . '.DATAS',
					'code_societe' => $codeSociete,
					'error' => false,
					'warning' => false,
					'message' => 'DATAS valide',
				];
			}
		}
		return $results;
	}
	
	
	public static function getSuppliers(PDO $pdo, string $companyCode, string $productCode) :? array 
	{
		$supplier = [];
		$d7rfarfo = D7RFARFO::getModelsByCompanyProduct($pdo, $companyCode, $productCode);
		foreach ($d7rfarfo as $d7) {	
			$iafappfour = null;		
			if(in_array($companyCode,['06','38','40'],true)) {
				$iafappfour = IAFAPPFOUR::getModelByKey($pdo,$companyCode,$d7->d7four,$productCode);					
			}
			$r5gespra = R5GESPRA::getModelsByCompanySupplierProduct($pdo,$companyCode,$d7->d7four,$productCode);
			$dates = [];
			foreach($r5gespra as $r5) {
				$year = trim((string)$r5->r5dapl);
				$month = trim((string)$r5->r5dmpl);
				$day = trim((string)$r5->r5djpl);
				if ($year === '' || $month === '' || $day === '') {
					continue;
				}
				if (strlen($year) === 2) {
					$year = '20' . $year;
				}
				$date = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
				$dates[] = $r5->toArrayLower();
			}			
			$supplier[]	= [
				'D7RFARFO' => $d7->toArrayLower(),
				'IAFAPPFOUR' =>  ($iafappfour) ? $iafappfour->toArrayLower() : null,  
				'R5GESPRA' => $dates
			];
		}		
		return $supplier;	
	}

	public static function getPrices(PDO $pdo, string $companyCode, string $productCode) :? array 
	{
		$prices = [];
		$c3libtar = C3LIBTAR::readModels($pdo, $companyCode);
		foreach($c3libtar as $c3) {
			$c3Row						= $c3->toArrayLower();
			$c3Row['extra']['nombre']	= B3CLIENT::getNombreParCodeTarif($pdo,$companyCode,$c3->c3indi);			
			$tarifCode					= (string)$c3->c3indi;			
			$a3gespvp					= A3GESPVP::getModelsByCompanyTarifProduct($pdo, $companyCode, $tarifCode, $productCode);
			$dates = [];
			if ($a3gespvp) {
				foreach($a3gespvp as $a3) {					
					$dates[] = $a3->toArrayLower();
				}
			}
			$price = [
				'C3LIBTAR'	=> $c3Row,
				'A3GESPVP'	=> $dates,
			];
			$prices[] = $price;
		}
		return $prices;
	}

	public static function getPagesCatalogues(PDO $pdo, string $companyCode, string $productCode) :? array 
	{
		$pages = [];
		if (in_array($companyCode, ['','06','38','40'],true)) {
			$catalogues = CATALOGUE::readModels($pdo, $companyCode);
			foreach($catalogues as $c) {
				$caid = $c->caid;
				$datas = ACARTCAT::getModelsByCompanyCatalogArticle($pdo,'',$caid,$productCode);
				$p = null;
				if($datas) {
					foreach($datas as $d) {
						$p[] = $d->toArrayLower();
					}
				}
				$datas = ACNARTCAT::getModelsByCompanyCatalogArticle($pdo,'',$caid,$productCode);
				$np = null;
				if($datas) {
					foreach($datas as $d) {
						$np[] = $d->toArrayLower();
					}
				}
				$pages[] = [
					'CATALOGUE'	=> $c->toArrayLower(),
					'ACARTCAT'	=> $p,
					'ACNARTCAT'	=> $np
				];
			}
		} else {
			$datas = ACARTCAT::getModelsByCompanyCatalogArticle($pdo,$companyCode,'0',$productCode);
			$p = null;
			if($datas) {
				foreach($datas as $d) {
					$p[] = $d->toArrayLower();
				}
			}
			$datas = ACNARTCAT::getModelsByCompanyCatalogArticle($pdo,$companyCode,'0',$productCode);
			$np = null;
			if($datas) {
				foreach($datas as $d) {
					$np[] = $d->toArrayLower();
				}
			}				
			$pages[] = [
					'CATALOGUE'	=> [
						'caid' => '0', 
						'calib' => 'catalogue'
					],
					'ACARTCAT'	=> $p,
					'ACNARTCAT'	=> $np
				];
		}
		return $pages;
	}

	public static function getCompteComptable($pdo, $companyCode, $productCode) :? array
	{
		$k1artcp = K1ARCPT::getModelById($pdo,$companyCode,$productCode);
		if($k1artcp) {
			return $k1artcp->toArrayLower();
		}
		return null;
	}

	public static function getDépôtPrincipalPourDropFournisseur(PDO $pdo, string $productCode) :? string
	{		
		$d7rfarfo = D7RFARFO::getMBIMainSupplier($pdo , $productCode);		
		if($d7rfarfo) {			
			//echo "Fournisseur trouvé : ".$d7rfarfo->d7four." société : ".$d7rfarfo->d7soc;
			$dfdepfour = DFDEPFOUR::getModelByFour($pdo,$d7rfarfo->d7four);
			if($dfdepfour) return $dfdepfour->dfsocgfou;			
		} 
		return null;
	}

	public static function getLibelleArticle(PDO $pdo, string $companyCode, string $productCode): ?array
	{
		$datas = [];
		foreach(cst::cstLangues as $langue) {
			$row = C0LIBART::readModel($pdo, $companyCode,$productCode,$langue);
			$lib = null;
			if($row) {
				$lib = $row->toArrayLower();				
			}
			$datas[] = [
				'langue' => $langue,
				'C0LIBART' => $lib,
			];
		}		
		return $datas;
	}

	private static function getControleReception(PDO $pdo, string $productCode) :? array
	{
		$datas = [];
		$reflex = TTTXT::readModels($pdo,'',$productCode,'ART',cst::cstTexteReflex);
		$dataReflex = [];
		if ($reflex) {
			foreach($reflex as $r) {
				$dataReflex[] = $r->toArrayLower();
			}
		}
		$texte = TTTXT::readModels($pdo,'',$productCode,'ART',cst::cstTexteControleReception);
		$dataCtrl = [];
		if($texte) {
			foreach($texte as $t) {
				$dataCtrl[] = $t->toArrayLower();
			}
		}
		$datas = [
			'REFLEX' => $dataReflex,
			'CTRLRECEP' => $dataCtrl
		];
		return $datas;
	}

	private static function getFloVendingDatas(PDO $pdo, string $productCode) :? array 
	{
		return self::getFilialeDatas($pdo, '15', $productCode);
	}

	private static function getVauconsantDatas(PDO $pdo, string $productCode) :? array 
	{
		return self::getFilialeDatas($pdo, '54', $productCode);
	}

	private static function getMBIDatas(PDO $pdo, string $productCode) :? array 
	{
		$datas                  		= [];
		$asartsoc               		= ASARTSOC::getModelByCompanyProduct($pdo, '06' , $productCode);
		$datas['ASARTSOC']	    		= $asartsoc ? $asartsoc->toArrayLower() : null;
		$datas['LIBELLE']     	 		= self::getLibelleArticle($pdo, '06' , $productCode);
		$datas['K1ARTCP']['06'] 		= self::getCompteComptable($pdo,'06',$productCode);
		$datas['K1ARTCP']['38'] 		= self::getCompteComptable($pdo,'38',$productCode);
		$datas['K1ARTCP']['40'] 		= self::getCompteComptable($pdo,'40',$productCode);
		$DépotPrincipal         		= ADARTDEP::getMainDepotByArticle($pdo, $productCode);
		$datas['ADARTDEP']      		= ADARTDEP::getDepotsArrayByProduct($pdo, $productCode);
		if(!in_array($DépotPrincipal,['06','38','40'], true )) {
			// On va chercher le dépot Principal pour le fournisseur DROP
			$dep              			= self::getDépôtPrincipalPourDropFournisseur($pdo, $productCode);
			//echo "Recherche dépot principal pour fournisseur DROP : ".$dep;
			if($dep) $DépotPrincipal = $dep;
		}
		$datas['FOURNISSEUR']  			= self::getSuppliers($pdo, $DépotPrincipal , $productCode);		
		$datas['PRIX_VENTE'] 	   		= self::getPrices($pdo, '' , $productCode);
		$datas['CATALOGUES']      		= self::getPagesCatalogues($pdo,'',$productCode);	
		$datas['CTRL_RECEP']			= self::getControleReception($pdo,$productCode);	
		return $datas;
	}
	
	private static function getFilialeDatas(PDO $pdo, string $companyCode, string $productCode) :? array 
	{
		$datas                  		= [];
		$asartsoc			    		= ASARTSOC::getModelByCompanyProduct($pdo, $companyCode , $productCode);	
		$datas['ASARTSOC']	    		= $asartsoc ? $asartsoc->toArrayLower() : null;
		$datas['LIBELLE']	    		= self::getLibelleArticle($pdo, $companyCode, $productCode);
		$datas['K1ARTCP']       		= self::getCompteComptable($pdo,$companyCode,$productCode);
		$datas['FOURNISSEUR']      		= self::getSuppliers($pdo, $companyCode, $productCode);		
		$datas['PRIX_VENTE'] 	   		= self::getPrices($pdo, $companyCode, $productCode);
		$datas['CATALOGUES']      		= self::getPagesCatalogues($pdo,$companyCode,$productCode);
		return $datas;
	}

	public static function getCompanyDatas(PDO $pdo, string $companyCode, string $productCode) :?array 
	{
		$start = microtime(true);	
		$datas = [];		
		switch($companyCode) {
			case '15':
				return [
					'SOCIETE' => $companyCode,
					'DATAS' => self::getFloVendingDatas($pdo,$productCode),
					'time' => microtime(true) - $start
				];				
				break;
			case '00':
			case '06':
			case '38':
			case '40':
				return [
					'SOCIETE' => cst::MBI,
					'DATAS' => self::getMBIDatas($pdo,$productCode),
					'time' => microtime(true) - $start
				];				
				break;
			case '54':
				return [
					'SOCIETE' => $companyCode,
					'DATAS' => self::getVauconsantDatas($pdo,$productCode),
					'time' => microtime(true) - $start
				];				
				break;				
			default:
				return [
					'SOCIETE' => $companyCode,
					'DATAS' => self::getFilialeDatas($pdo,$companyCode,$productCode),
					'time' => microtime(true) - $start
				];										
		}		
	}

	public static function getProductTextes(PDO $pdo, string $companyCode, string $productCode) :? array 
	{
		$textes = [];		
		$types = TTTXT::getModelsByType($pdo, $companyCode, 'ART');
		foreach($types as $tttyp) {
			$typeTTTXT = $tttyp->tttypcmt;			
			if ($typeTTTXT === cst::cstTexteControleReception) continue;
			$affichage = TTTXT::isDisplayable($pdo,$companyCode,$tttyp->tttype, $tttyp->tttypcmt); 			
			if(str_contains($typeTTTXT,"_")) {				
				foreach(cst::cstLangues as $langue ) {
					$txtLangue = [];
					$tttypcmt = $typeTTTXT.$langue;					
					$txt = TTTXT::readModels($pdo, $companyCode, $productCode, 'ART', $tttypcmt);
					$datas = [];
					foreach($txt as $t) {
						$datas[] = $t->toArrayLower();
					}
					$txtLangue[] = [
						'LANGUE' => $langue,
						'TTTXT' => $datas,
					];					
				}
				$textes[]	= [					
					'TYPE' => str_replace("_","",$typeTTTXT),
					'AFFICHE' => $affichage,
					'DATAS' => $txtLangue
				];					
			} else {
				$tttypcmt = $typeTTTXT;				
				$txt = TTTXT::readModels($pdo, $companyCode, $productCode, 'ART', $tttypcmt);
				$txtLangue = [];
				$datas = [];
				foreach($txt as $t) {
					$datas[] = $t->toArrayLower();
				}
				$txtLangue[] = [	
					'LANGUE' => 'DFT',				
					'TTTXT' => $datas,
				];
				$textes[]	= [
					'TYPE' => $typeTTTXT,
					'AFFICHE' => $affichage,
					'DATAS' => $txtLangue
				];					
			}
		}
		return $textes;
	}

	public static function getPrixRouges(PDO $pdo, string $productCode): ? array 
	{
		$model = PXROUGE::getPrixRougeArticle($pdo,$productCode);
		if($model) return $model->toArrayLower();
		$px = [];
		$px['prart'] = null;
		$px['prsoc'] = null;
		$px['prnet'] = null;
		$px['prddeb'] = null;
		$px['prdfin'] = null;
		return $px;
	}

	public static function getNouveauPrixRouges(PDO $pdo, string $productCode): ? array 
	{
		$model = PXNROUGE::getNouveauPrixRougeArticle($pdo,$productCode);
		if($model) return $model->toArrayLower();
		$px = [];
		$px['prart'] = null;
		$px['prsoc'] = null;
		$px['prnet'] = null;
		$px['prddeb'] = null;
		$px['prdfin'] = null;
		return $px;
	}

	public static function getEcoPart(PDO $pdo, string $productCode): ? array 
	{
		$data = [];
		$model = EAECOART::getEcoPartArticle($pdo,$productCode);
		if($model) {
			$data =  $model->toArrayLower();
		} else {
			$data['eaart'] = null;
			$data['eateco'] = null;
			$data['eaprix'] = null;
			$data['eahoro'] = null;
			$data['eautil'] = null;
			$data['eapgm'] = null;
			$data['numenreg'] = null;
		}
		return $data;
	}

	public static function get(PDO $pdo, string $companyCode, string $productCode)
    {
		$start = microtime(true);	
		$product = [];
        try {
			$company = Company::get($companyCode);
            if (!$company) return null;
            // Les fichiers satellites			
            $model = A1ARTICL::getModelById($pdo, $companyCode, $productCode);
            if (!$model) return null;
			
			$product['A1ARTICL']				= $model->toArrayLower();            			
			$product['bNomenclature']			= D4NOMENC::bEstNomenclature($pdo, $companyCode, $productCode);
			$product['libelle']					= C0LIBART::labelFor($pdo,$companyCode,$productCode,'FRA');
			$product['logistic_label']			= C0LIBART::labelFor($pdo,'06',$productCode,'LOG');
			$codePays							= str_pad((string) $model->A1CNUF, 3, '0', STR_PAD_LEFT);
			$g0iso								= G0ISO::readModel($pdo,$codePays);
			if($g0iso) {
				$product['G0ISO']				= $g0iso->toArrayLower();
			}						
			// Ici, il faut distinguier Flovending qui est à part et MBI / Filiales qui gèrent les articles de façon commune					
			$product['SOCIETES'] = [];
			if ($companyCode == '15') {
				// FloVending
				$product['SOCIETES'][] = self::getCompanyDatas($pdo, '15', $productCode);
				$product['TTTXT'] = self::getProductTextes($pdo, '15'  , $productCode);
			} else if ($companyCode == '54') {
					// Vauconsant
					$product['SOCIETES'][] = self::getCompanyDatas($pdo, '54', $productCode);
					$product['TTTXT'] = self::getProductTextes($pdo, '54'  , $productCode);
			} else {
				foreach( Company::all() as $comp ) {
					switch($comp['code']) {
						case '00':													
							break;
						case '06':
							$product['SOCIETES'][] = self::getCompanyDatas($pdo, '06', $productCode);
						case '38':							
							break;
						case '40':								
							break;
						case '15':							
							break;
						case '54':							
							break;
						default:
							$product['SOCIETES'][] = self::getCompanyDatas($pdo, $comp['code'], $productCode);
					}	
				}
			}
			$artnoweb											= ARTNOWEB::getByArticle($pdo,$productCode);
			if(!$artnoweb) $artnoweb['code_article'] = null;
			$product['ARTNOWEB']								= $artnoweb;
			$product['PXROUGE']									= self::getPrixRouges($pdo,$productCode);
			$product['PXNROUGE']								= self::getNouveauPrixRouges($pdo,$productCode);
			$product['EAECOART']								= self::getEcoPart($pdo, $productCode);
			$product['TEXTES']									= self::getProductTextes($pdo, '06'  , $productCode);
			$product['NOMENCLATURE_DIGITALE']					= Digital::getNomenclatureDigitale($pdo, $productCode);
			$product[cst::cstDatePublicationCatalogueDigital]	= Digital::getValeurAttribut($pdo,$productCode,cst::cstDatePublicationCatalogueDigital);
			$product[cst::cstCatégorieFonctionnelle]			= Digital::getValeurAttribut($pdo,$productCode,cst::cstCatégorieFonctionnelle);
			$product['AVANTAGES'][] 							= [ 
																	'TYPE' => cst::cstArguCEA, 
																	'APAVTPRD' => Digital::getAvtPrd($pdo,$productCode, cst::cstArguCEA,1)
																];
			$product['AVANTAGES'][] 							= [ 
																	'TYPE' => cst::cstArguPrint, 
																	'APAVTPRD' => Digital::getAvtPrd($pdo,$productCode, cst::cstArguPrint,1)
																];
			$product['AVANTAGES'][] 							= [ 
																	'TYPE' => cst::cstArguLOT, 
																	'APAVTPRD' => Digital::getAvtPrd($pdo,$productCode, cst::cstArguLOT,1)
																];
			$product['AVANTAGES'][] 							= [ 
																	'TYPE' => cst::cstArguDesc, 
																	'APAVTPRD' => Digital::getAvtPrd($pdo,$productCode, cst::cstArguDesc)
																];
			$product['MEDIAS']									= Digital::getMedias($pdo,$productCode,cst::cstTypPhoto,cst::cstPhotoModèle);
			$product['ATTRIBUTS']								= Digital::lireAttributs($pdo,$productCode);			
			//$product['DefAttributs']							= Digital::getDefAttributs($pdo);
						
		} catch (Throwable $e) {
    		$debug = (getenv('APP_DEBUG') === '1');
		    $payload = [
	    	    'error' => 'Internal server error',
        		'from'  => 'Products::get()',
        		'data'  => $e->getMessage(),
			];
		    if ($debug) {
		        $payload['file']  = $e->getFile();
		        $payload['line']  = $e->getLine();
		        $payload['trace'] = $e->getTraceAsString(); // ou $e->getTrace()
			}
    		Http::respond(500, $payload);
		}
		$product['time'] = microtime(true) - $start;
		return $product;
    }
	public static function getPRA(PDO $pdo, string $companyCode, string $productCode, ? DateTimeInterface $date = null) : ? array 
	{
		$companyCode = trim($companyCode);
		$productCode = strtoupper(trim($productCode));
		if ($companyCode === '' || $productCode === '') return null;

		$company = Company::get($companyCode);
		if (!$company) return null;

		$dateObj = $date ? DateTimeImmutable::createFromInterface($date) : new DateTimeImmutable('now');
		$dateRef = $dateObj->format('Y-m-d');

		$sql = "SELECT * FROM TABLE(
					SQLPGS.FULLPRADATE(
						CODART => :product_code,
						CODSOC => :company_code,
						DATE_REFF => :date_ref
					)
				)";
		$stmt = $pdo->prepare($sql);
		$stmt->bindValue(':product_code', $productCode, PDO::PARAM_STR);
		$stmt->bindValue(':company_code', $companyCode, PDO::PARAM_STR);
		$stmt->bindValue(':date_ref', $dateRef, PDO::PARAM_STR);
		$stmt->execute();

		$row = $stmt->fetch();
		return $row ?: null;
	}

	public static function getDangerousGoodDatas(PDO $pdo, string $productCode) : ? array
	{
		$bMatDGX			= false;
		$data				= [];
		$productCode		= strtoupper(trim($productCode));
		if ($productCode === '') {
			return [$bMatDGX, $data];
		}

		$attributs = cst::gtabAttributsMatDGX;
		$placeholders = [];
		foreach ($attributs as $index => $unused) {
			$placeholders[] = ':attribut' . $index;
		}

		$Sql = "select ae_code_attribut, ae_num_ordre, ae_data from matis.aeattete where ae_code_art = :product and ae_code_attribut in (" . implode(',', $placeholders) . ") order by ae_code_attribut, ae_num_ordre";
		$stmt = $pdo->prepare($Sql);
		$stmt->bindValue(':product', $productCode, PDO::PARAM_STR);
		foreach ($attributs as $index => $attribut) {
			$stmt->bindValue(':attribut' . $index, (string)$attribut, PDO::PARAM_STR);
		}
		$stmt->execute();
		$rows= $stmt->fetchAll();
		if($rows) {			
			foreach($rows as $row) {
				switch($row['AE_CODE_ATTRIBUT'])
				{
					case "MAT_DGX":				// Flag matière dangereuse O/N
						$bMatDGX = ((string)$row['AE_DATA'] === "1");
						break;
					case  "NUM_UFI":
					case  "DGX_UFI": 	// Numéro UFI 
						$data['UEFI'] = $row['AE_DATA'];
						break;
					case "DGX_UN":				//Le numéro ONU						Numéro d' identification  matières dangereuses
						$data['UN'] = $row['AE_DATA'];
						$un = $row['AE_DATA'];
						$sql = "SELECT * FROM MATIS.DGX_UN_CODES WHERE UN_CODE = :UN_CODE";
						$stmt_un = $pdo->prepare($sql);
						$stmt_un->bindValue(':UN_CODE', $un, PDO::PARAM_STR);
						$stmt_un->execute();
						$row_un= $stmt_un->fetch();
						if($row_un) {
							$data['Designation'] = $row_un['UN_DESIGNATION'];
						} else {
							$data['Designation'] = 'n/c';
						}				
						break;
					case "DGX_CL":				//Classe de danger						Classification risque associé à produit dangereux
						$data['CL'] = $row['AE_DATA'];
						break;
					case "DGX_G":				//Groupe d'emballage					Classification des emballages
						$data['G'] = $row['AE_DATA'];
						break;
					case "DGX_E":				//Étiquette								Étiquette information danger
						$data['ETIQ'][$row['AE_NUM_ORDRE']] = $row['AE_DATA'];
						break;
					case "DGX_CLS":			//Code de classement					Classification risque associé à produit dangereux
						$data['CLS'] = $row['AE_DATA'];
						break;
					case "DGX_DS":			//Dispositions spéciales				instructions et exigences spéciales
						$data['DS'] = $row['AE_DATA'];
						break;
					case "DGX_LQ":			//Quantités limitées					Quantités expédiées limitées
						$data['LQ'] = $row['AE_DATA'];
						break;
					case "DGX_CT":			//Catégorie transport					Classification par codification transport
						$data['CT'] = $row['AE_DATA'];
						break;
					case "DGX_TU":			//Code de restrictions des tunnels		Codes spécifiques de restrictions des tunnels
						$data['PU'] = $row['AE_DATA'];
						break;
					case "DGX_PE":			//Point d'éclair						Point d'éclair
						$data['PE'] = $row['AE_DATA'];
						break;
					case "DGX_EMS":			//EmS - Emergency Schedule				Informations sur le programme d'urgence
						$data['EMS'] = $row['AE_DATA'];
						break;
					case "DGX_QE":			//Quantité exceptée						Quantité exceptée
						$data['QE'] = $row['AE_DATA'];
						break;
					case "DGX_ID":			//Numéro identification de danger		Numéro identification de danger 		
						$data['ID'] = $row['AE_DATA'];
						break;
				}
			}			
		}
		return [$bMatDGX, $data];

	}

	public static function get_old(PDO $pdo, string $productCode) :?array
	{
		$datas = [];
		$product = VUE_API_ARTICLE::getProduct($pdo,$productCode);
		$datas['item'] = json_decode($product->jsdatas);
		return $datas;
		/*
			{
			    "error": false,
			    "elapsed_time": 1094,
			    "item": {
			        "product": "681014",
			        "labels": [
			            {
			                "lang": "ALL",
			                "label": "STIELKASSEROL O/D TRAD 14"
			            },
			            {
			                "lang": "ANG",
			                "label": "TRADITION SAUCEPAN NO LID - 14"
			            },
			            {
			                "lang": "ESP",
			                "label": "CACEROLA \"TRADITION\" S/T - 14"
			            },
			            {
			                "lang": "FRA",
			                "label": "CASSEROLE \"TRADITION\" S/C - 14"
			            },
			            {
			                "lang": "ITA",
			                "label": "CASSERUOLA \"\"TRADITION\"\" S/C-"
			            }
			        ],
			        "medias": [
			            {
			                "type": "PC/photos",
			                "ss_type": "accessoire",
			                "order": 1,
			                "url": "PC/photos/accessoire/a-681014.jpg"
			            },
			            {
			                "type": "PC/photos",
			                "ss_type": "accessoire",
			                "order": 2,
			                "url": "PC/photos/accessoire/a-681014-coupe.jpg"
			            },
			            {
			                "type": "PC/photos",
			                "ss_type": "devis",
			                "order": 1,
			                "url": "PC/photos/devis/d-681014.jpg"
			            },
			            {
			                "type": "PC/photos",
			                "ss_type": "devis",
			                "order": 2,
			                "url": "PC/photos/devis/d-681014-coupe.jpg"
			            },
			            {
			                "type": "PC/photos",
			                "ss_type": "modele",
			                "order": 1,
			                "url": "PC/photos/modele/681014.jpg"
			            },
			            {
			                "type": "PC/photos",
			                "ss_type": "modele",
			                "order": 2,
			                "url": "PC/photos/modele/681014-coupe.jpg"
			            },
			            {
			                "type": "PC/photos",
			                "ss_type": "modele",
			                "order": 3,
			                "url": "PC/photos/modele/681014-2.jpg"
			            },
			            {
			                "type": "PC/photos",
			                "ss_type": "picto",
			                "order": 1,
			                "url": "PC/photos/picto/p-picto-matfer-bourgeat.jpg"
			            },
			            {
			                "type": "PC/photos",
			                "ss_type": "picto",
			                "order": 2,
			                "url": "PC/photos/picto/p-picto-induction.jpg"
			            },
			            {
			                "type": "PC/photos",
			                "ss_type": "picto",
			                "order": 3,
			                "url": "PC/photos/picto/p-picto-lave-vaisselle.jpg"
			            },
			            {
			                "type": "PC/photos",
			                "ss_type": "picto",
			                "order": 4,
			                "url": "PC/photos/picto/p-picto-10-ans.jpg"
			            },
			            {
			                "type": "PC/photos",
			                "ss_type": "thumbnail",
			                "order": 1,
			                "url": "PC/photos/thumbnail/t-681014.jpg"
			            },
			            {
			                "type": "PC/photos",
			                "ss_type": "thumbnail",
			                "order": 2,
			                "url": "PC/photos/thumbnail/t-681014-coupe.jpg"
			            },
			            {
			                "type": "PC/videos",
			                "ss_type": "modele",
			                "order": 1,
			                "url": "PC/photos/modele/GMB-CASSEROLE-TRADITION-681014-32-FR.mp4"
			            }
			        ],
			        "status": {
			            "code": "N",
			            "label": "normal"
			        },
			        "supplier": {
			            "supplier": "00249",
			            "company_name": "BOURGEAT PRODUCTION",
			            "address": {
			                "adr1": "BP 19",
			                "adr2": "",
			                "adr3": "",
			                "zip": "38490",
			                "city": "LES ABRETS",
			                "country": {
			                    "internal": "001",
			                    "iso": "FR",
			                    "name": "FRANCE"
			                }
			            },
			            "contacts": [
			                {
			                    "id": 502572530,
			                    "order": 1,
			                    "function": "",
			                    "name": "",
			                    "address": {
			                        "company_name": "",
			                        "adr1": "",
			                        "adr2": "",
			                        "adr3": "",
			                        "zip": "",
			                        "city": "",
			                        "country": {
			                            "internal": "   ",
			                            "iso": null,
			                            "name": null
			                        }
			                    },
			                    "directory": {
			                        "phone": "",
			                        "fax": "0476322596",
			                        "telex": "",
			                        "mail": ""
			                    }
			                },
			                {
			                    "id": 502765220,
			                    "order": 2,
			                    "function": "Gestion industrielle",
			                    "name": "DELAUZUN Marion",
			                    "address": {
			                        "company_name": "",
			                        "adr1": "",
			                        "adr2": "",
			                        "adr3": "",
			                        "zip": "",
			                        "city": "",
			                        "country": {
			                            "internal": "001",
			                            "iso": "FR",
			                            "name": "FRANCE"
			                        }
			                    },
			                    "directory": {
			                        "phone": "04 76 32 68 9",
			                        "fax": "",
			                        "telex": "1",
			                        "mail": "mdelauzun@matferbourgeat.com"
			                    }
			                }
			            ]
			        }
			    }
			}
		*/
		
	}

}

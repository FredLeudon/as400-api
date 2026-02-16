<?php
declare(strict_types=1);

namespace App\Products;

use PDO;
use Throwable;

use App\Core\Http;
use App\Core\cst;

use App\Domain\Company;
use App\Digital\Digital;

use App\Domain\A1ARTICL;
use App\Domain\A3GESPVP;
use App\Domain\ACARTCAT;
use App\Domain\ACNARTCAT;
use App\Domain\ADARTDEP;
use App\Domain\ARTNOWEB;
use App\Domain\ASARTSOC;
use App\Domain\B3CLIENT;
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

	public static function getPrices(PDO $pdo, string $companyCode, string $productCode) :? array {
		$prices = [];
		$c3libtar = C3LIBTAR::allModels($pdo, $companyCode);
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

	public static function getPagesCatalogues(PDO $pdo, string $companyCode, string $productCode) :? array {
		$pages = [];
		if (in_array($companyCode, ['','06','38','40'],true)) {
			$catalogues = CATALOGUE::allModels($pdo, $companyCode);
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
			$rows = C0LIBART::allModels($pdo, $companyCode,$productCode,$langue);
			$lib = [];
			if($rows) {
				foreach($rows as $row) {
					$lib[] = $row->toArrayLower();
				}
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
		$reflex = TTTXT::allModels($pdo,'',$productCode,'ART',cst::cstTexteReflex);
		$dataReflex = [];
		if ($reflex) {
			foreach($reflex as $r) {
				$dataReflex[] = $r->toArrayLower();
			}
		}
		$texte = TTTXT::allModels($pdo,'',$productCode,'ART',cst::cstTexteControleReception);
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
				];										}
		
		
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
					$txt = TTTXT::allModels($pdo, $companyCode, $productCode, 'ART', $tttypcmt);
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
				$txt = TTTXT::allModels($pdo, $companyCode, $productCode, 'ART', $tttypcmt);
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
			$g0iso								= G0ISO::get($pdo,$codePays);
			if($g0iso) {
				$product['G0ISO']				= $g0iso;
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
			$product['ATTRIBUTS_FICHIER']						= Digital::lireAttributs($pdo,$productCode);			
			//$product['DefAttributs']							= Digital::getDefAttributes($pdo);
						
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
}

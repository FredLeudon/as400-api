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
use App\Domain\ADARTDEP;
use App\Domain\ARTNOWEB;
use App\Domain\ASARTSOC;

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
		$suppliers = [];
		$d7rfarfo = D7RFARFO::getModelsByCompanyProduct($pdo, $companyCode, $productCode);
		foreach ($d7rfarfo as $d7) {
			$suppliers[$d7->d7four] = $d7->toArrayLower();			
		}
		return self::processSuppliers($pdo, $suppliers, $companyCode, $productCode);
	}

	private static function processSuppliers(PDO $pdo, array $suppliers, string $companyCode, string $productCode) : array
	{
		foreach ($suppliers as $supplierCode => $supplier) {
			$suppliers[$supplierCode] = self::processSupplier($pdo, $supplier, (string)$supplierCode, $companyCode, $productCode);
		}
		return $suppliers;
	}

	private static function processSupplier(PDO $pdo,array $supplier, string $supplierCode, string $companyCode, string $productCode) : array
	{
		if(in_array($companyCode,['06','38','40'],true)) {
			$iafappfour = IAFAPPFOUR::getModelByKey($pdo,$companyCode,$supplierCode,$productCode);	
			if($iafappfour) {	
				$supplier['IAFAPPFOUR'] = $iafappfour->toArrayLower() ; 
			} else {
				$supplier['IAFAPPFOUR'] = null;
			}
		}
		$r5gespra = R5GESPRA::getModelsByCompanySupplierProduct($pdo,$companyCode,$supplierCode,$productCode);
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
			$supplier['R5GESPRA'][$date] = $r5->toArrayLower();
		}
		return $supplier;
	}

	public static function getPrices(PDO $pdo, string $companyCode, string $productCode) :? array {
		$prices = [];
		$c3libtar = C3LIBTAR::allModels($pdo, $companyCode);
		foreach($c3libtar as $c3) {			
			$a3gespvp = A3GESPVP::getModelsByCompanyTarifProduct($pdo, $companyCode, $c3->c3indi, $productCode);
			foreach($a3gespvp as $a3) {
				$date = $a3->a3dteapl;
				$prices[$c3->c3indi][$date] = $a3->toArrayLower();			
			}
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
				$pages[$caid] = $p;
			}
		} else {
			$datas = ACARTCAT::getModelsByCompanyCatalogArticle($pdo,$companyCode,'0',$productCode);
			$p = null;
			if($datas) {
				foreach($datas as $d) {
					$p[] = $d->toArrayLower();
				}
			}
			$pages["0"] = $p;
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

	private static function getFloVendingDatas(PDO $pdo, string $productCode) :? array 
	{
		$datas				 		    = [];		
		$asartsoc = ASARTSOC::getModelByCompanyProduct($pdo, '15' , $productCode);
		$datas['ASARTSOC']	    		= $asartsoc ? $asartsoc->toArrayLower() : null;
		$datas['C0LIBART']	    		= C0LIBART::allLabelsFor($pdo, '15', $productCode);
		$datas['K1ARTCP']       		= self::getCompteComptable($pdo,'15',$productCode);
		$datas['D7RFARFO']      		= self::getSuppliers($pdo, '15' , $productCode);
		$datas['A3GESPVP']      		= self::getPrices($pdo, '15' , $productCode);
		$datas['ACARTCAT']      		= self::getPagesCatalogues($pdo,'15',$productCode);
		return $datas;
	}

	private static function getVauconsantDatas(PDO $pdo, string $productCode) :? array 
	{
		$datas				    = [];		
		$asartsoc = ASARTSOC::getModelByCompanyProduct($pdo, '54' , $productCode);
		$datas['ASARTSOC']	    = $asartsoc ? $asartsoc->toArrayLower() : null;
		$datas['C0LIBART']	    = C0LIBART::allLabelsFor($pdo, '54', $productCode);
		$datas['K1ARTCP']       = self::getCompteComptable($pdo,'54',$productCode);
		$datas['D7RFARFO']      = self::getSuppliers($pdo, '54' , $productCode);
		$datas['A3GESPVP']      = self::getPrices($pdo, '54' , $productCode);
		$datas['ACARTCAT']      = self::getPagesCatalogues($pdo,'54',$productCode);
		return $datas;
		}

	private static function getMBIDatas(PDO $pdo, string $productCode) :? array 
	{
		$datas                  = [];
		$asartsoc               = ASARTSOC::getModelByCompanyProduct($pdo, '06' , $productCode);
		$datas['ASARTSOC']	    = $asartsoc ? $asartsoc->toArrayLower() : null;
		$datas['C0LIBART']      = C0LIBART::allLabelsFor($pdo, '06' , $productCode);
		$datas['K1ARTCP']['06'] = self::getCompteComptable($pdo,'06',$productCode);
		$datas['K1ARTCP']['38'] = self::getCompteComptable($pdo,'38',$productCode);
		$datas['K1ARTCP']['40'] = self::getCompteComptable($pdo,'40',$productCode);
		$DépotPrincipal         = ADARTDEP::getMainDepotByArticle($pdo, $productCode);
		$datas['ADARTDEP']      = ADARTDEP::getDepotsArrayByProduct($pdo, $productCode);
		if(!in_array($DépotPrincipal,['06','38','40'], true )) {
			// On va chercher le dépot Principal pour le fournisseur DROP
			$dep                = self::getDépôtPrincipalPourDropFournisseur($pdo, $productCode);
			//echo "Recherche dépot principal pour fournisseur DROP : ".$dep;
			if($dep) $DépotPrincipal = $dep;
		}
		$datas['D7RFARFO']      = self::getSuppliers($pdo, $DépotPrincipal , $productCode);
		$datas['A3GESPVP'] 	    = self::getPrices($pdo, '' , $productCode);
		$datas['ACARTCAT']      = self::getPagesCatalogues($pdo,'',$productCode);		
		return $datas;
	}
	
		private static function getFilialeDatas(PDO $pdo, string $companyCode, string $productCode) :? array 
	{
		$datas                  = [];
		$asartsoc			    = ASARTSOC::getModelByCompanyProduct($pdo, $companyCode , $productCode);	
		$datas['ASARTSOC']	    = $asartsoc ? $asartsoc->toArrayLower() : null;
		$datas['C0LIBART']	    = C0LIBART::allLabelsFor($pdo, $companyCode, $productCode);
		$datas['K1ARTCP']       = self::getCompteComptable($pdo,$companyCode,$productCode);
		$datas['D7RFARFO']      = self::getSuppliers($pdo, $companyCode, $productCode);
		$datas['A3GESPVP'] 	    = self::getPrices($pdo, $companyCode, $productCode);
		$datas['ACARTCAT']      = self::getPagesCatalogues($pdo,$companyCode,$productCode);
		return $datas;
	}

	public static function getCompanyDatas(PDO $pdo, string $companyCode, string $productCode) :?array 
	{
		$start = microtime(true);	
		$datas = [];		
		switch($companyCode) {
			case '15':
				$datas =  self::getFloVendingDatas($pdo,$productCode);
				break;
			case '00':
			case '06':
			case '38':
			case '40':
				$datas = self::getMBIDatas($pdo,$productCode);
				break;
			case '54':
				$datas = self::getVauconsantDatas($pdo,$productCode);
			default:
				$datas = self::getFilialeDatas($pdo, $companyCode, $productCode);
		}
		$datas['time'] = microtime(true) - $start;
		return $datas;
	}

	public static function getProductTextes(PDO $pdo, string $companyCode, string $productCode) :? array 
	{
		$textes = [];		
		$types = TTTXT::getModelsByType($pdo, $companyCode, 'ART');
		foreach($types as $tttyp) {
			$affichage = TTTXT::isDisplayable($pdo,$companyCode,$tttyp->tttype, $tttyp->tttypcmt); 
			$typeTTTXT = $tttyp->tttypcmt;
			//echo "Type de texte : " . $typeTTTXT . " Affichage ? : " . ( $affichage ? "oui" : "non" ) . "\n\r" ;			
			if(str_contains($typeTTTXT,"_")) {
				//$typeTTTXT = str_replace("_", "", $typeTTTXT);
				foreach(cst::cstLangues as $langue ) {
					$tttypcmt = $typeTTTXT.$langue;
					$txt = TTTXT::allModels($pdo, $companyCode, $productCode, 'ART', $tttypcmt);
					$datas = null;
					foreach($txt as $t) {
						$datas[] = $t->toArrayLower();
					}
					$textes[str_replace("_","",$typeTTTXT)][$langue] = [
						"afficher" => $affichage, 
						"datas" =>  $datas
					];					
				}
			} else {
				$tttypcmt = $typeTTTXT;
				$txt = TTTXT::allModels($pdo, $companyCode, $productCode, 'ART', $tttypcmt);
				$datas = null;
				foreach($txt as $t) {
					$datas[] = $t->toArrayLower();
				}
				$textes[$typeTTTXT] = [
					"afficher" => $affichage, 
					"datas" =>  $datas
				];
			}
		}
		return $textes;
	}

	public static function getPrixRouges(PDO $pdo, string $productCode): ? array 
	{
		$model = PXROUGE::getPrixRougeArticle($pdo,$productCode);
		if($model) return $model->toArrayLower();
		return null;
	}

	public static function getNouveauPrixRouges(PDO $pdo, string $productCode): ? array 
	{
		$model = PXNROUGE::getNouveauPrixRougeArticle($pdo,$productCode);
		if($model) return $model->toArrayLower();
		return null;
	}

	public static function getEcoPart(PDO $pdo, string $productCode): ? array 
	{
		$model = EAECOART::getEcoPartArticle($pdo,$productCode);
		if($model) return $model->toArrayLower();
		return null;
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
			$product['Company'] = [];
			if ($companyCode == '15') {
				// FloVending
				$product['Company'][$companyCode] = self::getCompanyDatas($pdo, '15', $productCode);
				$product['TTTXT'] = self::getProductTextes($pdo, '15'  , $productCode);
			} else if ($companyCode == '54') {
					// Vauconsant
					$product['Company'][$companyCode] = self::getCompanyDatas($pdo, '54', $productCode);
					$product['TTTXT'] = self::getProductTextes($pdo, '54'  , $productCode);
			} else {
				foreach( Company::all() as $comp ) {
					switch($comp['code']) {
						case '00':
							if (!isset($product['Company']['00'])) {
								$product['Company']['00'] = self::getCompanyDatas($pdo, '06', $productCode);
							}
							break;
						case '06':
						case '38':
						case '40':							
							break;
						case '15':
							break;
						case '54':
							break;
						default:
							$product['Company'][$comp['code']] = self::getCompanyDatas($pdo, $comp['code'], $productCode);
					}	
				}
			}
			$artnoweb											= ARTNOWEB::getByArticle($pdo,$productCode);
			$product['ARTNOWEB']								= ($artnoweb ? $artnoweb->toArrayLower() : null);
			$product['PXROUGE']									= self::getPrixRouges($pdo,$productCode);
			$product['PXNROUGE']								= self::getNouveauPrixRouges($pdo,$productCode);
			$product['EAECOART']								= self::getEcoPart($pdo, $productCode);
			$product['TTTXT']									= self::getProductTextes($pdo, '06'  , $productCode);
			$product['NOMENCLATURE_DIGITALE']					= Digital::getNomenclatureDigitale($pdo, $productCode);
			$product[cst::cstDatePublicationCatalogueDigital]	= Digital::getValeurAttribut($pdo,$productCode,cst::cstDatePublicationCatalogueDigital);
			$product[cst::cstCatégorieFonctionnelle]			= Digital::getValeurAttribut($pdo,$productCode,cst::cstCatégorieFonctionnelle);
			$product['APAVTPRD'][cst::cstArguCEA]				= Digital::getAvtPrd($pdo,$productCode, cst::cstArguCEA,1);
			$product['APAVTPRD'][cst::cstArguPrint]				= Digital::getAvtPrd($pdo,$productCode, cst::cstArguPrint,1);
			$product['APAVTPRD'][cst::cstArguLOT]				= Digital::getAvtPrd($pdo,$productCode, cst::cstArguLOT,1);
			$product['APAVTPRD'][cst::cstArguDesc]				= Digital::getAvtPrd($pdo,$productCode, cst::cstArguDesc);
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

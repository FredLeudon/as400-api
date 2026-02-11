<?php
declare(strict_types=1);

namespace App\Digital;

use App\Core\clFichier;
use PDO;
use Throwable;
use DateTimeImmutable;

use App\Core\Http;
use App\Core\cst;
use App\Domain\Company;
use App\Core\DbTable;
use App\Digital\APAVTPRD;
use App\Digital\NANOMART;
use App\Digital\TATXTATT;
use App\Digital\EVENSVAL;
use App\Digital\VUE_TATABATT;


final class Digital
{
	public static function getNomenclatureDigitale(PDO $pdo, string $productCode) :?array
	{
		$nanomart = NANOMART::getModelsByArticle($pdo, $productCode);
		if($nanomart) {
			$datas = [];
			foreach($nanomart as $na) {
				$datas[] = $na->toArrayLower();
			}
			return $datas;
		}
		return null;
	}

	public static function getAvtPrd(PDO $pdo, string $productCode, string $type, ? int $numordre = 0) : ? array
	{
		return APAVTPRD::getModelsByID($pdo, $type, $productCode, $numordre);
	}

	public static function getMedias(PDO $pdo, string $productCode, string $fileType, string $subType) : ?array
	{
		try {
			$productCode = trim($productCode);
			$fileType = trim($fileType);
			$subType = trim($subType);

			if ($productCode === '' || $fileType === '' || $subType === '') {
				return null;
			}

			$sql = "SELECT DISTINCT
						na_code_segment,
						na_code_famille,
						na_code_ssf,
						na_code_gamme,
						na_code_serie,
						na_code_modele,
						matis.tftabfic.tf_url,
						matis.nfnomfic.nf_num_ordre
					FROM matis.nanomart
					LEFT OUTER JOIN matis.nfnomfic
						ON na_code_segment = nf_segment
						AND na_code_famille = nf_famille
						AND na_code_ssf = nf_sous_famille
						AND na_code_gamme = nf_gamme
						AND na_code_serie = nf_serie
						AND na_code_modele = nf_modele
					LEFT OUTER JOIN matis.tftabfic
						ON nf_id_fic = tf_id_fic
						AND nf_code_type_fic = tf_code_type_fic
						AND nf_code_ss_type = tf_code_ss_type
					WHERE na_code_article = :product_code
						AND nf_code_type_fic = :file_type
						AND nf_code_ss_type = :sub_type
					ORDER BY
						na_code_segment,
						na_code_famille,
						na_code_ssf,
						na_code_gamme,
						na_code_serie,
						na_code_modele,
						nf_num_ordre";

			$stmt = $pdo->prepare($sql);
			$stmt->bindValue(':product_code', $productCode, PDO::PARAM_STR);
			$stmt->bindValue(':file_type', $fileType, PDO::PARAM_STR);
			$stmt->bindValue(':sub_type', $subType, PDO::PARAM_STR);
			$stmt->execute();

			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if (!$rows) {
				return null;
			}

			return array_map(
				static fn(array $row): array => array_change_key_case($row, CASE_LOWER),
				$rows
			);
		} catch (Throwable $e) {
			Http::respond(500, Http::exceptionPayload($e, __METHOD__));
		}
	}

	private static function buildKey(PDO $pdo, string $bib, string $fic, string $logique, string $variables, array $taVariables) : ? array
	{
		$logique = strtoupper(trim($logique));
		if ($logique === '') return null;
		// Récupère la définition d’index (liste ordonnée des rubriques) pour le fichier physique
		$dbTable  = new DbTable($bib, $fic, primaryKey: [], columns: []);
		$indexes  = $dbTable->indexes($pdo);
		$indexDef = $indexes[$logique] ?? null;
		if (!$indexDef) return null;
		// Associe chaque rubrique de la clé à une variable (constante) fournie dans $variables (séparées par des virgules)
		$varNames = array_values(array_filter(array_map('trim', explode(',', $variables)), fn($v) => $v !== ''));
		$fields   = $indexDef['fields'];
		$criteria = [];		
		for ($i = 0; $i < count($fields); $i++) {
			$field   = $fields[$i];
			$varName = $varNames[$i];
			// Le nom fourni peut être soit la clé de $taVariables (placeholder), soit le nom de constante cst::<X>
			$value   = $taVariables[$varName] ?? null;			
			if ($value === null) {
				//echo "Valeur de taVariables[$varName] non trouvée \n\r";
				continue;
			}
			$criteria[$field] = $value;
		}
		// Si on n'a pas pu renseigner toutes les rubriques de clé, on évite une recherche large
		//if (count($criteria) < count($fields)) return null;
		return $criteria;
	}

	private static function byVariante(PDO $pdo, array &$datas, clFichier $unAttributFichier, array $taVariables = []) 
	{
		$sKeys 				= $unAttributFichier->ta_cles;
		$tabKeys			= explode(",",$sKeys);
		if(in_array(cst::cstVarCodeLangue,$tabKeys,true)) {
			foreach(cst::gtabLangues as $Langue) {
				$taVariables[cst::cstVarCodeLangue] = $Langue;
					self::byLangue($pdo,$datas,$unAttributFichier,$taVariables);
				}
		} else {
			if(in_array(cst::cstVarTypeAccessoire,$tabKeys,true)) {
				foreach(cst::gtabTypesAccessoires as $Accessoire) {
					$taVariables[cst::cstVarTypeAccessoire] = $Accessoire;
					self::byTypeAccessoire($pdo,$datas,$unAttributFichier,$taVariables);
				}
			} else {
				self::byDéfaut($pdo,$datas,$unAttributFichier,$taVariables);
			}	
		}			
	}
	
	private static function byLangue(PDO $pdo, array &$datas, clFichier $unAttributFichier, array $taVariables = []) 
	{
		$sKeys 				= $unAttributFichier->ta_cles;
		$tabKeys			= explode(",",$sKeys);
		if(in_array(cst::cstVarTypeAccessoire,$tabKeys,true)) {
			foreach(cst::gtabTypesAccessoires as $Accessoire) {
				$taVariables[cst::cstVarTypeAccessoire] = $Accessoire;
				self::byTypeAccessoire($pdo,$datas,$unAttributFichier,$taVariables);
			}
		} else {
			self::byDéfaut($pdo,$datas,$unAttributFichier,$taVariables);
		}	
	}

	private static function byTypeAccessoire(PDO $pdo, array &$datas, clFichier $unAttributFichier, array $taVariables = []) 
	{
		self::byDéfaut($pdo,$datas,$unAttributFichier,$taVariables);
	}

	private static function byDéfaut(PDO $pdo, array &$datas, clFichier $unAttributFichier, array $taVariables = []) 
	{
		$sBibliothèque		= $unAttributFichier->ta_bibliotheque;
		$sFichier			= $unAttributFichier->ta_Fichier;
		$sLogique			= $unAttributFichier->ta_logique;
		$sKeys 				= $unAttributFichier->ta_cles;
		$tabKeys			= explode(",",$sKeys);
		$bTraiterMulti		= (in_array(cst::cstVarNumOrdre, $tabKeys));
		$Attributs			= explode(',',$unAttributFichier->ta_liste_attributs);			
		$nMaxIndice 		= 0;
		//echo "by Défaut $sBibliothèque, $sFichier,$sLogique, $sKeys \n";
		if(!$bTraiterMulti) {
			$nMaxIndice = $unAttributFichier->ta_nb_max_val;
			if ($nMaxIndice == 0) {
				if ($unAttributFichier->ta_multi_valeur == 1) {
					// ta_nb_max_val_type stocke un JSON de la forme {"%type_accessoire%":{"CD":10,"CP":10,"PD":30,"OP":30}}
					$nbMaxTypeJson = $unAttributFichier->ta_nb_max_val_type;
					$decoded = json_decode((string)$nbMaxTypeJson, true);
					if (is_array($decoded)) {
						foreach ($decoded as $varPlaceholder => $map) {
							$varValue = $taVariables[$varPlaceholder] ?? null;
							if ($varValue === null || !is_array($map)) continue;
							if (isset($map[$varValue]) && is_numeric($map[$varValue])) {
								$nMaxIndice = (int)$map[$varValue];
								break;
							}
						}
					}
					if ($nMaxIndice == 0) {
						$nMaxIndice = 10; // fallback si non trouvé
					}
				} else {
					$nMaxIndice = 1;
				}
			}
		}
		if ($nMaxIndice == 0) $nMaxIndice = 1;	
		if(!$bTraiterMulti) $nMaxIndice = 1;		
		for($nIndice = 0 ; $nIndice < $nMaxIndice; $nIndice++) {
			if(in_array(cst::cstVarCodeAttribut,$tabKeys,true)) {						
				foreach($Attributs as $unAttribut) {
					$sKey									= "";
					$taVariables[cst::cstVarCodeAttribut]	= $unAttribut;					
					$criteria								= self::buildKey($pdo, $sBibliothèque, $sFichier, $sLogique, $sKeys, $taVariables );
					foreach($criteria as $crit => $val) {
						if($crit === cst::cstVarNumOrdre) continue;	// ignore order index in composite key
						$sKey								= $sKey . (strlen($sKey) <> 0 ? ',' : '') . (string)$val ;
					}
					//$datas[$sFichier][$sKey]				= $tabAttributRubrique;
					$taVariables[cst::cstVarNumOrdre]		= $nIndice + 1;								
					$criteria								= self::buildKey($pdo, $sBibliothèque, $sFichier, $sLogique, $sKeys, $taVariables );
					$dbTable  								= new DbTable($sBibliothèque, $sFichier, primaryKey: [], columns: []);
					// Lecture du fichier avec la condition construite
					$rows = $criteria ? $dbTable->listWhere($pdo, $criteria, [], null, ['*']) : null;					
					if($rows) {
						foreach($rows as $numRow => $row) {
							$datas[$sFichier][$sKey][]	= $row;							
						}
					} else {
						$datas[$sFichier][$sKey][]		= null;
					}									
				} 
			}else {
				$sKey									= "";				
				$criteria								= self::buildKey($pdo, $sBibliothèque, $sFichier, $sLogique, $sKeys, $taVariables );
				if($criteria) {
					foreach($criteria as $crit => $val) {
						if($crit === cst::cstVarNumOrdre) continue;	// ignore order index in composite key
						$sKey								= $sKey . (strlen($sKey) <> 0 ? ',' : '') . (string)$val ;
					}
					//$datas[$sFichier][$sKey]				= $tabAttributRubrique;
					$taVariables[cst::cstVarNumOrdre]		= $nIndice + 1;								
					$criteria								= self::buildKey($pdo, $sBibliothèque, $sFichier, $sLogique, $sKeys, $taVariables );
					$dbTable  								= new DbTable($sBibliothèque, $sFichier, primaryKey: [], columns: []);
					// Lecture du fichier avec la condition construite
					$rows = $criteria ? $dbTable->listWhere($pdo, $criteria, [], null, ['*']) : null;					
					if($rows) {
						foreach($rows as $numRow => $row) {
							$datas[$sFichier][$sKey][]	= $row;							
						}
					} else {
						$datas[$sFichier][$sKey][]		= null;
					}		
				} else {
					//echo "Erreur crit $sBibliothèque, $sFichier,$sLogique, $sKeys \n";
				}
			}
		}
	}

	public static function getValeurAttribut(PDO $pdo, string $productCode, string $attribut, ?int $indice = 1) {
		$datas = self::lireAttributs($pdo,$productCode,$attribut);
		$defAttributs = TATABATT::getByAttribute($pdo,'',$attribut);
		foreach($defAttributs as $defAttribut) {
			$sBibliothèque		= $defAttribut->ta_bibliotheque;
			$sFichier			= $defAttribut->ta_Fichier;
			$sLogique			= $defAttribut->ta_logique;
			$sKeys 				= $defAttribut->ta_cles;
	
			$taVariables[cst::cstVarCodeArticle]	=	$productCode;
			if(array_key_exists($sFichier,$datas)) {
				$sKey									= "";				
				$criteria								= self::buildKey($pdo, $sBibliothèque, $sFichier, $sLogique, $sKeys, $taVariables );
				if($criteria) {
					foreach($criteria as $crit => $val) {
						if($crit === cst::cstVarNumOrdre) continue;	// ignore order index in composite key
						$sKey								= $sKey . (strlen($sKey) <> 0 ? ',' : '') . (string)$val ;
					}
				}
				$ind = $indice -1;
				// trouver le nom court/long
				$dbTable  								= new DbTable($sBibliothèque, $sFichier, primaryKey: [], columns: []);
				$mapping								= $dbTable->loadFieldMetadata($pdo);
				$rubrique								= $mapping[$defAttribut->ta_zone]['long'];		
				if(array_key_exists($sKey,$datas[$defAttribut->ta_fichier])) {		
					if(array_key_exists($ind,$datas[$defAttribut->ta_fichier][$sKey])) {		
						return $datas[$defAttribut->ta_fichier][$sKey][$ind][$rubrique];
					}
				}
			}
		}
		return null;
	}

	public static function lireAttributs(PDO $pdo, string $productCode, ? string $attribut = '') : ?array
	{
		$start = microtime(true);	
		$datas = [];		
		// Aller lire la vue tatabatt pour récuperer les attributs "fichier"
		$AttributsFichier = VUE_TATABATT::getAttributes($pdo, 'STANDARD', $attribut);
		if (!empty($AttributsFichier)) {
			foreach($AttributsFichier as $unAttributFichier) {
				$taVariables = null;
				$taVariables[cst::cstVarCodeArticle] = $productCode;
				// Pour chaque 'fichier', lire les attribut 			
				$sKeys 				= $unAttributFichier->ta_cles;
				$tabKeys			= explode(",",$sKeys);
				if(in_array(cst::cstVarVarianteCommerciale,$tabKeys,true)) {
					foreach(cst::gtabVariantesCommerciales as $Variante) {
						$taVariables[cst::cstVarVarianteCommerciale] = $Variante;
						self::byVariante($pdo,$datas,$unAttributFichier,$taVariables);
					}
				} else {
					if(in_array(cst::cstVarCodeLangue,$tabKeys,true)) {
						foreach(cst::gtabLangues as $Langue) {
							$taVariables[cst::cstVarCodeLangue] = $Langue;
							self::byLangue($pdo,$datas,$unAttributFichier,$taVariables);
						}
					} else {
						if(in_array(cst::cstVarTypeAccessoire,$tabKeys,true)) {
							foreach(cst::gtabTypesAccessoires as $Accessoire) {
								$taVariables[cst::cstVarTypeAccessoire] = $Accessoire;
								self::byTypeAccessoire($pdo,$datas,$unAttributFichier,$taVariables);
							}
						} else {
							self::byDéfaut($pdo,$datas,$unAttributFichier,$taVariables);
						}	
					}	
				}

			}
		}
		$datas['time'] = microtime(true) - $start;
		return $datas;
	}

	public static function getDefAttributsFichier(PDO $pdo, ?string $mode = ''): ? array
	{
		$datas = [];
		$tatabatt = VUE_TATABATT::getAttributes($pdo,$mode);
		foreach($tatabatt as $model) {
			$datas[$model->ta_fichier][] = $model->toArrayLower();
		}
		return $datas;
	}

	public static function getDefAttributes(PDO $pdo, string $mode, ?string $attribut = ''): ?array
	{
		$tabNbEvEnsVal = EVENSVAL::getNbEnsVal($pdo);		
		$tatabatt = TATABATT::getByAttribute($pdo,$mode, $attribut);
		$data = [];
		$nb = 0 ;
		foreach($tatabatt as $model) {
			$nb++;
			$data[$model->ta_code_attribut] = $model->toArrayLower();
			$data[$model->ta_code_attribut]['groupe'] = null;
			$data[$model->ta_code_attribut]['famille'] = null;
			$data[$model->ta_code_attribut]['sousfamille'] = null;
			$data[$model->ta_code_attribut]['TATXTATT'] = TATXTATT::getLibelle($pdo,$model->ta_code_attribut);
			$groupe = TAFAM::getElement($pdo,(int)$model->ta_groupe);			
			$famille = TAFAM::getElement($pdo,(int)$model->ta_groupe, (int)$model->ta_famille);
			$sousfamille = TAFAM::getElement($pdo,(int)$model->ta_groupe, (int)$model->ta_famille,(int)$model->ta_sous_famille);
			if($groupe) $data[$model->ta_code_attribut]['groupe'] = $groupe->toArrayLower();
			if($famille) $data[$model->ta_code_attribut]['famille'] = $famille->toArrayLower();
			if($sousfamille && $model->ta_sous_famille <> 0 ) $data[$model->ta_code_attribut]['sousfamille'] = $sousfamille->toArrayLower();
			if($model->ta_type_attribut !== '') {
				$nbEnsVal = 0;
				if(array_key_exists($model->ta_type_attribut,$tabNbEvEnsVal)) $nbEnsVal = $tabNbEvEnsVal[$model->ta_type_attribut];
				switch($nbEnsVal) {
					case 0:
						$data[$model->ta_code_attribut]["type_valeur"]		= cst::cstValeurSimble;
						$data[$model->ta_code_attribut]["EVENSVAL"]			= null;
						break;
					case 1:
						$data[$model->ta_code_attribut]["type_valeur"]		= cst::cstValeurComplexe;
						$data[$model->ta_code_attribut]["EVENSVAL"]			= EVENSVAL::getEVENSVAL($pdo,$model->ta_type_attribut);
						break;
					default:
						$data[$model->ta_code_attribut]["type_valeur"]		= cst::cstEnsenbleDeValeur;
						$data[$model->ta_code_attribut]["EVENSVAL"]			= EVENSVAL::getEVENSVAL($pdo,$model->ta_type_attribut);
						break;
				}
			}
		}
		$data['nombre'] = $nb;
		//var_dump($data);
		return $data;
	}

}

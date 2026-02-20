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
use App\Digital\LNLIBNOM;
use App\Digital\NANOMART;
use App\Digital\TATXTATT;
use App\Digital\EVENSVAL;
use App\Digital\VUE_TATABATT;


final class Digital
{
	/** @var array<string,array<string,mixed>|null> */
	private static array $attributeDefinitionCache = [];
	private static bool $attributeUiStyleInjected = false;

	public static function divUnAttribut(array $defAttribut, array $mapping, ?array $row): ?string
	{
		$fra = $defAttribut['TATXTATT']['FRA'] ?? [];

		$codeAttr = $defAttribut['ta_code_attribut'] ?? '';
		[$zone, $rawValue, $hasValue] = self::resolveZoneAndRawValue($defAttribut, $mapping, $row);

		$libelle    = $fra['tx_libelle'] ?? $codeAttr;
		$indication = $fra['tx_texte_indication'] ?? '';
		$bulle      = $fra['tx_texte_bulle'] ?? '';
		$val        = $hasValue ? (string)$rawValue : '';

		$inputType = 'text';
		if (($defAttribut['ta_est_numerique'] ?? '0') === '1') {
    		$inputType = 'number';
		}

		$html  = self::attributeUiStyleBlock();
		$html .= '<div class="attr"';
		$html .= ' id="attr_' . htmlspecialchars($codeAttr, ENT_QUOTES) . '"';
		$html .= ' data-attr-code="' . htmlspecialchars($codeAttr, ENT_QUOTES) . '"';
		$html .= ' data-attr-zone="' . htmlspecialchars($zone, ENT_QUOTES) . '"';
		$html .= ' data-attr-type="' . htmlspecialchars($defAttribut['ta_type_attribut'] ?? '', ENT_QUOTES) . '"';
		$html .= ' data-attr-multivaleur="' . htmlspecialchars($defAttribut['ta_est_multivaleur'] ?? '0', ENT_QUOTES) . '"';
		$html .= '>';
		$html .= '<div class="attr-text">';
		$html .= '<div class="attr-label" id="attr_label_' . htmlspecialchars($codeAttr, ENT_QUOTES) . '">';
		$html .= htmlspecialchars($libelle);
		$html .= '</div>';
		$html .= '<div class="attr-hint" id="attr_hint_' . htmlspecialchars($codeAttr, ENT_QUOTES) . '">';
		$html .= htmlspecialchars($indication);
		$html .= '</div>';
		$html .= '<div class="attr-code" id="attr_code_' . htmlspecialchars($codeAttr, ENT_QUOTES) . '">';
		$html .= htmlspecialchars($codeAttr);
		$html .= '</div>';
		$html .= '</div>';
		$html .= '<div class="attr-input-zone">';
		$html .= '<input class="attr-input"';
		$html .= ' id="attr_input_' . htmlspecialchars($codeAttr, ENT_QUOTES) . '"';
		$html .= ' name="' . htmlspecialchars($zone, ENT_QUOTES) . '"';
		$html .= ' type="' . $inputType . '"';
		$html .= ' value="' . htmlspecialchars($val, ENT_QUOTES) . '"';
		$html .= ' title="' . htmlspecialchars($bulle, ENT_QUOTES) . '"';
		$html .= ' data-wd-attr="' . htmlspecialchars($codeAttr, ENT_QUOTES) . '"';
		$html .= ' data-wd-zone="' . htmlspecialchars($zone, ENT_QUOTES) . '"';
		$html .= ' />';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	public static function divUnAttributOuiNon(array $defAttribut, array $mapping, ?array $row): ?string
	{
		$fra = $defAttribut['TATXTATT']['FRA'] ?? [];

		$codeAttr = $defAttribut['ta_code_attribut'] ?? '';
		[$zone, $rawValue, $hasValue] = self::resolveZoneAndRawValue($defAttribut, $mapping, $row);

		$libelle    = $fra['tx_libelle'] ?? $codeAttr;
		$indication = $fra['tx_texte_indication'] ?? '';
		$bulle      = $fra['tx_texte_bulle'] ?? '';

		$state = self::normalizeOuiNonState($rawValue, $hasValue);
		$trueValue = (($defAttribut['ta_est_numerique'] ?? '0') === '1') ? '1' : 'O';
		$falseValue = (($defAttribut['ta_est_numerique'] ?? '0') === '1') ? '0' : 'N';

		$switchId = 'attr_input_' . $codeAttr;
		$hiddenId = 'attr_hidden_' . $codeAttr;
		$initialPosition = 1;
		if ($state === 'yes') {
			$initialPosition = 2;
		} elseif ($state === 'no') {
			$initialPosition = 0;
		} elseif ($hasValue) {
			$initialPosition = 0;
		}
		$lockUndefinedInitially = $hasValue;

		$hiddenValue = '';
		if ($initialPosition === 2) {
			$hiddenValue = $trueValue;
		} elseif ($initialPosition === 0) {
			$hiddenValue = $falseValue;
		}

		$initialState = 'undefined';
		if ($initialPosition === 2) {
			$initialState = 'yes';
		} elseif ($initialPosition === 0) {
			$initialState = 'no';
		}

		$switchIdJs = json_encode($switchId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
		$hiddenIdJs = json_encode($hiddenId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
		$trueValueJs = json_encode($trueValue, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
		$falseValueJs = json_encode($falseValue, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
		$initialPositionJs = (string)$initialPosition;
		$lockUndefinedInitiallyJs = $lockUndefinedInitially ? 'true' : 'false';
		$ariaChecked = 'mixed';
		if ($initialPosition === 2) {
			$ariaChecked = 'true';
		} elseif ($initialPosition === 0) {
			$ariaChecked = 'false';
		}

		$html  = self::attributeUiStyleBlock();
		$html .= '<div class="attr"';
		$html .= ' id="attr_' . htmlspecialchars($codeAttr, ENT_QUOTES) . '"';
		$html .= ' data-attr-code="' . htmlspecialchars($codeAttr, ENT_QUOTES) . '"';
		$html .= ' data-attr-zone="' . htmlspecialchars($zone, ENT_QUOTES) . '"';
		$html .= ' data-attr-type="' . htmlspecialchars($defAttribut['ta_type_attribut'] ?? '', ENT_QUOTES) . '"';
		$html .= ' data-attr-multivaleur="' . htmlspecialchars($defAttribut['ta_est_multivaleur'] ?? '0', ENT_QUOTES) . '"';
		$html .= '>';
		$html .= '<div class="attr-text">';
		$html .= '<div class="attr-label" id="attr_label_' . htmlspecialchars($codeAttr, ENT_QUOTES) . '">';
		$html .= htmlspecialchars($libelle);
		$html .= '</div>';
		$html .= '<div class="attr-hint" id="attr_hint_' . htmlspecialchars($codeAttr, ENT_QUOTES) . '">';
		$html .= htmlspecialchars($indication);
		$html .= '</div>';
		$html .= '<div class="attr-code" id="attr_code_' . htmlspecialchars($codeAttr, ENT_QUOTES) . '">';
		$html .= htmlspecialchars($codeAttr);
		$html .= '</div>';
		$html .= '</div>';
		$html .= '<input type="hidden"';
		$html .= ' id="' . htmlspecialchars($hiddenId, ENT_QUOTES) . '"';
		$html .= ' name="' . htmlspecialchars($zone, ENT_QUOTES) . '"';
		$html .= ' value="' . htmlspecialchars($hiddenValue, ENT_QUOTES) . '"';
		$html .= ' data-wd-attr="' . htmlspecialchars($codeAttr, ENT_QUOTES) . '"';
		$html .= ' data-wd-zone="' . htmlspecialchars($zone, ENT_QUOTES) . '"';
		$html .= ' />';
		$html .= '<div class="attr-input-zone">';
		$html .= '<button class="attr-input attr-input-ouinon-switch"';
		$html .= ' id="' . htmlspecialchars($switchId, ENT_QUOTES) . '"';
		$html .= ' type="button"';
		$html .= ' role="checkbox"';
		$html .= ' aria-checked="' . htmlspecialchars($ariaChecked, ENT_QUOTES) . '"';
		$html .= ' aria-label="' . htmlspecialchars($libelle, ENT_QUOTES) . '"';
		$html .= ' title="' . htmlspecialchars($bulle, ENT_QUOTES) . '"';
		$html .= ' data-wd-attr="' . htmlspecialchars($codeAttr, ENT_QUOTES) . '"';
		$html .= ' data-wd-zone="' . htmlspecialchars($zone, ENT_QUOTES) . '"';
		$html .= ' data-state="' . htmlspecialchars($initialState, ENT_QUOTES) . '"';
		$html .= '><span class="attr-input-ouinon-knob"></span></button>';
		$html .= '</div>';
		$html .= '<script>(function(){';
		$html .= 'const sw=document.getElementById(' . $switchIdJs . ');';
		$html .= 'const hidden=document.getElementById(' . $hiddenIdJs . ');';
		$html .= 'if(!sw||!hidden){return;}';
		$html .= 'const trueValue=' . $trueValueJs . ';';
		$html .= 'const falseValue=' . $falseValueJs . ';';
		$html .= 'let lockUndefined=' . $lockUndefinedInitiallyJs . ';';
		$html .= 'let currentPos=' . $initialPositionJs . ';';
		$html .= 'const posToState=function(pos){return (pos===2)?"yes":((pos===0)?"no":"undefined");};';
		$html .= 'const applyPos=function(pos){';
		$html .= 'currentPos=pos;';
		$html .= 'if(!lockUndefined&&pos!==1){lockUndefined=true;}';
		$html .= 'const state=posToState(pos);';
		$html .= 'sw.dataset.state=state;';
		$html .= 'hidden.value=(state==="yes")?trueValue:((state==="no")?falseValue:"");';
		$html .= 'sw.classList.toggle("is-off",pos===0);';
		$html .= 'sw.classList.toggle("is-undef",pos===1);';
		$html .= 'sw.classList.toggle("is-on",pos===2);';
		$html .= 'sw.setAttribute("aria-checked",(state==="undefined")?"mixed":((state==="yes")?"true":"false"));';
		$html .= '};';
		$html .= 'const nextPos=function(){';
		$html .= 'if(lockUndefined){return currentPos===0?2:0;}';
		$html .= 'if(currentPos===0){return 1;}';
		$html .= 'if(currentPos===1){return 2;}';
		$html .= 'return 0;';
		$html .= '};';
		$html .= 'applyPos(currentPos);';
		$html .= 'sw.addEventListener("click",function(){applyPos(nextPos());});';
		$html .= 'sw.addEventListener("keydown",function(event){';
		$html .= 'if(event.key===" "||event.key==="Enter"){event.preventDefault();applyPos(nextPos());return;}';
		$html .= 'if(event.key==="ArrowLeft"||event.key==="ArrowDown"){event.preventDefault();applyPos(0);}';
		$html .= 'if(event.key==="ArrowRight"||event.key==="ArrowUp"){event.preventDefault();applyPos(2);}';
		$html .= '});';
		$html .= '})();</script>';
		$html .= '</div>';

		return $html;
	}

	public static function divUnAttributDate(array $defAttribut, array $mapping, ?array $row): ?string
	{
		$fra = $defAttribut['TATXTATT']['FRA'] ?? [];

		$codeAttr = $defAttribut['ta_code_attribut'] ?? '';
		[$zone, $rawValue, $hasValue] = self::resolveZoneAndRawValue($defAttribut, $mapping, $row);

		$libelle    = $fra['tx_libelle'] ?? $codeAttr;
		$indication = $fra['tx_texte_indication'] ?? '';
		$bulle      = $fra['tx_texte_bulle'] ?? '';
		$val        = self::normalizeDateForInput($rawValue, $hasValue);

		$html  = self::attributeUiStyleBlock();
		$html .= '<div class="attr"';
		$html .= ' id="attr_' . htmlspecialchars($codeAttr, ENT_QUOTES) . '"';
		$html .= ' data-attr-code="' . htmlspecialchars($codeAttr, ENT_QUOTES) . '"';
		$html .= ' data-attr-zone="' . htmlspecialchars($zone, ENT_QUOTES) . '"';
		$html .= ' data-attr-type="' . htmlspecialchars($defAttribut['ta_type_attribut'] ?? '', ENT_QUOTES) . '"';
		$html .= ' data-attr-multivaleur="' . htmlspecialchars($defAttribut['ta_est_multivaleur'] ?? '0', ENT_QUOTES) . '"';
		$html .= '>';
		$html .= '<div class="attr-text">';
		$html .= '<div class="attr-label" id="attr_label_' . htmlspecialchars($codeAttr, ENT_QUOTES) . '">';
		$html .= htmlspecialchars($libelle);
		$html .= '</div>';
		$html .= '<div class="attr-hint" id="attr_hint_' . htmlspecialchars($codeAttr, ENT_QUOTES) . '">';
		$html .= htmlspecialchars($indication);
		$html .= '</div>';
		$html .= '<div class="attr-code" id="attr_code_' . htmlspecialchars($codeAttr, ENT_QUOTES) . '">';
		$html .= htmlspecialchars($codeAttr);
		$html .= '</div>';
		$html .= '</div>';
		$html .= '<div class="attr-input-zone">';
		$html .= '<input class="attr-input attr-input-date"';
		$html .= ' id="attr_input_' . htmlspecialchars($codeAttr, ENT_QUOTES) . '"';
		$html .= ' name="' . htmlspecialchars($zone, ENT_QUOTES) . '"';
		$html .= ' type="date"';
		$html .= ' value="' . htmlspecialchars($val, ENT_QUOTES) . '"';
		$html .= ' title="' . htmlspecialchars($bulle, ENT_QUOTES) . '"';
		$html .= ' data-wd-attr="' . htmlspecialchars($codeAttr, ENT_QUOTES) . '"';
		$html .= ' data-wd-zone="' . htmlspecialchars($zone, ENT_QUOTES) . '"';
		$html .= ' />';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	public static function unAttributDate(array $defAttribut, array $mapping, ?array $row): ?string
	{
		return self::divUnAttributDate($defAttribut, $mapping, $row);
	}

	private static function attributeUiStyleBlock(): string
	{
		if (self::$attributeUiStyleInjected) {
			return '';
		}
		self::$attributeUiStyleInjected = true;

		$html = '<style>';
		$html .= '.attr{display:grid;grid-template-columns:minmax(0,75%) minmax(0,25%);column-gap:8px;align-items:stretch;min-height:58px;padding:6px 8px;box-sizing:border-box;}';
		$html .= '.attr-text{min-width:0;display:flex;flex-direction:column;height:100%;overflow:hidden;}';
		$html .= '.attr-label{font-size:.86rem;line-height:1.1;font-weight:600;margin:0;}';
		$html .= '.attr-hint{flex:1;display:flex;align-items:center;font-size:.77rem;line-height:1.15;color:#5f6b7a;overflow:hidden;}';
		$html .= '.attr-code{font-size:.61rem;line-height:1;color:#8f98a6;margin-top:2px;letter-spacing:.02em;}';
		$html .= '.attr-input-zone{display:flex;align-items:center;justify-content:center;}';
		$html .= '.attr-input-zone .attr-input:not(.attr-input-ouinon-switch){width:90%;max-width:100%;}';
		$html .= '.attr-input-ouinon-switch{position:relative;display:inline-block;width:54px;height:30px;padding:0;border:0;border-radius:999px;cursor:pointer;transition:background-color .2s ease;vertical-align:middle;}';
		$html .= '.attr-input-ouinon-switch .attr-input-ouinon-knob{position:absolute;top:3px;left:3px;width:24px;height:24px;border-radius:50%;background:#fff;box-shadow:0 1px 3px rgba(0,0,0,.35);transition:transform .2s ease;}';
		$html .= '.attr-input-ouinon-switch.is-off{background:#f97373;}';
		$html .= '.attr-input-ouinon-switch.is-undef{background:#cbd5e1;}';
		$html .= '.attr-input-ouinon-switch.is-on{background:#34c759;}';
		$html .= '.attr-input-ouinon-switch.is-off .attr-input-ouinon-knob{transform:translateX(0);}';
		$html .= '.attr-input-ouinon-switch.is-undef .attr-input-ouinon-knob{transform:translateX(11px);}';
		$html .= '.attr-input-ouinon-switch.is-on .attr-input-ouinon-knob{transform:translateX(22px);}';
		$html .= '.attr-input-ouinon-switch:focus-visible{outline:2px solid #0f172a;outline-offset:2px;}';
		$html .= '</style>';

		return $html;
	}

	private static function resolveZoneAndRawValue(array $defAttribut, array $mapping, ?array $row): array
	{
		$zone = (string)($defAttribut['ta_zone'] ?? '');

		if ($row === null || $zone === '') {
			return [$zone, null, false];
		}

		if (!array_key_exists($zone, $row)) {
			$zoneMapped = $mapping[$zone] ?? null;
			if (is_array($zoneMapped) && isset($zoneMapped['long'])) {
				$mappedZone = (string)$zoneMapped['long'];
				if ($mappedZone !== '' && array_key_exists($mappedZone, $row)) {
					$zone = $mappedZone;
				}
			}
		}

		if (!array_key_exists($zone, $row)) {
			return [$zone, null, false];
		}

		return [$zone, $row[$zone], true];
	}

	private static function normalizeOuiNonState(mixed $rawValue, bool $hasValue): string
	{
		if (!$hasValue) {
			return 'undefined';
		}

		if (is_bool($rawValue)) {
			return $rawValue ? 'yes' : 'no';
		}

		$value = strtoupper(trim((string)$rawValue));
		if ($value === '') {
			return 'undefined';
		}

		$yesValues = ['1', 'O', 'OUI', 'Y', 'YES', 'TRUE', 'T', 'ON'];
		if (in_array($value, $yesValues, true)) {
			return 'yes';
		}

		$noValues = ['0', 'N', 'NON', 'NO', 'FALSE', 'F', 'OFF'];
		if (in_array($value, $noValues, true)) {
			return 'no';
		}

		return 'undefined';
	}

	private static function normalizeDateForInput(mixed $rawValue, bool $hasValue): string
	{
		if (!$hasValue || $rawValue === null) {
			return '';
		}

		if ($rawValue instanceof \DateTimeInterface) {
			return $rawValue->format('Y-m-d');
		}

		$value = trim((string)$rawValue);
		if ($value === '') {
			return '';
		}

		$formats = ['!Y-m-d', '!Ymd', '!Y/m/d', '!d/m/Y', '!d-m-Y', '!Y-m-d H:i:s', '!Y-m-d H:i'];
		foreach ($formats as $format) {
			$date = DateTimeImmutable::createFromFormat($format, $value);
			if ($date === false) {
				continue;
			}

			$errors = DateTimeImmutable::getLastErrors();
			$warningCount = is_array($errors) ? (int)($errors['warning_count'] ?? 0) : 0;
			$errorCount = is_array($errors) ? (int)($errors['error_count'] ?? 0) : 0;
			if ($warningCount === 0 && $errorCount === 0) {
				return $date->format('Y-m-d');
			}
		}

		return '';
	}
	public static function buildHtml(PDO $pdo, array $attributs, array $mapping, ?array $row): ?string
	{
		// Récuperer la définition de l'attribut		
		//echo "Je traite les attributs (".var_dump($attributs).") pour (".var_dump($row).")";
		$html = "";
		$bTraité = false;
		// Traiter les cas à la con
		if (array_key_exists('DIM_REFLEX', $attributs)) {
			// DIM_REFLEX ....							
		} else if (array_key_exists('QTE_VCM1_VC01', $attributs)) {
			// QTE_VCM1_VC01
		} else if (array_key_exists('MATIERE_COM', $attributs)) {
			// MATIERE_COM						
		} else {					
			foreach($attributs as $attribut) {
				$defAttribut = null;
				if (array_key_exists($attribut,self::$attributeDefinitionCache)) {
					$defAttribut = self::$attributeDefinitionCache[$attribut];
				} else {
					$defAttribut = self::getDefAttributs($pdo,cst::cstAttributStandard,$attribut);
				}
				if ($defAttribut) {
					if(array_key_exists($attribut, $defAttribut)) {
						$defAttribut = $defAttribut[$attribut];
						switch(strtoupper($defAttribut['ta_mode_gestion'])){							
							case cst::cstAttributStandard;							
								$Combo = ($defAttribut['ta_fichier_lie'] =='1' || $defAttribut['type_valeur'] == 'ENSVAL' );
								// Ok, c'est un attribut 'standard', on va encore extraire les cas tordus
								switch(strtoupper($attribut)) {
									case 'CODE_ACCESSOIRE':
										break;
									case 'CAPACITE_TYPE':
										break;
									case 'CAPACITE_NB':
										break;
									default:
										// pour se concentrer sur les attributs dits 'simples' et pas déconnants
										if($Combo ) {
											// combo
										} else {
											// pas combo
											switch(strtolower($defAttribut['ta_type_attribut'])){
												case 'booleen':
												case 'interrupteur':
												case 'ouinon':
													$html .= self::divUnAttributOuiNon($defAttribut,$mapping, $row);
													break;
												case 'date':
													$html .= self::divUnAttributDate($defAttribut,$mapping, $row);
													break;													
												default:
													$html .= self::divUnAttribut($defAttribut,$mapping, $row);
													break;
											}
										}
										break;
								}		
								break;
							case cst::cstAttributSpécif:
								// Gérer les attributs spécifs non encore traités par les spécificités ci-dessus
								switch(strtoupper($attribut)) {
									case 'COMMENTAIRE_PRD':
										break;									
									case 'ARG_MEA_CATA_PRINT':
										break;
									case 'EMPL_NUM_LOT':
										break;
									case 'AVANTAGE_PRD':
										break;
									default:
										$html = $html . "<div id='$attribut'>";
										$html = $html . "attribut spécifique non traité !";
										$html = $html . "</div>";				
										break;
								}
								break;
						}
					}
				} else {
					$html = $html . "<div id='$attribut'>";
					$html = $html . "attribut inconnu !";
					$html = $html . "</div>";
				}				
			}			
		}
		return $html;
	}

	public static function getNomenclatureDigitale(PDO $pdo, string $productCode) :?array
	{
		$nanomart = NANOMART::getModelsByArticle($pdo, $productCode);
		if($nanomart) {
			$datas = [];
			foreach($nanomart as $na) {				
				$data["NANOMART"]					= $na->toArrayLower();				
				$data["LNLIBNOM"]["segment"]		= LNLIBNOM::DonneLibelléNomenclatureLangue($pdo,[$na->na_code_segment]);
				$data["LNLIBNOM"]["famille"]		= LNLIBNOM::DonneLibelléNomenclatureLangue($pdo,[$na->na_code_segment,$na->na_code_famille]);
				$data["LNLIBNOM"]["categorie"]		= NCNOMCAT::DonneLibelléCatégorie($pdo,[$na->na_code_segment,$na->na_code_famille, $na->na_code_ssf]); 		
				$data["LNLIBNOM"]["sous_famille"]	= LNLIBNOM::DonneLibelléNomenclatureLangue($pdo,[$na->na_code_segment,$na->na_code_famille, $na->na_code_ssf]);
				$data["LNLIBNOM"]["gamme"]			= LNLIBNOM::DonneLibelléNomenclatureLangue($pdo,[$na->na_code_segment,$na->na_code_famille, $na->na_code_ssf, $na->na_code_gamme]);
				$data["LNLIBNOM"]["serie"]			= LNLIBNOM::DonneLibelléNomenclatureLangue($pdo,[$na->na_code_segment,$na->na_code_famille, $na->na_code_ssf, $na->na_code_gamme, $na->na_code_serie]);
				$data["LNLIBNOM"]["modele"]			= LNLIBNOM::DonneLibelléNomenclatureLangue($pdo,[$na->na_code_segment,$na->na_code_famille, $na->na_code_ssf, $na->na_code_gamme, $na->na_code_serie, $na->na_code_modele]);
				$datas[] = $data;
				
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
			$varName = $varNames[$i] ?? '';
			if ($varName === '') {
				continue;
			}
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
					$sKey											= "";
					$taVariables[cst::cstVarCodeAttribut]			= $unAttribut;					
					$criteria										= self::buildKey($pdo, $sBibliothèque, $sFichier, $sLogique, $sKeys, $taVariables );
					foreach($criteria as $crit => $val) {
						if($crit === cst::cstVarNumOrdre) continue;	// ignore order index in composite key
						$sKey										= $sKey . (strlen($sKey) <> 0 ? ',' : '') . (string)$val ;
					}					
					$taVariables[cst::cstVarNumOrdre]				= $nIndice + 1;								
					$criteria										= self::buildKey($pdo, $sBibliothèque, $sFichier, $sLogique, $sKeys, $taVariables );
					$dbTable  										= new DbTable($sBibliothèque, $sFichier, primaryKey: [], columns: []);
					$mapping										= $dbTable->loadFieldMetadata($pdo);
					// Lecture du fichier avec la condition construite
					$rows											= $criteria ? $dbTable->listWhere($pdo, $criteria, [], null, ['*']) : null;					
					if($rows) {
						foreach($rows as $numRow => $row) {
							$datas[$sFichier][$sKey][]				= ['DATAS'		=> $row, 																		
																		'HTML'		=> self::buildHtml($pdo,[$unAttribut],$mapping,$row)];	
						}
					} else {
							$datas[$sFichier][$sKey][]				= ['DATAS'		=> null, 																		
																		'HTML'		=> self::buildHtml($pdo,[$unAttribut],$mapping, null)];	;
					}									
				} 
			} else {
				$sKey												= "";				
				$criteria											= self::buildKey($pdo, $sBibliothèque, $sFichier, $sLogique, $sKeys, $taVariables );
				if($criteria) {
					foreach($criteria as $crit => $val) {
						if($crit === cst::cstVarNumOrdre) continue;	// ignore order index in composite key
						$sKey										= $sKey . (strlen($sKey) <> 0 ? ',' : '') . (string)$val ;
					}
					$taVariables[cst::cstVarNumOrdre]				= $nIndice + 1;								
					$criteria										= self::buildKey($pdo, $sBibliothèque, $sFichier, $sLogique, $sKeys, $taVariables );
					$dbTable  										= new DbTable($sBibliothèque, $sFichier, primaryKey: [], columns: []);
					// Lecture du fichier avec la condition construite
					$mapping										= $dbTable->loadFieldMetadata($pdo);
					$rows											= $criteria ? $dbTable->listWhere($pdo, $criteria, [], null, ['*']) : null;					
					if($rows) {
						foreach($rows as $numRow => $row) {
							$datas[$sFichier][$sKey][]				= ['DATAS'		=> $row, 																		
																		'HTML'		=> self::buildHtml($pdo,$Attributs,$mapping,$row)];		
						}
					} else {
						$datas[$sFichier][$sKey][]					= ['DATAS'		=> null, 																		
																		'HTML'		=> self::buildHtml($pdo,$Attributs,$mapping,null)];	
					}		
				} else {
					//echo "Erreur crit $sBibliothèque, $sFichier,$sLogique, $sKeys \n";
				}
			}
		}
	}

	public static function getValeurAttribut(PDO $pdo, string $productCode, string $attribut, ?int $indice = 1) {
		$datas												= self::lireAttributs($pdo,$productCode,$attribut);
		$defAttributs										= TATABATT::getByAttribute($pdo,'',$attribut);
		foreach($defAttributs as $defAttribut) {
			$sBibliothèque									= $defAttribut->ta_bibliotheque;
			$sFichier										= $defAttribut->ta_Fichier;
			$sLogique										= $defAttribut->ta_logique;
			$sKeys 											= $defAttribut->ta_cles;
	
			$taVariables[cst::cstVarCodeArticle]			=	$productCode;
			if(array_key_exists($sFichier,$datas)) {
				$sKey										= "";				
				$criteria									= self::buildKey($pdo, $sBibliothèque, $sFichier, $sLogique, $sKeys, $taVariables );
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
					$rubrique = $mapping[$defAttribut->ta_zone]['long'] ?? null;
					$fileData = $datas[$sFichier] ?? null;
					if (!is_array($fileData)) {
						continue;
					}

					$itemData = $fileData[$sKey][$ind] ?? null;
					if (!is_array($itemData)) {
						continue;
					}

					if ($rubrique !== null && array_key_exists($rubrique, $itemData)) {
						return $itemData[$rubrique];
					}

					if (array_key_exists($defAttribut->ta_zone, $itemData)) {
						return $itemData[$defAttribut->ta_zone];
					}
				}
			}
		return null;
	}

	public static function lireAttributs(PDO $pdo, string $productCode, ? string $attribut = '') : ?array
	{
		$start = microtime(true);	
		$datas = [];		
		self::getDefAttributsFichier($pdo,cst::cstAttributStandard);
		self::getDefAttributs($pdo, cst::cstAttributSpécif);
		// Aller lire la vue tatabatt pour récuperer les attributs "fichier"
		$AttributsFichier = VUE_TATABATT::getAttributes($pdo, cst::cstAttributStandard, $attribut);
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

	public static function getDefAttributs(PDO $pdo, string $mode, ?string $attribut = ''): ?array
	{
		if ($attribut != '' ) {
			if(array_key_exists($attribut,self::$attributeDefinitionCache)) {
				return self::$attributeDefinitionCache[$attribut];
			}
		}
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
			self::$attributeDefinitionCache[$model->ta_code_attribut]		= $data;
		}
		$data['nombre'] = $nb;
		//var_dump($data);
		return $data;
	}

public static function getDefAttribut(PDO $pdo, string $attribut): ?array
	{
		if ($attribut != '' ) {
			if(array_key_exists($attribut,self::$attributeDefinitionCache)) {
				return self::$attributeDefinitionCache[$attribut];
			}
		}
		$tabNbEvEnsVal = EVENSVAL::getNbEnsVal($pdo);		
		$tatabatt = TATABATT::getByAttribute($pdo,'' , $attribut);
		$data = null;
		$nb = 0 ;
		if($tatabatt) {
			$first_key = array_key_first($tatabatt);
			$model = $tatabatt[$first_key];
			if ($model) {
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
				self::$attributeDefinitionCache[$model->ta_code_attribut]		= $data;
			}
		} 
		$data['nombre'] = $nb;
		//var_dump($data);
		return $data;
	}


	public function getGroupeFamille(PDO $pdo): ?array 
	{
		$sql = "Select ta_groupe As ngroupe,
			       groupe.ta_ordre As nordregroupe,
			       groupe.ta_libelle As libgroupe,
			       ta_famille As nfamille,
			       Ifnull(famille.ta_ordre , 0) As nordrefamille,
			       Ifnull(famille.ta_libelle , '') As libfamille,
			       ta_sous_famille As nsousfamille,
			       Ifnull(sousfamille.ta_ordre , 0) As nordresousfamille,
			       Ifnull(sousfamille.ta_libelle, '') As libsousfamille,
			       Listagg(tatabatt.ta_ordre || ':' || Rtrim(ta_code_attribut) , ',') Within Group ( Order By tatabatt.ta_ordre ) As codesattributs,
			       groupe.ta_calcul_workflow As groupeworkflow,
			       groupe.ta_verouille As groupeverouille,
			       famille.ta_calcul_workflow As familleworkflow,
			       famille.ta_verouille As familleverouille,
			       sousfamille.ta_calcul_workflow As sousfamilleworkflow,
			       sousfamille.ta_verouille As sousfamilleverouille
			  From (
			         Select *
			           From matis.tatabatt
			           Order By ta_groupe,
			                    ta_famille,
			                    ta_sous_famille,
			                    ta_ordre
			       ) As tatabatt
			       Left Outer Join matis.tafam groupe
			         On groupe.ta_code_groupe = ta_groupe And
			           groupe.ta_code_famille = 0
			       Left Outer Join matis.tafam famille
			         On famille.ta_code_groupe = ta_groupe And
			           famille.ta_code_famille = ta_famille And
			           famille.ta_code_sous_famille = 0
			       Left Outer Join matis.tafam sousfamille
			         On sousfamille.ta_code_groupe = ta_groupe And
			           sousfamille.ta_code_famille = ta_famille And
			           sousfamille.ta_code_sous_famille = ta_sous_famille And
			           sousfamille.ta_code_sous_famille <> 0
			  Where (ta_groupe <> 0 And ta_mode_gestion <> 'FICHE_ARTICLE')
			  Group By ta_groupe,
			           groupe.ta_ordre,
			           groupe.ta_libelle,
			           ta_famille,
			           famille.ta_ordre,
			           famille.ta_libelle,
			           ta_sous_famille,
			           sousfamille.ta_ordre,
			           sousfamille.ta_libelle,
			           groupe.ta_calcul_workflow,
			           groupe.ta_verouille,
			           famille.ta_calcul_workflow,
			           famille.ta_verouille,
			           sousfamille.ta_calcul_workflow,
			           sousfamille.ta_verouille
			  Order By groupe.ta_ordre,
			           Ifnull(famille.ta_ordre , 0 ),
			           Ifnull(sousfamille.ta_ordre , 0 )
		";
 		$stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
		return $rows;
	}

}

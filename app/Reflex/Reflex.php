<?php
declare(strict_types=1);

namespace App\Reflex;

use PDO;
use Throwable;
use DateTimeImmutable;

use App\Programmes\Programmes;

use App\Reflex\Dépot;
use App\Reflex\HLTYSKP;
use App\Reflex\HLSTOCP;
use App\Reflex\HLGEINP;

use App\Domain\A1ARTICL;
use App\Domain\PLPTFLOG;

final class Reflex
{
    public static function stockArticle(PDO $pdo,string $CodeSociété, string $CodeArticle ) : ? array
    {
        $depot  = Dépot::get($CodeSociété);        
        $datas  = [];
        foreach(Dépot::TypesStocks() as $value) {            
            if($value['affichage'] == true) {
                $qté            = 0;
                $ok             = false;
                switch($value['code']) {
                    case 'SAI':
                        $plptflog = PLPTFLOG::readModel($pdo,$CodeSociété,$CodeArticle);
                        if($plptflog) {
                            $qté    = (int) $plptflog->plsai;
                            $ok     = true;    
                        }                        
                        break;
                    case 'RFLX':
                        $plptflog = PLPTFLOG::readModel($pdo,$CodeSociété,$CodeArticle);
                        if($plptflog) {
                            $qté    = (int) $plptflog->plrflx;
                            $ok     = true;    
                        }                        
                        break;
                    case 'RAL':
                        $plptflog = PLPTFLOG::readModel($pdo,$CodeSociété,$CodeArticle);
                        if($plptflog) {
                            $qté    = (int) $plptflog->plral;
                            $ok     = true;    
                        }                        
                        break;
                    case 'ENC':
                        $a1articl = A1ARTICL::getLocalModelById($pdo,$CodeSociété,$CodeArticle);
                        if($a1articl) {
                            $qté    = (int) $a1articl->a1encf;
                            $ok     = true;    
                        }                        
                        break;
                    default:
                        //echo "Calcul du stock pour ".$value['code']." aricle ".$CodeArticle."\n";
                        //var_dump($depot);
                        $stock      = self::stock($pdo,$depot,$CodeArticle,$value['code'],'30','STD','STD');                        
                        if($stock) {
                            $qté    = (int) $stock['qté'];
                            $ok     = $stock['ok'];
                        }
                        break;
                }
                $datas[$value['code']] = [
                    'libellé' => $value['libellé'],
                    'qté' => $qté,
                    'ok' => $ok
                ] ;
            }
        }
                                                
        return $datas;
    }

    public static function stock(PDO $pdo, array $dépot, string $CodeArticle,string $TypeStock, string $CodeConditionnement, ? string $Propriétaire='STD', ? string $Qualité='STD', ? bool $bAvecSubstitution=False) : ? array
    {
        $bOk = false;        
        $rQtéStock = 0;
        $rPoids = 0;
        $rMontant = 0;
        $rPrix = 0;
        $hltyskp = HLTYSKP::readModel($pdo, $TypeStock);
        if($hltyskp) {
            if ($hltyskp->TKTACA === '1' ) {
                if($hltyskp->tkcpgm === 'HLST52') {
		    		$hlgeinp = HLGEINP::readModels($pdo, $dépot['DPOPhysique'], $dépot['Activité'], $CodeArticle, $CodeConditionnement, $Propriétaire, $Qualité);
	    			if ($hlgeinp) {
	    				foreach($hlgeinp as $row) {
	    					switch($TypeStock) {
	    						case '210':	//Physique bloqué									
	    							if($row->getgdi === '0') $rQtéStock += $row->geqgei;
	    							break;
	    						case '211':	//Bloqué POUR motif particulier									
	    							if($row->getgbl === '1') $rQtéStock += $row->geqgei;
	    							break;
	    						case '212':	//Bloqué sous douane		
	    							if($row->getbdo === '1') $rQtéStock += $row->geqgei;
	    							break;
	    						case '213':	//Bloqué POUR stabilisation		
	    							if($row->getbst === '1') $rQtéStock += $row->geqgei;
	    							break;
	    						case '214':	//Bloqué POUR contrôle		
	    							if($row->getbct === '1') $rQtéStock += $row->geqgei;
	    							break;
	    						case '215':	//Bloqué POUR reconditionnement		
	    							if($row->getbrc === '1') $rQtéStock += $row->geqgei;
	    							break;
	    						case '216':	//sur emplacement bloqué		
	    							if($row->getbem === '1') $rQtéStock += $row->geqgei;
	    							break;
	    						case '217':	//Bloqué POUR inventaire
	    							if($row->getgin === '1') $rQtéStock += $row->geqgei;
	    							break;
	    					}
	    				}
				    }
                } else {
                    switch($TypeStock) {
				    	case '270':
                        case '260':
                        case '010':
                        case '200':                         
                            $rPoids		    = 0;
                            $rPrix		    = 0;
                            $rMontant		= 0;
                            $rQtéStock      = Programmes::STKART($pdo,$TypeStock,$CodeArticle,$dépot['code']);     
                            break;
                        default:	  
                            $l = ($hltyskp->tkcbic = '*LIBL' ? $dépot['library'] :  $hltyskp->tkcbic );
				    		$stock			= programmes::HLST50CL($pdo,$l ,$hltyskp->tkcpgm,$dépot['DPOLogique'],$dépot['DPOPhysique'],$dépot['Activité'],$CodeArticle,$CodeConditionnement,$Propriétaire,$Qualité);
				    		if($stock) {
				    			$bOk        = true;
				    			$rQtéStock  = $stock['qté'];    
				    			$rPoids     = $stock['poids'];   
				    			$rPrix      = $stock['prix'];    
				    			$rMontant   = $stock['montant']; 
				    		}		
				    		break;														
                    }
                }
            } else {
                $hlstocp = HLSTOCP::readModel($pdo,$dépot['DPOPhysique'],$TypeStock,$dépot['Activité'],$CodeArticle,$CodeConditionnement,$Propriétaire,$Qualité);
                if($hlstocp) {
                    $bOk                    = true;
                    $rQtéStock	            = $hlstocp->skqstk;
			    	$rPoids		            = $hlstocp->skpdst;
			    	$rPrix		            = $hlstocp->skpxst;
			    	$rMontant	            = $hlstocp->skmtst;
                }
            }
        }
        if ($bAvecSubstitution) {
            $hlsubsp = HLSUBSP::readModels($pdo,$dépot['Activité'],$CodeArticle,$CodeConditionnement,$Propriétaire,$Qualité);
            if ($hlsubsp) {
                foreach($hlsubsp as $row) {
                    $stock              = self::stock($pdo,$dépot,$row->sbcars,$hltyskp->tkctst,$CodeConditionnement,$Propriétaire,$Qualité);
                    if($stock['ok']) {
                        if($row->sbnqsu != 0 ) {
                            $bOk        =  true;
                            $rQtéStock  += ($stock['qté']       * $row->sbnqsu) / $row->sbnqsu;
                            $rPoids     += ($stock['poids']     * $row->sbnqsu) / $row->sbnqsu;
                            $rPrix      += ($stock['prix']      * $row->sbnqsu) / $row->sbnqsu;
                            $rMontant   += ($stock['montant']   * $row->sbnqsu) / $row->sbnqsu;
                        }
                    }
                }
            }
        }

         return [
            'ok'                        => $bOk,
            'qté'                       => $rQtéStock,
            'poids'                     => $rPoids,
            'prix'                      => $rPrix,
            'montant'                   => $rMontant
        ];
    }

    public static function DonneCodeBarreVL(PDO $pdo, string $CodeActivité, string $CodeArticle, ? string $CodeConditionnement='01', ? string $CodeIdentifiant = 'EAN13', ?DateTimeImmutable $DateCAB = null)
    {
        $DateCAB ??= new DateTimeImmutable();

    }

    public static function DélaiFournisseur(PDO $pdo, string $code_article): ?string
    {
        $sql = 'select A1ART as "Code_article", "Délai_Livraison"
                from matis.A1ARTICL
                left join (
                    select distinct(A8ART), Listagg(A8DELD, \'-\') as "Délai_Livraison"
                    from MATFER.A8CDEFOU
                    inner join MATFER.E6CDEFOC on A8NCDE = E6NCDE
                    where E6ETAT <> \'FIN\'
                      and (A8QTLI - A8QTCD) <> 0
                    group by A8ART
                    UNION
                    select distinct(A8ART), Listagg(A8DELD, \'-\') as "Délai_Livraison"
                    from BOURGEAT.A8CDEFOU
                    inner join BOURGEAT.E6CDEFOC on A8NCDE = E6NCDE
                    where E6ETAT <> \'FIN\'
                      and (A8QTLI - A8QTCD) <> 0
                    group by A8ART
                    UNION
                    select distinct(A8ART), Listagg(A8DELD, \'-\') as "Délai_Livraison"
                    from INSITU.A8CDEFOU
                    inner join INSITU.E6CDEFOC on A8NCDE = E6NCDE
                    where E6ETAT <> \'FIN\'
                      and (A8QTLI - A8QTCD) <> 0
                    group by A8ART
                ) DELAI on DELAI.A8ART = A1ART
                where A1ART = :code_article
                group by A1ART, "Délai_Livraison"';

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':code_article', $code_article, PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $row['Délai_Livraison'] ?? null;
    }
}

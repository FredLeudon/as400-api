<?php
declare(strict_types=1);

namespace App\Programmes;

use PDO;
use Throwable;
use DateTimeImmutable;


final class Programmes
{
    public static function STKART(PDO $pdo, string $TypeStock, string $CodeArticle, string $CodeDépot): ? int
    {
        $sql = 'values sqlpgs.stkart( :stktyp, :codeart, :depot)';                    
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':stktyp', $TypeStock, PDO::PARAM_STR);
        $stmt->bindValue(':codeart', $CodeArticle, PDO::PARAM_STR);
        $stmt->bindValue(':depot', $CodeDépot, PDO::PARAM_STR);
        $stmt->execute();            
        $row = $stmt->fetch(PDO::FETCH_COLUMN);
        $value = (int) $row;
        if($value == 9999) $value = 0;
        return $value;
    }

    public static function HLST50CL(PDO $pdo, string $Bibliothèque , string $Programme, string $DPOLogique, string $DPOPhysique, string $CodeActivité, string $CodeArticle , string $CodeConditionnement, string $Propriétaire, string $Qualité) : ? array
    {
        $Bibliothèque = ( $Bibliothèque === '*LIBL' ? 'HLRFX70' : $Bibliothèque);
        $sql = "select * from table ( wdoutils.hlst50cl( '$Bibliothèque' , '$Programme' , '$DPOLogique' , '$DPOLogique' , '$CodeActivité' , '$CodeArticle' , '$CodeConditionnement' , '$Propriétaire' , '$Qualité') )";
        var_dump($sql);                
        $stmt = $pdo->prepare($sql);        
        $stmt->execute();           
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$row){
            return [];
        }
        return [
            'ok'        => $row['PCRET'],
            'qté'       => (int) $row['WQVLDI'],
            'poids'     => (float) $row['WPNDIS'],
            'prix'      => (float) $row['WPSTK'],
            'montant'   => (float) $row['WMSTK']                		
        ];
    }
}

/*
LOCAL sBibliothèque est une chaîne, sProgramme est une chaîne, sDPO_Logique est une chaîne,  sDPO_Physique est une chaîne, sCode_Activité est une chaîne, sCode_Article est une chaîne, sCode_Conditionnement est une chaîne,sPropriétaire est une chaîne, sQualité est une chaîne)


sSQL est une chaîne = [
select * from table ( wdoutils.hlst50cl(  '%1' , '%2' , '%3' , '%4' , '%5' , '%6' , '%7' , '%8' , '%9' ) )
]

sdHLST50CL est une Source de Données
cRetour		est un caractère	= "E"
rQtéStock	est un réel			= 0
rPoids		est un réel			= 0
rPrix		est un réel			= 0
rMontant	est un réel			= 0

SI HExécuteRequêteSQL(sdHLST50CL,pclConnexion:Connexion,hRequêteSansCorrection,ChaîneConstruit(sSQL,Remplace(sBibliothèque,"*LIBL","HLRFX70"),
sProgramme,
sDPO_Logique,
sDPO_Physique,
sCode_Activité,
sCode_Article,
sCode_Conditionnement, 
sPropriétaire,
sQualité)) ALORS
	SI HLitPremier(sdHLST50CL) ALORS
		cRetour		= 	sdHLST50CL.pcret
		rQtéStock	= 	Val(sdHLST50CL.WQVLDI)
		rPoids		= 	Val(sdHLST50CL.WPNDIS)
		rPrix		= 	Val(sdHLST50CL.WPSTK)
		rMontant	= 	Val(sdHLST50CL.WMSTK)		
	SINON
		STOP
	FIN
SINON
	STOP
FIN

//HLST50CL_MATR.PCRET		= ""								
//HLST50CL_MATR.TKCBIC	= Remplace(HLTYSKP_MATR.TKCBIC,"*LIBL","HLRFX70")
//HLST50CL_MATR.TKCPGM	= HLTYSKP_MATR.TKCPGM
//HLST50CL_MATR.PCDLOG	= DPO_Logique
//HLST50CL_MATR.PCDPHY	= DPO_Physique
//HLST50CL_MATR.VLCACT	= Code_Activité
//HLST50CL_MATR.VLCART	= Loc_Article
//HLST50CL_MATR.VLCVLA	= Code_Conditionnement	
//HLST50CL_MATR.PCDPRO	= Propriétaire
//HLST50CL_MATR.PCDQUA	= Qualité
//HLST50CL_MATR.WQVLDI	= 0
//HLST50CL_MATR.WPNDIS	= 0
//HLST50CL_MATR.WPSTK		= 0
//HLST50CL_MATR.WMSTK		= 0																
//SI PAS ASLanceRPC(HLST50CL_MATR) ALORS
//	Erreur("Marche pas !"+RC+RC+HErreurInfo(hErrComplet))
//FIN								

RENVOYER (rQtéStock,rPoids,rPrix,rMontant)
*/
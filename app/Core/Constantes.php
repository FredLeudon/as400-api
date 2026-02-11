<?php
declare(strict_types=1);

namespace App\Core;

final class cst
{
    // Dépôts principaux
    public const cstDépotDropFournisseur = '--';
    public const cstDépotMatfer = '06';
    public const cstDépotInSitu = '38';
    public const cstDépotBourgeat = '40';

    // Langues
    public const cstLangues = ['FRA','ANG','ALL','ITA','ESP'];

    // Sociétés / codes
    public const Bourgeat = '38';
    public const Calle = '31';
    public const Flo = '02';
    public const FloVending = '15';
    public const GMB = '12';
    public const InSitu = '40';
    public const Jacquemin = '04';
    public const JVillette = '16';
    public const MBI = '00';
    public const Matfer = '06';
    public const MatferIndustrie = '07';
    public const Matik = '69';
    public const MBHE = '19';
    public const Safran = '20';
    public const Sima = '09';
    public const SoGeMat = '91';
    public const TecMAT = '96';
    public const Vauconsant = '54';

    // Bibliothèques
    public const bibMATFER = 'MATFER';
    public const bibINSITU = 'INSITU';
    public const bibBOURGEAT = 'BOURGEAT';
    public const bibMATIK = 'MATIK';
    public const bibMBI = 'FCMBI';
    public const bibSOGEMAT = 'SOGEMAT';
    public const bibCALLE = 'CALLE';
    public const bibFLOVENDING = 'FLOVENDING';
    public const bibTECMAT = 'TECMAT';
    public const bibMBHE = 'MBHE';
    public const bibSafran = 'SAFRAN';
    public const bibVauconsant = 'VAUCONSANT';
    public const bibGMB = 'GMB';
    public const bibMATIS = 'MATIS';

    // Statuts et modes
    public const cstRien = 'rien';
    public const cstAjouter = 'ajout';
    public const cstModifier = 'modifie';
    public const cstSupprimer = 'supprime';
    public const cstVerrouiller = 'verrouille';
    public const cstRestaurer = 'restaure';
    public const cstErreur = 'erreur';

    public const Mode_Ajout = 'Ajout';
    public const Mode_Copie = 'Copie';
    public const Mode_Modif = 'Modifie';
    public const Mode_Supprime = 'Supprime';
    public const Mode_Visu = 'Visu';
    public const Mode_Sélection = 'Sélection';
    public const Mode_Table = 'Table';

    // Types de documents
    public const e_BL = 'E_BL';
    public const e_MDP = 'MDP_WEB';
    public const e_AR_Commande = 'AR_CDE_WEB';
    public const e_AR_Assistante = 'AR_CDE_ASS';
    public const e_Assistante = 'E_ASSIST';
    public const e_AssistanteWeb = 'E_ASSISTWE';

    public const cstRéférent = 'Référent';
    public const cstClient = 'Client';
    public const cstComptable = 'Comptable';

    // Flags & rôles
    public const b_GestionMBI = 'B_MBI';
    public const b_EstStandard = 'B_STANDARD';
    public const b_AccèsCatalogue = 'B_CATALOG';
    public const b_MDP_Bloqué = 'B_NOCHGMDP';
    public const b_EstRéférent = 'B_REFERENT';
    public const e_Référent = 'E_REFERENT';
    public const e_RéférentValidation = 'VALCDEREF';
    public const b_EstComptable = 'B_COMPTABL';
    public const e_ComptableValidation = 'VALCDECPT';
    public const e_Comptable = 'E_COMPTABL';
    public const e_Budget = 'BUDGET';
    public const e_Budget_Montant = 'BUD_MTT';
    public const e_Budget_Encours = 'BUD_ENC';
    public const e_Budget_Reset = 'BUD_RAZ';
    public const e_PrixTTC = 'PRIXTTC';
    public const e_FraisDePort = 'ART_PORT';

    public const nMaxCaractPassword = 8;

    // Couleurs
    public const cCouleurGris = 0x333333;
    public const cCouleurInfo = 0x666666;
    public const cCouleurErreur = 0xFD7864;
    public const cCouleurWarning = 0xFFC400;

    public const cVertClair = 0xD8FF84;
    public const cRougeClair = 0xFFD1D1;
    public const cGrisé = 0xDCDCDC;

    // Affichages / dispositions
    public const cstDispLiaisonFichier = 12;
    public const cstDispLiaisonSQL = 13;
    public const cstDispTypeSimple = 8;
    public const cstDispTypeComplexe = 9;
    public const cstDispEnsembleValeur = 10;

    // Zones
    public const cstZoneClient = 'Client';
    public const cstZoneChrono = 'Chronomètre';
    public const cstZoneAdressesLivraison = 'Adresses_Livraison';
    public const cstZoneClientPlus = 'Client_Plus';
    public const cstZoneContacts = 'Contacts';
    public const cstZoneRemisesArticle = 'Remises_Client_Article';
    public const cstZoneRéférencesArticle = 'Références_Article_Client';
    public const cstZoneNotes = 'Notes';
    public const cstZoneRépertoire = 'Répertoire';
    public const cstZoneStructureEmails = 'Structure_Emails';
    public const cstZoneStatistiques = 'Statistiques';
    public const cstZoneFacturation = 'Facturation';
    public const cstZoneCommande = 'Commande';
    public const cstZoneClientRisque = 'Client_Risque';
    public const cstZoneCorrespondanceCode = 'Correspondance_Code';
    public const cstZoneCommentaires = 'Commentaires';
    public const cstZoneCommercial = 'Commercial';
    public const cstZoneAdresse = 'Adresse';
    public const cstZoneComptabilité = 'Comptabilité';
    public const cstZoneDonnéesComplémentaires = 'Données_Complémentaires';
    public const cstZoneDocuments = 'Documents';
    public const cstZoneDocumentsWeb = 'DocumentsWeb';
    public const cstChargementTerminé = 'Fini';
    public const cstChargementTimeout = 'TimeOut';

    // Objets
    public const cstObjetMBI = 'ObjetMBI';
    public const cstObjetFiliale = 'ObjetFiliale';

    // Chargement
    public const cstNonChargé = 0;
    public const cstChargementEnCours = 1;
    public const cstChargé = 2;

    // Fiches
    public const cstChargementSynchrone = 1;
    public const cstChargementAsynchrone = 2;
    public const cstChargementSynchroneàlaDemande = 3;
    public const cstChargementAsynchroneàLaDemande = 4;

    public const cstModeFiche = 1;
    public const cstModeFenêtreInterne = 2;

    public const cstClientPlanFenêtreInterne = 20;
    public const cstFournisseurPlanFenêtreInterne = 21;
    public const cstArticlePlanFenêtreInterne = 22;

    public const cstAfficherTDBClient = 1;
    public const cstNePasAfficherTDBClient = 0;

    public const cstRubanArticle = 'article';
    public const cstRubanClient = 'client';
    public const cstRubanFournisseur = 'fournisseur';

    // Remises
    public const cRemisePérimée = 0x666666;
    public const cRemiseActive = 0x009933;
    public const cRemiseFuture = 0x0066FF;

    // Langues codes
    public const cstDefault = 'DFT';
    public const cstFrancais = 'FRA';
    public const cstAnglais = 'ANG';
    public const cstEspagnol = 'ESP';
    public const cstAllemand = 'ALL';
    public const cstItalien = 'ITA';

    public const cstLangueFrançais = 'FRA';
    public const cstLangueFrançaisISO = 'fr';
    public const cstLangueAnglais = 'ANG';
    public const cstLangueAnglaisISO = 'en';
    public const cstLangueAllemand = 'ALL';
    public const cstLangueAllemandISO = 'de';
    public const cstLangueItalien = 'ITA';
    public const cstLangueItalienISO = 'it';
    public const cstLangueEspagnol = 'ESP';
    public const cstLangueEspagnolISO = 'es';
    public const cstLangueIndéfinie = '---';
    public const cstLangueVide = '';

    // Groupes
    public const cstGpwAdminDigital = 'Manage_Digital';
    public const cstGpwAdminPrint = 'Manage_Print';
    public const cstGpwOrdimajor = 'Ordimajor';
    public const cstGpwInventaire = 'Inventaire';
    public const cstGroupeCEA = 'CEA';
    public const cstGroupePRA = 'Gestion des PRAs';
    public const cstGroupeMarketing06 = 'Marketing 06';
    public const cstGroupeMarketing38 = 'Marketing 38';
    public const cstGroupeMarketing40 = 'Marketing 40';

    // Tableau de bord
    public const cstTableauDeBordArticle = 'TDBArticle';
    public const cstTableauDeBordClient = 'TDBClient';
    public const cstTableauDeBordFournisseur = 'TDBFournisseur';

    public const csNomRepertoireGabarits = 'Gabarits';
    public const csNomParametreGabarit = 'GABARIT_DYNAMIQUE';

    public const cstClair = 'Clair';
    public const cstSombre = 'Sombre';

    // Divers utiles
    public const cstNoPage = -1;

    public const st_Annulé = 1;
    public const st_Suspendu = 2;
    public const st_Normal = 3;
    public const st_Temporaire = 4;
    public const st_Contremarque = 5;
    public const st_Nouveauté = 6;

    public const cstArtInconnu = '';
    public const cstArtNonVendable = '*';
    public const cstArtAnnulé = 'A';
    public const cstArtSuspendu = 'S';
    public const cstArtNormal = 'N';
    public const cstArtTemporaire = 'T';
    public const cstArtPièceDétachée = 'D';
    public const cstArtContremarque = 'C';
    public const cstArtNouveauté = 'Z';

    // Images
    public const cstImageVignette = 1;
    public const cstImageMoyenne = 2;
    public const cstImageGrande = 3;
    public const cstImageServeur = 'serveur';
    public const cstImageWeb = 'web';
    public const cstBaliseImg = 'balise_img';

    // Cache API
    public const cstCacheFull = '/api/cache/flush';
    public const cstCacheSousFamille = '/api/cache/subfamily/%1';
    public const cstCacheGamme = '/api/cache/range/%1';
    public const cstCacheModèle = '/api/cache/model/%1';
    public const cstCacheMarques = '/api/cache/brands';
    public const cstCacheMarque = '/api/cache/brand/%1';
    public const cstCacheListeSegment = '/api/cache/segments';
    public const cstCacheListeFamille = '/api/cache/families/%1';
    public const cstCacheListeSousFamille = '/api/cache/subfamilies/%1/%2';
    public const cstCacheListeGamme = '/api/cache/ranges/%1/%2/%3';
    public const cstCacheListeSérie = '/api/cache/series/%1/%2/%3/%4';
    public const cstCacheListeModèle = '/api/cache/serie/%1/%2/%3/%4/%5';

    // Tokens / URLs (initial values from cl_constantes.txt)
    public const cstToken = '4FF238F0-9D38-490B-AA60-041D963EE4A2';
    public const cstApiToken = 'ProgescomApi-4d00edea2ee16ad1d4d10d21799fedd933da79d58f03b12ff0b69f9ce9bf8ef7';

    // Quelques chemins/urls utiles
    public const cst_URL_Site = 'https://digital.matferbourgeat.com';
    public const cst_Staging_Url_Site = 'https://digital-staging.matferbourgeat.com';
    public const cst_URL_Media = 'https://medias.matferbourgeat.com/';
    public const cst_Staging_URL_Media = 'https://medias-staging.matferbourgeat.com/';

    // Mot de passe généré
    public const MotDePasseFichierCS = 'B9l8A7v6E5t4T3e2';

    // Fallbacks / defaults
    public const cstPaysFrance = '001';
    public const cstPaysMonaco = '002';
    public const cstTVA_20 = 2;
    public const cstDeviseEuro = 'EUR';

    public const cstCatégorieFonctionnelle									= "CAT_FONCT";
	public const cstDateValidationFicheDigital								= "DAT_DEB_VAL_FCDIG";
	public const cstDateModificationFicheDigital							= "DAT_MAJ_FCDIG";

	public const cstDatePublicationCatalogueDigital							= "DAT_PUB_CATA_PRT";


    public const  cstArguDesc												= "DESC";
    public const  cstArguPrint												= "MEAP";
	public const  cstArguLOT												= "LOT";
	public const  cstArguCEA											    = "COMC";
	public const  cstArguFicheTechnique										= "VCS";
	public const  cstArguPlusProduit										= "PPRD";

    // Types de titres
    public const cstTypTitreInconnu                                         = "?????";
    public const cstTypTitreStandard                                        = "STD";
    public const cstTypTitreAccroche                                        = "ACC";
    public const cstTypTitrePlusProduit                                     = "PLUSP";
    public const cstTypTitreIndex                                           = "INDEX";
    public const cstTypTitrePieceDetachee                                   = "PD";

    // Types de médias
    public const cstTypPhoto                                                = "PC/photos";
    public const cstTypVideo                                                = "PC/videos";
    public const cstPhotoPicto                                              = "picto";
    public const cstPhotoMarques                                            = "marques";
	public const cstPhotoModèle												= "modele";

    public const  cstVarCodeAttribut										= "%code_att%";
	public const  cstVarCodeArticle											= "%code_art%";
	public const  cstVarCodeFournisseur										= "%code_four%";
	public const  cstVarCodeSociété											= "%code_soc%";
	public const  cstVarCodeLangue											= "%code_langue%";
	public const  cstVarCatégorie											= "%catégorie%";
	public const  cstVarVarianteCommerciale									= "%code_vc%";
	public const  cstVarVarianteLogistique									= "%code_vl%";
	public const  cstVarTypeAccessoire										= "%type_accessoire%";
	public const  cstVarCodePays											= "%code_pays_num%";
	public const  cstVarNumOrdre											= "%num_ordre%";
	public const  cstVarRawValue											= "%raw_value%";

    public const  cstValeurSimble											= "SIMPLE";
	public const  cstValeurComplexe											= "COMPLEXE";
	public const  cstEnsenbleDeValeur										= "ENSVAL";

    // Applications
    public const cstAppliDigital   = "DIGITAL";
    public const cstAppliCatalogue = "CATALOG";

    // Tables / listes issues de WinDev
    public const gtaArgumentaires = [
        self::cstArguDesc            => "Digital",
        self::cstArguPrint           => "Print",
        self::cstArguFicheTechnique  => "FT Vauconsant",
        self::cstArguPlusProduit     => "Plus produit",
    ];

    public const gtaTypeFichiers = [
        self::cstTypPhoto => [
            "accessoire","devis","famille","gamme","hactualite","hchef-list","home","hsurmesure",
            self::cstPhotoMarques,"marquesprc","modele",self::cstPhotoPicto,"segment","serie",
            "tetieres","thumbnail","vchef-list","vsurmesure"
        ],
        self::cstTypVideo => ["modele"],
    ];

    public const gtaLangues = [
        self::cstLangueFrançais => "Français",
        self::cstLangueAnglais  => "Anglais",
        self::cstLangueAllemand => "Allemand",
        self::cstLangueEspagnol => "Espagnol",
        self::cstLangueItalien  => "Italien",
    ];

    public const gtaTypeTitre = [
        self::cstTypTitreStandard      => "Standard",
        self::cstTypTitreAccroche      => "Accroche",
        self::cstTypTitrePlusProduit   => "Plus Produit",
        self::cstTypTitreIndex         => "Index",
        self::cstTypTitrePieceDetachee => "Pièce détachée",
        self::cstTypTitreInconnu       => "Erreur, type inconnu !",
    ];

    public const gtaLanguesDefAttributs = [
        self::cstLangueFrançais => "Français",
    ];

    public const gtaVariables = [
        self::cstVarCodeAttribut          => "Code de l'attribut",
        self::cstVarCodeArticle           => "Code de l'article",
        self::cstVarVarianteCommerciale   => "Variante logistique ou commerciale",
        self::cstVarCodeLangue            => "Code de la langue",
        self::cstVarTypeAccessoire        => "Code type accessoire",
        self::cstVarCodePays              => "Code du pays (numérique)",
        self::cstVarNumOrdre              => "Numéro d'ordre",
    ];

    public const gtabFichiers = [
        "AAACCART","AEATTETE","ANATTNOM","APARGPRD","ATARGTXT","APAVTPRD","ATAVTTXT","ASATTSERAP",
        "ASSMARQFIC","BPBCLPRT","CACOMART","CACOMARTVC","GLN","GPGABPRT","LALSTATTS","LNLIBNOM",
        "LVLIBVCART","MAMATART","MARQUES","NANOMART","NCNOMCAT","NFNOMFIC","NNNIVNOMCA","PAYISO3166",
        "STSERTET","STSOUTYPFI","TATYPACCAR","TFTABFIC","TFTYPFIC","TMTABMAT","TATABATT","TATXTATT",
        "TAFAM","TPTITPRT","EVENSVAL","TPTITPRT","BPBCLPRT","GPGABPRT","DICOKEY","DICOTEXTE","ADATTDRO","NDNOMDRO"
    ];

    public const gtabVariantesCommerciales = ["-2","-1"," 1"];
    public const gtabVariantesLogistiques  = ["01","05","10","30"];
    public const gtabVariantes            = ["-2","-1"," 1","05","10","30"];

    public const gtabLangues = [
        self::cstLangueFrançais,
        self::cstLangueAnglais,
        self::cstLangueAllemand,
        self::cstLangueEspagnol,
        self::cstLangueItalien,
    ];

    public const gtabTypesAccessoires = ["CD","CP","PD","OP"];

    public const gtaTypeAccessoires = [
        ["CD","Complément Digital"],
        ["CP","Complément Print"],
        ["PD","Pièces détachées"],
        ["OP","Options"],
    ];

    public const gtaNbAttributsSérie = [
        self::cstAppliDigital   => 3,
        self::cstAppliCatalogue => 3,
    ];


    private function __construct() {}
}

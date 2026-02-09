<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\Http;
use App\Core\clFichier;

/**
 * Article master (A1ARTICL)
 *
 * Primary key: A1ART
 */
final class A1ARTICL extends clFichier
{
    protected static string $table = 'A1ARTICL';
    protected static array $primaryKey = ['A1ART'];
    /** @var array<int,array<int,string>> */
    protected static array $uniqueKeys = [
        ['A1ART'], // A1L0
    ];
    /** @var array<string,array<int,string>> */
    protected static array $indexes = [
        'A1C0L0' => ['A1ART'],
        'A1C0L1' => ['A1ART'],
        'A1L0'   => ['A1ART'],
        'A1L2'   => ['A1FAMI'],
        'A1L99'  => ['A1ART'],
        // Source mentions A4CTVA; using A1CTVA to match columns in A1ARTICL.
        'A4L999' => ['A1CTVA'],
    ];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'A1ART'   => ['label' => 'Article',                                           'type' => 'CHAR',     'nullable' => false],
        'A1CNUF'  => ['label' => 'Code du pays + CNUF',                               'type' => 'DECIMAL',  'nullable' => false],
        'A1SAIS'  => ['label' => "Indice de saison d'utilisation",                   'type' => 'CHAR',     'nullable' => false],
        'A1POID'  => ['label' => "Poids unitaire de l'article (kg)",                  'type' => 'DECIMAL',  'nullable' => false],
        'A1UNVT'  => ['label' => 'Unite de vente',                                   'type' => 'DECIMAL',  'nullable' => false],
        'A1DNS'   => ['label' => 'Demande non satisfaite',                           'type' => 'DECIMAL',  'nullable' => false],
        'A1QTMC'  => ['label' => 'Quantite mini de commande client',                 'type' => 'DECIMAL',  'nullable' => false],
        'A1TVA'   => ['label' => "T.V.A. appliquee a l'article",                     'type' => 'DECIMAL',  'nullable' => false],
        'A1FAMI'  => ['label' => 'Code de la famille',                               'type' => 'CHAR',     'nullable' => false],
        'A1QTST'  => ['label' => 'Quantite de conditionnement stockage',             'type' => 'DECIMAL',  'nullable' => false],
        'A1MTRO'  => ['label' => 'Reference article chez le client (O/N)',           'type' => 'CHAR',     'nullable' => false],
        'A1MATI'  => ['label' => 'Code matiere',                                     'type' => 'CHAR',     'nullable' => false],
        'A1DATC'  => ['label' => 'Date de creation MM/AAAA',                         'type' => 'NUMERIC',  'nullable' => false],
        'A1TYPE'  => ['label' => "Type ou niveau de finition de l'article",          'type' => 'CHAR',     'nullable' => false],
        'A1DOUA'  => ['label' => 'No de tarif douanier',                             'type' => 'CHAR',     'nullable' => false],
        'A1EMPL'  => ['label' => 'Tag conservation dernier emplacement (O/N)',       'type' => 'CHAR',     'nullable' => false],
        'A1CTVA'  => ['label' => 'Code T.V.A.',                                      'type' => 'CHAR',     'nullable' => false],
        'A1DI10'  => ['label' => 'Dimension 1 longueur',                             'type' => 'DECIMAL',  'nullable' => false],
        'A1DI11'  => ['label' => 'Dimension 1 largeur',                              'type' => 'DECIMAL',  'nullable' => false],
        'A1DI12'  => ['label' => 'Dimension 1 hauteur',                              'type' => 'DECIMAL',  'nullable' => false],
        'A1DI20'  => ['label' => 'Dimension 2 longueur',                             'type' => 'DECIMAL',  'nullable' => false],
        'A1DI21'  => ['label' => 'Dimension 2 largeur',                              'type' => 'DECIMAL',  'nullable' => false],
        'A1DI22'  => ['label' => 'Dimension 2 hauteur',                              'type' => 'DECIMAL',  'nullable' => false],
        'A1ECAV'  => ['label' => 'Dernier P.R.A.',                                   'type' => 'DECIMAL',  'nullable' => false],
        'A1MOYV'  => ['label' => 'Filler',                                           'type' => 'DECIMAL',  'nullable' => false],
        'A1TEND'  => ['label' => 'Type acces (Libre/Fixe)',                          'type' => 'CHAR',     'nullable' => false],
        'A1SASO'  => ['label' => 'H = Supprimer Article en historique REDA09',        'type' => 'CHAR',     'nullable' => false],
        'A1PRIV'  => ['label' => 'Prix Vente Cata creation',                         'type' => 'DECIMAL',  'nullable' => false],
        'A1ACT'   => ['label' => 'Actif',                                            'type' => 'CHAR',     'nullable' => false],
        'A1HOACT' => ['label' => 'Horodatage action',                                'type' => 'TIMESTMP', 'nullable' => false],
        'A1UTIL'  => ['label' => 'Utilisateur',                                      'type' => 'CHAR',     'nullable' => false],
        'A1PGM'   => ['label' => 'Programme',                                        'type' => 'CHAR',     'nullable' => false],
    ];

    /**
     * Resolve the main library from a company code.
     */
    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;

        $library = (string)($company['main_library'] ?? '');
        return $library !== '' ? $library : null;
    }

    /**
     * Get one article by id (A1ART).
     *
     * @return array<string,mixed> Empty array if not found.
     */
    public static function getById(\PDO $pdo, string $companyCode, string $articleId ): array {
        try {
            $library = self::libraryOf($companyCode);
            if ($library === null) return [];

            $articleId = trim($articleId);
            if ($articleId === '') return [];
            $row = self::for($pdo, $library)
              
                ->whereEq('A1ART', $articleId)
                ->first();

            return is_array($row) ? $row : [];

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * Get one article by id (A1ART).
     *
     * @return ?static Empty array if not found.
     */
    public static function getModelById(\PDO $pdo, string $companyCode, string $articleId ): ? static
    {
        try {
            $library = self::libraryOf($companyCode);
            if ($library === null) return null;

            $articleId = trim($articleId);
            if ($articleId === '') return null;
            return self::for($pdo, $library)                
                ->whereEq('A1ART', $articleId)
                ->firstModel();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * Quick existence check.
     */
    public static function exists(\PDO $pdo, string $companyCode, string $articleId): bool
    {
        return self::getById($pdo, $companyCode, $articleId) !== [];
    }

    /**
     * List articles by family (A1FAMI).
     *
     * @return array<int,array<string,mixed>>
     */
    public static function listByFamily(
        \PDO $pdo,
        string $companyCode,
        string $familyCode,
        int $limit = 500
    ): array {
        try {
            $library = self::libraryOf($companyCode);
            if ($library === null) return [];

            $familyCode = trim($familyCode);
            if ($familyCode === '') return [];

            $limit = max(1, min(2000, $limit));

            return self::for($pdo, $library)
                ->select(['A1ART','A1FAMI','A1TYPE','A1CTVA','A1TVA','A1PRIV','A1DATC','A1ACT'])
                ->whereEq('A1FAMI', $familyCode)
                ->orderBy('A1ART', 'ASC')
                ->limit($limit)
                ->get();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

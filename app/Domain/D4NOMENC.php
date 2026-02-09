<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\Http;
use App\Core\clFichier;
use PDO;

/**
 * FCMBI.D4NOMENC — nomenclature article (composition).
 *
 * Indexes: D4L1(D4ART,D4ASS), D4L2(D4ART,D4NORD), D4L3(D4BASE),
 *          D4L4(D4ASS,D4ART), D4L5(D4ART), D4L6(D4ART,D4ASS),
 *          D4L7(D4ASS,D4ART), D4L8(D4ART,D4ASS).
 */
final class D4NOMENC extends clFichier
{
    protected static string $table = 'D4NOMENC';
    protected static array $primaryKey = ['D4BASE'];
    /** @var array<int,array<int,string>> */
    protected static array $uniqueKeys = [
        ['D4BASE'], // D4L3
    ];
    /** @var array<string,array<int,string>> */
    protected static array $indexes = [
        'D4L1' => ['D4ART', 'D4ASS'],
        'D4L2' => ['D4ART', 'D4NORD'],
        'D4L3' => ['D4BASE'],
        'D4L4' => ['D4ASS', 'D4ART'],
        'D4L5' => ['D4ART'],
        'D4L6' => ['D4ART', 'D4ASS'],
        'D4L7' => ['D4ASS', 'D4ART'],
        'D4L8' => ['D4ART', 'D4ASS'],
    ];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'D4ART'  => ['label' => 'Code article maitre',                  'type' => 'CHAR',    'nullable' => false],
        'D4NORD' => ['label' => 'N° ordre',                              'type' => 'NUMERIC', 'nullable' => false],
        'D4ASS'  => ['label' => 'Code article associé',                  'type' => 'CHAR',    'nullable' => false],
        'D4QTE'  => ['label' => "Quantité de l'article associé",        'type' => 'DECIMAL', 'nullable' => false],
        'D4TAUX' => ['label' => 'Val.composant/val.nomenclature',        'type' => 'DECIMAL', 'nullable' => false],
        'D4PRA'  => ['label' => "Prix d'achat en FF",                   'type' => 'DECIMAL', 'nullable' => false],
        'D4SOC'  => ['label' => 'N° de société',                         'type' => 'CHAR',    'nullable' => false],
        'D4STO'  => ['label' => 'Tenu ou non en stock',                  'type' => 'CHAR',    'nullable' => false],
        'D4ANNU' => ['label' => "Tag d'annulation",                     'type' => 'CHAR',    'nullable' => false],
        'D4VALI' => ['label' => 'Tag de MAJ de T6',                      'type' => 'CHAR',    'nullable' => false],
        'D4BASE' => ['label' => 'N° unique base de donnée',              'type' => 'NUMERIC', 'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;

        $library = (string)($company['common_library'] ?? '');
        return $library !== '' ? $library : null;
    }

    public static function bEstNomenclature(PDO $pdo, string $companyCode, string $articleId): bool
    {
        try {
            $companyCode = trim($companyCode);
            $articleId = trim($articleId);
            if ($companyCode === '' || $articleId === '') return false;
            $library = self::libraryOf($companyCode);
            if ($library === null) return false;
            //select count(*) as NBELEM, sum(case d4annu when '*' then 0 else 1 end) as NBELEMOK from fcmbi.d4nomenc where d4art = '%1'
            $row = self::for($pdo, $library)
                ->select(['#COUNT(*) AS NBELEM',"#sum(case d4annu when '*' then 0 else 1 end) as NBELEMOK"])
                ->whereEq('D4ART', $articleId)  
                ->groupBy('D4ART')                              
                ->firstModel();
            if($row) {
                return ( $row->nbelem > 0 && $row->nbelemok > 0) ;
            }    

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
        return false;
    }

    /**
     * Liste la nomenclature d'un article pour une société.
     *
     * @return array<int,static> Lignes typées ; vide si article ou société manquants.
     */
    public static function listByArticle(PDO $pdo, string $companyCode, string $articleId): array
    {
        try {
            $companyCode = trim($companyCode);
            $articleId = trim($articleId);
            if ($companyCode === '' || $articleId === '') return [];

            $library = self::libraryOf($companyCode);
            if ($library === null) return [];

            return self::for($pdo, $library)
                ->whereEq('D4ART', $articleId)
                ->whereEq('D4SOC', $companyCode)
                ->orderBy('D4NORD', 'ASC')
                ->getModels();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * Récupère une ligne par identifiant unique (D4BASE).
     */
    public static function getByBase(PDO $pdo, string $companyCode, string $baseId): ?static
    {
        try {
            $companyCode = trim($companyCode);
            $baseId = trim($baseId);
            if ($companyCode === '' || $baseId === '') return null;

            $library = self::libraryOf($companyCode);
            if ($library === null) return null;

            return self::for($pdo, $library)
                ->whereEq('D4BASE', $baseId)
                ->firstModel();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

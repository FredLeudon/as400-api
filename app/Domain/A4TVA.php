<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use Throwable;

use App\Core\Http;
use App\Core\clFichier;

final class A4TVA extends clFichier
{
    protected static string $table = 'A4TVA';
    protected static array $primaryKey = ['A4CTVA'];
    /** @var array<string,array<int,string>> */
    protected static array $indexes = [
        'A4L0' => ['A4CTVA'],
        'A4L1' => ['A4TTVA'],
        'A4L2' => ['A4LTVA'],
    ];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'A4CTVA'  => ['label' => 'Code T.V.A.',                  'type' => 'CHAR',     'nullable' => false],
        'A4TTVA'  => ['label' => 'Taux de T.V.A. en %',          'type' => 'DECIMAL',  'nullable' => false],
        'A4LTVA'  => ['label' => 'Libelle du type de T.V.A.',    'type' => 'CHAR',     'nullable' => false],
        'A4SOC'   => ['label' => 'N de societe',                 'type' => 'CHAR',     'nullable' => false],
        'A4ACT'   => ['label' => 'Actif',                        'type' => 'CHAR',     'nullable' => false],
        'A4HOACT' => ['label' => 'Horodatage action',            'type' => 'TIMESTMP', 'nullable' => false],
        'A4UTIL'  => ['label' => 'Utilisateur',                  'type' => 'CHAR',     'nullable' => false],
        'A4PGM'   => ['label' => 'Programme',                    'type' => 'CHAR',     'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;

        $library = (string)($company['main_library'] ?? '');
        return $library !== '' ? $library : null;
    }

    public static function readModel(PDO $pdo, string $companyCode, string $codeTva): ?static
    {
        try {
            $companyCode = trim($companyCode);
            if ($companyCode === '') {
                return null;
            }
            $library = self::libraryOf($companyCode);
            if ($library === null) {
                return null;
            }
            $codeTva = trim($codeTva);
            if ($codeTva === '') {
                return null;
            }
            return self::for($pdo, $library)
                ->whereEq('A4CTVA', $codeTva)                
                ->firstModel();
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    public static function exists(PDO $pdo, string $companyCode, string $codeTva): bool
    {
        try {
            $companyCode = trim($companyCode);
            if ($companyCode === '') {
                return false;
            }
            $library = self::libraryOf($companyCode);
            if ($library === null) {
                return false;
            }
            $searchCodeTva = trim($codeTva);
            if ($searchCodeTva === '') {
                return false;
            }
            $row = self::for($pdo, $library)
                ->select(['A4CTVA'])
                ->whereEq('A4CTVA', $searchCodeTva)                
                ->firstModel();
            return ($row != null);
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

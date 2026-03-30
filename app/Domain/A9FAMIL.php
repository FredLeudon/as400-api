<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use Throwable;

use App\Core\Http;
use App\Core\clFichier;

final class A9FAMIL extends clFichier
{
    protected static string $table = 'A9FAMIL';
    protected static array $primaryKey = ['A9FAM'];
    /** @var array<string,array<int,string>> */
    protected static array $indexes = [
        'A9L0' => ['A9FAM'],
        'A9L1' => ['A9LIB'],
    ];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'A9FAM'   => ['label' => 'Code famille',                  'type' => 'CHAR',     'nullable' => false],
        'A9LIB'   => ['label' => 'Nom de la famille',             'type' => 'CHAR',     'nullable' => false],
        'A9REMI'  => ['label' => 'Remise affiliee a la famille',  'type' => 'DECIMAL',  'nullable' => false],
        'A9SOC'   => ['label' => 'N de societe',                  'type' => 'CHAR',     'nullable' => false],
        'A9ACT'   => ['label' => 'Actif',                         'type' => 'CHAR',     'nullable' => false],
        'A9HOACT' => ['label' => 'Horodatage action',             'type' => 'TIMESTMP', 'nullable' => false],
        'A9UTIL'  => ['label' => 'Utilisateur',                   'type' => 'CHAR',     'nullable' => false],
        'A9PGM'   => ['label' => 'Programme',                     'type' => 'CHAR',     'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;

        $library = (string)($company['main_library'] ?? '');
        return $library !== '' ? $library : null;
    }

    public static function readModel(PDO $pdo, string $companyCode, string $familyCode): ?static
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

            $familyCode = trim($familyCode);
            if ($familyCode === '') {
                return null;
            }

            return self::for($pdo, $library)
                ->whereEq('A9FAM', $familyCode)                
                ->firstModel();
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    public static function exists(PDO $pdo, string $companyCode, string $familyCode): bool
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

            $searchFamilyCode = trim($familyCode);
            if ($searchFamilyCode === '') {
                return false;
            }

            $row = self::for($pdo, $library)
                ->select(['A9FAM'])
                ->whereEq('A9FAM', $searchFamilyCode)
                ->firstModel();

            return ($row != null);
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

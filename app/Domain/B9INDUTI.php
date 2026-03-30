<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use Throwable;

use App\Core\Http;
use App\Core\cst;
use App\Core\clFichier;

final class B9INDUTI extends clFichier
{
    protected static string $table = 'B9INDUTI';
    protected static array $primaryKey = ['B9INDU'];
    /** @var array<string,array<int,string>> */
    protected static array $indexes = [
        'B9L0' => ['B9INDU'],
        'B9L1' => ['B9LIB'],
    ];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'B9INDU'  => ['label' => "Indice d'utilisation",                      'type' => 'CHAR',     'nullable' => false],
        'B9LIB'   => ['label' => "Libelle de l'indice d'utilisation",         'type' => 'CHAR',     'nullable' => false],
        'B9SOC'   => ['label' => 'N° de société',                               'type' => 'CHAR',     'nullable' => false],
        'B9ACT'   => ['label' => 'Actif',                                       'type' => 'CHAR',     'nullable' => false],
        'B9HOACT' => ['label' => 'Horodatage action',                           'type' => 'TIMESTMP', 'nullable' => false],
        'B9UTIL'  => ['label' => 'Utilisateur',                                 'type' => 'CHAR',     'nullable' => false],
        'B9PGM'   => ['label' => 'Programme',                                   'type' => 'CHAR',     'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;

        $library = (string)($company['main_library'] ?? '');
        return $library !== '' ? $library : null;
    }

    public static function readModel(PDO $pdo, string $companyCode, string $indice): ?static
    {
        try {
            $companyCode = trim($companyCode);
            if ($companyCode === '') null;
            $library = self::libraryOf($companyCode);
            if ($library === null) null;
            $indice = trim($indice);
            if ($indice === '') {
                return null;
            }

            return self::for($pdo, $library)
                ->whereEq('B9INDU', $indice)
                ->firstModel();
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    public static function exists(PDO $pdo, string $companyCode, string $indice): bool
    {
        try {
            $companyCode = trim($companyCode);
            if ($companyCode === '') null;
            $library = self::libraryOf($companyCode);
            if ($library === null) null;            
            $searchIndice = trim((string)($indice));
            if ($searchIndice === '') {
                return false;
            }
            $row = self::for($pdo, $library)
                ->select(['B9INDU'])
                ->whereEq('B9INDU', $searchIndice)
                ->firstModel();
            return ($row != null);
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

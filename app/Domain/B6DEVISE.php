<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use Throwable;

use App\Core\Http;
use App\Core\clFichier;

final class B6DEVISE extends clFichier
{
    protected static string $table = 'B6DEVISE';
    protected static array $primaryKey = ['B6DEVI'];
    /** @var array<string,array<int,string>> */
    protected static array $indexes = [
        'B6L0' => ['B6DEVI'],
        'B6L2' => ['B6LIB'],
    ];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'B6DEVI'  => ['label' => 'Sigle de la devise',              'type' => 'CHAR',     'nullable' => false],
        'B6LIB'   => ['label' => 'Libelle de la devise',            'type' => 'CHAR',     'nullable' => false],
        'B6TAUX'  => ['label' => 'Cours d application devise',      'type' => 'DECIMAL',  'nullable' => false],
        'B6DATE'  => ['label' => 'Date de derniere mise a jour',    'type' => 'NUMERIC',  'nullable' => false],
        'B6DPRA'  => ['label' => 'Date de m.a.j. du p.r.a.',        'type' => 'NUMERIC',  'nullable' => false],
        'B6TAUM'  => ['label' => 'Cours moyen de la devise',        'type' => 'DECIMAL',  'nullable' => false],
        'B6SOC'   => ['label' => 'N de la societe',                 'type' => 'CHAR',     'nullable' => false],
        'B6BASE'  => ['label' => 'No unique base de donnee',        'type' => 'NUMERIC',  'nullable' => false],
        'B6ACT'   => ['label' => 'Actif',                           'type' => 'CHAR',     'nullable' => false],
        'B6HOACT' => ['label' => 'Horodatage action',               'type' => 'TIMESTMP', 'nullable' => false],
        'B6UTIL'  => ['label' => 'Utilisateur',                     'type' => 'CHAR',     'nullable' => false],
        'B6PGM'   => ['label' => 'Programme',                       'type' => 'CHAR',     'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;

        $library = (string)($company['main_library'] ?? '');
        return $library !== '' ? $library : null;
    }

    public static function readModel(PDO $pdo, string $companyCode, string $Id): static
    {
        try {
            $companyCode = trim($companyCode);
            if ($companyCode === '') null;

            $library = self::libraryOf($companyCode);
            if ($library === null) null;

            $Id = trim($Id);
            if ($Id === '') null;

            $row = self::for($pdo, $library)
                ->whereEq('B6DEVI', $Id)
                ->whereEq('B6SOC', $companyCode)
                ->firstModel();

            return $row;
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

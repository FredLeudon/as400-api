<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class CATALOGUE extends clFichier
{
    protected static string $table = 'CATALOGUE';
    protected static array $primaryKey = ['CAID'];

    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'CAID'    => ['label' => 'Id du fichier catalogue', 'type' => 'DECIMAL',  'nullable' => false],
        'CALIB'   => ['label' => 'Libellé',                 'type' => 'CHAR',     'nullable' => false],
        'CAACT'   => ['label' => 'Actif',                   'type' => 'CHAR',     'nullable' => false],
        'CAHOACT' => ['label' => 'Horodatage action',       'type' => 'TIMESTMP', 'nullable' => false],
        'CAUTIL'  => ['label' => 'Utilisateur',             'type' => 'CHAR',     'nullable' => false],
        'CAPGM'   => ['label' => 'Programme',               'type' => 'CHAR',     'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;

        $library = (string)($company['main_library'] ?? '');
        return $library !== '' ? $library : null;
    }

    /**
     * Get one catalogue by id (CAID).
     *
     * @return array<string,mixed> Empty array if not found.
     */
    public static function getById(PDO $pdo, string $companyCode, string $id): array
    {
        try {
            $library = self::libraryOf($companyCode);
            if ($library === null) return [];

            $id = trim($id);
            if ($id === '') return [];

            $row = self::for($pdo, $library)
                ->select(['CAID','CALIB','CAACT','CAHOACT','CAUTIL','CAPGM'])
                ->whereEq('CAID', $id)
                ->first();

            return is_array($row) ? $row : [];

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * Get one catalogue model by id (CAID).
     *
     * @return ?static
     */
    public static function getModelById(PDO $pdo, string $companyCode, string $id): ?static
    {
        try {
            $library = self::libraryOf($companyCode);
            if ($library === null) return null;

            $id = trim($id);
            if ($id === '') return null;

            return self::for($pdo, $library)
                ->select(['CAID','CALIB','CAACT','CAHOACT','CAUTIL','CAPGM'])
                ->whereEq('CAID', $id)
                ->firstModel();

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
    
    /**
     * Like get(), but returns hydrated model instances instead of raw arrays.
     *
     * @return array<int,static>
     */
    
    public static function allModels(PDO $pdo, string $companyCode): ?array
    {
        try {
            $library = self::libraryOf($companyCode);
            if ($library === null) return [];
            return self::for($pdo, $library)
                ->select(['CAID','CALIB','CAACT','CAHOACT','CAUTIL','CAPGM'])
                ->orderBy('CAID','ASC')
                ->getModels();
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

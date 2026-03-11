<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use Throwable;

use App\Core\Http;
use App\Core\clFichier;

final class FSFOUSOC extends clFichier
{
    protected static string $table = 'FSFOUSOC';
    protected static array $primaryKey = ['FSFOUR', 'FSSOC'];

    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'FSFOUR'  => ['label' => 'Fournisseur',                           'type' => 'CHAR',     'nullable' => false],
        'FSCREM'  => ['label' => 'Premiere remise accordee',              'type' => 'DECIMAL',  'nullable' => false],
        'FSEO2'   => ['label' => 'ET/OU 2eme remise',                     'type' => 'CHAR',     'nullable' => false],
        'FSREM2'  => ['label' => 'Deuxieme remise accordee',              'type' => 'DECIMAL',  'nullable' => false],
        'FSEO3'   => ['label' => 'ET/OU 3eme remise',                     'type' => 'CHAR',     'nullable' => false],
        'FSREM3'  => ['label' => 'Troisieme remise accordee',             'type' => 'DECIMAL',  'nullable' => false],
        'FSRFA'   => ['label' => 'Remise de fin d annee',                 'type' => 'DECIMAL',  'nullable' => false],
        'FSESCT'  => ['label' => 'Escompte en % pour paiement comptant',  'type' => 'DECIMAL',  'nullable' => false],
        'FSPNET'  => ['label' => 'Prix net \"PN\"',                       'type' => 'CHAR',     'nullable' => false],
        'FSEXCL'  => ['label' => 'Exclusivite O/N',                       'type' => 'CHAR',     'nullable' => false],
        'FSRAL'   => ['label' => 'Tag de RAL gere',                       'type' => 'CHAR',     'nullable' => false],
        'FSSOC'   => ['label' => 'N de societe',                          'type' => 'CHAR',     'nullable' => false],
        'FSACT'   => ['label' => 'Actif',                                 'type' => 'CHAR',     'nullable' => false],
        'FSHOACT' => ['label' => 'Horodatage action',                     'type' => 'TIMESTMP', 'nullable' => false],
        'FSUTIL'  => ['label' => 'Utilisateur',                           'type' => 'CHAR',     'nullable' => false],
        'FSPGM'   => ['label' => 'Programme',                             'type' => 'CHAR',     'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;

        $library = (string)($company['main_library'] ?? '');
        return $library !== '' ? $library : null;
    }

    /**
     * Get one row by supplier + company (FSFOUR, FSSOC).
     */
    public static function getModel(PDO $pdo, string $companyCode, string $supplierId): static
    {
        try {
            $companyCode = trim($companyCode);
            if ($companyCode === '') null;

            $library = self::libraryOf($companyCode);
            if ($library === null) null;

            $supplierId = trim($supplierId);
            if ($supplierId === '') null;

            $row = self::for($pdo, $library)
                ->whereEq('FSFOUR', $supplierId)
                ->whereEq('FSSOC', $companyCode)
                ->firstModel();

            return $row;
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

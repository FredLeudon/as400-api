<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\Http;
use App\Core\clFichier;

final class ASARTSOC extends clFichier
{
    protected static string $table = 'ASARTSOC';
    protected static array $primaryKey = ['ASART', 'ASSOC'];
    /** @var array<int,array<int,string>> */
    protected static array $uniqueKeys = [
        ['ASART', 'ASSOC'], // ASL0
        ['ASSOC', 'ASART'], // ASL1
    ];
    /** @var array<string,array<int,string>> */
    protected static array $indexes = [
        'ASL0'   => ['ASART', 'ASSOC'],
        'ASL1'   => ['ASSOC', 'ASART'],
        'ASL2'   => ['ASANCI'],
        'ASL3'   => ['ASANCI', 'ASSOC'],
        // Source mentions A1ART; using ASART to match ASARTSOC columns.
        'A1C0L0' => ['ASART'],
        'A1C0L1' => ['ASART'],
    ];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'ASART'   => ['label' => 'Article',                                             'type' => 'CHAR',     'nullable' => false],
        'ASSUSP'  => ['label' => "Tag de mise en suspens de l'approvisionnement",       'type' => 'CHAR',     'nullable' => false],
        'ASANCI'  => ['label' => 'Ancien code article pour transfert S36',              'type' => 'CHAR',     'nullable' => false],
        'ASMINS'  => ['label' => 'Quantite de stock minimum',                           'type' => 'DECIMAL',  'nullable' => false],
        'ASMAXS'  => ['label' => 'Quantite de stock maximum',                           'type' => 'DECIMAL',  'nullable' => false],
        'ASSOC'   => ['label' => 'Numero de societe',                                   'type' => 'CHAR',     'nullable' => false],
        'ASACT'   => ['label' => 'Actif',                                               'type' => 'CHAR',     'nullable' => false],
        'ASHOACT' => ['label' => 'Horodatage action',                                   'type' => 'TIMESTMP', 'nullable' => false],
        'ASUTIL'  => ['label' => 'Utilisateur',                                         'type' => 'CHAR',     'nullable' => false],
        'ASPGM'   => ['label' => 'Programme',                                           'type' => 'CHAR',     'nullable' => false],
    ];

     /**
     * Get one row by article + company (ASART, ASSOC).
     *
     * @return array<string,mixed> Empty array if not found.
     */
    public static function getByCompanyProduct(
        \PDO $pdo,
        string $companyCode,
        string $articleId
    ): array {
        try {            
            $library = 'MATIS';
            $articleId = trim($articleId);
            if ($articleId === '') return [];
            $row = self::for($pdo, $library)
                ->whereEq('ASART', $articleId)
                ->whereEq('ASSOC', $companyCode)
                ->first();
            return is_array($row) ? $row : [];

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    public static function getModelByCompanyProduct(
        \PDO $pdo,
        string $companyCode,
        string $articleId
    ): ?static 
    {
        try {            
            $library = 'MATIS';
            $articleId = trim($articleId);
            if ($articleId === '') null;
            $row = self::for($pdo, $library)
                ->whereEq('ASART', $articleId)
                ->whereEq('ASSOC', $companyCode)
                ->firstModel();
            return $row;

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

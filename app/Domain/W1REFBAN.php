<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use Throwable;
//use App\Core\DbTable;
use App\Core\Http;

final class W1REFBAN
{
     public static function get(PDO $pdo, string $companyCode, string $supplierId): array
    {
        try {
            $company = Company::get($companyCode);
            if (!$company) return [];
            $sql = "SELECT *
                    FROM {$company['main_library']}.W1REFBAN
                    WHERE W1FOUR = :supplierID fetch first 1 rows only";                    
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':supplierID', $supplierId, PDO::PARAM_STR);
            $stmt->execute();            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!$row){
                return [];
            }
            return $row;
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
    /*
    private const TABLE = 'W1REFBAN';
    private const PK    = 'W1FOUR';

    private const COLS = [
        'W1FOUR',
        'W1CHAR',
        'W1TVA',
        'W1BIC',
        'W1IBAN',
        'W1ADR1',
        'W1ADR2',
        'W1EMBE',
        'W1ACT',
        'W1HOACT',
        'W1UTIL',
        'W1PGM',
    ];

    private static function gateway(string $companyCode): ?DbTable
    {
        $company = Company::get($companyCode);
        if (!$company) return null;

        return new DbTable(
            library: (string)$company['main_library'],   // MATIS
            table: self::TABLE,
            primaryKey: self::PK,
            columns: self::COLS
        );
    }

    public static function get(PDO $pdo, string $companyCode, string $supplierId): array
    {
        try {
            $gw = self::gateway($companyCode);
            if (!$gw) return [];

            return $gw->get($pdo, $supplierId) ?? [];
        } catch (Throwable $e) {
            Http::respond(500, [
                'error' => 'Internal server error',
                'from'  => 'W1REFBAN::get',
                'data'  => $e->getMessage(),
            ]);
        }
    }

    public static function update(PDO $pdo, string $companyCode, string $supplierId, array $patch): bool
    {
        try {
            $gw = self::gateway($companyCode);
            if (!$gw) return false;

            return $gw->update($pdo, $supplierId, $patch);
        } catch (Throwable $e) {
            Http::respond(500, [
                'error' => 'Internal server error',
                'from'  => 'W1REFBAN::update',
                'data'  => $e->getMessage(),
            ]);
        }
    }

    public static function delete(PDO $pdo, string $companyCode, string $supplierId): bool
    {
        try {
            $gw = self::gateway($companyCode);
            if (!$gw) return false;

            return $gw->delete($pdo, $supplierId);
        } catch (Throwable $e) {
            Http::respond(500, [
                'error' => 'Internal server error',
                'from'  => 'W1REFBAN::delete',
                'data'  => $e->getMessage(),
            ]);
        }
    }
    */
}
<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use Throwable;
//use App\Core\DbTable;
use App\Core\Http;

final class C0LIBART
{

    /*
    private const TABLE = 'C0LIBART';

    // clé composée
    private const PK = ['C0ART', 'C0SOC', 'C0LANG', 'C0NUM'];

    private const COLS = [
        'C0ART',
        'C0NUM',
        'C0LANG',
        'C0SOC',
        'C0LIB',
        'C0BASE',
        'C0ACT',
        'C0HOACT',
        'C0UTIL',
        'C0PGM',
    ];

    private static function gateway(string $companyCode): ?DbTable
    {
        $company = Company::get($companyCode);
        if (!$company) return null;

        return new DbTable(
            library: (string)$company['main_library'], // MATIS
            table: self::TABLE,
            primaryKey: self::PK,
            columns: self::COLS
        );
    }

    public static function get(
        PDO $pdo,
        string $companyCode,
        string $articleCode,
        string $soc,
        string $lang,
        string $num
    ): array {
        try {
            $gw = self::gateway($companyCode);
            if (!$gw) return [];

            $key = [
                'C0ART'  => $articleCode,
                'C0SOC'  => $soc,
                'C0LANG' => $lang,
                'C0NUM'  => $num,
            ];

            return $gw->get($pdo, $key) ?? [];
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    public static function delete(
        PDO $pdo,
        string $companyCode,
        string $articleCode,
        string $soc,
        string $lang,
        string $num
    ): bool {
        try {
            $gw = self::gateway($companyCode);
            if (!$gw) return false;

            $key = [
                'C0ART'  => $articleCode,
                'C0SOC'  => $soc,
                'C0LANG' => $lang,
                'C0NUM'  => $num,
            ];

            return $gw->delete($pdo, $key);
        } catch (Throwable $e) {
            Http::respond(500, [
                'error' => 'Internal server error',
                'from'  => 'C0LIBART::delete',
                'data'  => $e->getMessage(),
            ]);
        }
    }

    public static function update(
        PDO $pdo,
        string $companyCode,
        string $articleCode,
        string $soc,
        string $lang,
        string $num,
        array $patch
    ): bool {
        try {
            $gw = self::gateway($companyCode);
            if (!$gw) return false;

            $key = [
                'C0ART'  => $articleCode,
                'C0SOC'  => $soc,
                'C0LANG' => $lang,
                'C0NUM'  => $num,
            ];

            return $gw->update($pdo, $key, $patch);
        } catch (Throwable $e) {
            Http::respond(500, [
                'error' => 'Internal server error',
                'from'  => 'C0LIBART::update',
                'data'  => $e->getMessage(),
            ]);
        }
    }
    */
    public static function get(PDO $pdo, string $companyCode, string $productId, string $langId): array
    {
        try {
            $company = Company::get($companyCode);
            if (!$company) return [];
            $sql = "SELECT {$company['main_library']}.C0LIBART.*, RRN({$company['main_library']}.C0LIBART) AS NUMROW
                    FROM {$company['main_library']}.C0LIBART
                    WHERE C0ART = :productId AND C0LANG = :LangId AND C0SOC = :companyCode fetch first 1 rows only";                    
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':productId', $productId, PDO::PARAM_STR);
            $stmt->bindValue(':LangId', $langId, PDO::PARAM_STR);
            $stmt->bindValue(':companyCode', ($company['mbi']) ? '' : $companyCode, PDO::PARAM_STR);
            $stmt->execute();            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!$row){
                return [];
            }            
    
            return $row;
        } catch (Throwable $e) {
            Http::respond(500, ['error' => 'Internal server error', 'from' => 'C0LIBART::get', 'data' => $e->getMessage()]);
        }
    }  
    
    public static function all(PDO $pdo, string $companyCode, string $productId): array
    {
        try {
            $company = Company::get($companyCode);
            if (!$company) return [];
            // Liste des langues (idéalement déjà triée dans C2LANGUE::all)
            $languages = C2LANGUE::all($pdo, $companyCode);   
            $rows = [];
            foreach ($languages as $lang) {
                $langId = (string)($lang['C2LANG'] ?? '');             
                if ($langId === '') {
                    continue;
                }
                $rows[$langId] = self::get($pdo, $companyCode, $productId, $langId);
            }
            return $rows;
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }  

}    




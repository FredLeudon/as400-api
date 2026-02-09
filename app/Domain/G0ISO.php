<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use Throwable;

use App\Core\Http;

final class G0ISO
{
    /**
     * Cache en mémoire pour la table PROGESCOM.G0ISO.
     *
     * @var array<string,array{country_code:string,country:string,iso:string}>
     */
    private static array $cacheByCountryCode = [];

    /**
     * @var array<string,array{country_code:string,country:string,iso:string}>
     */
    private static array $cacheByIso = [];

    private static bool $cacheLoaded = false;

    private static function loadCache(PDO $pdo): void
    {
        if (self::$cacheLoaded) {
            return;
        }

        $sql = "SELECT G0PAY, G0LIBELLE, G0ISO
                FROM PROGESCOM.G0ISO";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            $rows = [];
        }

        self::$cacheByCountryCode = [];
        self::$cacheByIso = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $pays = [
                'country_code' => (string)($row['G0PAY'] ?? ''),
                'country'      => (string)($row['G0LIBELLE'] ?? ''),
                'iso'          => (string)($row['G0ISO'] ?? ''),
            ];
            if ($pays['country_code'] !== '') {
                self::$cacheByCountryCode[strtoupper($pays['country_code'])] = $pays;
            }
            if ($pays['iso'] !== '') {
                self::$cacheByIso[strtoupper($pays['iso'])] = $pays;
            }
        }

        self::$cacheLoaded = true;
    }

    public static function get(PDO $pdo, string $countryCode): array
    {
        $pays = ['country_code' => '', 'country' => '', 'iso' => ''];
        try {
            self::loadCache($pdo);
            $key = strtoupper($countryCode);
            return self::$cacheByCountryCode[$key] ?? $pays;
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
        return $pays;
    }

    public static function byISO(PDO $pdo, string $iso2): array
    {
        $pays = ['country_code' => '', 'country' => '', 'iso' => ''];
        try {
            self::loadCache($pdo);
            $key = strtoupper($iso2);
            return self::$cacheByIso[$key] ?? $pays;
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
        return $pays;
    }
}

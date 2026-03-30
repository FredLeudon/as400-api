<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use Throwable;

use App\Core\Http;
use App\Core\clFichier;

final class G0ISO extends clFichier
{
    protected static string $table = 'G0ISO';
    protected static array $primaryKey = ['G0PAY'];
    /** @var array<string,array<int,string>> */
    protected static array $indexes = [
        'G0ISOL0' => ['G0PAY'],
        'G0ISOL1' => ['G0ISO'],
    ];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'G0LIBELLE' => ['label' => 'Nom du Pays', 'type' => 'CHAR', 'nullable' => false],
        'G0ISO' => ['label' => 'Code ISO 3166-1 alpha-2', 'type' => 'CHAR', 'nullable' => false],
        'G0PAY' => ['label' => 'Code Pays dans G0PAYS', 'type' => 'CHAR', 'nullable' => false],
    ];

    private const LIBRARY = 'PROGESCOM';

    private static function emptyCountry(): array
    {
        return [
            'country_code' => '',
            'country' => '',
            'iso' => '',
        ];
    }

    /**
     * @param array<string,mixed>|null $row
     */
    private static function mapCountryRow(?array $row): array
    {
        if (!is_array($row)) {
            return self::emptyCountry();
        }

        return [
            'country_code' => trim((string)($row['G0PAY'] ?? '')),
            'country' => trim((string)($row['G0LIBELLE'] ?? '')),
            'iso' => trim((string)($row['G0ISO'] ?? '')),
        ];
    }

    /**
     * Get country by internal country code (G0PAY).
     *
     * Legacy compatibility:
     * Some old callers use (pdo, companyCode, countryCode).
     * If a 3rd parameter is provided, it is used as country code.
     */
    public static function readModel(PDO $pdo, string $countryCode): ? static
    {
        try {
            $searchCode = strtoupper(trim((string)($countryCode)));
            if ($searchCode === '') {
                return null;
            }

            $row = self::for($pdo, self::LIBRARY)
                ->select(['G0PAY', 'G0LIBELLE', 'G0ISO'])
                ->whereEq('G0PAY', $searchCode)
                ->firstModel();

            return $row;
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * Get country by ISO-2 code (G0ISO).
     */
    public static function readModelByISO(PDO $pdo, string $iso2): ? static
    {
        try {
            $iso2 = strtoupper(trim($iso2));
            if ($iso2 === '') {
                return null;
            }

            $row = self::for($pdo, self::LIBRARY)
                ->select(['G0PAY', 'G0LIBELLE', 'G0ISO'])
                ->whereEq('G0ISO', $iso2)
                ->firstModel();

            return $row;
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
    public static function exists(PDO $pdo, string $countryCode): bool
    {
        try {
            $searchCode = strtoupper(trim((string)($countryCode)));
            if ($searchCode === '') {
                return false;
            }
            $row = self::for($pdo, self::LIBRARY)
                ->select(['G0PAY'])
                ->whereEq('G0PAY', $searchCode)
                ->firstModel();
            return ($row != null);
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

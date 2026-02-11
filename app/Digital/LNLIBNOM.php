<?php
declare(strict_types=1);

namespace App\Digital;

use App\Core\clFichier;
use App\Core\Http;
use PDO;

final class LNLIBNOM extends clFichier
{
    protected static string $table = 'LNLIBNOM';
    protected static array $primaryKey = ['LNCODFAM','LNLANG'];

    protected static array $columns = [
        'LNCODSEG' => ['label'=>'LNCODSEG','type'=>'SMALLINT','nullable'=>true],
        'LNCODFAM' => ['label'=>'LNCODFAM','type'=>'SMALLINT','nullable'=>true],
        'LNCODSSF' => ['label'=>'LNCODSSF','type'=>'SMALLINT','nullable'=>true],
        'LNCODGAM' => ['label'=>'LNCODGAM','type'=>'SMALLINT','nullable'=>true],
        'LNCODSER' => ['label'=>'LNCODSER','type'=>'SMALLINT','nullable'=>true],
        'LNCODMOD' => ['label'=>'LNCODMOD','type'=>'SMALLINT','nullable'=>true],
        'LNLANG' => ['label'=>'LNLANG','type'=>'CHAR','nullable'=>true],
        'LNLIB' => ['label'=>'LNLIB','type'=>'VARCHAR','nullable'=>true],
    ];

    /**
     * Retourne les libellés de nomenclature indexés par langue.
     *
     * $codes accepte au maximum 6 valeurs (indexées de 0 à 5):
     * 0 => LNCODSEG, 1 => LNCODFAM, 2 => LNCODSSF, 3 => LNCODGAM, 4 => LNCODSER, 5 => LNCODMOD.
     * Un filtre de langue peut être ajouté via $lang.
     *
     * @param array<int|string,int|string|null> $codes
     * @return array<string,string> [LNLANG => LNLIB]
     */
    public static function DonneLibelléNomenclatureLangue(PDO $pdo, array $codes, ?string $lang = null): array
    {
        try {
            $library = 'MATIS';
            $map = [
                0 => 'LNCODSEG',
                1 => 'LNCODFAM',
                2 => 'LNCODSSF',
                3 => 'LNCODGAM',
                4 => 'LNCODSER',
                5 => 'LNCODMOD',
            ];
            $aliases = [
                'LNCODESEG' => 'LNCODSEG',
                'LNCODEFAM' => 'LNCODFAM',
                'LNCODESSF' => 'LNCODSSF',
                'LNCODEGAM' => 'LNCODGAM',
                'LNCODESER' => 'LNCODSER',
                'LNCODEMOD' => 'LNCODMOD',
            ];

            $namedCodes = [];
            foreach ($codes as $k => $v) {
                if (is_string($k)) {
                    $key = strtoupper(trim($k));
                    $key = $aliases[$key] ?? $key;
                    $namedCodes[$key] = $v;
                }
            }

            $qb = self::for($pdo, $library)->select(['LNLANG', 'LNLIB']);
            $hasAtLeastOneCode = false;
            $missingLevelDetected = false;

            foreach ($map as $index => $column) {
                $value = $codes[$index] ?? ($namedCodes[$column] ?? null);
                if (is_string($value)) {
                    $value = trim($value);
                    if ($value === '') {
                        $value = null;
                    }
                }

                if ($value === null) {
                    if ($hasAtLeastOneCode) {
                        // Si un niveau est manquant, les niveaux suivants doivent être NULL.
                        $qb->whereNull($column);
                    }
                    $missingLevelDetected = true;
                    continue;
                }

                if ($missingLevelDetected) {
                    throw new \InvalidArgumentException(
                        'Les codes de nomenclature doivent être fournis dans l\'ordre sans niveau manquant.'
                    );
                }

                if (!is_numeric((string)$value)) {
                    throw new \InvalidArgumentException("Code nomenclature invalide pour {$column}");
                }

                $qb->whereEq($column, (int)$value);
                $hasAtLeastOneCode = true;
            }

            if (!$hasAtLeastOneCode) {
                return [];
            }

            if ($lang !== null && trim($lang) !== '') {
                $qb->whereEq('LNLANG', strtoupper(trim($lang)));
            } else {
                $qb->orderBy('LNLANG', 'ASC');
            }

            $rows = $qb->getModels();
            $labels = [];
            foreach ($rows as $row) {
                $data = $row->toArrayLower();
                $lnlang = strtoupper(trim((string)($data['lnlang'] ?? '')));
                if ($lnlang === '') {
                    continue;
                }
                $labels[$lnlang] = isset($data['lnlib']) ? (string)$data['lnlib'] : '';
            }

            return $labels;
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
        return [];
    }
}

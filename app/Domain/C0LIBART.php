<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\clFichier;

final class C0LIBART extends clFichier
{
    protected static string $table = 'C0LIBART';
    protected static array $primaryKey = ['C0ART','C0SOC','C0LANG','C0NUM'];

    /**
     * Columns metadata (AS/400).
     * Rich format: column => [label, type, nullable, key info, etc.]
     */
    protected static array $columns = [
        'C0ART'   => ['label' => 'Article (K1)',                 'type' => 'CHAR',     'nullable' => false, 'key' => 1, 'key_name' => 'K1'],
        'C0NUM'   => ['label' => 'N° du libellé (K2)',           'type' => 'CHAR',     'nullable' => false, 'key' => 2, 'key_name' => 'K2'],
        'C0LANG'  => ['label' => 'Code langue',                 'type' => 'CHAR',     'nullable' => false],
        'C0SOC'   => ['label' => 'N° de société',                'type' => 'CHAR',     'nullable' => false],
        'C0LIB'   => ['label' => 'Libellé associé au tarif',     'type' => 'CHAR',     'nullable' => false],
        'C0BASE'  => ['label' => 'No unique base de donnée',     'type' => 'NUMERIC',  'nullable' => false],
        'C0ACT'   => ['label' => 'Activité',                     'type' => 'CHAR',     'nullable' => false],
        'C0HOACT' => ['label' => 'Horodatage action',            'type' => 'TIMESTMP', 'nullable' => false],
        'C0UTIL'  => ['label' => 'Utilisateur',                  'type' => 'CHAR',     'nullable' => false],
        'C0PGM'   => ['label' => 'Programme',                    'type' => 'CHAR',     'nullable' => false],
    ];

    /**
     * Retourne la valeur de C0SOC à utiliser dans C0LIBART pour une société donnée.
     * Pour les sociétés MBI, C0SOC est vide (""), sinon c'est le code société.
     */
    private static function c0socOf(string $companyCode): string
    {
        $company = Company::get($companyCode);
        if (!$company) {
            return $companyCode;
        }
        return ($company['mbi'] ?? false) ? '' : $companyCode;
    }

    /**
     * Retourne un libellé (C0LIB) pour un article / société / langue.
     * 
     * - On privilégie C0NUM = '0' (libellé principal) quand il existe.
     * - Sinon on prend le premier enregistrement trouvé (C0NUM le plus petit).
     */
    public static function labelFor(\PDO $pdo, string $companyCode, string $productId, string $lang ): ?string 
    {
        try {
            $company = Company::get($companyCode);
            if (!$company) {
                return null;
            }
            $c0soc = self::c0socOf($companyCode);
            $library = (string)($company['main_library'] ?? '');
            if ($library === '') {
                return null;
            }
            $m = self::for($pdo, $library)->select(['C0LIB'])->whereEq('C0ART', $productId)->whereEq('C0SOC', $c0soc)->whereEq('C0LANG', $lang)->firstModel();
            if ($m) {
                $v = $m->c0lib;
                return $v === null ? null : (string)$v;
            } else {
                return null;
            }            
        } catch (\Throwable $e) {
            \App\Core\Http::respond(500, \App\Core\Http::exceptionPayload($e, __METHOD__));
        }
    }    
    public static function allLabelsFor(\PDO $pdo, string $companyCode, string $productId ): ?array 
    {
        $labels = [];
        try {
            $company = Company::get($companyCode);
            if (!$company) {
                return null;
            }
            $c0soc = self::c0socOf($companyCode);
            $library = (string)($company['main_library'] ?? '');
            if ($library === '') {
                return null;
            }
            $m = self::for($pdo, $library)->select([])->whereEq('C0ART', $productId)->whereEq('C0SOC', $c0soc)->orderBy('C0LANG')->orderBy('C0NUM')->getModels();
            if ($m) {
                foreach($m as $label){
                    $labels[$label->c0lang] = $label->c0lib;
                }
                return $labels;
            } else {
                return null;
            }            
        } catch (\Throwable $e) {
            \App\Core\Http::respond(500, \App\Core\Http::exceptionPayload($e, __METHOD__));
        }
    }    
}
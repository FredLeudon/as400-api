<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\clFichier;
use App\Core\cst;

final class PLPTFLOG extends clFichier
{
    protected static string $table = 'PLPTFLOG';
    protected static array $primaryKey = ['PLART', 'PLDEP'];

    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'PLART'  => ['label' => 'Article',    'type' => 'CHAR',    'nullable' => false],
        'PLDEP'  => ['label' => 'Code depot', 'type' => 'CHAR',    'nullable' => false],
        'PLTOT'  => ['label' => 'Qte totale', 'type' => 'NUMERIC', 'nullable' => false],
        'PLSAI'  => ['label' => 'Qte saisie', 'type' => 'NUMERIC', 'nullable' => false],
        'PLRFLX' => ['label' => 'Qte Reflex', 'type' => 'NUMERIC', 'nullable' => false],
        'PLRAL'  => ['label' => 'Qte RAL',    'type' => 'NUMERIC', 'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;

        if($company['code'] == cst::FloVending) {
            return cst::bibFLOVENDING;
        } else {
            return cst::bibMBI;        
        }        
    }

     public static function readModel(\PDO $pdo, string $codeSociété, string $codeArticle) : ? static
    {
        $library = self::libraryOf($codeSociété);
        $row = self::for($pdo,$library)->whereEq('PLART',$codeArticle)->whereEq('PLDEP',$codeSociété)->firstModel();
        return $row;
    }
}

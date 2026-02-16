<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class TTTXT extends clFichier
{
    protected static string $table = 'TTTXT';
    protected static array $primaryKey = ['TTCODE', 'TTTYPE', 'TTTYPCMT', 'TTLIGNE'];
    /** @var array<int,array<int,string>> */
    protected static array $uniqueKeys = [
        ['TTCODE', 'TTTYPE', 'TTTYPCMT', 'TTLIGNE'],
    ];
    /** @var array<string,array<int,string>> */
    protected static array $indexes = [
        'TTL001' => ['TTCODE', 'TTTYPE', 'TTTYPCMT', 'TTLIGNE'],
    ];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'TTCODE'   => ['label' => 'Code Clt/Four/Art/Etc.',     'type' => 'CHAR',    'nullable' => false],
        'TTTYPE'   => ['label' => 'Type Clt/Four/Art/Etc.',     'type' => 'CHAR',    'nullable' => false],
        'TTTYPCMT' => ['label' => 'Nature du commentaire',      'type' => 'CHAR',    'nullable' => false],
        'TTLIGNE'  => ['label' => 'N° de ligne',                'type' => 'DECIMAL', 'nullable' => false],
        'TTTEXTE'  => ['label' => 'Commentaire',                'type' => 'CHAR',    'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);        
        if (!$company) return null;
        
        if($company['code'] == '15' ) {
             return 'FLOVENDING'; 
        } else { 
            return 'FCMBI';
        }
    }

    private static function decodeTextFromHex(string $hex): string
    {
        $hex = strtoupper(trim($hex));
        if ($hex === '') return '';

        $bin = @hex2bin($hex);
        if ($bin === false) return '';

        foreach (['CP297', 'IBM297', 'CP037', 'IBM037', 'CP500', 'IBM500'] as $enc) {
            $converted = @iconv($enc, 'UTF-8//IGNORE', $bin);
            if ($converted !== false && $converted !== '') {
                return (string)$converted;
            }
        }

        $fallback = @iconv('ISO-8859-1', 'UTF-8//IGNORE', $bin);
        return $fallback !== false ? (string)$fallback : '';
    }

    public static function getModelsByType(PDO $pdo, string $companyCode, string $typeCode):array
    {
        //select * from fcmbi.tttxt where tttype = 'ART' and ttcode = '*INIT' and ttligne = 1 order by tttypcmt
      try {
            $companyKey = trim($companyCode);            
            $library = self::libraryOf($companyKey);
            if ($library === null) return [];
            $rows = self::for($pdo, $library)                
                ->whereEq('TTCODE','*INIT')           
                ->whereEq('TTTYPE',$typeCode)
                ->whereEq('TTLIGNE', 1)
                ->orderBy('TTTYPE')
                ->orderBy('TTTYPCMT')
                ->getModels();
            return $rows;

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
    public static function isDisplayable(PDO $pdo, string $companyCode, string $typeCode, string $textCode): bool
    {
        //select * from fcmbi.tttxt where tttype = 'ART' and ttcode = '*INIT' and ttligne = 1 order by tttypcmt
      try {
            $companyKey = trim($companyCode);            
            $library = self::libraryOf($companyKey);
            if ($library === null) false;
            $row = self::for($pdo, $library)                
                ->whereEq('TTCODE','*INIT')           
                ->whereEq('TTTYPE',$typeCode)
                ->whereEq('TTTYPCMT',$textCode)
                ->whereEq('TTLIGNE', 2)                
                ->orderBy('TTTYPCMT')
                ->firstModel();
                //echo "TTCODE = ".$row->ttcode." TTTYPE = ".$row->tttype." TTTYPCMT = ".$row->tttypcmt." TTLIGNE = ".$row->ttligne." TTTEXTE = ".$row->tttexte;
                
            return ( trim($row->tttexte)  === "1" );
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    public static function allModels( PDO $pdo, string $companyCode, string $objectCode, string $typeCode , string $typeCommentaire): ?array 
    {
        try {
            $companyKey = trim($companyCode);            
            $library = self::libraryOf($companyKey);
            if ($library === null) return [];

            // Ancien code conserve temporairement pour rollback rapide:
            // $rows = self::for($pdo, $library)
            //     ->whereEq('TTCODE',$objectCode)
            //     ->whereEq('TTTYPE',$typeCode)
            //     ->whereEq('TTTYPCMT', $typeCommentaire)
            //     ->orderBy('TTLIGNE', 'ASC')
            //     ->getModels();
            // return $rows;

            // Workaround IBM i ODBC: read TTTEXTE as HEX to avoid driver hang on some bytes during fetch.
            $rawRows = self::for($pdo, $library)
                ->select([
                    'TTCODE',
                    'TTTYPE',
                    'TTTYPCMT',
                    'TTLIGNE',
                    '#HEX(TTTEXTE) AS TTTEXTE_HEX',
                ])
                ->whereEq('TTCODE', $objectCode)
                ->whereEq('TTTYPE', $typeCode)
                ->whereEq('TTTYPCMT', $typeCommentaire)
                ->orderBy('TTLIGNE', 'ASC')
                ->get();

            $prototype = self::for($pdo, $library);
            $types = $prototype->db()->fieldTypes($pdo);
            $rows = [];

            foreach ($rawRows as $rawRow) {
                $hex = isset($rawRow['TTTEXTE_HEX']) ? (string)$rawRow['TTTEXTE_HEX'] : '';
                $rawRow['TTTEXTE'] = self::decodeTextFromHex($hex);
                unset($rawRow['TTTEXTE_HEX']);

                $model = clone $prototype;
                $model->fill($rawRow)->withFieldTypes($types);
                $rows[] = $model;
            }

            return $rows;

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

}

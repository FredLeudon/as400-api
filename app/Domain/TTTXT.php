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
            $rows = self::for($pdo, $library)                
                ->whereEq('TTCODE',$objectCode)
                ->whereEq('TTTYPE',$typeCode)
                ->whereEq('TTTYPCMT', $typeCommentaire)
                ->orderBy('TTLIGNE', 'ASC')
                ->getModels();
            return $rows;

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

}

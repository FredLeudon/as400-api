<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use Throwable;

use App\Core\Http;

final class U3FOURN
{    
    public static function get(PDO $pdo, string $companyCode, string $supplierId): array
    {        
        try {
            $company = Company::get($companyCode);
            if (!$company) return [];
            $sql = "SELECT *
                    FROM {$company['main_library']}.U3FOURN
                    WHERE U3FOUR = :supplierID fetch first 1 rows only";                    
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
}
<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use Throwable;

use App\Core\Http;

final class B8ACTFOU
{    
    public static function get(PDO $pdo, string $companyCode, string $activityId): array
    {
        try {
            $company = Company::get($companyCode);
            if (!$company) return [];
            $sql = "SELECT *
                    FROM {$company['main_library']}.B8ACTFOU
                    WHERE B8ACTI = :activityId fetch first 1 rows only";                    
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':activityId', $activityId, PDO::PARAM_STR);
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

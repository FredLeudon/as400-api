<?php
declare(strict_types=1);

namespace App\Customers;

use PDO;
use Throwable;

use App\Core\Http;
use App\Core\Debug;
use App\Domain\Company;
use App\Domain\G0ISO;
use App\Enums\ContactType;
use App\Enums\CoordonneesType;

final class Customers
{

    public static function getGroupement(PDO $pdo, string $companyCode, string $searchCode): array
    {
        $return = ['code' => '', 'text' => ''];
        try {
            $company = Company::get($companyCode);
            if (!$company) return $return;

            $sql = "SELECT H0GRP, H0LIB FROM {$company['common_library']}.H0GROUP WHERE H0GRP = :search_code";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':search_code', $searchCode, PDO::PARAM_STR);
            $stmt->execute();
            if ($row = $stmt->fetch()) {
                $return = ['code' => $row['H0GRP'], 'text' => $row['H0LIB']];
            }
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
        return $return;
    }

    public static function getQualification(PDO $pdo, string $companyCode, string $searchCode): array
    {
        $return = ['code' => '', 'sigle' => '', 'text' => ''];
        try {
            $library = ($companyCode === '15') ? 'FLOVENFING' : 'FCMBI';
            $sql = "SELECT V8INFO, V8SIGL, V8LIB FROM {$library}.V8QUALIF WHERE V8INFO = :search_code";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':search_code', $searchCode, PDO::PARAM_STR);
            $stmt->execute();
            if ($row = $stmt->fetch()) {
                $return = ['code' => $row['V8INFO'], 'sigle' => $row['V8SIGL'], 'text' => $row['V8LIB']];
            }
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
        return $return;
    }

    public static function getCommercial(PDO $pdo, string $companyCode, string $searchCode): array
    {
        $return = ['code' => '', 'text' => ''];
        try {
            $company = Company::get($companyCode);
            if (!$company) return $return;

            $sql = "SELECT N5CMER, N5LIB FROM {$company['common_library']}.N5COMMER WHERE N5CMER = :search_code";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':search_code', $searchCode, PDO::PARAM_STR);
            $stmt->execute();
            if ($row = $stmt->fetch()) {
                $return = ['code' => $row['N5CMER'], 'text' => $row['N5LIB']];
            }
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
        return $return;
    }

    public static function getAssistant(PDO $pdo, string $companyCode, string $searchCode): array
    {
        $return = ['code' => '', 'text' => ''];
        try {
            $company = Company::get($companyCode);
            if (!$company) return $return;

            $sql = "SELECT H3SECR, H3LIB FROM {$company['common_library']}.H3SECRET WHERE H3SECR = :search_code";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':search_code', $searchCode, PDO::PARAM_STR);
            $stmt->execute();
            if ($row = $stmt->fetch()) {
                $return = ['code' => $row['H3SECR'], 'text' => $row['H3LIB']];
            }
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
        return $return;
    }

    public static function getIndicatif(PDO $pdo, int $searchCode): string
    {
        $return = '';
        try {
            $sql = "SELECT ITIND FROM FCINTERSIT.ITINDTEL WHERE ITID = :search_code";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':search_code', $searchCode, PDO::PARAM_INT);
            $stmt->execute();
            if ($row = $stmt->fetch()) {
                $indTel = $row['ITIND'] ?? '0033';
                $return = str_starts_with($indTel, '00') ? '+' . substr($indTel, 2) : $indTel;
            }
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
        return $return;
    }

    public static function getCoordonnees(
        PDO $pdo,
        string $companyCode,
        string $searchCode,
        ContactType $contactType,
        CoordonneesType $searchType,
        int $searchBase = 0
    ): string {
        $return = '';
        try {
            $sql = "SELECT RTSTID, RTITID, RTLCOO
                    FROM FCINTERSIT.RTREPTEL
                    WHERE RTLETR = :contactType
                      AND RTCODE = :searchCode
                      AND RTCODESOC = :searchCompany
                      AND RTBASE = :searchBase
                      AND RTSTID = :searchType";

            $searchCompany = in_array($companyCode, ['06','38','40'], true) ? '' : $companyCode;

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':contactType', $contactType->value, PDO::PARAM_STR);
            $stmt->bindValue(':searchCode', $searchCode, PDO::PARAM_STR);
            $stmt->bindValue(':searchCompany', $searchCompany, PDO::PARAM_STR);
            $stmt->bindValue(':searchBase', $searchBase, PDO::PARAM_INT);
            $stmt->bindValue(':searchType', $searchType->value, PDO::PARAM_INT);

            Debug::trace('getCoordonnees params', [
                'contactType' => $contactType->value,
                'searchCode' => $searchCode,
                'searchCompany' => $searchCompany,
                'searchBase' => $searchBase,
                'searchType' => $searchType->value,
            ]);

            $stmt->execute();
            while ($row = $stmt->fetch()) {
                $type = CoordonneesType::tryFrom((int)$row['RTSTID']);
                if (in_array($type, [CoordonneesType::TelephoneBureau, CoordonneesType::TelephoneMobile, CoordonneesType::FaxBureau], true)) {
                    $return = self::getIndicatif($pdo, (int)$row['RTITID']) . (string)$row['RTLCOO'];
                } else {
                    $return = (string)$row['RTLCOO'];
                }
            }
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
        return $return;
    }

    public static function getCustomerContacts(PDO $pdo, string $companyCode, string $customerCode, bool $allContacts = false): array
    {
        $contacts = [];
        try {
            $company = Company::get($companyCode);
            if (!$company) return [];

            $library = $company['common_library'];
            $sql = "SELECT * FROM {$library}.D5CONTAC WHERE D5LETR = 'C' AND D5CODE = :customer_code";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':customer_code', $customerCode, PDO::PARAM_STR);
            $stmt->execute();

            while ($row = $stmt->fetch()) {
                $email = '';
                $phone = '';

                if (($row['D5MAIL'] ?? '') !== '') {
                    $email = (string)$row['D5MAIL'];
                } else {
                    $email = self::getCoordonnees($pdo, $companyCode, $customerCode, ContactType::Client, CoordonneesType::MailBureau);
                }

                $phone = self::getCoordonnees($pdo, $companyCode, $customerCode, ContactType::Client, CoordonneesType::TelephoneBureau);
                if ($phone === '') {
                    $phone = self::getCoordonnees($pdo, $companyCode, $customerCode, ContactType::Client, CoordonneesType::TelephoneMobile);
                }

                if ($phone !== '' || $email !== '' || $allContacts) {
                    $contacts[] = [
                        'id'    => $row['D5BASE'],
                        'name'  => $row['D5NOM'],
                        'job'   => $row['D5FONC'],
                        'email' => $email,
                        'phone' => $phone,
                    ];
                }
            }
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
        return $contacts;
    }

    public static function getCustomerMainDeliveryAddress(PDO $pdo, string $companyCode, string $customerCode): array
    {
        $address = [];
        try {
            $company = Company::get($companyCode);
            if (!$company) return [];

            $library = $company['common_library'];
            $sql = "SELECT * FROM {$library}.D5CONTAC
                    WHERE D5LETR = 'L' AND D5NODR = 99 AND D5CODE = :customer_code";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':customer_code', $customerCode, PDO::PARAM_STR);
            $stmt->execute();

            $row = $stmt->fetch();
            if ($row) {
                $address = [
                    'id'       => $row['D5BASE'],
                    'type'     => $row['D5NODR'],
                    'name'     => $row['D5RAIS'],
                    'address1' => $row['D5ADR1'],
                    'address2' => $row['D5ADR2'],
                    'address3' => $row['D5ADR3'],
                    'zipcode'  => $row['D5CPOS'],
                    'city'     => $row['D5VILL'],
                    'country'  => G0ISO::get($pdo, (string)$row['D5CPAY'])['country'] ?? 'error',
                ];
            }
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
        return $address;
    }

    public static function getCustomerAdditionalDeliveryAddresses(PDO $pdo, string $companyCode, string $customerCode, bool $allAddresses = false): array
    {
        $addresses = [];
        try {
            $company = Company::get($companyCode);
            if (!$company) return [];

            $library = $company['common_library'];

            if ($allAddresses) {
                $sql = "SELECT * FROM {$library}.D5CONTAC
                        WHERE D5LETR = 'L' AND D5CODE = :customer_code
                        ORDER BY D5RAIS";
            } else {
                $sql = "SELECT * FROM {$library}.D5CONTAC
                        WHERE D5LETR = 'L' AND D5NODR <> 99 AND D5CODE = :customer_code
                        ORDER BY D5RAIS";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':customer_code', $customerCode, PDO::PARAM_STR);
            $stmt->execute();

            while ($row = $stmt->fetch()) {
                $addresses[] = [
                    'id'       => $row['D5BASE'],
                    'type'     => $row['D5NODR'],
                    'name'     => $row['D5RAIS'],
                    'address1' => $row['D5ADR1'],
                    'address2' => $row['D5ADR2'],
                    'address3' => $row['D5ADR3'],
                    'zipcode'  => $row['D5CPOS'],
                    'city'     => $row['D5VILL'],
                    'country'  => G0ISO::get($pdo, (string)$row['D5CPAY'])['country'] ?? 'error',
                ];
            }
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
        return $addresses;
    }

    public static function getCustomer(PDO $pdo, string $companyCode, string $customerCode, bool $allContacts = false, bool $withAdditionalDeliveryAddresses = false): ?array
    {
        try {
            $company = Company::get($companyCode);
            if (!$company) return null;

            if (!empty($company['mbi'])) {
                $sql = "SELECT * FROM FCMBI.B3CLIENT
                        LEFT OUTER JOIN FCMBI.CSCLISOC ON CSCLI = B3CLI
                        WHERE B3CLI = :customer_code
                        FETCH FIRST 1 ROW ONLY";
            } else {
                $lib = $company['library'];
                $sql = "SELECT {$lib}.B3CLIENT.*, '{$companyCode}' as CSSOC
                        FROM {$lib}.B3CLIENT
                        WHERE B3CLI = :customer_code
                        FETCH FIRST 1 ROW ONLY";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':customer_code', $customerCode, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch();
            if (!$row) return null;
            $qualificationCode = $row['B3FIL3'][13] ?? 'Z';
            return [
                'code'                  => $row['B3CLI'],
                'status'                => $row['B3INPC'],
                'name'                  => $row['B3RAIS'],
                // Adresse de facturation
                'billing_address1'      => $row['B3ADF1'],
                'billing_address2'      => $row['B3ADF2'],
                'billing_address3'      => $row['B3ADF3'],
                'billing_zipcode'       => $row['B3CPF'],
                'billing_city'          => $row['B3VILF'],
                'billing_country_code'  => (string)$row['B3PAYF'] ?? 'xxx',
                'billing_country'       => G0ISO::get($pdo, (string)$row['B3PAYF'])['country'] ?? 'error',
                // Géré par la société
                'billing_company_code'  => $row['CSSOC'],
                'billing_company_name'  => Company::get((string)$row['CSSOC'])['name'] ?? 'error',
                // Adresse de livraison
                'delivery_company_name' => $row['B3RAIL'],
                'delivery_address1'     => $row['B3ADL1'],
                'delivery_address2'     => $row['B3ADL2'],
                'delivery_address3'     => $row['B3ADL3'],
                'delivery_zipcode'      => $row['B3CPL'],
                'delivery_city'         => $row['B3VILL'],
                'delivery_country_code' => (string)$row['B3PAYL'] ?? 'xxx',
                'delivery_country'      => G0ISO::get($pdo, (string)$row['B3PAYL'])['country'] ?? 'error',
                // Commercial
                'sales_agent_code'      => $row['B3CMER'],
                'sales_agent_name'      => self::getCommercial($pdo, $companyCode, (string)$row['B3CMER'])['text'] ?? 'error',
                // Secrétaire
                'sales_secretary_code'  => $row['B3SECR'],
                'sales_secretary_name'  => self::getAssistant($pdo, $companyCode, (string)$row['B3SECR'])['text'] ?? 'error',
                // Groupement
                'group_code'            => $row['B3GRP'],
                'group_name'            => self::getGroupement($pdo, $companyCode, (string)$row['B3GRP'])['text'] ?? 'error',
                // Qualification
                'qualification_type'    => $qualificationCode,
                'qualification_name'    => self::getQualification($pdo, $companyCode, (string)$qualificationCode)['text'] ?? 'error',
                // Adresse de livraison principale
                'main_delivery_address'         => self::getCustomerMainDeliveryAddress($pdo, $companyCode, $customerCode) ?? [],
                // Adresses de livraison supplémentaires
                'additional_delivery_addresses' => $withAdditionalDeliveryAddresses
                    ? (self::getCustomerAdditionalDeliveryAddresses($pdo, $companyCode, $customerCode) ?? [])
                    : [],
                // Contacts
                'contacts' => self::getCustomerContacts($pdo, $companyCode, $customerCode, $allContacts) ?? [],
            ];
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    public static function searchCustomer(
        PDO $pdo,
        string $companyCode,
        ?string $customerCode = '',
        ?array $status = ['C','P','S','A'],
        ?string $ownedCompanyCode = '',
        ?string $companyName = '',
        ?string $siret = '',
        ?bool $searchOnBillingAddress = true,
        ?string $address = '',
        ?string $postalCode = '',
        ?string $city = '',
        ?string $countryCode = '',
        ?string $vatCode = '',
        ?string $salesAgentCode = '',
        ?string $salesAssistantCode = '',
        ?string $groupCode = '',
        ?string $qualificationCode = '',
        ?string $classificationCode = '',
        ?int $limit = 200,
        ?bool $allContacts = false,
        ?bool $withDeliveryAddresses = false,
        ?bool $full = false
    ): array {
        $customers = [];
        try {
            $company = Company::get($companyCode);
            if (!$company) return [];

            $sqlWhereParts = [];

            if (strlen((string)$customerCode) >= 2) {
                $sqlWhereParts[] = " B3CLI like '" . addslashes((string)$customerCode) . "%' ";
            }

            if (strlen((string)$companyName) >= 3) {
                $name = strtolower((string)$companyName);
                if ($searchOnBillingAddress) {
                    $sqlWhereParts[] = " ( lower(B3RAIS) like '" . addslashes($name) . "%' or lower(B3SIGL) like '" . addslashes($name) . "%' ) ";
                } else {
                    $sqlWhereParts[] = " ( lower(B3RAIL) like '" . addslashes($name) . "%' or lower(B3SIGL) like '" . addslashes($name) . "%' ) ";
                }
            }

            if (strlen((string)$address) >= 3) {
                $addr = strtolower((string)$address);
                if ($searchOnBillingAddress) {
                    $sqlWhereParts[] = " ( lower(trim(B3ADF1) || trim(B3ADF2) || trim(B3ADF3)) like '%" . addslashes($addr) . "%' ) ";
                } else {
                    $sqlWhereParts[] = " ( lower(trim(B3ADL1) || trim(B3ADL2) || trim(B3ADL3)) like '%" . addslashes($addr) . "%' ) ";
                }
            }

            if (strlen((string)$postalCode) >= 2) {
                $pc = strtolower((string)$postalCode);
                if ($searchOnBillingAddress) {
                    $sqlWhereParts[] = " lower(B3CPF) like '" . addslashes($pc) . "%' ";
                } else {
                    $sqlWhereParts[] = " lower(B3CPL) like '" . addslashes($pc) . "%' ";
                }
            }

            if (strlen((string)$city) >= 2) {
                $ct = strtolower((string)$city);
                if ($searchOnBillingAddress) {
                    $sqlWhereParts[] = " lower(B3VILF) like '" . addslashes($ct) . "%' ";
                } else {
                    $sqlWhereParts[] = " lower(B3VILL) like '" . addslashes($ct) . "%' ";
                }
            }

            if (is_string($siret) && strlen($siret) === 14) {
                $sBorneMinSiret = str_pad($siret, 14, "0", STR_PAD_RIGHT);
                $sBorneMaxSiret = str_pad($siret, 14, "9", STR_PAD_RIGHT);
                $sqlWhereParts[] = " ( B3SIRT >= '" . addslashes($sBorneMinSiret) . "' and B3SIRT <= '" . addslashes($sBorneMaxSiret) . "') ";
            }

            if (strlen((string)$vatCode) >= 3) {
                $vat = strtolower((string)$vatCode);
                $sqlWhereParts[] = " lower(B3TVAE) like '" . addslashes($vat) . "%' ";
            }

            if (is_array($status) && (
                in_array('C', $status, true) ||
                in_array('P', $status, true) ||
                in_array('S', $status, true) ||
                in_array('A', $status, true)
            )) {
                $statusList = [];
                if (in_array('C', $status, true)) $statusList[] = "'C'";
                if (in_array('P', $status, true)) $statusList[] = "'P'";
                if (in_array('S', $status, true)) $statusList[] = "'S'";
                if (in_array('A', $status, true)) $statusList[] = "'A'";
                $sqlWhereParts[] = " B3INPC in (" . implode(",", $statusList) . ") ";
            }

            if (strlen((string)$ownedCompanyCode) === 2) {
                $sqlWhereParts[] = " CSSOC = '" . addslashes((string)$ownedCompanyCode) . "' ";
            }
            if (strlen((string)$salesAgentCode) >= 1) {
                $sqlWhereParts[] = " B3CMER = '" . addslashes((string)$salesAgentCode) . "' ";
            }
            if (strlen((string)$salesAssistantCode) >= 1) {
                $sqlWhereParts[] = " B3SECR = '" . addslashes((string)$salesAssistantCode) . "' ";
            }
            if (strlen((string)$groupCode) >= 1) {
                $sqlWhereParts[] = " B3GRP = '" . addslashes((string)$groupCode) . "' ";
            }
            if (strlen((string)$qualificationCode) >= 1) {
                $sqlWhereParts[] = " substr( B3FIL3 , 14 , 1 ) = '" . addslashes((string)$qualificationCode) . "' ";
            }
            if (strlen((string)$classificationCode) >= 1) {
                $sqlWhereParts[] = " substr( B3FIL3 , 16 , 3 ) = '" . addslashes((string)$classificationCode) . "' ";
            }

            $sqlWhere = (count($sqlWhereParts) > 0) ? (" WHERE " . implode(" AND ", $sqlWhereParts)) : "";
            $sqlLimit = ($limit !== null && $limit > 0) ? (" FETCH FIRST " . (int)$limit . " ROWS ONLY ") : "";
            $orderBy  = $searchOnBillingAddress ? " ORDER BY B3RAIS " : " ORDER BY B3RAIL ";

            if (!empty($company['mbi'])) {
                $sql = "SELECT B3CLI, B3RAIS, B3INPC, CSSOC
                        FROM FCMBI.B3CLIENT
                        LEFT OUTER JOIN FCMBI.CSCLISOC ON CSCLI = B3CLI
                        {$sqlWhere} {$orderBy} {$sqlLimit}";
            } else {
                $lib = $company['library'];
                $sql = "SELECT {$lib}.B3CLIENT.B3CLI, {$lib}.B3CLIENT.B3RAIS, {$lib}.B3CLIENT.B3INPC,
                               '{$companyCode}' as CSSOC
                        FROM {$lib}.B3CLIENT
                        {$sqlWhere} {$orderBy} {$sqlLimit}";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute();

            while ($row = $stmt->fetch()) {
                if ($full) {
                    $customers[] = self::getCustomer($pdo, $companyCode, (string)$row['B3CLI'], (bool)$allContacts, (bool)$withDeliveryAddresses);
                } else {
                    $customers[] = [
                        'code'   => $row['B3CLI'],
                        'status' => $row['B3INPC'],
                        'name'   => $row['B3RAIS'],
                    ];
                }
            }
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }

        return $customers;
    }
}
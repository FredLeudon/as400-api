<?php
declare(strict_types=1);

namespace App\Prices;

use PDO;
use Throwable;
use DateTimeInterface;
use DateTimeImmutable;

use App\Core\Http;
use App\Core\Debug;
use App\Domain\Company;

final class Prices
{
    public static function getCustomerProductPrice(
        PDO $pdo,
        string $companyCode,
        string $customerCode,
        string $productCode,
        ?DateTimeInterface $date = null,
        ?int $quantity = 1
    ): ?array {
        try {
            $company = Company::get($companyCode);
            if (!$company) return null;
            $commonLib = $company['common_library'];
            $quantity = ($quantity === null || $quantity <= 0) ? 1 : $quantity;
            $dateObj = $date ? DateTimeImmutable::createFromInterface($date) : new DateTimeImmutable('now');
            $dateRef = $dateObj->format('Ymd');
            // 1) bib via BASE.P8
            $bib = 'INSITU';
            $sql = "SELECT P8B1 FROM BASE.P8 WHERE P8NUMS = :company_code FETCH FIRST 1 ROW ONLY";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':company_code', $companyCode, PDO::PARAM_STR);
            $stmt->execute();
            if ($row = $stmt->fetch()) {
                $bib = trim((string)$row['P8B1']);
                if ($bib === '') $bib = 'INSITU';
            }
            // 2) tarif client
            $sql = "SELECT B3TARI FROM {$commonLib}.B3CLIENT WHERE B3CLI = :customer_code FETCH FIRST 1 ROW ONLY";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':customer_code', $customerCode, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch();
            if (!$row) {
                Http::respond(404, ['error' => 'Customer not found', 'customer' => $customerCode]);
            }
            $tarif = trim((string)$row['B3TARI']);
            // 3) famille article
            $sql = "SELECT A1FAMI FROM MATIS.A1ARTICL WHERE A1ART = :product_code FETCH FIRST 1 ROW ONLY";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':product_code', $productCode, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch();
            if (!$row) {
                Http::respond(404, ['error' => 'Product not found', 'product' => $productCode]);
            }
            $family = trim((string)$row['A1FAMI']);
            // 4) ADDLIBLE (ignore CPF2103)
            $bibUpper = strtoupper($bib);
            if (!preg_match('/^[A-Z0-9_#$@]{1,10}$/', $bibUpper)) {
                Http::respond(500, ['error' => 'Invalid library name resolved', 'bib' => $bib]);
            }
            $cmd = "ADDLIBLE LIB($bibUpper)";
            $sql = "CALL QSYS2.QCMDEXC(:cmd)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':cmd', $cmd, PDO::PARAM_STR);
            try {
                $stmt->execute();
            } catch (Throwable $e) {
                if (str_contains($e->getMessage(), 'CPF2103')) {
                    Debug::trace('ADDLIBLE ignored (already in libl)', ['bib' => $bibUpper, 'msg' => $e->getMessage()]);
                } else {
                    throw $e;
                }
            }
            // 5) gentarsoc
            $sql = "SELECT * FROM TABLE(
                        wdoutils.gentarsoc(
                            pCodeArticle => :product_code,
                            pCodeSociete => :company_code,
                            pCodeClient  => :customer_code,
                            pCodeFamille => :family_code,
                            pQuantite    => :quantity,
                            pDateRef     => :date_ref,
                            pTarif       => :tarif_code,
                            pCatalogue   => 999,
                            pBibFic      => :library_code
                        )
                    )";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':product_code', $productCode, PDO::PARAM_STR);
            $stmt->bindValue(':company_code', $companyCode, PDO::PARAM_STR);
            $stmt->bindValue(':customer_code', $customerCode, PDO::PARAM_STR);
            $stmt->bindValue(':family_code', $family, PDO::PARAM_STR);
            $stmt->bindValue(':quantity', (int)$quantity, PDO::PARAM_INT);
            $stmt->bindValue(':date_ref', $dateRef, PDO::PARAM_STR);
            $stmt->bindValue(':tarif_code', $tarif, PDO::PARAM_STR);
            $stmt->bindValue(':library_code', $bibUpper, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch();
            if (!$row) return null;
            return [             
                'price_reference_date'  => $dateObj->format('d-m-Y'),
                'public_price'          => $row['PRIXBRUT'] ?? 0,
                'price'                 => $row['PRIXNET'] ?? 0,
            ];
        } catch (Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    public static function bulkCustomerProductsPrices(PDO $pdo, string $companyCode, string $customerCode, array $body): array
    {
        // 1) Date optionnelle (ISO 8601)
        $date = null;
        if (array_key_exists('date', $body)) {
            if (!is_string($body['date'])) {
                Http::respond(400, ['error' => '`date` must be a string (ISO 8601)']);
            }
            $date = Http::parseAndValidateDate($body['date']);
            if ($date === null) {
                Http::respond(400, [
                    'error' => 'Invalid date format',
                    'expected' => 'YYYY-MM-DD or YYYY-MM-DDTHH:MM or YYYY-MM-DDTHH:MM:SS',
                ]);
            }
        }
        // 2) Quantité par défaut optionnelle
        $defaultQuantity = 1;
        if (array_key_exists('defaultQuantity', $body)) {
            $defaultQuantity = (int)$body['defaultQuantity'];
            if ($defaultQuantity <= 0) {
                Http::respond(400, ['error' => '`defaultQuantity` must be >= 1']);
            }
        }
        // 3) Items obligatoire
        if (!array_key_exists('items', $body) || !is_array($body['items']) || count($body['items']) === 0) {
            Http::respond(400, ['error' => '`items` (array) is required']);
        }
        $results = [];
        foreach ($body['items'] as $idx => $item) {
            if (!is_array($item)) {
                $results[] = [
                    'index' => $idx,
                    'ok' => false,
                    'error' => 'Invalid item (must be an object)',
                ];
                continue;
            }
            $code = trim((string)($item['code'] ?? ''));
            if ($code === '') {
                $results[] = [
                    'index' => $idx,
                    'ok' => false,
                    'error' => 'Missing item code',
                ];
                continue;
            }
            // Sécurisation minimale du code produit
            if (!preg_match('/^[A-Za-z0-9._\-]+$/', $code)) {
                $results[] = [
                    'index' => $idx,
                    'code' => $code,
                    'ok' => false,
                    'error' => 'Invalid item code format',
                ];
                continue;
            }
            $qty = array_key_exists('quantity', $item) ? (int)$item['quantity'] : $defaultQuantity;
            if ($qty <= 0) {
                $results[] = [
                    'index' => $idx,
                    'code' => $code,
                    'ok' => false,
                    'error' => 'Quantity must be >= 1',
                ];
                continue;
            }
            try {
                $price = self::getCustomerProductPrice(
                    pdo: $pdo,
                    companyCode: $companyCode,
                    customerCode: $customerCode,
                    productCode: $code,
                    date: $date,
                    quantity: $qty
                );

                if ($price === null) {
                    $results[] = [
                        'index' => $idx,
                        'code' => $code,
                        'ok' => false,
                        'error' => 'Price not found',
                    ];
                    continue;
                }

                $results[] = [
                    'index' => $idx,
                    'code' => $code,
                    'quantity' => $qty,
                    'date' => $date ? $date->format('d-m-Y') : (new DateTimeImmutable('now'))->format('d-m-Y'),
                    'ok' => true,
                    'price' => $price,
                ];

            } catch (Throwable $e) {
                // Important: on ne stoppe pas tout le bulk si un item plante
                $results[] = [
                    'index' => $idx,
                    'code' => $code,
                    'ok' => false,
                    'error' => 'Internal error while pricing item',
                    'data' => $e->getMessage(),
                ];
            }
        }
        return [
            'company' => $companyCode,
            'customer' => $customerCode,
            'count' => count($results),
            'date' => $date ? $date->format('Y-m-d') : null,
            'results' => $results,
        ];
    }
}

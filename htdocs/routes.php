<?php
declare(strict_types=1);

$__REQUEST_START__ = microtime(true);

// ------------------------------------------------------------
// Includes (sans Composer) - ADAPTE A TON ARBORESCENCE
// routes.php est dans /www/apis/htdocs donc ../app = /www/apis/app
// ------------------------------------------------------------
$APP_DIR = realpath(__DIR__ . '/../app');
if ($APP_DIR === false) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'APP_DIR not found'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../app/bootstrap.php';

// ------------------------------------------------------------
// Namespaces
// ------------------------------------------------------------
use App\Core\Debug;
use App\Core\Http;
use App\Core\Db;
use App\Core\DbTable;
use App\Core\Mailer;

use App\Domain\Company;
use App\Domain\G0ISO;

use App\Help\Help;
use App\Customers\Customers;
use App\Products\Products;
use App\Prices\Prices;
use App\Phone\Phone;
use App\Suppliers\Suppliers;
use App\Digital\Digital;

// ------------------------------------------------------------
// Debug / errors bootstrap (via ton module Debug)
// ------------------------------------------------------------
$forceHtmlDebug = true; // mets false quand OK
$debug = $forceHtmlDebug || (getenv('APP_DEBUG') === '1');

if (method_exists(Debug::class, 'init')) {
    // Appel en positionnel pour éviter une Fatal Error si le nom du paramètre n'est pas `debug`
    Debug::init($debug);
} else {
    // fallback minimal si Debug::init n’existe pas
    ini_set('display_errors', $debug ? '1' : '0');
    ini_set('display_startup_errors', $debug ? '1' : '0');
    error_reporting(E_ALL);
}

// ------------------------------------------------------------
// Request parsing
// ------------------------------------------------------------
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

$path = preg_replace('#^/api#', '', (string)$uri);
$path = rtrim((string)$path, '/') ?: '/';

// ------------------------------------------------------------
// Env
// ------------------------------------------------------------
$expectedToken = getenv('API_TOKEN') ?: '';
$dsn  = getenv('API_DSN')  ?: 'odbc:DRIVER={IBM i Access ODBC Driver};SYSTEM=localhost;CommitMode=2;CCSID=1208;TranslationOption=1;';
$user = getenv('API_USER') ?: 'LEDUR';
$pass = getenv('API_PASS') ?: 'ifcubmdp';

// ------------------------------------------------------------
// Auth helpers (car tu n’as pas Core/Auth.php)
// ------------------------------------------------------------
function getAuthorizationBearerToken(): string
{
    $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($h === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $h = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }
    if (preg_match('/^\s*Bearer\s+(.+)\s*$/i', $h, $m)) {
        return trim($m[1]);
    }
    return '';
}

function requireToken(string $expectedToken, float $requestStart): void
{
    if ($expectedToken === '') {
        Http::respond(500, ['error' => 'Server token not configured'], $requestStart);
    }
    $token = getAuthorizationBearerToken();
    if ($token === '' || !hash_equals($expectedToken, $token)) {
        header('WWW-Authenticate: Bearer');
        Http::respond(401, ['error' => 'Unauthorized'], $requestStart);
    }
}

// ------------------------------------------------------------
// Public routes: /health, /help, /help/json, /qadbifld
// ------------------------------------------------------------
if (!(($method === 'GET' && $path === '/health') || ($method === 'GET' && $path === '/help') || ($method === 'GET' && $path === '/help/json') || ($method === 'GET' && $path === '/qadbifld'))) {
    requireToken($expectedToken, $__REQUEST_START__);
}

// ------------------------------------------------------------
// PDO (lazy) : on évite de planter /health et /help(/json) si la BDD est indisponible
// ------------------------------------------------------------
$pdoProvider = static function () use ($dsn, $user, $pass, $__REQUEST_START__): \PDO {
    try {
        return Db::connect($dsn, $user, $pass);
    } catch (\Throwable $e) {
        Http::respond(500, [
            'error' => 'Database connection failed',
            'from'  => 'Db::pdo',
            'data'  => $e->getMessage(),
        ], $__REQUEST_START__);
    }
};

// ------------------------------------------------------------
// Routes
// ------------------------------------------------------------
if ($method === 'GET' && $path === '/health') {
    Http::respond(200, ['ok' => true, 'ts' => date('c')], $__REQUEST_START__);
}

if ($method === 'GET' && $path === '/help/json') {
    if (method_exists(Help::class, 'payload')) {
        Http::respond(200, Help::payload(), $__REQUEST_START__);
    }
    if (method_exists(Help::class, 'data')) {
        Http::respond(200, Help::data(), $__REQUEST_START__);
    }
    Http::respond(500, ['error' => 'Help module: missing payload()/data()'], $__REQUEST_START__);
}


if ($method === 'GET' && $path === '/help') {
    // HTML help page
    if (method_exists(Help::class, 'render')) {
        http_response_code(200);
        header('Content-Type: text/html; charset=utf-8');
        echo Help::render();
        exit;
    }

    // Fallback JSON (si render() n'existe pas)
    if (method_exists(Help::class, 'payload')) {
        Http::respond(200, Help::payload(), $__REQUEST_START__);
    }
    if (method_exists(Help::class, 'data')) {
        Http::respond(200, Help::data(), $__REQUEST_START__);
    }
    Http::respond(500, ['error' => 'Help module: missing render()/payload()/data()'], $__REQUEST_START__);
}

// Public route: QADBIFLD diagnostic (no token required)
if ($method === 'GET' && $path === '/qadbifld') {
    require __DIR__ . '/qadbifld.php';
    exit;
}

// --------------------
// GET test mail
// /mail/test
// --------------------
if ($method === 'GET' && $path === '/mail/test') {
    $to = 'frichard@matferbourgeat.com';
    $subject = 'Test mail';
    $html = '<p>coucou c&#39;est mou</p>';

    $ok = Mailer::sendHtml($to, $subject, $html);
    if (!$ok) {
        Http::respond(500, [
            'ok' => false,
            'error' => Mailer::lastError(),
        ], $__REQUEST_START__);
    }

    Http::respond(200, [
        'ok' => true,
        'to' => $to,
        'subject' => $subject,
    ], $__REQUEST_START__);
}

// --------------------
// GET price single
// /company/{company}/customer/{id}/product/{code}/price
// --------------------
if ($method === 'GET' && preg_match('#^/company/([^/]+)/customer/(\d+)/product/([^/]+)/price$#', $path, $m)) {
    [, $company, $customerId, $productCode] = $m;
    $companyCode = Company::codeOf((string)$company);
    if ($companyCode === null) {
        Http::respond(404, ['error' => 'Unknown company', 'company' => (string)$company], $__REQUEST_START__);
    }
    $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;
    if ($quantity <= 0) {
        Http::respond(400, ['error' => 'Quantity must be >= 1'], $__REQUEST_START__);
    }
    $date = null;
    if (isset($_GET['date'])) {
        $date = Http::parseAndValidateDate((string)$_GET['date']);
        if (!$date) {
            Http::respond(400, ['error' => 'Invalid date format'], $__REQUEST_START__);
        }
    }
    $pdo = $pdoProvider();
    $price = Prices::getCustomerProductPrice(
        pdo: $pdo,
        companyCode: $companyCode,
        customerCode: (string)$customerId,
        productCode: (string)$productCode,
        date: $date,
        quantity: $quantity
    );
    if ($price === null) {
        Http::respond(404, ['error' => 'Price not found'], $__REQUEST_START__);
    }
    Http::respond(200, [
        'company'   => $companyCode,
        'customer'  => (string)$customerId,
        'product'   => (string)$productCode,
        'quantity'  => $quantity,
        'price'     => $price,
    ], $__REQUEST_START__);
}

// --------------------
// POST bulk prices
// /company/{company}/customer/{id}/products/prices
// --------------------
if ($method === 'POST' && preg_match('#^/company/([^/]+)/customer/(\d+)/products/prices$#', $path, $m)) {
    [, $company, $customerId] = $m;

    $companyCode = Company::codeOf((string)$company);
    if ($companyCode === null) {
        Http::respond(404, ['error' => 'Unknown company', 'company' => (string)$company], $__REQUEST_START__);
    }

    $body = Http::readJsonBody();
    if (!is_array($body)) {
        Http::respond(400, ['error' => 'Invalid JSON body'], $__REQUEST_START__);
    }

    $pdo = $pdoProvider();

    $payload = Prices::bulkCustomerProductsPrices(
        pdo: $pdo,
        companyCode: $companyCode,
        customerCode: (string)$customerId,
        body: $body
    );

    Http::respond(200, $payload, $__REQUEST_START__);
}

// --------------------
// Customers routes
// --------------------
if ($method === 'GET' && preg_match('#^/company/([^/]+)/customer/(\d+)$#', $path, $m)) {
    [, $company, $customerId] = $m;

    $companyCode = Company::codeOf((string)$company);
    if ($companyCode === null) {
        Http::respond(404, ['error' => 'Unknown company', 'company' => (string)$company], $__REQUEST_START__);
    }

    $allContacts = ((string)($_GET['allContacts'] ?? '0')) === '1';

    $pdo = $pdoProvider();

    $customer = Customers::getCustomer(
        pdo: $pdo,
        companyCode: $companyCode,
        customerCode: (string)$customerId,
        allContacts: $allContacts,
        withAdditionalDeliveryAddresses: false
    );

    if ($customer === null) {
        Http::respond(404, ['error' => 'Unknown customer', 'customer' => (string)$customerId], $__REQUEST_START__);
    }

    Http::respond(200, ['customer' => $customer], $__REQUEST_START__);
}

if ($method === 'GET' && preg_match('#^/company/([^/]+)/customer/(\d+)/contacts$#', $path, $m)) {
    [, $company, $customerId] = $m;

    $companyCode = Company::codeOf((string)$company);
    if ($companyCode === null) {
        Http::respond(404, ['error' => 'Unknown company', 'company' => (string)$company], $__REQUEST_START__);
    }

    $allContacts = ((string)($_GET['allContacts'] ?? '0')) === '1';

    $pdo = $pdoProvider();

    $contacts = Customers::getCustomerContacts(
        pdo: $pdo,
        companyCode: $companyCode,
        customerCode: (string)$customerId,
        allContacts: $allContacts
    );

    Http::respond(200, ['contacts' => $contacts ?? []], $__REQUEST_START__);
}

if ($method === 'GET' && preg_match('#^/company/([^/]+)/customer/(\d+)/main-delivery-address$#', $path, $m)) {
    [, $company, $customerId] = $m;

    $companyCode = Company::codeOf((string)$company);
    if ($companyCode === null) {
        Http::respond(404, ['error' => 'Unknown company', 'company' => (string)$company], $__REQUEST_START__);
    }

    $pdo = $pdoProvider();

    $address = Customers::getCustomerMainDeliveryAddress(
        pdo: $pdo,
        companyCode: $companyCode,
        customerCode: (string)$customerId
    );

    if (empty($address)) {
        Http::respond(404, ['error' => 'Main delivery address not found'], $__REQUEST_START__);
    }

    Http::respond(200, ['main_delivery_address' => $address], $__REQUEST_START__);
}

if ($method === 'GET' && preg_match('#^/company/([^/]+)/customer/(\d+)/delivery-addresses$#', $path, $m)) {
    [, $company, $customerId] = $m;

    $companyCode = Company::codeOf((string)$company);
    if ($companyCode === null) {
        Http::respond(404, ['error' => 'Unknown company', 'company' => (string)$company], $__REQUEST_START__);
    }

    $pdo = $pdoProvider();

    $addresses = Customers::getCustomerAdditionalDeliveryAddresses(
        pdo: $pdo,
        companyCode: $companyCode,
        customerCode: (string)$customerId,
        allAddresses: true
    );

    Http::respond(200, ['delivery_addresses' => $addresses ?? []], $__REQUEST_START__);
}

// --------------------
// Customer search
// --------------------
if ($method === 'GET' && preg_match('#^/company/([^/]+)/customer/search$#', $path, $m))
{
    [$fullMatch, $company] = $m;

    $companyCode = Company::codeOf((string)$company);
    if ($companyCode === null) {
        Http::respond(404, ['error' => 'Unknown company', 'company' => (string)$company], $__REQUEST_START__);
    }

    // Petit help intégré
    if (($_GET['help'] ?? '0') === '1') {
        Http::respond(200, [
            'route' => '/company/{company}/customer/search',
            'method' => 'GET',
            'description' => 'Recherche clients. Retourne un résumé par défaut, ou full=1 pour charger la fiche complète via Customers::getCustomer().',
            'query' => [
                'customerCode' => 'string, >=2 (commence par) — B3CLI',
                'companyName' => 'string, >=3 (commence par) — B3RAIS/B3RAIL (+ B3SIGL)',
                'address' => 'string, >=3 (contient) — adresses fact/liv',
                'postalCode' => 'string, >=2 (commence par) — B3CPF/B3CPL',
                'city' => 'string, >=2 (commence par) — B3VILF/B3VILL',
                'countryCode' => 'ISO2 (FR) ou code interne (optionnel)',
                'vatCode' => 'string, >=3 (commence par) — B3TVAE',
                'siret' => 'string, 14 chiffres',
                'status' => 'ex: C,P,S,A (défaut C,P,S,A)',
                'ownedCompanyCode' => 'CSSOC (2 chars) (optionnel)',
                'salesAgentCode' => 'B3CMER (optionnel)',
                'salesAssistantCode' => 'B3SECR (optionnel)',
                'groupCode' => 'B3GRP (optionnel)',
                'qualificationCode' => 'substr(B3FIL3,14,1) (optionnel)',
                'classificationCode' => 'substr(B3FIL3,16,3) (optionnel)',
                'searchOnBillingAddress' => '0|1 (défaut 1)',
                'limit' => 'entier (défaut 200, max 500)',
                'full' => '0|1 (défaut 0)',
                'allContacts' => '0|1 (défaut 0) (uniquement si full=1)',
                'withDeliveryAddresses' => '0|1 (défaut 0) (uniquement si full=1)',
            ],
        ], $__REQUEST_START__);
    }

    // Lecture params
    $customerCode = (string)($_GET['customerCode'] ?? '');
    $companyName  = (string)($_GET['companyName'] ?? '');
    $siret        = (string)($_GET['siret'] ?? '');
    $address      = (string)($_GET['address'] ?? '');
    $postalCode   = (string)($_GET['postalCode'] ?? '');
    $city         = (string)($_GET['city'] ?? '');
    $countryCode  = (string)($_GET['countryCode'] ?? '');
    $vatCode      = (string)($_GET['vatCode'] ?? '');

    $ownedCompanyCode   = (string)($_GET['ownedCompanyCode'] ?? '');
    $salesAgentCode     = (string)($_GET['salesAgentCode'] ?? '');
    $salesAssistantCode = (string)($_GET['salesAssistantCode'] ?? '');
    $groupCode          = (string)($_GET['groupCode'] ?? '');
    $qualificationCode  = (string)($_GET['qualificationCode'] ?? '');
    $classificationCode = (string)($_GET['classificationCode'] ?? '');

    $searchOnBillingAddress = (string)($_GET['searchOnBillingAddress'] ?? '1') !== '0';

    $limit = (int)($_GET['limit'] ?? 200);
    if ($limit <= 0) $limit = 200;
    if ($limit > 500) $limit = 500;

    $full = (string)($_GET['full'] ?? '0') === '1';
    $allContacts = (string)($_GET['allContacts'] ?? '0') === '1';
    $withDeliveryAddresses = (string)($_GET['withDeliveryAddresses'] ?? '0') === '1';

    // Status "C,P,S,A"
    $statusRaw = (string)($_GET['status'] ?? 'C,P,S,A');
    $status = array_values(array_filter(array_map(function ($x) {
        $x = strtoupper(trim((string)$x));
        return in_array($x, ['C','P','S','A'], true) ? $x : null;
    }, preg_split('/[;,\s]+/', $statusRaw) ?: [])));
    if (count($status) === 0) $status = ['C','P','S','A'];

    // Validations légères
    if ($countryCode !== '' && strlen($countryCode) > 3) {
        Http::respond(400, ['error' => 'Invalid countryCode'], $__REQUEST_START__);
    }

    $pdo = $pdoProvider();

    // ISO2 -> code interne si besoin
    $countryCodeResolved = $countryCode;
    if ($countryCodeResolved !== '' && strlen($countryCodeResolved) === 2) {
        $cc = G0ISO::byISO($pdo, $countryCodeResolved);
        if (!empty($cc['country_code'])) {
            $countryCodeResolved = (string)$cc['country_code'];
        }
    }

    $customers = Customers::searchCustomer(
        $pdo,
        $companyCode,
        $customerCode,
        $status,
        $ownedCompanyCode,
        $companyName,
        $siret,
        $searchOnBillingAddress,
        $address,
        $postalCode,
        $city,
        $countryCodeResolved,
        $vatCode,
        $salesAgentCode,
        $salesAssistantCode,
        $groupCode,
        $qualificationCode,
        $classificationCode,
        $limit,
        $allContacts,
        $withDeliveryAddresses,
        $full
    );

    Http::respond(200, [
        'company' => $companyCode,
        'count' => count($customers),
        'full' => $full,
        'customers' => $customers,
    ], $__REQUEST_START__);
}

// --------------------
// Product search
// --------------------
if ($method === 'GET' && preg_match('#^/company/([^/]+)/product/([^/]+)$#', $path, $m)) {
    [, $company, $productId] = $m;

    $companyCode = Company::codeOf((string)$company);
    if ($companyCode === null) {
        Http::respond(404, ['error' => 'Unknown company', 'company' => (string)$company], $__REQUEST_START__);
    }
    
    $pdo = $pdoProvider();

    $product = Products::get(
        pdo: $pdo,
        companyCode: $companyCode,
        productCode: (string)$productId
    );

    if ($product === null) {
        Http::respond(404, ['error' => 'Unknown product', 'product' => (string)$productId], $__REQUEST_START__);
    }

    Http::respond(200, ['product' => $product], $__REQUEST_START__);
}

// --------------------
// Phone check
// --------------------
if ($method === 'GET' && $path === '/phone/check') {
    $phone   = (string)($_GET['phone'] ?? '');
    $country = (string)($_GET['country'] ?? '');
    $payload = Phone::check($phone, $country);
    Http::respond(200, $payload, $__REQUEST_START__);
}

// --------------------
// Digital - définitions d'attributs
// --------------------
if ($method === 'GET' && preg_match('#^/digital/attributs/definitions$#i', $path)) {
    $pdo = $pdoProvider();
    $defs = Digital::getDefAttributes($pdo,'STANDARD','');
    if (empty($defs)) {
        Http::respond(404, ['error' => 'Aucune définition trouvée'], $__REQUEST_START__);
    }
    Http::respond(200, ['definitions' => $defs], $__REQUEST_START__);
}

if ($method === 'GET' && preg_match('#^/digital/attributs/fichiers$#i', $path)) {
    $pdo = $pdoProvider();
    $defs = Digital::getDefAttributsFichier($pdo, 'STANDARD');
    if (empty($defs)) {
        Http::respond(404, ['error' => 'Aucune définition trouvée'], $__REQUEST_START__);
    }
    Http::respond(200, ['definitions' => $defs], $__REQUEST_START__);
}

if ($method === 'GET' && preg_match('#^/digital/attribut/([^/]+)/definition$#i', $path, $m)) {
    [, $attr] = $m;
    $pdo = $pdoProvider();
    $defs = Digital::getDefAttributes($pdo, (string)$attr);
    if (empty($defs)) {
        Http::respond(404, ['error' => 'Définition non trouvée', 'attribut' => (string)$attr], $__REQUEST_START__);
    }
    Http::respond(200, ['attribut' => (string)$attr, 'definitions' => $defs], $__REQUEST_START__);
}

// --------------------
// Suppliers
// --------------------
if ($method === 'PUT' && preg_match('#^/company/([^/]+)/supplier/order/(\d+)$#', $path, $m)) {
    [, $company, $orderId] = $m;

    $companyCode = Company::codeOf((string)$company);
    if ($companyCode === null) {
        Http::respond(404, ['error' => 'Unknown company', 'company' => (string)$company], $__REQUEST_START__);
    }

    $data = Http::readJsonBody();
    if (!is_array($data)) {
        Http::respond(400, ['error' => 'Invalid JSON body'], $__REQUEST_START__);
    }
    if (!array_key_exists('confirmed', $data) || !is_bool($data['confirmed'])) {
        Http::respond(400, ['error' => '`confirmed` (boolean) is required'], $__REQUEST_START__);
    }

    $confirmed = (bool)$data['confirmed'];

    $confirmedDate = null;
    if (array_key_exists('date', $data)) {
        if (!is_string($data['date'])) {
            Http::respond(400, ['error' => '`date` must be a string'], $__REQUEST_START__);
        }
        $confirmedDate = Http::parseAndValidateDate($data['date']);
        if ($confirmedDate === null) {
            Http::respond(400, ['error' => 'Invalid date format'], $__REQUEST_START__);
        }
    }

    $pdo = $pdoProvider();
    $ok = Suppliers::confirmSupplierOrder($pdo, $companyCode, (int)$orderId, $confirmed, $confirmedDate);
    if ($ok) {
        Http::respond(200, [
            'status' => 'ok',
            'company' => $companyCode,
            'orderId' => (int)$orderId,
            'confirmed' => $confirmed,
            'date' => $confirmedDate?->format('Y-m-d'),
        ], $__REQUEST_START__);
    }
    Http::respond(500, ['error' => 'Failed to confirm order'], $__REQUEST_START__);
}

if ($method === 'PUT' && preg_match('#^/company/([^/]+)/supplier/order/(\d+)/product/([^/]+)/delay$#', $path, $m)) {
    [, $company, $orderId, $productId] = $m;

    $companyCode = Company::codeOf((string)$company);
    if ($companyCode === null) {
        Http::respond(404, ['error' => 'Unknown company', 'company' => (string)$company], $__REQUEST_START__);
    }

    $data = Http::readJsonBody();
    if (!is_array($data)) {
        Http::respond(400, ['error' => 'Invalid JSON body'], $__REQUEST_START__);
    }

    $delay = 0;
    if (array_key_exists('delay', $data)) {
        $delay = (int)$data['delay'];
        if ($delay < 0) Http::respond(400, ['error' => '`delay` must be >= 0'], $__REQUEST_START__);
        if ($delay > 52) Http::respond(400, ['error' => '`delay` max is 52'], $__REQUEST_START__);
    }

    $pdo = $pdoProvider();

    $ok = Suppliers::confirmSupplierOrderProductDelay($pdo, $companyCode, (int)$orderId, (string)$productId, $delay);

    if ($ok) {
        Http::respond(200, [
            'status' => 'ok',
            'company' => $companyCode,
            'orderId' => (int)$orderId,
            'productId' => (string)$productId,
            'delay' => $delay,
        ], $__REQUEST_START__);
    }

    Http::respond(500, ['error' => 'Failed to update delay'], $__REQUEST_START__);
}

// ------------------------------------------------------------
// Not found
// ------------------------------------------------------------
Http::respond(404, ['error' => 'Not found', 'method' => $method, 'path' => $path], $__REQUEST_START__);

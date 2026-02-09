<?php
declare(strict_types=1);
$__REQUEST_START__ = microtime(true);
// ============
// Debug HTML forcé (à désactiver dès que tu as trouvé)
// ============
$forceHtmlDebug = true; // <-- mets false quand c'est OK
$debug = $forceHtmlDebug || (getenv('APP_DEBUG') === '1');

// Affichage des erreurs (utile si pas de proxy qui filtre)
ini_set('display_errors', $debug ? '1' : '0');
ini_set('display_startup_errors', $debug ? '1' : '0');
error_reporting(E_ALL);

// Pour être sûr d'envoyer quelque chose même si buffering
while (ob_get_level() > 0) { ob_end_clean(); }
ob_start();

// Active/désactive les traces facilement
$traceEnabled = true;

function trace(string $label, $data = null): void
{
    global $traceEnabled;
    if (!$traceEnabled) return;

    // Buffer HTML si tu es en debug HTML
    static $buf = '';
    $time = date('H:i:s');

    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $data = print_r($data, true);
        }
        $line = "[$time] $label: $data";
    } else {
        $line = "[$time] $label";
    }

    // 1) log serveur si possible
    error_log($line);

    // 2) buffer HTML (tu pourras l'afficher en cas d'erreur)
    $buf .= htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "\n";

    // Expose dans une variable globale pour la réutiliser
    $GLOBALS['__TRACE_HTML__'] = $buf;
}

// Optionnel: afficher les traces dans la réponse en cas d’erreur (ou même succès)
function traceHtmlBlock(): string
{
    $t = $GLOBALS['__TRACE_HTML__'] ?? '';
    if ($t === '') return '';
    return "<h2>Traces</h2><pre>{$t}</pre>";
}

/**
 * Rend une page HTML d'erreur (avec stack trace)
 */
function renderHtmlException(Throwable $e): void
{
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');

    $msg   = htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $file  = htmlspecialchars($e->getFile(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $line  = (int)$e->getLine();
    $trace = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    echo "<!doctype html><html lang='fr'><head><meta charset='utf-8'>";
    echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";
    echo "<title>Erreur PHP</title>";
    echo "<style>
            body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:20px;line-height:1.4}
            .box{padding:12px;border:1px solid #ddd;border-radius:10px;background:#fafafa}
            pre{white-space:pre-wrap;background:#111;color:#eee;padding:12px;border-radius:10px;overflow:auto}
            h1{margin-top:0}
          </style></head><body>";
    echo "<h1>Erreur PHP</h1>";
    echo "<div class='box'>";
    echo "<p><b>Message :</b> {$msg}</p>";
    echo "<p><b>Fichier :</b> {$file} : {$line}</p>";
    echo "</div>";
    echo "<h2>Stack trace</h2><pre>{$trace}</pre>";
    echo traceHtmlBlock();
    echo "</body></html>";
}

/**
 * Convertit warnings/notices en exceptions
 */
set_error_handler(function (int $severity, string $message, string $file, int $line) {
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});

/**
 * Exceptions non catchées => HTML
 */
set_exception_handler(function (Throwable $e) use ($debug) {
    // Toujours log serveur si possible, mais on ne dépend pas de ça
    error_log((string)$e);

    if ($debug) {
        renderHtmlException($e);
    } else {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Internal server error', 'from' => 'html'], JSON_UNESCAPED_UNICODE);
    }
    exit;
});

/**
 * Capture les fatal errors (E_ERROR, parse errors, etc.)
 */
register_shutdown_function(function () use ($debug) {
    $err = error_get_last();
    if (!$err) return;

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($err['type'], $fatalTypes, true)) return;

    $e = new ErrorException(
        $err['message'] ?? 'Fatal error',
        0,
        $err['type'] ?? E_ERROR,
        $err['file'] ?? 'unknown',
        $err['line'] ?? 0
    );

    if ($debug) {
        renderHtmlException($e);
    } else {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Internal server error'], JSON_UNESCAPED_UNICODE);
    }
    exit;
});


$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Si ton API est sous /api, on retire le préfixe
$path = preg_replace('#^/api#', '', $uri);
$path = rtrim($path, '/') ?: '/';

// Secrets via environnement (recommandé)
$expectedToken = getenv('API_TOKEN') ?: '';
$dsn  = getenv('API_DSN')  ?: 'odbc:DRIVER={IBM i Access ODBC Driver};SYSTEM=localhost;CommitMode=2;CCSID=1208;TranslationOption=1;';
$user = getenv('API_USER') ?: 'LEDUR';
$pass = getenv('API_PASS') ?: 'ifcubmdp';

enum CoordonneesType: int
{
    case TelephoneBureau  = 1;
    case TelephoneMobile  = 2;
    case FaxBureau        = 4;
    case MailBureau       = 62;
}

enum ContactType: string
{
    case Client             = 'C';
    case Fournisseur        = 'F';
    case Livraison          = 'L';
}


$companiesByName = [
    'matfer' => '06',
    'insitu' => '40', 
    'bourgeat' => '38',
    'flovending' => '15',
    'matik' => '69',
    'mbc' => '69',
    'mbhe' => '19',
    'mbhea' => '19',
    'calle' => '31',
    'sogemat' => '91',
    'tecmat' => '96'
];

$companiesByCode = [
    '06' => [
        'name' => 'Matfer',
        'library' => 'MATFER',
        'common_library' => 'FCMBI',
        'code' => '06',
        'mbi' => true
    ],    
    '38' => [
        'name' => 'Bourgeat',
        'library' => 'BOURGEAT',
        'common_library' => 'FCMBI',
        'code' => '38',
        'mbi' => true
    ],
    '40' => [
        'name' => 'In-Situ',
        'library' => 'INSITU',
        'common_library' => 'FCMBI',
        'code' => '40',
        'mbi' => true
    ],    
    '15' => [
        'name' => 'Flo-Vending',
        'library' => 'FLOVENDING',
        'common_library' => 'FLOVENDING',
        'code' => '15',
        'mbi' => false
    ],
    '69' => [
        'name' => 'Matfer-Bourgeat Corporate',
        'library' => 'MATIK',
        'common_library' => 'MATIK',
        'code' => '69',
        'mbi' => false
    ],
    '91' => [
        'name' => 'Sogémat',
        'library' => 'SOGEMAT',
        'common_library' => 'SOGEMAT',
        'code' => '91',
        'mbi' => false
    ],
    '31' => [
        'name' => 'Ets André Calle',
        'library' => 'CALLE',
        'common_library' => 'CALLE',
        'code' => '31',
        'mbi' => false
    ],
    '96' => [
        'name' => 'Tec-Mat',
        'library' => 'TECMAT',
        'common_library' => 'TECMAT',
        'code' => '96',
        'mbi' => false
    ],
    '19' => [
        'name' => 'MBHE-A',
        'library' => 'MBHE',
        'common_library' => 'MBHE',
        'code' => '19',
        'mbi' => false
    ]
];

function respond(int $code, array $payload): void
{
    // Temps de traitement
    $start = $GLOBALS['__REQUEST_START__'] ?? null;
    if ($start !== null) {
        $durationMs = (microtime(true) - $start) * 1000;
        header('X-Response-Time: ' . number_format($durationMs, 2) . ' ms');
    }

    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
    );
    //echo traceHtmlBlock();
    exit;
}

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

function requireToken(string $expectedToken): void
{
    if ($expectedToken === '') {
        respond(500, ['error' => 'Server token not configured']);
    }

    $token = getAuthorizationBearerToken();
    if ($token === '' || !hash_equals($expectedToken, $token)) {
        header('WWW-Authenticate: Bearer');
        respond(401, ['error' => 'Unauthorized']);
    }
}

/**
 * Valide et normalise une date ISO 8601
 * Retourne DateTimeImmutable ou null si invalide
 */
function parseAndValidateDate(string $date): ?DateTimeImmutable
{
    $date = trim($date);

    $formats = [
        'Y-m-d',
        'Y-m-d\TH:i',
        'Y-m-d\TH:i:s',
    ];
    foreach ($formats as $format) {
        $dt = DateTimeImmutable::createFromFormat($format, $date);
        if (!$dt) { 
            continue;
        }
        $errors = DateTimeImmutable::getLastErrors();
        // getLastErrors() peut retourner false
        if ($errors === false) {
            return $dt; // pas d'erreurs remontées
        }
        if (($errors['warning_count'] ?? 0) === 0 && ($errors['error_count'] ?? 0) === 0) {
            return $dt;
        }
    }
    return null;
}

function getCompanyCode(string $company): ?string
{
    global $companiesByName, $companiesByCode;
    $key = strtolower(trim($company));
    // 1️⃣ Recherche par nom
    if (isset($companiesByName[$key])) {
        return $companiesByName[$key];
    }
    // 2️⃣ Recherche directe par code
    if (isset($companiesByCode[$key])) {
        return $companiesByCode[$key]['code'];
    }
    // 3️⃣ Introuvable
    return null;
}

function getCompanyLibrary(string $company): ?string
{
    global $companiesByName, $companiesByCode;
    $key = strtolower(trim($company));
    // 1️⃣ Résolution par nom → code
    if (isset($companiesByName[$key])) {
        $code = $companiesByName[$key];
        return $companiesByCode[$code]['library'] ?? null;
    }
    // 2️⃣ Résolution directe par code
    if (isset($companiesByCode[$key])) {
        return $companiesByCode[$key]['library'] ?? null;
    }
    return null;
}

function getCompany(string $company): ?array
{
    global $companiesByName, $companiesByCode;
    $key = strtolower(trim($company));
    // 1️⃣ Résolution par nom → code
    if (isset($companiesByName[$key])) {
        $code = $companiesByName[$key];
        return $companiesByCode[$code] ?? null;
    }
    // 2️⃣ Résolution directe par code
    if (isset($companiesByCode[$key])) {
        return $companiesByCode[$key] ?? null;
    }
    return null;
}

/**
 * Normalise un terme de recherche pour comparaison accent/casse-insensible.
 */
function normalizeSearchTerm(string $s): string
{
    $s = trim($s);
    if ($s === '') return '';

    // Try to strip accents using iconv (works well in most PHP builds)
    $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    if ($t !== false && $t !== null) {
        $s = $t;
    }

    // Normalize spaces and uppercase
    $s = preg_replace('/\s+/', ' ', $s) ?? $s;
    return strtoupper($s);
}

/**
 * Génère une expression SQL pour comparaison accent/casse-insensible sur IBM i (DB2).
 */
function sqlAccentFoldExpr(string $colExpr): string
{
    // DB2 for i: TRANSLATE(expr, to, from) replaces characters from->to.
    // We fold common French/Latin accented characters to their base letter and then UPPER.
    // Note: lengths of "to" and "from" strings must match.
    $from = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÑÒÓÔÕÖŒÙÚÛÜÝŸàáâãäåæçèéêëìíîïñòóôõöœùúûüýÿ';
    $to   = 'AAAAAAACEEEEIIIINOOOOOOEUUUUYYaaaaaaaceeeeiiiinooooooeuuuuyy';

    // If for any reason strings do not match length, fallback to UPPER only
    if (strlen($from) !== strlen($to)) {
        return "UPPER($colExpr)";
    }

    // Note: we apply UPPER after translate; on IBM i, UPPER respects CCSID.
    return "UPPER(TRANSLATE($colExpr, '$to', '$from'))";
}


/**
 * Connexion PDO réutilisée (évite de reconnecter à chaque route)
 */
function db(string $dsn, string $user, string $pass): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function confirmSupplierOrder(PDO $pdo, string $companyCode, int $orderId, bool $confirmed, ? DateTimeImmutable $confirmedDate = null): bool
{
    try {        
        $company = getCompany($companyCode);
        // implémentation plus tard
        $sqlDate = $confirmedDate ? $confirmedDate->format('dmY') : 0;
        $sql = "UPDATE {$company['library']}.E6CDEFOC 
                SET 
                    E6CONF = :confirmed,
                    E6DATC = :confirmed_date
                WHERE                                     
                    E6NCDE = :order";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':confirmed', $confirmed ? 'O' : 'N', PDO::PARAM_STR);
        if ($confirmedDate !== null) {
            $stmt->bindValue(':confirmed_date',(int)$confirmedDate->format('dmY'), PDO::PARAM_INT);
        } else {
            $stmt->bindValue(':confirmed_date', 0, PDO::PARAM_INT);
        }                
        $stmt->bindValue(':order', $orderId, PDO::PARAM_INT);        
        $stmt->execute();
        // 👉 true si au moins une ligne a été modifiée
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        respond(500, ['error' => 'Internal server error',
        'from' => 'confirmSupplierOrder',
        'data' => $e->getMessage()]);
        return false;
    }
}

function confirmSupplierOrderProductDelay(PDO $pdo, string $companyCode, int $orderId, string $productId, ? int $delay = 0): bool
{
    try {        
        $company = getCompany($companyCode);        
        $sql = "UPDATE {$company['library']}.A8CDEFOU
                SET 
                    A8DELD = :delay
                WHERE                                     
                    A8NCDE = :order 
                  AND
                    A8ART = :product";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':delay', $delay, PDO::PARAM_INT);
        $stmt->bindValue(':order', $orderId, PDO::PARAM_INT);
        $stmt->bindValue(':product', $productId, PDO::PARAM_STR);
        $stmt->execute();
        // 👉 true si au moins une ligne a été modifiée
        return $stmt->rowCount() > 0;
    } catch (Throwable $e) {
        respond(500, ['error' => 'Internal server error',
        'from' => 'confirmSupplierOrderProductDelay',
        'data' => $e->getMessage()]);
        return false;
    }
}
function getCountry(PDO $pdo, string $countryCode): ?array
{
    $pays = [
        'country_code' => '',
        'country' => '',
        'iso' => ''
    ];
    try {        
        $sql = "SELECT G0PAY, G0LIBELLE, G0ISO FROM PROGESCOM.G0ISO WHERE upper(G0PAY) = upper(:country_code)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':country_code', $countryCode, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row) {
            $pays = [
                'country_code' => $row['G0PAY'],
                'country' => $row['G0LIBELLE'],
                'iso' => $row['G0ISO']
            ];            
        }            
    } catch (Throwable $e) {
        respond(500, ['error' => 'Internal server error',
        'from' => 'getCountry',
        'data' => $e->getMessage()]);
    }    
    return $pays;
}
function getCountryByISO(PDO $pdo, string $countryCode): ?array
{
    $pays = [
        'country_code' => '',
        'country' => '',
        'iso' => ''
    ];
    try {        
        $sql = "SELECT G0PAY, G0LIBELLE, G0ISO FROM PROGESCOM.G0ISO WHERE upper(G0ISO) = upper(:country_code)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':country_code', $countryCode, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row) {
            $pays = [
                'country_code' => $row['G0PAY'],
                'country' => $row['G0LIBELLE'],
                'iso' => $row['G0ISO']
            ];            
        }            
    } catch (Throwable $e) {
        respond(500, ['error' => 'Internal server error',
        'from' => 'getCountry',
        'data' => $e->getMessage()]);
    }    
    return $pays;
}

function getGroupement(PDO $pdo, string $companyCode, string $searchCode): ?array 
{
    $return = [
        'code' => '',
        'text' => ''
    ];
    try {        
        $company = getCompany($companyCode);
        $sql = "SELECT H0GRP, H0LIB FROM {$company['common_library']}.H0GROUP WHERE H0grp = :search_code";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':search_code', $searchCode, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row) {
            $return = [
                'code' => $row['H0GRP'],
                'text' => $row['H0LIB']
            ];            
        }            
    } catch (Throwable $e) {
        respond(500, ['error' => 'Internal server error',
        'from' => 'getGroupement',
        'data' => $e->getMessage()]);
    }    
    return $return;
}

function getQualification(PDO $pdo, string $companyCode, string $searchCode): ?array 
{
    $return = [
        'code' => '',
        'sigle' => '',
        'text' => ''
    ];
    try {        
        $company = getCompany($companyCode);
        $library = ($companyCode == '15') ? 'FLOVENFING' : 'FCMBI';
        $sql = "SELECT V8INFO, V8SIGL, V8LIB FROM {$library}.V8QUALIF WHERE V8INFO = :search_code";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':search_code', $searchCode, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row) {
            $return = [
                'code' => $row['V8INFO'],
                'sigle' => $row['V8SIGL'],
                'text' => $row['V8LIB']
            ];            
        }            
    } catch (Throwable $e) {
        respond(500, ['error' => 'Internal server error',
        'from' => 'getQualification',
        'data' => $e->getMessage()]);
    }    
    return $return;
}

function getCommercial(PDO $pdo, string $companyCode, string $searchCode): ?array 
{
    $return = [
        'code' => '',
        'text' => ''
    ];
    try {        
        $company = getCompany($companyCode);
        $sql = "SELECT N5CMER, N5LIB  FROM {$company['common_library']}.N5COMMER WHERE N5CMER = :search_code";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':search_code', $searchCode, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row) {
            $return = [
                'code' => $row['N5CMER'],
                'text' => $row['N5LIB']
            ];            
        }            
    } catch (Throwable $e) {
        respond(500, [
            'error' => 'Internal server error',
            'from'  => 'getCommercial',
            'datas' => [
                'companyCode' => $companyCode,
                'searchCode'  => $searchCode,
                'library'     => $company['common_library'] ?? null,
                'sql'         => debugSql($sql ?? '', [':search_code' => $searchCode]),
            ],
            'data'  => $e->getMessage(),
        ]);
    }    
    return $return;
}

function getIndicatif(PDO $pdo, int $searchCode): ? string
{
    $return = '';
    try {                
        $sql = "SELECT ITIND FROM FCINTERSIT.ITINDTEL WHERE ITID = :search_code";                
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':search_code', (int)$searchCode, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row) {
            $indTel = $row['ITIND'] ?? '0033';
            $return = str_starts_with($indTel, '00') ? '+' . substr($indTel, 2) : $indTel;            
        }            
    } catch (Throwable $e) {
        // Log serveur (regarde logs/error_log)
        respond(500, ['error' => 'Internal server error',
        'from' => 'getIndicatif',
        'data' => $e->getMessage()]);
    }    
    return $return;
}

function getCoordonnees(
    PDO $pdo,
    string $companyCode,
    string $searchCode,
    ContactType $contactType,
    CoordonneesType $searchType,
    int $searchBase = 0
): ?string
{
    $return = '';
    try {                
        $sql = "SELECT 
                    RTSTID, RTITID, RTLCOO 
                FROM 
                    FCINTERSIT.RTREPTEL 
                WHERE 
                    RTLETR = :contactType
                   AND
                    RTCODE = :searchCode
                   AND
                    RTCODESOC = :searchCompany
                   AND
                    RTBASE = :searchBase
                   AND
                    RTSTID = :searchType";                
        $searchCompany = in_array($companyCode, ['06','38','40'], true) ? '' : $companyCode;
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':contactType', $contactType->value, PDO::PARAM_STR);
        $stmt->bindValue(':searchCode', $searchCode, PDO::PARAM_STR);
        $stmt->bindValue(':searchCompany', $searchCompany , PDO::PARAM_STR);
        $stmt->bindValue(':searchBase', $searchBase, PDO::PARAM_INT);
        $stmt->bindValue(':searchType', $searchType->value, PDO::PARAM_INT);

        trace('getCoordonnees params', [
        'contactType' => $contactType->value,
        'contactTypeLen' => strlen($contactType->value),
        'searchCode' => $searchCode,
        'searchCodeLen' => strlen($searchCode),
        'searchCompany' => $searchCompany,
        'searchCompanyLen' => strlen($searchCompany),
        'searchBase' => $searchBase,
        'searchType' => $searchType->value,
        ]);

        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $type = CoordonneesType::tryFrom((int)$row['RTSTID']);
            if (in_array($type, [CoordonneesType::TelephoneBureau, CoordonneesType::TelephoneMobile, CoordonneesType::FaxBureau], true)) {
                $return = getIndicatif($pdo, (int)$row['RTITID']) . (string)$row['RTLCOO'];
            } else {
                $return = (string)$row['RTLCOO'];
            }
        }
    } catch (Throwable $e) {
         // Log serveur (regarde logs/error_log)
        respond(500, ['error' => 'Internal server error',
        'from' => 'getCoordonnees',
        'data' => $e->getMessage()]);
    }    
    return $return;
} 

function getAssistant(PDO $pdo, string $companyCode, string $searchCode): ?array 
{
    $return = [
        'code' => '',
        'text' => ''
    ];
    try {        
        $company = getCompany($companyCode);
        $sql = "SELECT H3SECR , H3LIB  FROM {$company['common_library']}.H3SECRET WHERE H3SECR = :search_code";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':search_code', $searchCode, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row) {
            $return = [
                'code' => $row['H3SECR'],
                'text' => $row['H3LIB']
            ];            
        }            
    } catch (Throwable $e) {
        // Log serveur (regarde logs/error_log)
        respond(500, ['error' => 'Internal server error',
        'from' => 'getAssistant',
        'data' => $e->getMessage()]);
    }    
    return $return;
}

function getCustomerProductPrice(PDO $pdo, string $companyCode, string $customerCode, string $productCode, ?DateTimeInterface $date = null, ?int $quantity = 1): ?array
{
    $price = [];
    try {
        $company = getCompany($companyCode);
        if (!$company) {
            return null;
        }

        $commonLib = $company['common_library'];

        // Valeurs par défaut
        $quantity = ($quantity === null || $quantity <= 0) ? 1 : $quantity;

        // On garde un objet date pour pouvoir formatter proprement dans la réponse
        $dateObj = $date ? DateTimeImmutable::createFromInterface($date) : new DateTimeImmutable('now');
        $dateRef = $dateObj->format('Ymd');

        // 1) Bibliothèque "bib" via BASE.P8 (fallback INSITU)
        $bib = 'INSITU';
        $sql = "SELECT P8B1 FROM BASE.P8 WHERE P8NUMS = :company_code FETCH FIRST 1 ROW ONLY";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':company_code', $companyCode, PDO::PARAM_STR);
        $stmt->execute();
        if ($row = $stmt->fetch()) {
            $bib = trim((string)$row['P8B1']);
            if ($bib === '') $bib = 'INSITU';
        }

        // 2) Tarif client
        $sql = "SELECT B3TARI FROM {$commonLib}.B3CLIENT WHERE B3CLI = :customer_code FETCH FIRST 1 ROW ONLY";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':customer_code', $customerCode, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        if (!$row) {
            // Ici je laisse respond car tu l'utilises ailleurs
            respond(404, ['error' => 'Customer not found', 'customer' => $customerCode]);
        }
        $tarif = trim((string)$row['B3TARI']);

        // 3) Famille article (attention: ici c'est MATIS en dur comme dans ton code)
        $sql = "SELECT A1FAMI FROM MATIS.A1ARTICL WHERE A1ART = :product_code FETCH FIRST 1 ROW ONLY";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':product_code', $productCode, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        if (!$row) {
            respond(404, ['error' => 'Product not found', 'product' => $productCode]);
        }
        $family = trim((string)$row['A1FAMI']);

        // 4) Ajouter la bibliothèque au *LIBL (QCMDEXC)
        // Sécurisation stricte du nom de lib (A-Z0-9_#@$)
        $bibUpper = strtoupper($bib);
        if (!preg_match('/^[A-Z0-9_#$@]{1,10}$/', $bibUpper)) {
            respond(500, ['error' => 'Invalid library name resolved', 'bib' => $bib]);
        }

        $cmd = "ADDLIBLE LIB($bibUpper)";
        $sql = "CALL QSYS2.QCMDEXC(:cmd)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':cmd', $cmd, PDO::PARAM_STR);

        try {
            $stmt->execute();
        } catch (Throwable $e) {
            // CPF2103 = la bibliothèque est déjà présente dans la liste des bibliothèques => non bloquant
            $msg = $e->getMessage();
            if (str_contains($msg, 'CPF2103')) {
                // On ignore et on continue
                trace('ADDLIBLE ignored (already in libl)', ['bib' => $bibUpper, 'msg' => $msg]);
            } else {
                throw $e; // autre erreur => bloquant
            }
        }
        // 5) Appel GenTarSoc (enlever les quotes sur :library_code !)
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
        return [            
            'public_price'     => $row['PRIXNET'] ?? 0,
            'price'            => $row['PRIXBRUT'] ?? 0
        ] ?: null;

    } catch (Throwable $e) {
        respond(500, [
            'error' => 'Internal server error',
            'from'  => 'getCustomerProductPrice',
            'data'  => $e->getMessage()
        ]);
        return null;
    }
}

function getCustomerContacts(PDO $pdo, string $companyCode, string $customerCode, bool $allContacts = false): ?array
{
    $contacts = [];
    try {
        $company = getCompany($companyCode);
        if (!$company) {        
            return null;
        }
        $library = $company['common_library'];
        $sql = "SELECT * FROM {$library}.D5CONTAC WHERE D5LETR = 'C' AND D5CODE = :customer_code";   
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':customer_code', $customerCode, PDO::PARAM_STR);
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $email = '';
            $phone = '';
            if ($row['D5MAIL'] != '') {
                $email = $row['D5MAIL'];
            } else {
                $email = getCoordonnees($pdo, $companyCode, $customerCode, ContactType::Client, CoordonneesType::MailBureau);
            }
            $phone = getCoordonnees($pdo, $companyCode, $customerCode, ContactType::Client, CoordonneesType::TelephoneBureau);
            if ($phone == '') {
                $phone = getCoordonnees($pdo, $companyCode, $customerCode, ContactType::Client, CoordonneesType::TelephoneMobile);
            }
            // Lire le mail dans RTREPTEL 
            if ($phone != '' || $email != '' || $allContacts) {
                $contacts[] = [
                    'id' => $row['D5BASE'],
                    'name' => $row['D5NOM'],
                    'job' => $row['D5FONC'],
                    'email' => $email,
                    'phone' => $phone
                ];
            }
        }    
    } catch (Throwable $e) {
        // Log serveur (regarde logs/error_log)
        respond(500, ['error' => 'Internal server error',
        'from' => 'getCustomerContacts',
        'data' => $e->getMessage()]);  
    }   
    return $contacts;              
}

function getCustomerMainDeliveryAddress(PDO $pdo, string $companyCode, string $customerCode): ?array    
{
    $address = [];
    try {
        $company = getCompany($companyCode);
        if (!$company) {        
            return null;
        }
        $library = $company['common_library'];
        $sql = "SELECT * FROM {$library}.D5CONTAC WHERE D5LETR = 'L' AND D5NODR = 99 AND D5CODE = :customer_code";   
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':customer_code', $customerCode, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        if($row) {
            $address = [
                'id'       => $row['D5BASE'],
                'type'     => $row['D5NODR'],
                'name'     => $row['D5RAIS'],
                'address1' => $row['D5ADR1'],
                'address2' => $row['D5ADR2'],
                'address3' => $row['D5ADR3'],
                'zipcode'  => $row['D5CPOS'],
                'city'     => $row['D5VILL'],                    
                'country'  => getCountry($pdo, $row['D5CPAY'])['country'] ?? 'error',
            ];
        } 
    }
    catch (Throwable $e) {
        // Log serveur (regarde logs/error_log)
        respond(500, ['error' => 'Internal server error',
        'from' => 'getCustomerMainDeliveryAddress',
        'data' => $e->getMessage()]);
    }
    return $address;
}

function getCustomerAdditionalDeliveryAddresses(PDO $pdo, string $companyCode, string $customerCode, bool $allAdresses = false): ?array    
{
    $addresses = [];
    try {
        $company = getCompany($companyCode);
        if (!$company) {        
            return null;
        }
        $library = $company['common_library'];
        if($allAdresses) {
            $sql = "SELECT * FROM {$library}.D5CONTAC WHERE D5LETR = 'L' AND D5CODE = :customer_code ORDER BY D5RAIS";       
        } else {
            $sql = "SELECT * FROM {$library}.D5CONTAC WHERE D5LETR = 'L' AND D5NODR <> 99 AND D5CODE = :customer_code ORDER BY D5RAIS";       
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
                'country'  => getCountry($pdo, $row['D5CPAY'])['country'] ?? 'error',
            ];
        } 
    }
    catch (Throwable $e) {
        // Log serveur (regarde logs/error_log)
        respond(500, ['error' => 'Internal server error',
        'from' => 'getCustomerMainDeliveryAddress',
        'data' => $e->getMessage()]);
    }
    return $addresses;
}
function searchCustomer(
    PDO $pdo,
    string $companyCode,
    ?string $customerCode = '',
    ?array $status = ['C','P','S','A'],
    ?string $ownedCompanyCode = '',
    ?string $companyName = '',
    ?string $siret = '',
    ?bool $shearchOnBillingAddress = true,
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
): ?array
{
    $customers = [];
    try {
        $company = getCompany($companyCode);
        if (!$company) {        
            return null;
        }
        $sqlWhereParts = [];
        if (strlen($customerCode) >= 2) {
            $sqlWhereParts[] = " B3CLI like '".$customerCode."%' ";
        }
        if (strlen($companyName) >= 3) { 
            if($shearchOnBillingAddress) {
                $sqlWhereParts[] = " ( lower(B3RAIS) like '".strtolower($companyName)."%' or lower(B3SIGL) like '".strtolower($companyName)."%' ) ";
            } else {
                $sqlWhereParts[] = " ( lower(B3RAIL) like '".strtolower($companyName)."%' or lower(B3SIGL) like '".strtolower($companyName)."%' ) ";
            }
        }
        if (strlen($address) >= 3) {
            if($shearchOnBillingAddress) {
                $sqlWhereParts[] = " ( lower(trim(B3ADF1) || trim(B3ADF2) || trim(B3ADF3))  like '%".strtolower($address)."%' ) ";
            } else {
                $sqlWhereParts[] = " ( lower(trim(B3ADL1) || trim(B3ADL2) || trim(B3ADL3))  like '%".strtolower($address)."%' ) ";
            }
        }
        if (strlen($postalCode) >= 2) {
            if($shearchOnBillingAddress) {
                $sqlWhereParts[] = " lower(B3CPF) like '".strtolower($postalCode)."%' ";
            } else {
                $sqlWhereParts[] = " lower(B3CPL) like '".strtolower($postalCode)."%' ";
            }
        }
        if (strlen($city) >= 2) {
            if($shearchOnBillingAddress) {
                $sqlWhereParts[] = " lower(B3VILF) like '".strtolower($city)."%' ";
            } else {
                $sqlWhereParts[] = " lower(B3VILL) like '".strtolower($city)."%' ";
            }
        }
        if (strlen($siret) == 14) {
            $sBorneMinSiret = str_pad($siret,14,"0",STR_PAD_RIGHT);
            $sBorneMaxSiret = str_pad($siret,14,"9",STR_PAD_RIGHT);
            $sqlWhereParts[] = " ( B3SIRT >= '".$sBorneMinSiret."' and B3SIRT <= '".$sBorneMaxSiret."') ";
        }
        if (strlen($vatCode) >= 3) {
            $sqlWhereParts[] = " lower(B3TVAE) like '".strtolower($vatCode)."%' ";
        }
        if (
            in_array('C', $status, true) ||
            in_array('P', $status, true) ||
            in_array('S', $status, true) ||
            in_array('A', $status, true)
        ) {
            $statusList = [];
            if (in_array('C', $status, true)) {
                $statusList[] = "'C'";
            }
            if (in_array('P', $status, true)) {
                $statusList[] = "'P'";
            }
            if (in_array('S', $status, true)) {
                $statusList[] = "'S'";
            }
            if (in_array('A', $status, true)) {
                $statusList[] = "'A'";
            }
            $sqlWhereParts[] = " B3INPC in (".implode(",",$statusList).") ";
        }
        if (strlen($ownedCompanyCode) == 2) {   
            $sqlWhereParts[] = " cssoc = '".$ownedCompanyCode."' ";
        }
        if (strlen($salesAgentCode) >= 1) {
            $sqlWhereParts[] = " B3CMER = '".$salesAgentCode."' ";
        }
        if (strlen($salesAssistantCode) >= 1) {
            $sqlWhereParts[] = " B3SECR = '".$salesAssistantCode."' ";
        }
        if (strlen($groupCode) >= 1) {
            $sqlWhereParts[] = " B3GRP = '".$groupCode."' ";
        }
        if (strlen($qualificationCode) >= 1) {
            $sqlWhereParts[] = " substr( B3FIL3 , 14 , 1 ) = '".$qualificationCode."' ";
        }
        if (strlen($classificationCode) >= 1) {
            $sqlWhereParts[] = " substr( B3FIL3 , 16 , 3 ) = '".$classificationCode."' ";
        } 
        $sqlWhere = "";
        if (isset($sqlWhereParts) && count($sqlWhereParts) > 0) {
            $sqlWhere = " WHERE ".implode(" AND ",$sqlWhereParts);  
        }
        $sqlLimit = "";
        if ($limit !== null && $limit > 0) {
            $sqlLimit = " FETCH FIRST ".$limit." ROWS ONLY ";
        }
        if($shearchOnBillingAddress) {
            $orderBy = " ORDER BY B3RAIS ";
        } else {
            $orderBy = " ORDER BY B3RAIL ";
        }        
        if($company['mbi']) {
            $sql = "SELECT B3CLI, B3RAIS, B3INPC, CSSOC FROM FCMBI.B3CLIENT LEFT OUTER JOIN FCMBI.CSCLISOC ON CSCLI = B3CLI ".$sqlWhere." ".$orderBy." ".$sqlLimit;      
        } else {
            $sql = "SELECT {$company['library']}.B3CLIENT.B3CLI, {$company['library']}.B3CLIENT.B3RAIS, {$company['library']}.B3CLIENT.B3INPC, '{$companyCode}' as CSSOC FROM {$company['library']}.B3CLIENT ".$sqlWhere." ".$orderBy." ".$sqlLimit; 
        }           
        echo $sql;
        //return $customers;
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            if($full) {
                $customers[] = getCustomer($pdo, $companyCode, $row['B3CLI'], $allContacts, $withDeliveryAddresses);
            } else {
                $customers[] = [
                    'code'         => $row['B3CLI'],
                    'status'       => $row['B3INPC'],
                    'name'         => $row['B3RAIS']
                ];
            }
        }   
    } catch (Throwable $e) {
        // Log serveur (regarde logs/error_log)
        respond(500, ['error' => 'Internal server error',
        'from' => 'searchCustomer',
        'data' => $e->getMessage()]);
    }   
    return $customers;                    
}

function getCustomer(PDO $pdo, string $companyCode, string $customerCode, bool $allContacts = false, bool $WithAdditionalDeliveryAddresses = false): ?array    
{
    $customer = [];
    try {
        $company = getCompany($companyCode);
        if (!$company) {        
            return null;
        }
        if($company['mbi']) {
            $sql = "SELECT * FROM FCMBI.B3CLIENT LEFT OUTER JOIN FCMBI.CSCLISOC ON CSCLI = B3CLI WHERE B3CLI = :customer_code FETCH FIRST 1 ROW ONLY";      
        } else {
            $sql = "SELECT {$company['library']}.B3CLIENT.*, '{$companyCode}' as CSSOC FROM {$company['library']}.B3CLIENT WHERE B3CLI = :customer_code FETCH FIRST 1 ROW ONLY";      
        }           
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':customer_code', $customerCode, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        if (!$row) { 
            return null;
        }       
        $qualificationCode = $row['B3FIL3'][13] ?? 'Z';
        $customer = [
            'code'                            => $row['B3CLI'],
            'status'                          => $row['B3INPC'],
            'name'                            => $row['B3RAIS'],
            'billing_address1'                => $row['B3ADF1'],
            'billing_address2'                => $row['B3ADF2'],
            'billing_address3'                => $row['B3ADF3'],
            'billing_zipcode'                 => $row['B3CPF'],
            'billing_city'                    => $row['B3VILF'],
            'billing_country'                 => getCountry($pdo, $row['B3PAYF'])['country'] ?? 'error',            
            'billing_company_code'            => $row['CSSOC'],
            'billing_company_name'            => getCompany($row['CSSOC'])['name'] ?? 'error',
            'delivery_company_name'           => $row['B3RAIL'],
            'delivery_address1'               => $row['B3ADL1'],
            'delivery_address2'               => $row['B3ADL2'],
            'delivery_address3'               => $row['B3ADL3'],
            'delivery_zipcode'                => $row['B3CPL'],
            'delivery_city'                   => $row['B3VILL'],            
            'delivery_country'                => getCountry($pdo, $row['B3PAYL'])['country'] ?? 'error',
            'sales_agent_code'                => $row['B3CMER'],
            'sales_agent_name'                => getCommercial($pdo, $companyCode, $row['B3CMER'])['text'] ?? 'error',
            'sales_secretary_code'            => $row['B3SECR'],
            'sales_secretary_name'            => getAssistant($pdo, $companyCode, $row['B3SECR'])['text'] ?? 'error',
            'group_code'                      => $row['B3GRP'],
            'group_name'                      => getGroupement($pdo, $companyCode, $row['B3GRP'])['text'] ?? 'error',
            'qualification_type'              => $qualificationCode,
            'qualification_name'              => getQualification($pdo, $companyCode, $qualificationCode)['text'] ?? 'error',
            'main_delivery_address'           => getCustomerMainDeliveryAddress($pdo, $companyCode, $customerCode) ?? [],
            'additional_delivery_addresses'   => ($WithAdditionalDeliveryAddresses ) ? getCustomerAdditionalDeliveryAddresses($pdo, $companyCode, $customerCode) ?? [] : [],
            'contacts'                        => getCustomerContacts($pdo, $companyCode, $customerCode, $allContacts) ?? [],
        ]; 
    } catch (Throwable $e) {
        // Log serveur (regarde logs/error_log)
        respond(500, ['error' => 'Internal server error',
        'from' => 'getCustomer',
        'data' => $e->getMessage()]);
    }   
    return $customer;                    
}



// =====================
// Auth : /health et /help publics, tout le reste protégé
// =====================
if (!(($method === 'GET' && $path === '/health') || ($method === 'GET' && $path === '/help'))) {
    requireToken($expectedToken);
}

// =====================
// Routes
// =====================
if ($method === 'GET' && $path === '/health') {
    respond(200, ['ok' => true, 'ts' => date('c')]);
}

if ($method === 'GET' && $path === '/help') {
    respond(200, [
        'name' => 'APIs Help',
        'auth' => [
            'type' => 'Bearer',
            'note' => 'Bearer token required for all routes except /health and /help',
        ],
        'routes' => [
            [
                'method' => 'GET',
                'path' => '/health',
                'public' => true,
                'description' => 'Healthcheck',
            ],
            [
                'method' => 'GET',
                'path' => '/help',
                'public' => true,
                'description' => 'List all available routes',
            ],

            // ---------- Customers ----------
            [
                'method' => 'GET',
                'path' => '/company/{company}/customer/search',
                'description' => 'Search customers (starts-with / contains depending on fields). Returns summary by default; set full=1 to expand using getCustomer().',
                'query' => [
                    'customerCode' => 'string, >=2 (starts with) — B3CLI',
                    'companyName' => 'string, >=3 (starts with) — B3RAIS/B3RAIL (+B3SIGL)',
                    'address' => 'string, >=3 (contains) — address fields',
                    'postalCode' => 'string, >=2 (starts with) — B3CPF/B3CPL',
                    'city' => 'string, >=2 (starts with) — B3VILF/B3VILL',
                    'countryCode' => 'ISO2 (ex: FR) or internal country code (2-3 chars)',
                    'vatCode' => 'string, >=3 (starts with) — B3TVAE',
                    'siret' => '14 digits',
                    'status' => 'CSV of C,P,S,A (default: C,P,S,A)',
                    'ownedCompanyCode' => 'CSSOC (2 chars) (optional)',
                    'salesAgentCode' => 'B3CMER (optional)',
                    'salesAssistantCode' => 'B3SECR (optional)',
                    'groupCode' => 'B3GRP (optional)',
                    'qualificationCode' => 'substr(B3FIL3,14,1) (optional)',
                    'classificationCode' => 'substr(B3FIL3,16,3) (optional)',
                    'searchOnBillingAddress' => '1 (billing, default) or 0 (delivery)',
                    'limit' => '1..500 (default 200)',
                    'full' => '0|1 (default 0)',
                    'allContacts' => '0|1 (default 0) (used when full=1)',
                    'withDeliveryAddresses' => '0|1 (default 0) (used when full=1)',
                    'help' => '0|1 (if 1, returns route-specific help)',
                ],
                'examples' => [
                    '/company/matfer/customer/search?companyName=DUF&postalCode=77&limit=50',
                    '/company/matfer/customer/search?companyName=DUF&full=1&allContacts=1&withDeliveryAddresses=1&limit=50',
                ],
            ],
            [
                'method' => 'GET',
                'path' => '/company/{company}/customer/{customerId}',
                'description' => 'Get customer details',
                'query' => [
                    'allContacts' => '0|1 (default 0)',
                ],
            ],
            [
                'method' => 'GET',
                'path' => '/company/{company}/customer/{customerId}/contacts',
                'description' => 'Get customer contacts',
                'query' => [
                    'allContacts' => '0|1 (default 0)',
                ],
            ],
            [
                'method' => 'GET',
                'path' => '/company/{company}/customer/{customerId}/main-delivery-address',
                'description' => 'Get main delivery address (D5NODR=99)',
            ],
            [
                'method' => 'GET',
                'path' => '/company/{company}/customer/{customerId}/delivery-addresses',
                'description' => 'Get all delivery addresses (including main)',
            ],

            // ---------- Pricing ----------
            [
                'method' => 'GET',
                'path' => '/company/{company}/customer/{customerId}/product/{productCode}/price',
                'description' => 'Get customer price for a single product',
                'query' => [
                    'date' => 'optional, ISO: YYYY-MM-DD or YYYY-MM-DDTHH:MM:SS',
                    'quantity' => 'optional, integer >= 1 (default 1)',
                ],
                'examples' => [
                    '/company/matfer/customer/03188/product/707634/price?date=2026-01-01&quantity=5',
                ],
            ],
            [
                'method' => 'POST',
                'path' => '/company/{company}/customer/{customerId}/products/prices',
                'description' => 'Get customer prices for multiple products (bulk)',
                'body' => [
                    'date' => 'optional, ISO',
                    'defaultQuantity' => 'optional, integer >= 1',
                    'items' => [
                        ['code' => '707634', 'quantity' => 5],
                        ['code' => '707635'],
                    ],
                ],
            ],

            // ---------- Utilities ----------
            [
                'method' => 'GET',
                'path' => '/phone/check',
                'description' => 'Validate/format a phone number via Python script',
                'query' => [
                    'phone' => 'required',
                    'country' => 'required ISO2 (ex: FR)',
                ],
            ],

            // ---------- Supplier orders ----------
            [
                'method' => 'PUT',
                'path' => '/company/{company}/supplier/order/{orderId}',
                'description' => 'Confirm/unconfirm a supplier order',
                'body' => [
                    'confirmed' => 'required boolean',
                    'date' => 'optional ISO',
                ],
            ],
            [
                'method' => 'PUT',
                'path' => '/company/{company}/supplier/order/{orderId}/product/{productId}/delay',
                'description' => 'Update product delay (weeks) on a supplier order line',
                'body' => [
                    'delay' => 'optional integer (0..52)',
                ],
            ],
        ],
        'notes' => [
            'company can be an alias (ex: matfer) or a company code (ex: 06).',
            'Most routes require Authorization: Bearer <token>.',
        ],
    ]);
}

if ($method === 'GET'  && preg_match('#^/company/([^/]+)/customer/(\d+)/product/([^/]+)/price$#', $path, $m) ) 
{
    [$full, $company, $customerId, $productCode] = $m;
    $companyCode = getCompanyCode($company);
    if ($companyCode === null) {
        respond(404, ['error' => 'Unknown company']);
    }
    // Paramètres optionnels
    $quantity = isset($_GET['quantity']) ? (int)$_GET['quantity'] : 1;
    if ($quantity <= 0) {
        respond(400, ['error' => 'Quantity must be >= 1']);
    }    
    if (isset($_GET['date'])) {
        $date = parseAndValidateDate($_GET['date']);
        if (!$date) {
            respond(400, ['error' => 'Invalid date format']);
        }
    }
    // Si pas de paramètre date, on passe null (la fonction mettra now() par défaut)
    $date = $date ?? null;
    try {
        $pdo = db($dsn, $user, $pass);
        $price = getCustomerProductPrice(
            $pdo,
            $companyCode,
            (string)$customerId,
            (string)$productCode,
            $date,
            $quantity
        );
        if ($price === null) {
            respond(404, [
                'error' => 'Price not found',
                'customer' => $customerId,
                'product' => $productCode
            ]);
        }
        respond(200, [
            'price'    => $price
        ]);
    } catch (Throwable $e) {
        respond(500, [
            'error' => 'Internal server error',
            'from'  => 'GET /product/price',
            'data'  => $e->getMessage()
        ]);
    }
}

// =====================
// BULK PRICES : plusieurs articles en une seule requête
// POST /company/{company}/customer/{customerId}/products/prices
// Body:
// {
//   "date": "2026-01-01",           // optionnel (ISO)
//   "defaultQuantity": 1,           // optionnel
//   "items": [
//      {"code":"707634", "quantity": 5},
//      {"code":"707635"}
//   ]
// }
// =====================
if ($method === 'POST' && preg_match('#^/company/([^/]+)/customer/(\d+)/products/prices$#', $path, $m))
{
    [$full, $company, $customerId] = $m;
    $companyCode = getCompanyCode($company);
    if ($companyCode === null) {
        respond(404, ['error' => 'Unknown company']);
    }
    $rawBody = file_get_contents('php://input');
    try {
        $data = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        respond(400, ['error' => 'Invalid JSON body']);
    }
    if (!is_array($data)) {
        respond(400, ['error' => 'Invalid JSON body']);
    }
    // date optionnelle (ISO 8601)
    $date = null;
    if (array_key_exists('date', $data)) {
        if (!is_string($data['date'])) {
            respond(400, ['error' => '`date` must be a string (ISO 8601)']);
        }
        $date = parseAndValidateDate($data['date']);
        if ($date === null) {
            respond(400, [
                'error' => 'Invalid date format',
                'expected' => 'YYYY-MM-DD or YYYY-MM-DDTHH:MM:SS'
            ]);
        }
    }
    // quantité par défaut optionnelle
    $defaultQuantity = 1;
    if (array_key_exists('defaultQuantity', $data)) {
        $defaultQuantity = (int)$data['defaultQuantity'];
        if ($defaultQuantity <= 0) {
            respond(400, ['error' => '`defaultQuantity` must be >= 1']);
        }
    }
    // items obligatoire
    if (!array_key_exists('items', $data) || !is_array($data['items']) || count($data['items']) === 0) {
        respond(400, ['error' => '`items` (array) is required']);
    }
    $pdo = db($dsn, $user, $pass);
    $results = [];
    foreach ($data['items'] as $idx => $item) {
        if (!is_array($item)) {
            $results[] = [
                'index' => $idx,
                'ok' => false,
                'error' => 'Invalid item (must be an object)'
            ];
            continue;
        }

        $code = (string)($item['code'] ?? '');
        $code = trim($code);
        if ($code === '') {
            $results[] = [
                'index' => $idx,
                'ok' => false,
                'error' => 'Missing item code'
            ];
            continue;
        }

        // Optionnel: sécuriser un minimum le code (évite les injections via code)
        // Ici on autorise lettres/chiffres et quelques séparateurs usuels
        if (!preg_match('/^[A-Za-z0-9._\-]+$/', $code)) {
            $results[] = [
                'index' => $idx,
                'code' => $code,
                'ok' => false,
                'error' => 'Invalid item code format'
            ];
            continue;
        }

        $qty = array_key_exists('quantity', $item) ? (int)$item['quantity'] : $defaultQuantity;
        if ($qty <= 0) {
            $results[] = [
                'index' => $idx,
                'code' => $code,
                'ok' => false,
                'error' => 'Quantity must be >= 1'
            ];
            continue;
        }

        try {
            $price = getCustomerProductPrice(
                $pdo,
                $companyCode,
                (string)$customerId,
                $code,
                $date,
                $qty
            );

            if ($price === null) {
                $results[] = [
                    'index' => $idx,
                    'code' => $code,
                    'ok' => false,
                    'error' => 'Price not found'
                ];
                continue;
            }

            $results[] = [
                'index'     => $idx,
                'code'      => $code,
                'quantity'  => $qty,
                'date'      => $date ? $date->format('d-m-Y') : date('d-m-Y'),
                'ok'        => true,
                'price'     => $price
            ];

        } catch (Throwable $e) {
            // Important: on ne stoppe pas tout le bulk si un item plante
            $results[] = [
                'index' => $idx,
                'code'  => $code,
                'ok'    => false,
                'error' => 'Internal error while pricing item',
                'data'  => $e->getMessage(),
            ];
        }
    }
    respond(200, $results);
}

if ($method === 'GET' && preg_match('#^/company/([^/]+)/customer/(\d+)$#', $path, $m)) {
    $_company = (string)$m[1];
    $companyCode = getCompanyCode($_company);
    if ($companyCode === null ) {
        respond(404, ['error' => 'Unknown company', 'company' => $_company]);
    }
    $customerId = (string)$m[2];
    $allContacts = ($_GET['allContacts'] ?? '0') === '1';
    try {
        $pdo = db($dsn, $user, $pass); 
        $customer = getCustomer($pdo, $companyCode, $customerId, $allContacts);           
        if ($customer === null ) {
            respond(404, ['error' => 'Unknown customer', 'customer' => $customerId]);                          
        }
        respond(200, [
            'customer' => $customer
        ]);
    } catch (Throwable $e) {        
        respond(500, ['error' => 'Internal server error',
        'from' => 'getCustomer route',
        'data' => $e->getMessage()]);
    }
}

if ($method === 'GET' && preg_match('#^/company/([^/]+)/customer/(\d+)/contacts$#', $path, $m)) {    
    $_company = (string)$m[1];
    $companyCode = getCompanyCode($_company);
    if ($companyCode === null ) {
        respond(404, ['error' => 'Unknown company', 'company' => $_company]);
    }
    $customerId = (string)$m[2];
    $allContacts = ($_GET['allContacts'] ?? '0') === '1';
    try {
        $pdo = db($dsn, $user, $pass); 
        $contacts = getCustomerContacts($pdo, $companyCode, $customerId, $allContacts);                   
        respond(200, ['contacts' => $contacts]);
    } catch (Throwable $e) {
        respond(500, ['error' => 'Internal server error',
        'from' => 'getCustomerContacts route',
        'data' => $e->getMessage()]);
    }
}

if ($method === 'GET' && $path === '/phone/check') {
    $phone   = $_GET['phone']   ?? '';
    $country = $_GET['country'] ?? '';
    if ($phone === '' || $country === '') {
        respond(400, [
            'error' => 'Missing parameters',
            'expected' => '?phone=XXXXXXXX&country=FR'
        ]);
    }
    // Sécurité minimale
    if (!preg_match('/^[0-9+\.\-\s]+$/', $phone)) {
        respond(400, ['error' => 'Invalid phone format']);
    }
    if (!preg_match('/^[A-Z]{2}$/', $country)) {
        respond(400, ['error' => 'Invalid country code']);
    }
    $python  = '/QOpenSys/pkgs/bin/python3';
    $script  = '/www/apis/python/phoneNumber.py';
    // Construction commande sécurisée
    $cmd = sprintf(
        '%s %s %s %s 2>&1',
        escapeshellcmd($python),
        escapeshellarg($script),
        escapeshellarg($phone),
        escapeshellarg($country)
    );
    trace('python cmd', $cmd);
    $output = shell_exec($cmd);
    if ($output === null) {
        respond(500, ['error' => 'Python execution failed']);
    }    
    $result = json_decode($output, true);  
    if ($result === null) {
        respond(500, ['error' => 'Invalid JSON from Python', 'output' => $output]);
    }
    respond(200, $result);
}

if ($method === 'GET' && preg_match('#^/company/([^/]+)/customer/(\d+)/main-delivery-address$#', $path, $m)) 
{
    [$full, $company, $customerId] = $m;
    $companyCode = getCompanyCode($company);
    if ($companyCode === null) {
        respond(404, ['error' => 'Unknown company']);
    }
    try {
        $pdo = db($dsn, $user, $pass);
        $address = getCustomerMainDeliveryAddress(
            $pdo,
            $companyCode,
            (string)$customerId
        );
        if (empty($address)) {
            respond(404, [
                'error' => 'Main delivery address not found',
                'customer' => $customerId
            ]);
        }
        respond(200, [
            'main_delivery_address' => $address
        ]);

    } catch (Throwable $e) {
        respond(500, [
            'error' => 'Internal server error',
            'from'  => 'GET /main-delivery-address',
            'data'  => $e->getMessage()
        ]);
    }
}

if ($method === 'GET' && preg_match('#^/company/([^/]+)/customer/(\d+)/delivery-addresses$#', $path, $m)) 
{
    [$full, $company, $customerId] = $m;
    $companyCode = getCompanyCode($company);
    if ($companyCode === null) {
        respond(404, ['error' => 'Unknown company']);
    }
    try {
        $pdo = db($dsn, $user, $pass);
        $addresses = getCustomerAdditionalDeliveryAddresses(
            $pdo,
            $companyCode,
            (string)$customerId, true
        );
        respond(200, [
            'delivery_addresses' => $addresses
        ]);
    } catch (Throwable $e) {
        respond(500, [
            'error' => 'Internal server error',
            'from'  => 'GET /delivery-addresses',
            'data'  => $e->getMessage()
        ]);
    }
}

if ( $method === 'PUT' && preg_match('#^/company/([^/]+)/supplier/order/(\d+)$#', $path, $m) ) 
{
    [$full, $company, $orderId] = $m;
    $companyCode = getCompanyCode($company);
    if ($companyCode === null) {
        respond(404, ['error' => 'Unknown company']);
    }    
    $orderId    = (int)$orderId;
    // Lecture du body JSON
    $rawBody = file_get_contents('php://input');
    try {
        $data = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        respond(400, ['error' => 'Invalid JSON body']);
    }
   // confirmed obligatoire
    if (!array_key_exists('confirmed', $data) || !is_bool($data['confirmed'])) {
        respond(400, ['error' => '`confirmed` (boolean) is required']);
    }
    $confirmed = $data['confirmed'];
    // date facultative
    $confirmedDate = null;
    if (array_key_exists('date', $data)) {
        if (!is_string($data['date'])) {
            respond(400, ['error' => '`date` must be a string (ISO 8601)']);
        }
        $confirmedDate = parseAndValidateDate($data['date']);
        if ($confirmedDate === null) {
            respond(400, [
                'error' => 'Invalid date format',
                'expected' => 'YYYY-MM-DD or YYYY-MM-DDTHH:MM:SS'
            ]);
        }
    }    
    $pdo = db($dsn, $user, $pass);
    // Appel métier
    if (confirmSupplierOrder( $pdo, $companyCode, $orderId, $confirmed, $confirmedDate )) {
        respond(200, [ 
            'status'     => 'ok',
            'company'    => $companyCode,            
            'orderId'    => $orderId,
            'confirmed'  => $confirmed,
            'date'       => $confirmedDate ? $confirmedDate->format('Y-m-d') : null
        ]);
    } else {
        respond(500, [
            'error'      => 'Failed to confirm order',
            'company'    => $companyCode,            
            'orderId'    => $orderId,
            'confirmed'  => $confirmed,
            'date'       => $confirmedDate ? $confirmedDate->format('Y-m-d') : null
        ]);
    }
}

if ( $method === 'PUT' && preg_match('#^/company/([^/]+)/supplier/order/(\d+)/product/([^/]+)/delay$#', $path, $m) ) 
{
    [$full, $company, $orderId, $productId] = $m;
    $companyCode = getCompanyCode($company);
    if ($companyCode === null) {
        respond(404, ['error' => 'Unknown company']);
    }    
    $orderId    = (int)$orderId;
    // Lecture du body JSON
    $rawBody = file_get_contents('php://input');
    try {
        $data = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable $e) {
        respond(400, ['error' => 'Invalid JSON body']);
    }
    // date facultative
    $delay = 0;
    if (array_key_exists('delay', $data)) {
        $delay = (int)$data['delay'];
        if ($delay < 0) {
            respond(400, ['error' => '`delay` must be a positive integer']);
        }
        if ($delay > 52) {
            respond(400, ['error' => '`delay` seems too high (max 52 weeks)']);
        }
    }         
    $pdo = db($dsn, $user, $pass);
    // Appel métier
    if (confirmSupplierOrderProductDelay( $pdo, $companyCode, $orderId, $productId, $delay )) {
        respond(200, [ 
            'status'     => 'ok',
            'company'    => $companyCode,            
            'orderId'    => $orderId,
            'productId'  => $productId,
            'delay'      => $delay
        ]);
    } else {
        respond(500, [
            'error'      => 'Failed to confirm order',
            'company'    => $companyCode,            
            'orderId'    => $orderId,
            'productId'  => $productId,
            'delay'      => $delay
        ]);
    }    
}

function debugSql(string $sql, array $params): string
{
    foreach ($params as $key => $value) {
        if (is_string($value)) {
            $value = "'" . addslashes($value) . "'";
        } elseif ($value === null) {
            $value = 'NULL';
        }
        $sql = str_replace($key, (string)$value, $sql);
    }
    return $sql;
}

// =====================
// SEARCH CUSTOMERS
// GET /company/{company}/customer/search?term=...&limit=50
// Recherche "commence par" sur :
// - raison sociale facturation/livraison (B3RAIS/B3RAIL)
// - code client (B3CLI)
// - code postal fact/liv (B3CPF/B3CPL)
// - ville fact/liv (B3VILF/B3VILL)
// 
// IMPORTANT: on ne SELECT QUE B3CLI (et CSSOC si MBI) puis on appelle getCustomer()
// pour construire un "beau" tableau de clients.
// Tri: ORDER BY B3RAIS
// Accent/casse: on tente de les ignorer (TRANSLATE + UPPER)
// =====================
if ($method === 'GET' && preg_match('#^/company/([^/]+)/customer/search$#', $path, $m))
{
    [$fullMatch, $company] = $m;
    $companyCode = getCompanyCode((string)$company);
    if ($companyCode === null) {
        respond(404, ['error' => 'Unknown company', 'company' => (string)$company]);
    }

    // Petit help intégré
    if (($_GET['help'] ?? '0') === '1') {
        respond(200, [
            'route' => '/company/{company}/customer/search',
            'method' => 'GET',
            'description' => 'Recherche clients (commence par) sur code, raison sociale, CP, ville, etc. Peut retourner un résumé (défaut) ou full=1 (fiche complète via getCustomer).',
            'query' => [
                'customerCode' => 'string, >=2 (commence par) — B3CLI',
                'companyName' => 'string, >=3 (commence par) — B3RAIS/B3RAIL (+ B3SIGL)',
                'address' => 'string, >=3 (contient) — adresses fact/liv',
                'postalCode' => 'string, >=2 (commence par) — B3CPF/B3CPL',
                'city' => 'string, >=2 (commence par) — B3VILF/B3VILL',
                'countryCode' => 'string, ISO2 (FR) ou code pays interne (optionnel)',
                'vatCode' => 'string, >=3 (commence par) — B3TVAE',
                'siret' => 'string, 14 chiffres',
                'status' => 'ex: C,P,S,A (défaut C,P,S,A)',
                'ownedCompanyCode' => 'code société (CSSOC) ex: 06 (optionnel)',
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
            'examples' => [
                '/company/matfer/customer/search?companyName=DUF&postalCode=77&limit=50',
                '/company/matfer/customer/search?companyName=DUF&full=1&allContacts=1&withDeliveryAddresses=1&limit=50'
            ]
        ]);
    }

    // Lecture + normalisation des paramètres
    $customerCode = (string)($_GET['customerCode'] ?? '');
    $companyName  = (string)($_GET['companyName']  ?? '');
    $siret        = (string)($_GET['siret']        ?? '');
    $address      = (string)($_GET['address']      ?? '');
    $postalCode   = (string)($_GET['postalCode']   ?? '');
    $city         = (string)($_GET['city']         ?? '');
    $countryCode  = (string)($_GET['countryCode']  ?? '');
    $vatCode      = (string)($_GET['vatCode']      ?? '');

    $ownedCompanyCode    = (string)($_GET['ownedCompanyCode']    ?? '');
    $salesAgentCode      = (string)($_GET['salesAgentCode']      ?? '');
    $salesAssistantCode  = (string)($_GET['salesAssistantCode']  ?? '');
    $groupCode           = (string)($_GET['groupCode']           ?? '');
    $qualificationCode   = (string)($_GET['qualificationCode']   ?? '');
    $classificationCode  = (string)($_GET['classificationCode']  ?? '');

    $searchOnBillingAddress = (string)($_GET['searchOnBillingAddress'] ?? '1') !== '0';

    $limit = (int)($_GET['limit'] ?? 200);
    if ($limit <= 0) $limit = 200;
    if ($limit > 500) $limit = 500;

    $full = (string)($_GET['full'] ?? '0') === '1';
    $allContacts = (string)($_GET['allContacts'] ?? '0') === '1';
    $withDeliveryAddresses = (string)($_GET['withDeliveryAddresses'] ?? '0') === '1';

    // Status: "C,P,S,A"
    $statusRaw = (string)($_GET['status'] ?? 'C,P,S,A');
    $status = array_values(array_filter(array_map(function ($x) {
        $x = strtoupper(trim((string)$x));
        return in_array($x, ['C','P','S','A'], true) ? $x : null;
    }, preg_split('/[;,\s]+/', $statusRaw) ?: [])));
    if (count($status) === 0) {
        $status = ['C','P','S','A'];
    }

    // Sécurités légères
    if ($siret !== '' && !preg_match('/^\d{14}$/', $siret)) {
        respond(400, ['error' => 'Invalid siret (expected 14 digits)']);
    }
    if ($countryCode !== '' && strlen($countryCode) > 3) {
        // ISO2 ou code interne (2-3 chars)
        respond(400, ['error' => 'Invalid countryCode']);
    }

    try {
        $pdo = db($dsn, $user, $pass);

        // Si countryCode est ISO, on peut le convertir en code interne (G0PAY) via table ISO
        // On ne force pas: si introuvable, on garde tel quel.
        $countryCodeResolved = $countryCode;
        if ($countryCodeResolved !== '' && strlen($countryCodeResolved) === 2) {
            $cc = getCountryByISO($pdo, $countryCodeResolved);
            if (!empty($cc['country_code'])) {
                $countryCodeResolved = (string)$cc['country_code'];
            }
        }

        $customers = searchCustomer(
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

        respond(200, [
            'company' => $companyCode,
            'count'   => is_array($customers) ? count($customers) : 0,
            'full'    => $full,
            'customers' => $customers ?? [],
        ]);

    } catch (Throwable $e) {
        respond(500, [
            'error' => 'Internal server error',
            'from'  => 'GET /customer/search',
            'data'  => $e->getMessage(),
        ]);
    }
}


// ===========================================================
//                  Route non trouvée
// ===========================================================

respond(404, ['error' => 'Not found', 'method' => $method, 'path' => $path]);
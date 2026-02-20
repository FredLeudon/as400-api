<?php
declare(strict_types=1);

namespace App\Help;

/**
 * HTML help page for GET /help
 *
 * Usage in routes.php (example):
 *   use App\Help\Help;
 *   ...
 *   if ($method === 'GET' && $path === '/help') {
 *       http_response_code(200);
 *       header('Content-Type: text/html; charset=utf-8');
 *       echo Help::render();
 *       exit;
 *   }
 */
final class Help
{
    /**
     * Return the raw help payload (routes, auth, notes...).
     * Useful for JSON endpoints or for other modules that need the definitions.
     *
     * @return array<string,mixed>
     */
    public static function payload(): array
    {
        return self::data();
    }

    /**
     * Render a complete HTML document.
     */
    public static function render(): string
    {
        $data = self::data();

        $title = htmlspecialchars((string)($data['name'] ?? 'API Help'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $authType   = htmlspecialchars((string)($data['auth']['type'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $authNote   = htmlspecialchars((string)($data['auth']['note'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $authHeader = htmlspecialchars((string)($data['auth']['header'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $routes = is_array($data['routes'] ?? null) ? $data['routes'] : [];
        $notes  = is_array($data['notes'] ?? null) ? $data['notes'] : [];

        $rowsHtml = '';
        foreach ($routes as $r) {
            if (!is_array($r)) continue;

            $method = htmlspecialchars((string)($r['method'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $path   = htmlspecialchars((string)($r['path'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $desc   = htmlspecialchars((string)($r['description'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $public = (bool)($r['public'] ?? false);
            $badge  = $public
                ? '<span class="badge badge-public">public</span>'
                : '<span class="badge badge-protected">token</span>';

            $details = self::renderRouteDetails($r);

            $rowsHtml .= "\n<tr>\n";
            $rowsHtml .= "  <td class=\"col-method\"><code>{$method}</code></td>\n";
            $rowsHtml .= "  <td class=\"col-path\"><code>{$path}</code><div class=\"badge-wrap\">{$badge}</div></td>\n";
            $rowsHtml .= "  <td class=\"col-desc\">{$desc}{$details}</td>\n";
            $rowsHtml .= "</tr>\n";
        }

        $notesHtml = '';
        if (!empty($notes)) {
            $notesItems = '';
            foreach ($notes as $n) {
                $notesItems .= '<li>' . htmlspecialchars((string)$n, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>';
            }
            $notesHtml = "<h2>Notes</h2><ul>{$notesItems}</ul>";
        }

        $html = <<<HTML
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>{$title}</title>
  <style>
    :root { color-scheme: light dark; }
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;margin:24px;line-height:1.45}
    header{display:flex;gap:16px;align-items:flex-end;justify-content:space-between;flex-wrap:wrap}
    h1{margin:0 0 6px 0;font-size:24px}
    .sub{color:#666;font-size:14px}
    .box{padding:12px;border:1px solid #ddd;border-radius:12px;background:rgba(127,127,127,.07)}
    table{width:100%;border-collapse:collapse;margin-top:16px}
    th,td{border-bottom:1px solid rgba(127,127,127,.25);padding:10px;vertical-align:top}
    th{font-size:12px;text-transform:uppercase;letter-spacing:.05em;text-align:left;opacity:.8}
    code{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:12.5px}
    .col-method{width:90px;white-space:nowrap}
    .col-path{width:420px}
    .badge-wrap{margin-top:6px}
    .badge{display:inline-block;font-size:11px;padding:2px 8px;border-radius:999px;border:1px solid rgba(127,127,127,.35)}
    .badge-public{background:rgba(0,128,0,.12)}
    .badge-protected{background:rgba(255,165,0,.12)}
    details{margin-top:8px}
    summary{cursor:pointer}
    .kv{display:grid;grid-template-columns:180px 1fr;gap:6px 12px;margin-top:8px}
    .k{opacity:.75}
    pre{white-space:pre-wrap;background:rgba(0,0,0,.12);padding:10px;border-radius:10px;overflow:auto}
    .muted{opacity:.75}
  </style>
</head>
<body>
  <header>
    <div>
      <h1>{$title}</h1>
      <div class="sub">{$authNote}</div>
    </div>
    <div class="box">
      <div><strong>Auth</strong> : <span class="muted">{$authType}</span></div>
      <div class="muted"><code>{$authHeader}</code></div>
    </div>
  </header>

  <table>
    <thead>
      <tr>
        <th>Méthode</th>
        <th>Route</th>
        <th>Description</th>
      </tr>
    </thead>
    <tbody>
      {$rowsHtml}
    </tbody>
  </table>

  {$notesHtml}
</body>
</html>
HTML;

        return $html;
    }

    /**
     * Keep the route definitions in one place.
     *
     * @return array<string,mixed>
     */
    public static function data(): array
    {
        return [
            'name' => 'APIs Help',
            'auth' => [
                'type' => 'Bearer',
                'note' => 'Bearer token required for all routes except /health, /help, /help/json and /qadbifld',
                'header' => 'Authorization: Bearer <token>',
            ],
            'routes' => [
                // ---------- Public ----------
                [
                    'method' => 'GET',
                    'path' => '/health',
                    'public' => true,
                    'description' => 'Healthcheck',
                    'response' => [
                        'ok' => true,
                        'ts' => '2026-01-20T10:15:30+01:00',
                    ],
                ],
                [
                    'method' => 'GET',
                    'path' => '/help',
                    'public' => true,
                    'description' => 'List all available routes (HTML)',
                ],
                [
                    'method' => 'GET',
                    'path' => '/help/json',
                    'public' => true,
                    'description' => 'List all available routes (JSON)',
                ],
                [
                    'method' => 'GET',
                    'path' => '/qadbifld',
                    'public' => true,
                    'description' => 'QADBIFLD diagnostic endpoint',
                ],
                // ---------- Test ----------
                [
                    'method' => 'GET',
                    'path' => '/mail/test',
                    'description' => 'Send a test HTML email to frichard@matferbourgeat.com',
                ],

                // ---------- Customers ----------
                [
                    'method' => 'GET',
                    'path' => '/company/{company}/customer/search',
                    'description' => 'Search customers. Returns summary by default; set full=1 to expand via getCustomer().',
                    'query' => [
                        'customerCode' => 'string, >=2 (starts with) — B3CLI',
                        'companyName' => 'string, >=3 (starts with) — B3RAIS/B3RAIL (+B3SIGL)',
                        'address' => 'string, >=3 (contains) — addresses fields',
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
                    'examples' => [
                        '/company/matfer/customer/03188',
                        '/company/matfer/customer/03188?allContacts=1',
                    ],
                ],
                [
                    'method' => 'GET',
                    'path' => '/company/{company}/customer/{customerId}/contacts',
                    'description' => 'Get customer contacts',
                    'query' => [
                        'allContacts' => '0|1 (default 0)',
                    ],
                    'examples' => [
                        '/company/matfer/customer/03188/contacts',
                        '/company/matfer/customer/03188/contacts?allContacts=1',
                    ],
                ],
                [
                    'method' => 'GET',
                    'path' => '/company/{company}/customer/{customerId}/main-delivery-address',
                    'description' => 'Get main delivery address (D5NODR=99)',
                    'examples' => [
                        '/company/matfer/customer/03188/main-delivery-address',
                    ],
                ],
                [
                    'method' => 'GET',
                    'path' => '/company/{company}/customer/{customerId}/delivery-addresses',
                    'description' => 'Get delivery addresses (all).',
                    'examples' => [
                        '/company/matfer/customer/03188/delivery-addresses',
                    ],
                ],

                // ---------- Products ----------
                [
                    'method' => 'GET',
                    'path' => '/company/{company}/product/{productId}',
                    'description' => 'Get product details',
                    'examples' => [
                        '/company/matfer/product/707634',
                    ],
                ],

                // ---------- Pricing ----------
                [
                    'method' => 'GET',
                    'path' => '/company/{company}/customer/{customerId}/product/{productCode}/price',
                    'description' => 'Get customer price for a single product',
                    'query' => [
                        'date' => 'optional, ISO: YYYY-MM-DD or YYYY-MM-DDTHH:MM or YYYY-MM-DDTHH:MM:SS',
                        'quantity' => 'optional, integer >= 1 (default 1)',
                    ],
                    'examples' => [
                        '/company/matfer/customer/03188/product/707634/price?date=2026-01-01&quantity=5',
                        '/company/matfer/customer/03188/product/707634/price',
                    ],
                ],
                [
                    'method' => 'POST',
                    'path' => '/company/{company}/customer/{customerId}/products/prices',
                    'description' => 'Get customer prices for multiple products (bulk).',
                    'body' => [
                        'date' => 'optional, ISO',
                        'defaultQuantity' => 'optional, integer >= 1',
                        'items' => [
                            ['code' => '707634', 'quantity' => 5],
                            ['code' => '707635'],
                        ],
                    ],
                    'examples' => [
                        [
                            'curl' => "curl -X POST -H 'Authorization: Bearer <token>' -H 'Content-Type: application/json' \\\n"
                                    . "  -d '{\"date\":\"2026-01-01\",\"defaultQuantity\":1,\"items\":[{\"code\":\"707634\",\"quantity\":5},{\"code\":\"707635\"}]}' \\\n"
                                    . "  http://<host>:8080/company/matfer/customer/03188/products/prices",
                        ],
                    ],
                ],

                // ---------- Utilities ----------
                [
                    'method' => 'GET',
                    'path' => '/phone/check',
                    'description' => 'Validate/format a phone number via Python script.',
                    'query' => [
                        'phone' => 'required (digits, +, spaces, .,- allowed)',
                        'country' => 'required ISO2 (ex: FR)',
                    ],
                    'examples' => [
                        '/phone/check?phone=0674800235&country=FR',
                        '/phone/check?phone=+33674800235&country=FR',
                    ],
                ],

                // ---------- Digital ----------
                [
                    'method' => 'GET',
                    'path' => '/digital/product/{productCode}/attributs',
                    'description' => 'Read digital attributes for one product (without company).',
                    'query' => [
                        'attribut' => 'optional, one attribute code to filter (alias: attribute)',
                    ],
                    'examples' => [
                        '/digital/product/707634/attributs',
                        '/digital/product/707634/attributs?attribut=DAT_PUB_CATA_PRT',
                    ],
                ],
                [
                    'method' => 'GET',
                    'path' => '/digital/attributs/definitions',
                    'description' => 'Get digital attributes definitions (STANDARD).',
                    'examples' => [
                        '/digital/attributs/definitions',
                    ],
                ],
                [
                    'method' => 'GET',
                    'path' => '/digital/attributs/fichiers',
                    'description' => 'Get file-level digital attributes definitions (STANDARD).',
                    'examples' => [
                        '/digital/attributs/fichiers',
                    ],
                ],
                [
                    'method' => 'GET',
                    'path' => '/digital/attribut/{attributeCode}/definition',
                    'description' => 'Get definition for one digital attribute code.',
                    'examples' => [
                        '/digital/attribut/COLOR/definition',
                    ],
                ],
                [
                    'method' => 'GET',
                    'path' => '/digital/attribut/{attributeCode}/valeur',
                    'description' => 'Get one digital attribute value for an article.',
                    'query' => [
                        'article' => 'required, product code (aliases: code_article, product)',
                        'indice' => 'optional, integer >= 1 (alias: index, default: 1)',
                    ],
                    'examples' => [
                        '/digital/attribut/DAT_PUB_CATA_PRT/valeur?article=707634',
                        '/digital/attribut/DAT_PUB_CATA_PRT/valeur?article=707634&indice=1',
                    ],
                ],
                [
                    'method' => 'GET',
                    'path' => '/digital/medias',
                    'description' => 'Get media links for one article and one media type/subtype.',
                    'query' => [
                        'article' => 'required, product code (aliases: code_article, product)',
                        'type' => 'required, media type (alias: type_fichier). Example: PC/photos',
                        'sous_type' => 'required, media subtype (alias: sub_type). Example: modele',
                    ],
                    'examples' => [
                        '/digital/medias?article=707634&type=PC%2Fphotos&sous_type=modele',
                    ],
                ],

                // ---------- Supplier orders ----------
                [
                    'method' => 'PUT',
                    'path' => '/company/{company}/supplier/order/{orderId}',
                    'description' => 'Confirm/unconfirm a supplier order.',
                    'body' => [
                        'confirmed' => 'required boolean',
                        'date' => 'optional ISO',
                    ],
                    'examples' => [
                        [
                            'curl' => "curl -X PUT -H 'Authorization: Bearer <token>' -H 'Content-Type: application/json' \\\n"
                                    . "  -d '{\"confirmed\":true,\"date\":\"2026-01-20\"}' \\\n"
                                    . "  http://<host>:8080/company/matfer/supplier/order/123456",
                        ],
                    ],
                ],
                [
                    'method' => 'PUT',
                    'path' => '/company/{company}/supplier/order/{orderId}/product/{productId}/delay',
                    'description' => 'Update product delay (weeks) on a supplier order line.',
                    'body' => [
                        'delay' => 'optional integer (0..52)',
                    ],
                    'examples' => [
                        [
                            'curl' => "curl -X PUT -H 'Authorization: Bearer <token>' -H 'Content-Type: application/json' \\\n"
                                    . "  -d '{\"delay\":4}' \\\n"
                                    . "  http://<host>:8080/company/matfer/supplier/order/123456/product/707634/delay",
                        ],
                    ],
                ],
            ],
            'notes' => [
                'company can be an alias (ex: matfer) or a company code (ex: 06).',
                'Most routes require Authorization: Bearer <token>.',
            ],
        ];
    }

    /**
     * @param array<string,mixed> $route
     */
    private static function renderRouteDetails(array $route): string
    {
        $query = $route['query'] ?? null;
        $body  = $route['body'] ?? null;
        $resp  = $route['response'] ?? null;
        $ex    = $route['examples'] ?? null;

        $parts = [];

        if (is_array($query) && !empty($query)) {
            $parts[] = '<details><summary>Query parameters</summary>' . self::renderKeyValueList($query) . '</details>';
        }
        if (is_array($body) && !empty($body)) {
            $parts[] = '<details><summary>Body</summary>' . self::renderPretty($body) . '</details>';
        }
        if (is_array($resp) && !empty($resp)) {
            $parts[] = '<details><summary>Response example</summary>' . self::renderPretty($resp) . '</details>';
        }
        if (is_array($ex) && !empty($ex)) {
            $parts[] = '<details><summary>Examples</summary>' . self::renderExamples($ex) . '</details>';
        }

        if (empty($parts)) {
            return '';
        }

        return '<div class="route-details">' . implode('', $parts) . '</div>';
    }

    /**
     * @param array<mixed> $examples
     */
    private static function renderExamples(array $examples): string
    {
        $html = '';
        foreach ($examples as $e) {
            if (is_string($e)) {
                $html .= '<div><code>' . htmlspecialchars($e, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></div>';
                continue;
            }
            if (is_array($e) && isset($e['curl']) && is_string($e['curl'])) {
                $html .= '<pre><code>' . htmlspecialchars($e['curl'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>';
                continue;
            }
            if (is_array($e)) {
                $html .= self::renderPretty($e);
            }
        }
        return $html;
    }

    /**
     * @param array<string,mixed> $kv
     */
    private static function renderKeyValueList(array $kv): string
    {
        $out = '<div class="kv">';
        foreach ($kv as $k => $v) {
            $kk = htmlspecialchars((string)$k, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $vv = htmlspecialchars(
                is_scalar($v)
                    ? (string)$v
                    : (json_encode($v, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: ''),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            );
            $out .= "<div class=\"k\"><code>{$kk}</code></div><div class=\"v\">{$vv}</div>";
        }
        $out .= '</div>';
        return $out;
    }

    /**
     * Render any array as pretty JSON inside a <pre>.
     *
     * @param array<mixed> $value
     */
    private static function renderPretty(array $value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRETTY_PRINT);
        if (!is_string($json)) {
            $json = '';
        }
        return '<pre><code>' . htmlspecialchars($json, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>';
    }
}

<?php
declare(strict_types=1);

// Simple diagnostic page to query hfsql.QADBIFLD
// Params: lib (bibliothèque, will be uppercased), file (fichier)

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Db;

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

$lib = strtoupper(trim((string)($_GET['lib'] ?? '')));
$file = strtoupper(trim((string)($_GET['file'] ?? '')));

if ($lib === '' || $file === '') {
    echo "<p>Usage: ?lib=MATIS&file=XYZ</p>";
    exit;
}

// DSN / credentials from env loaded by bootstrap
$dsn  = getenv('API_DSN')  ?: '';
$user = getenv('API_USER') ?: '';
$pass = getenv('API_PASS') ?: '';

try {
    $pdo = Db::connect($dsn, $user, $pass);

    // Choose which column to compare for the file identifier
    $fileCond = (mb_strlen($file) <= 10) ? 'DBIFIL = :file' : 'DBILFI = :file';

    $sql = "SELECT
       dbifld,
       dbilfl,
       dbipos,
       dbiitp,
       dbityp,
       dbitxt,
       dbinul,
       dbiupd,
       dbidfi,
       Case Ifnull(dbidft, 'NULL')
         When '' Then 'NULL'
         Else Ifnull(dbidft, 'NULL')
       End As dbidft,
       dbifln,
       dbicln,
       dbicnc,
       dbiccc,
       dbinln,
       dbinsc,
       dbidln
    FROM hfsql.QADBIFLD
    WHERE DBILIB = :lib AND {$fileCond} AND DBIDFI IN ('Y','N','I') ORDER BY DBIPOS";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':lib', $lib, PDO::PARAM_STR);
    $stmt->bindValue(':file', $file, PDO::PARAM_STR);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    http_response_code(500);
    echo "<h2>Database error</h2>";
    echo '<pre>' . h($e->getMessage()) . '</pre>';
    exit;
}

// Render HTML table
?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>QADBIFLD - <?= h($lib) ?> / <?= h($file) ?></title>
  <style>table{border-collapse:collapse;width:100%}th,td{border:1px solid #ccc;padding:6px;text-align:left;font-family:monospace;font-size:13px}th{background:#f3f3f3}</style>
</head>
<body>
  <h1>QADBIFLD — <?= h($lib) ?> / <?= h($file) ?></h1>
  <p>Query: <code>DBILIB = <?= h($lib) ?> AND <?= h($fileCond) ?> AND DBIDFI IN ('Y','N','I')</code></p>
  <p>Rows: <?= count($rows) ?></p>

  <table>
    <thead>
      <tr>
        <th>dbifld</th><th>dbilfl</th><th>dbipos</th><th>dbiitp</th><th>dbityp</th><th>dbitxt</th>
        <th>dbinul</th><th>dbiupd</th><th>dbidfi</th><th>dbidft</th><th>dbifln</th><th>dbicln</th>
        <th>dbicnc</th><th>dbiccc</th><th>dbinln</th><th>dbinsc</th><th>dbidln</th>
      </tr>
    </thead>
    <tbody>
<?php foreach ($rows as $r): ?>
      <tr>
        <td><?= h($r['DBIFLD'] ?? '') ?></td>
        <td><?= h($r['DBILFL'] ?? '') ?></td>
        <td><?= h($r['DBIPOS'] ?? '') ?></td>
        <td><?= h($r['DBIITP'] ?? '') ?></td>
        <td><?= h($r['DBITYP'] ?? '') ?></td>
        <td><?= h($r['DBITXT'] ?? '') ?></td>
        <td><?= h($r['DBINUL'] ?? '') ?></td>
        <td><?= h($r['DBIUPD'] ?? '') ?></td>
        <td><?= h($r['DBIDFI'] ?? '') ?></td>
        <td><?= h($r['DBIDFT'] ?? '') ?></td>
        <td><?= h($r['DBIFLN'] ?? '') ?></td>
        <td><?= h($r['DBICLN'] ?? '') ?></td>
        <td><?= h($r['DBICNC'] ?? '') ?></td>
        <td><?= h($r['DBICCC'] ?? '') ?></td>
        <td><?= h($r['DBINLN'] ?? '') ?></td>
        <td><?= h($r['DBINSC'] ?? '') ?></td>
        <td><?= h($r['DBIDLN'] ?? '') ?></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>

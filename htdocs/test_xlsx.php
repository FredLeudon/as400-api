<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Xlsx;

session_start();

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function formatCellValue(mixed $value): string
{
    if ($value === null) return 'NULL';
    if (is_bool($value)) return $value ? 'true' : 'false';
    if (is_scalar($value)) return (string)$value;

    $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return is_string($json) ? $json : '[unserializable value]';
}

$uploadPathKey = 'test_xlsx_path';
$uploadNameKey = 'test_xlsx_name';
$storageDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'as400-apis-xlsx-test';

if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0770, true);
}

$messages = [];
$errors = [];
$action = (string)($_POST['action'] ?? '');
$selectedSheet = trim((string)($_POST['sheet'] ?? ''));
$cellRef = strtoupper(trim((string)($_POST['cell'] ?? 'A1')));
$cellResult = null;
$hasCellResult = false;

if ($action === 'clear') {
    $oldPath = (string)($_SESSION[$uploadPathKey] ?? '');
    if ($oldPath !== '' && is_file($oldPath)) {
        @unlink($oldPath);
    }
    unset($_SESSION[$uploadPathKey], $_SESSION[$uploadNameKey]);
    $messages[] = 'Fichier ferme.';
}

if ($action === 'upload') {
    if (!isset($_FILES['xlsx_file']) || !is_array($_FILES['xlsx_file'])) {
        $errors[] = 'Aucun fichier recu.';
    } else {
        $upload = $_FILES['xlsx_file'];
        $errorCode = (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            $errors[] = 'Erreur upload (code ' . $errorCode . ').';
        } else {
            $tmpName = (string)($upload['tmp_name'] ?? '');
            $originalName = (string)($upload['name'] ?? 'fichier.xlsx');
            $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));

            if ($extension !== 'xlsx') {
                $errors[] = 'Le fichier doit etre au format .xlsx';
            } elseif (!is_uploaded_file($tmpName)) {
                $errors[] = 'Fichier temporaire invalide.';
            } else {
                $oldPath = (string)($_SESSION[$uploadPathKey] ?? '');
                if ($oldPath !== '' && is_file($oldPath)) {
                    @unlink($oldPath);
                }

                $targetPath = $storageDir
                    . DIRECTORY_SEPARATOR
                    . session_id()
                    . '_'
                    . bin2hex(random_bytes(8))
                    . '.xlsx';

                if (!move_uploaded_file($tmpName, $targetPath)) {
                    $errors[] = 'Impossible de deplacer le fichier upload.';
                } else {
                    $_SESSION[$uploadPathKey] = $targetPath;
                    $_SESSION[$uploadNameKey] = $originalName;
                    $messages[] = 'Fichier upload avec succes.';
                    $selectedSheet = '';
                    $cellRef = 'A1';
                }
            }
        }
    }
}

$uploadedPath = (string)($_SESSION[$uploadPathKey] ?? '');
$uploadedName = (string)($_SESSION[$uploadNameKey] ?? '');
$sheetNames = [];

if ($uploadedPath !== '' && !is_file($uploadedPath)) {
    unset($_SESSION[$uploadPathKey], $_SESSION[$uploadNameKey]);
    $uploadedPath = '';
    $uploadedName = '';
    $errors[] = 'Le fichier upload n\'est plus disponible. Veuillez le re-uploader.';
}

if ($uploadedPath !== '') {
    try {
        $xlsx = new Xlsx($uploadedPath);
        $sheetNames = $xlsx->getSheetNames();

        if ($sheetNames === []) {
            $errors[] = 'Aucune feuille detectee dans ce fichier.';
        } else {
            if ($selectedSheet === '' || !in_array($selectedSheet, $sheetNames, true)) {
                $selectedSheet = $sheetNames[0];
            }

            $xlsx->setActiveSheet($selectedSheet);

            if ($action === 'query') {
                if ($cellRef === '') {
                    $errors[] = 'Veuillez saisir une reference de cellule (ex: B2).';
                } else {
                    $cellResult = $xlsx->getCell($cellRef, $selectedSheet);
                    $hasCellResult = true;
                }
            }
        }
    } catch (Throwable $e) {
        $errors[] = 'Erreur ouverture XLSX: ' . $e->getMessage();
    }
}

?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Test XLSX</title>
  <style>
    body { font-family: Arial, sans-serif; max-width: 980px; margin: 2rem auto; padding: 0 1rem; }
    h1 { margin: 0 0 1rem; }
    .card { border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; background: #fff; }
    .row { display: flex; gap: .75rem; flex-wrap: wrap; align-items: center; }
    input[type="text"], select, input[type="file"] { padding: .45rem .55rem; }
    button { padding: .5rem .8rem; cursor: pointer; }
    .ok { background: #eef9f0; border: 1px solid #b9e3c2; color: #1d6a2b; padding: .65rem .8rem; border-radius: 6px; margin-bottom: .75rem; }
    .err { background: #fff1f0; border: 1px solid #f0c5c1; color: #8a2820; padding: .65rem .8rem; border-radius: 6px; margin-bottom: .75rem; }
    code, pre { background: #f6f8fa; padding: .15rem .3rem; border-radius: 4px; }
    pre { padding: .8rem; overflow: auto; }
    ol { margin-top: .4rem; }
  </style>
</head>
<body>
  <h1>Test de la classe Xlsx</h1>

<?php foreach ($messages as $msg): ?>
  <div class="ok"><?= h($msg) ?></div>
<?php endforeach; ?>

<?php foreach ($errors as $err): ?>
  <div class="err"><?= h($err) ?></div>
<?php endforeach; ?>

  <div class="card">
    <h2>1) Uploader un fichier .xlsx</h2>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="action" value="upload">
      <div class="row">
        <input type="file" name="xlsx_file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
        <button type="submit">Uploader et ouvrir</button>
      </div>
    </form>
<?php if ($uploadedPath !== ''): ?>
    <p><strong>Fichier courant:</strong> <?= h($uploadedName !== '' ? $uploadedName : basename($uploadedPath)) ?></p>
    <form method="post">
      <input type="hidden" name="action" value="clear">
      <button type="submit">Fermer le fichier</button>
    </form>
<?php endif; ?>
  </div>

<?php if ($uploadedPath !== '' && $sheetNames !== []): ?>
  <div class="card">
    <h2>2) Feuilles detectees</h2>
    <p><strong>Nombre de feuilles:</strong> <?= count($sheetNames) ?></p>
    <ol>
<?php foreach ($sheetNames as $sheetName): ?>
      <li><?= h($sheetName) ?></li>
<?php endforeach; ?>
    </ol>
  </div>

  <div class="card">
    <h2>3) Interroger une cellule</h2>
    <form method="post">
      <input type="hidden" name="action" value="query">
      <div class="row">
        <label for="sheet">Feuille</label>
        <select name="sheet" id="sheet">
<?php foreach ($sheetNames as $sheetName): ?>
          <option value="<?= h($sheetName) ?>" <?= $sheetName === $selectedSheet ? 'selected' : '' ?>>
            <?= h($sheetName) ?>
          </option>
<?php endforeach; ?>
        </select>

        <label for="cell">Cellule</label>
        <input type="text" name="cell" id="cell" value="<?= h($cellRef) ?>" placeholder="A1" pattern="[A-Za-z]+[0-9]+" required>

        <button type="submit">Lire la cellule</button>
      </div>
    </form>

<?php if ($hasCellResult): ?>
    <p>
      <strong>Resultat:</strong>
      feuille <code><?= h($selectedSheet) ?></code>,
      cellule <code><?= h($cellRef) ?></code>
    </p>
    <pre><?= h(formatCellValue($cellResult)) ?></pre>
<?php endif; ?>
  </div>
<?php endif; ?>
</body>
</html>

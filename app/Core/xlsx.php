<?php
declare(strict_types=1);

namespace App\Core;

use InvalidArgumentException;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

final class Xlsx
{
    private const NS_MAIN = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const NS_REL_OFFICE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const NS_REL_PACKAGE = 'http://schemas.openxmlformats.org/package/2006/relationships';

    private const WORKBOOK_PATH = 'xl/workbook.xml';
    private const WORKBOOK_RELS_PATH = 'xl/_rels/workbook.xml.rels';
    private const SHARED_STRINGS_PATH = 'xl/sharedStrings.xml';

    private ?ZipArchive $zip = null;
    private ?string $path = null;
    private int $activeSheetIndex = 0;

    /** @var array<int,string> */
    private array $sharedStrings = [];

    /**
     * @var array<int,array{name:string,path:string}>
     */
    private array $sheets = [];

    /**
     * @var array<int,array{rows:array<int,array<int,mixed>>,maxRow:int,maxCol:int}>
     */
    private array $sheetCache = [];

    public function __construct(?string $path = null)
    {
        if ($path !== null) {
            $this->open($path);
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    public static function fromFile(string $path): self
    {
        return new self($path);
    }

    public function open(string $path): self
    {
        $path = trim($path);
        if ($path === '') {
            throw new InvalidArgumentException('Le chemin du fichier XLSX est vide.');
        }
        if (!is_file($path)) {
            throw new InvalidArgumentException(sprintf('Fichier introuvable: %s', $path));
        }
        if (!is_readable($path)) {
            throw new InvalidArgumentException(sprintf('Fichier non lisible: %s', $path));
        }

        $this->close();

        $zip = new ZipArchive();
        $status = $zip->open($path);
        if ($status !== true) {
            throw new RuntimeException(sprintf('Impossible d\'ouvrir le fichier XLSX: %s', $path));
        }

        $this->zip = $zip;
        $this->path = $path;
        $this->activeSheetIndex = 0;
        $this->sheetCache = [];
        $this->sharedStrings = $this->loadSharedStrings();
        $this->sheets = $this->loadSheets();

        if ($this->sheets === []) {
            throw new RuntimeException(sprintf('Aucune feuille trouvée dans le fichier: %s', $path));
        }

        return $this;
    }

    public function close(): void
    {
        if ($this->zip instanceof ZipArchive) {
            $this->zip->close();
        }

        $this->zip = null;
        $this->path = null;
        $this->activeSheetIndex = 0;
        $this->sharedStrings = [];
        $this->sheets = [];
        $this->sheetCache = [];
    }

    public function isOpen(): bool
    {
        return $this->zip instanceof ZipArchive;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @return array<int,string>
     */
    public function getSheetNames(): array
    {
        $this->assertOpened();
        return array_map(static fn(array $sheet): string => $sheet['name'], $this->sheets);
    }

    public function getSheetCount(): int
    {
        $this->assertOpened();
        return count($this->sheets);
    }

    public function hasSheet(string $sheetName): bool
    {
        $this->assertOpened();
        try {
            $this->resolveSheetIndex($sheetName);
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public function setActiveSheet(int|string $sheet): self
    {
        $this->activeSheetIndex = $this->resolveSheetIndex($sheet);
        return $this;
    }

    public function getActiveSheetIndex(): int
    {
        $this->assertOpened();
        return $this->activeSheetIndex;
    }

    public function getActiveSheetName(): string
    {
        $this->assertOpened();
        return $this->sheets[$this->activeSheetIndex]['name'];
    }

    public function getMaxRow(int|string|null $sheet = null): int
    {
        $data = $this->loadSheetData($this->resolveSheetIndex($sheet));
        return $data['maxRow'];
    }

    public function getMaxColumnIndex(int|string|null $sheet = null): int
    {
        $data = $this->loadSheetData($this->resolveSheetIndex($sheet));
        return $data['maxCol'];
    }

    public function getMaxColumnLetter(int|string|null $sheet = null): string
    {
        $maxCol = $this->getMaxColumnIndex($sheet);
        if ($maxCol < 1) return 'A';
        return self::indexToColumn($maxCol);
    }

    public function getCell(string $cellReference, int|string|null $sheet = null): mixed
    {
        [$columnIndex, $row] = self::splitCellReference($cellReference);
        return $this->getCellByPosition($columnIndex, $row, $sheet);
    }

    public function getCellByPosition(int|string $column, int $row, int|string|null $sheet = null): mixed
    {
        if ($row < 1) {
            throw new InvalidArgumentException('Le numero de ligne doit etre >= 1.');
        }

        $columnIndex = self::columnToIndex($column);
        $data = $this->loadSheetData($this->resolveSheetIndex($sheet));
        return $data['rows'][$row][$columnIndex] ?? null;
    }

    /**
     * @return array<string,mixed>
     */
    public function getRow(int $row, int|string|null $sheet = null, bool $includeEmpty = false): array
    {
        if ($row < 1) {
            throw new InvalidArgumentException('Le numero de ligne doit etre >= 1.');
        }

        $data = $this->loadSheetData($this->resolveSheetIndex($sheet));
        $rowData = $data['rows'][$row] ?? [];

        if ($includeEmpty) {
            $maxCol = $data['maxCol'];
            $out = [];
            for ($col = 1; $col <= $maxCol; $col++) {
                $out[self::indexToColumn($col)] = $rowData[$col] ?? null;
            }
            return $out;
        }

        $out = [];
        foreach ($rowData as $col => $value) {
            $out[self::indexToColumn($col)] = $value;
        }
        return $out;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getRows(
        int $startRow = 1,
        ?int $endRow = null,
        int|string|null $sheet = null,
        bool $includeEmpty = false
    ): array {
        if ($startRow < 1) {
            throw new InvalidArgumentException('La ligne de debut doit etre >= 1.');
        }

        $data = $this->loadSheetData($this->resolveSheetIndex($sheet));
        $maxRow = $data['maxRow'];
        $endRow = $endRow ?? $maxRow;

        if ($endRow < $startRow) {
            throw new InvalidArgumentException('La ligne de fin doit etre >= a la ligne de debut.');
        }

        $out = [];
        for ($row = $startRow; $row <= $endRow; $row++) {
            $rowValues = $this->getRow($row, $sheet, $includeEmpty);
            if (!$includeEmpty && $rowValues === []) continue;
            $out[$row] = $rowValues;
        }

        return $out;
    }

    /**
     * @return array<int,mixed>
     */
    public function getColumn(
        int|string $column,
        int|string|null $sheet = null,
        int $startRow = 1,
        ?int $endRow = null,
        bool $includeEmpty = false
    ): array {
        if ($startRow < 1) {
            throw new InvalidArgumentException('La ligne de debut doit etre >= 1.');
        }

        $columnIndex = self::columnToIndex($column);
        $data = $this->loadSheetData($this->resolveSheetIndex($sheet));
        $maxRow = $data['maxRow'];
        $endRow = $endRow ?? $maxRow;

        if ($endRow < $startRow) {
            throw new InvalidArgumentException('La ligne de fin doit etre >= a la ligne de debut.');
        }

        $out = [];
        for ($row = $startRow; $row <= $endRow; $row++) {
            if (array_key_exists($columnIndex, $data['rows'][$row] ?? [])) {
                $out[$row] = $data['rows'][$row][$columnIndex];
            } elseif ($includeEmpty) {
                $out[$row] = null;
            }
        }

        return $out;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getSheetAsArray(int|string|null $sheet = null, bool $includeEmpty = false): array
    {
        return $this->getRows(1, null, $sheet, $includeEmpty);
    }

    public static function columnToIndex(int|string $column): int
    {
        if (is_int($column)) {
            if ($column < 1) {
                throw new InvalidArgumentException('L\'index de colonne doit etre >= 1.');
            }
            return $column;
        }

        $column = strtoupper(trim($column));
        if ($column === '' || !preg_match('/^[A-Z]+$/', $column)) {
            throw new InvalidArgumentException(sprintf('Colonne invalide: %s', $column));
        }

        $index = 0;
        $len = strlen($column);
        for ($i = 0; $i < $len; $i++) {
            $index = ($index * 26) + (ord($column[$i]) - 64);
        }

        return $index;
    }

    public static function indexToColumn(int $index): string
    {
        if ($index < 1) {
            throw new InvalidArgumentException('L\'index de colonne doit etre >= 1.');
        }

        $column = '';
        while ($index > 0) {
            $index--;
            $column = chr(($index % 26) + 65) . $column;
            $index = intdiv($index, 26);
        }

        return $column;
    }

    private function assertOpened(): void
    {
        if (!$this->zip instanceof ZipArchive) {
            throw new RuntimeException('Aucun fichier XLSX ouvert.');
        }
    }

    /**
     * @return array<int,string>
     */
    private function loadSharedStrings(): array
    {
        $xml = $this->readZipEntry(self::SHARED_STRINGS_PATH, false);
        if ($xml === null) {
            return [];
        }

        $root = $this->loadXml($xml);
        $out = [];
        $main = $root->children(self::NS_MAIN);
        foreach ($main->si as $item) {
            $out[] = $this->extractRichText($item);
        }

        return $out;
    }

    /**
     * @return array<int,array{name:string,path:string}>
     */
    private function loadSheets(): array
    {
        $relsXml = $this->readZipEntry(self::WORKBOOK_RELS_PATH, true);
        $relsRoot = $this->loadXml($relsXml);

        /** @var array<string,string> $ridToPath */
        $ridToPath = [];
        $relsChildren = $relsRoot->children(self::NS_REL_PACKAGE);
        foreach ($relsChildren->Relationship as $relationship) {
            $attrs = $relationship->attributes();
            $id = trim((string)($attrs['Id'] ?? ''));
            $target = trim((string)($attrs['Target'] ?? ''));
            if ($id === '' || $target === '') continue;
            $ridToPath[$id] = self::normalizeZipPath('xl', $target);
        }

        $workbookXml = $this->readZipEntry(self::WORKBOOK_PATH, true);
        $workbookRoot = $this->loadXml($workbookXml);
        $main = $workbookRoot->children(self::NS_MAIN);

        $out = [];
        foreach ($main->sheets->sheet as $sheetNode) {
            $attrs = $sheetNode->attributes();
            $name = trim((string)($attrs['name'] ?? ''));
            $relAttrs = $sheetNode->attributes(self::NS_REL_OFFICE);
            $rid = trim((string)($relAttrs['id'] ?? ''));
            $path = $ridToPath[$rid] ?? null;

            if ($name === '' || $path === null) {
                continue;
            }

            $out[] = [
                'name' => $name,
                'path' => $path,
            ];
        }

        return $out;
    }

    /**
     * @return array{rows:array<int,array<int,mixed>>,maxRow:int,maxCol:int}
     */
    private function loadSheetData(int $sheetIndex): array
    {
        if (isset($this->sheetCache[$sheetIndex])) {
            return $this->sheetCache[$sheetIndex];
        }

        $sheetPath = $this->sheets[$sheetIndex]['path'] ?? null;
        if ($sheetPath === null) {
            throw new InvalidArgumentException(sprintf('Index de feuille invalide: %d', $sheetIndex));
        }

        $sheetXml = $this->readZipEntry($sheetPath, true);
        $root = $this->loadXml($sheetXml);
        $main = $root->children(self::NS_MAIN);

        $rows = [];
        $maxRow = 0;
        $maxCol = 0;

        $dimensionAttrs = $main->dimension->attributes();
        $dimensionRef = trim((string)($dimensionAttrs['ref'] ?? ''));
        if ($dimensionRef !== '') {
            [$dimMaxRow, $dimMaxCol] = self::parseRangeMax($dimensionRef);
            $maxRow = max($maxRow, $dimMaxRow);
            $maxCol = max($maxCol, $dimMaxCol);
        }

        $nextRow = 1;
        foreach ($main->sheetData->row as $rowNode) {
            $rowAttrs = $rowNode->attributes();
            $rowNumber = (int)($rowAttrs['r'] ?? 0);
            if ($rowNumber < 1) {
                $rowNumber = $nextRow;
            }

            $nextCol = 1;
            $cellNodes = $rowNode->children(self::NS_MAIN);
            foreach ($cellNodes->c as $cellNode) {
                $cellAttrs = $cellNode->attributes();
                $ref = trim((string)($cellAttrs['r'] ?? ''));
                if ($ref === '') {
                    $row = $rowNumber;
                    $col = $nextCol;
                } else {
                    [$col, $row] = self::splitCellReference($ref);
                }

                $rows[$row][$col] = $this->extractCellValue($cellNode);
                if ($row > $maxRow) $maxRow = $row;
                if ($col > $maxCol) $maxCol = $col;
                $nextCol = $col + 1;
            }

            $nextRow = $rowNumber + 1;
        }

        ksort($rows);
        foreach ($rows as &$rowData) {
            ksort($rowData);
        }
        unset($rowData);

        $data = [
            'rows' => $rows,
            'maxRow' => $maxRow,
            'maxCol' => $maxCol,
        ];
        $this->sheetCache[$sheetIndex] = $data;

        return $data;
    }

    private function extractCellValue(SimpleXMLElement $cellNode): mixed
    {
        $attrs = $cellNode->attributes();
        $type = trim((string)($attrs['t'] ?? ''));
        $main = $cellNode->children(self::NS_MAIN);

        if ($type === 'inlineStr') {
            return isset($main->is) ? $this->extractRichText($main->is) : '';
        }

        $raw = isset($main->v) ? (string)$main->v : null;
        if ($raw === null || $raw === '') {
            if (isset($main->f)) {
                $formula = trim((string)$main->f);
                return $formula === '' ? null : '=' . $formula;
            }
            return null;
        }

        if ($type === 's') {
            $idx = (int)$raw;
            return $this->sharedStrings[$idx] ?? null;
        }
        if ($type === 'b') {
            return $raw === '1';
        }
        if ($type === 'str' || $type === 'e') {
            return $raw;
        }
        if (!is_numeric($raw)) {
            return $raw;
        }

        if (str_contains($raw, '.') || stripos($raw, 'e') !== false) {
            return (float)$raw;
        }

        $intValue = (int)$raw;
        if ((string)$intValue === $raw) {
            return $intValue;
        }

        return (float)$raw;
    }

    private function extractRichText(SimpleXMLElement $node): string
    {
        $main = $node->children(self::NS_MAIN);
        $text = '';

        if (isset($main->t)) {
            $text .= (string)$main->t;
        }

        foreach ($main->r as $run) {
            $runMain = $run->children(self::NS_MAIN);
            if (isset($runMain->t)) {
                $text .= (string)$runMain->t;
            }
        }

        return $text;
    }

    private function resolveSheetIndex(int|string|null $sheet): int
    {
        $this->assertOpened();

        if ($sheet === null) {
            return $this->activeSheetIndex;
        }

        if (is_int($sheet)) {
            if (!isset($this->sheets[$sheet])) {
                throw new InvalidArgumentException(sprintf('Index de feuille invalide: %d', $sheet));
            }
            return $sheet;
        }

        $sheet = trim($sheet);
        if ($sheet === '') {
            throw new InvalidArgumentException('Le nom de feuille est vide.');
        }

        foreach ($this->sheets as $idx => $sheetMeta) {
            if ($sheetMeta['name'] === $sheet) {
                return $idx;
            }
        }

        foreach ($this->sheets as $idx => $sheetMeta) {
            if (strcasecmp($sheetMeta['name'], $sheet) === 0) {
                return $idx;
            }
        }

        if (ctype_digit($sheet)) {
            $idx = (int)$sheet;
            if (isset($this->sheets[$idx])) return $idx;
        }

        throw new InvalidArgumentException(sprintf('Feuille introuvable: %s', $sheet));
    }

    private function readZipEntry(string $entry, bool $required): ?string
    {
        $this->assertOpened();

        $content = $this->zip?->getFromName($entry);
        if ($content === false) {
            if ($required) {
                throw new RuntimeException(sprintf('Entree XLSX introuvable: %s', $entry));
            }
            return null;
        }

        return $content;
    }

    private function loadXml(string $xml): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $root = simplexml_load_string($xml);
            if (!$root instanceof SimpleXMLElement) {
                throw new RuntimeException('XML XLSX invalide.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $root;
    }

    /**
     * @return array{0:int,1:int}
     */
    private static function splitCellReference(string $reference): array
    {
        $reference = str_replace('$', '', strtoupper(trim($reference)));
        if (!preg_match('/^([A-Z]+)(\d+)$/', $reference, $matches)) {
            throw new InvalidArgumentException(sprintf('Reference de cellule invalide: %s', $reference));
        }

        $column = self::columnToIndex($matches[1]);
        $row = (int)$matches[2];
        if ($row < 1) {
            throw new InvalidArgumentException(sprintf('Reference de cellule invalide: %s', $reference));
        }

        return [$column, $row];
    }

    /**
     * @return array{0:int,1:int}
     */
    private static function parseRangeMax(string $rangeRef): array
    {
        $rangeRef = str_replace('$', '', strtoupper(trim($rangeRef)));
        if (str_contains($rangeRef, ':')) {
            [, $end] = explode(':', $rangeRef, 2);
        } else {
            $end = $rangeRef;
        }

        [$col, $row] = self::splitCellReference($end);
        return [$row, $col];
    }

    private static function normalizeZipPath(string $baseDir, string $target): string
    {
        $target = str_replace('\\', '/', trim($target));
        $path = str_starts_with($target, '/')
            ? ltrim($target, '/')
            : trim($baseDir, '/') . '/' . ltrim($target, '/');

        $parts = [];
        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') continue;
            if ($part === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }

        return implode('/', $parts);
    }
}

<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use InvalidArgumentException;

abstract class clFichier
{
    protected PDO $pdo;
    protected string $library;

    /** Override dans les enfants */
    protected static string $table = '';
    protected static array $primaryKey = [];
    /**
     * @var array<string,string|array<string,mixed>>
     * Can be either ['COL'=>'Label'] or ['COL'=>['label'=>'...', 'type'=>...]]
     */
    protected static array $columns = [];
    protected static array $uniqueKeys = [];

    /** @var array<int,array{col:string,op:string,val:mixed}> */
    protected array $where = [];
    /** @var array<string,string> */
    protected array $orderBy = [];
    /** @var array<int,string> */
    protected array $groupBy = [];
    protected ?int $limit = null;
    protected array $select = ['*'];

    /** @var array<string,mixed> Hydrated DB row for model-like access */
    protected array $row = [];
    /** @var array<string,string> Optional per-instance map of column types (COL => TYPE) including long names */
    protected array $fieldTypes = [];

    final public function __construct(PDO $pdo, string $library)
    {
        $this->pdo = $pdo;
        $this->library = trim($library);

        if ($this->library === '') {
            throw new InvalidArgumentException('Missing library');
        }
        if (static::$table === '') {
            throw new InvalidArgumentException('Missing static::$table in ' . static::class);
        }
    }

    public static function for(PDO $pdo, string $library): static
    {
        $inst = new static($pdo, $library);
        try {
            $dbTable = new DbTable($library, static::$table, static::$primaryKey, array_keys(static::$columns));
            $types = $dbTable->fieldTypes($pdo);
            if (!empty($types)) $inst->withFieldTypes($types);
        } catch (\Throwable $e) {
            trigger_error(sprintf('Failed to load field metadata for %s.%s: %s', $library, static::$table, $e->getMessage()), E_USER_WARNING);
        }
        return $inst;
    }

    // -------------------- Model helpers --------------------

    /**
     * Hydrate this instance with a DB row.
     *
     * @param array<string,mixed> $row
     */
    public function fill(array $row): static
    {
        $this->row = $row;
        return $this;
    }

    /**
     * Attach a per-instance map of column => type (e.g. ['APART'=>'CHAR','AP_CODE_ARTICLE'=>'CHAR']).
     * This is used to cast long-name columns which may not exist in static::$columns.
     *
     * @param array<string,string> $map
     */
    public function withFieldTypes(array $map): static
    {
        $this->fieldTypes = array_change_key_case($map, CASE_UPPER);
        return $this;
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return $this->typedRow();
    }

    /**
     * Return the hydrated row with lowercased column keys.
     *
     * @return array<string,mixed>
     */
    public function toArrayLower(): array
    {
        return self::mapRowKeys($this->typedRow(), 'lower');
    }

    /**
     * Return the hydrated row with values cast according to static::$columns definitions.
     * Keys are kept as in the internal row (AS/400 column names uppercase).
     *
     * @return array<string,mixed>
     */
    protected function typedRow(): array
    {
        $out = [];
        foreach ($this->row as $col => $val) {
            $colUp = is_string($col) ? strtoupper($col) : (string)$col;
            $out[$colUp] = $this->castValueByColumn($colUp, $val);
        }
        return $out;
    }

    /**
     * Cast a value according to the column definition in static::$columns.
     */
    private function castValueByColumn(string $col, mixed $val): mixed
    {
        // preserve nulls
        if ($val === null) return null;

        $def = static::$columns[$col] ?? null;
        $type = null;

        // First check static::$columns
        if (is_array($def)) {
            $type = $def['type'] ?? null;
        } elseif (is_string($def) && $def !== '') {
            $type = $def;
        }

        // Fallback to per-instance field types (useful for long names)
        if (($type === null || $type === '') && isset($this->fieldTypes[$col])) {
            $type = $this->fieldTypes[$col];
        }

        if (!is_string($type) || $type === '') {
            // no type info, return original
            return $val;
        }

        $type = strtoupper(trim($type));

        // normalize string values
        if (is_string($val)) {
            $trimmed = trim($val);
            if ($trimmed === '') return null;
            $val = $trimmed;
        }

        // integer-like types
        if (str_contains($type, 'INT') || in_array($type, ['SMALLINT','INTEGER'], true)) {
            // if already numeric
            if (is_int($val)) return $val;
            if (is_numeric($val)) return (int)$val;
            return (int) $val;
        }

        // decimal / numeric types -> float
        if (str_contains($type, 'DEC') || str_contains($type, 'NUM') || $type === 'DECIMAL' || $type === 'NUMERIC') {
            if (is_float($val) || is_int($val)) return $val + 0.0;
            // replace comma decimal separator if present
            if (is_string($val)) {
                $normalized = str_replace([',',' '], ['.',''], $val);
                if (is_numeric($normalized)) return (float)$normalized;
            }
            return (float)$val;
        }

        // dates/timestamps/time -> keep as string (could be parsed later)
        if (in_array($type, ['TIMESTMP','TIMESTAMP','DATE','TIME'], true) || str_contains($type, 'TIME')) {
            return (string)$val;
        }

        // default: treat as string
        return (string)$val;
    }

    /**
     * Magic getter to access AS/400 columns directly.
     * Supports both `$obj->C0LIB` and `$obj->c0lib`.
     */
    public function __get(string $name): mixed
    {
        $col = strtoupper($name);
        $hasUnderscore = strpos($name, '_') !== false;

        // If caller used a long-name with underscores, require exact match
        if ($hasUnderscore) {
            if (array_key_exists($col, $this->row)) return $this->row[$col];
            trigger_error(sprintf("Undefined column '%s' for %s", $name, static::class), E_USER_WARNING);
            return null;
        }

        // No underscore: try short-name then long-name (exact long-name with underscore)
        if (array_key_exists($col, $this->row)) return $this->row[$col];

        // Try exact long-name key (may be present if rows were augmented)
        foreach ($this->row as $k => $v) {
            if (!is_string($k)) continue;
            if (strtoupper($k) === $col) continue; // already checked
            if (strpos($k, '_') !== false && strtoupper($k) === strtoupper($k)) {
                // exact long-name match
                if (strtoupper($k) === strtoupper($name)) return $v;
            }
        }

        // As last resort, check against known fieldTypes mapping: if a long-name exists for this short-name, prefer it
        $shortUp = $col;
        if (isset($this->fieldTypes[$shortUp])) {
            // find long name key in row
            foreach ($this->row as $k => $v) {
                if (!is_string($k)) continue;
                if (strtoupper($k) === strtoupper($shortUp)) continue;
                if (str_replace('_', '', strtoupper($k)) === str_replace('_', '', $shortUp)) return $v;
            }
        }

        trigger_error(sprintf("Undefined column '%s' for %s", $name, static::class), E_USER_WARNING);
        return null;
    }

    /**
     * Magic isset for columns.
     */
    public function __isset(string $name): bool
    {
        $col = strtoupper($name);
        $hasUnderscore = strpos($name, '_') !== false;
        if ($hasUnderscore) {
            return array_key_exists($col, $this->row) && $this->row[$col] !== null;
        }

        if (array_key_exists($col, $this->row) && $this->row[$col] !== null) return true;

        // check exact long-name presence
        foreach ($this->row as $k => $v) {
            if (!is_string($k)) continue;
            if (strtoupper($k) === strtoupper($name) && $v !== null) return true;
        }

        // fallback: check fieldTypes mapping for long-name keys
        $shortUp = $col;
        if (isset($this->fieldTypes[$shortUp])) {
            foreach ($this->row as $k => $v) {
                if (!is_string($k)) continue;
                if (strtoupper($k) === strtoupper($shortUp)) continue;
                if (str_replace('_', '', strtoupper($k)) === str_replace('_', '', $shortUp) && $v !== null) return true;
            }
        }

        return false;
    }

    /**
     * Magic setter to assign AS/400 columns directly.
     * Supports both `$obj->C0LIB = '...'` and `$obj->c0lib = '...'`.
     */
    public function __set(string $name, mixed $value): void
    {
        $col = strtoupper($name);
        $this->row[$col] = $value;
    }

    /**
     * Returns true if the hydrated row contains the column.
     */
    public function hasColumn(string $column): bool
    {
        $col = strtoupper($column);
        return array_key_exists($col, $this->row);
    }

    /**
     * Get a column value from the hydrated row.
     */
    public function getColumn(string $column, mixed $default = null): mixed
    {
        $col = strtoupper($column);
        $hasUnderscore = strpos($column, '_') !== false;
        if ($hasUnderscore) {
            if (array_key_exists($col, $this->row)) return $this->row[$col];
            trigger_error(sprintf("Undefined column '%s' for %s", $column, static::class), E_USER_WARNING);
            return $default;
        }

        if (array_key_exists($col, $this->row)) return $this->row[$col];

        // try exact long-name key
        foreach ($this->row as $k => $v) {
            if (!is_string($k)) continue;
            if (strtoupper($k) === strtoupper($column)) return $v;
        }

        // fallback: use fieldTypes mapping to locate a long-name equivalent
        $shortUp = $col;
        if (isset($this->fieldTypes[$shortUp])) {
            foreach ($this->row as $k => $v) {
                if (!is_string($k)) continue;
                if (strtoupper($k) === strtoupper($shortUp)) continue;
                if (str_replace('_', '', strtoupper($k)) === str_replace('_', '', $shortUp)) return $v;
            }
        }

        trigger_error(sprintf("Undefined column '%s' for %s", $column, static::class), E_USER_WARNING);
        return $default;
    }

    /**
     * Set a column value in the hydrated row.
     */
    public function setColumn(string $column, mixed $value): static
    {
        $col = strtoupper($column);
        $this->row[$col] = $value;
        return $this;
    }

    // -------------------- Schema helpers --------------------

    /**
     * Returns a human label for an AS/400 column.
     *
     * Supported formats for static::$columns:
     *  - ['COL' => 'Label']
     *  - ['COL' => ['label' => 'Label', ...]]
     */
    public static function columnLabel(string $column): string
    {
        $def = static::$columns[$column] ?? null;
        if (is_array($def)) {
            $label = $def['label'] ?? null;
            return is_string($label) && $label !== '' ? $label : $column;
        }
        if (is_string($def) && $def !== '') {
            return $def;
        }
        return $column;
    }

    /**
     * Returns the map of columns => labels.
     *
     * @return array<string,string>
     */
    public static function columnsLabels(): array
    {
        $out = [];
        foreach (static::$columns as $col => $def) {
            if (!is_string($col) || $col === '') continue;
            if (is_array($def)) {
                $label = $def['label'] ?? null;
                $out[$col] = (is_string($label) && $label !== '') ? $label : $col;
                continue;
            }
            if (is_string($def) && $def !== '') {
                $out[$col] = $def;
                continue;
            }
            $out[$col] = $col;
        }
        return $out;
    }

    /**
     * Maps an associative DB row (AS/400 column names) to a human-labeled array.
     *
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    public static function mapRowToLabels(array $row): array
    {
        $out = [];
        foreach ($row as $col => $val) {
            $label = is_string($col) ? static::columnLabel($col) : (string)$col;
            $out[$label] = $val;
        }
        return $out;
    }

    /**
     * Map row keys to a specific case ("upper" or "lower").
     *
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    public static function mapRowKeys(array $row, string $case = 'upper'): array
    {
        $out = [];
        $mode = strtolower($case);
        foreach ($row as $col => $val) {
            if (!is_string($col)) {
                $out[(string)$col] = $val;
                continue;
            }
            $out[$mode === 'lower' ? strtolower($col) : strtoupper($col)] = $val;
        }
        return $out;
    }

    // -------------------- Query builder --------------------

    public function select(array $columns): static
    {
        $this->select = $columns ?: ['*'];
        return $this;
    }

    public function where(string $col, string $op, mixed $val): static
    {
        $this->where[] = ['col' => $col, 'op' => $op, 'val' => $val];
        return $this;
    }

    public function whereEq(string $col, mixed $val): static
    {
        return $this->where($col, '=', $val);
    }

    public function whereIn(string $col, array $vals): static
    {
        return $this->where($col, 'IN', $vals);
    }

     public function whereNotIn(string $col, array $vals): static
    {
        return $this->where($col, 'NOT IN', $vals);
    }

    public function whereNull(string $col): static
    {
        return $this->where($col, 'IS NULL', null);
    }

    public function orderBy(string $col, string $dir = 'ASC'): static
    {
        $this->orderBy[$col] = $dir;
        return $this;
    }

    /**
     * Add one or multiple columns to GROUP BY clause.
     *
     * @param string|array<int,string> $columns
     */
    public function groupBy(string|array $columns): static
    {
        $cols = is_array($columns) ? $columns : [$columns];
        foreach ($cols as $col) {
            $col = trim((string)$col);
            if ($col === '') continue;
            $this->groupBy[] = $col;
        }
        return $this;
    }

    public function limit(int $n): static
    {
        $this->limit = max(0, $n);
        return $this;
    }

    public function first(): ?array
    {               
        $row = $this->db()->getWhere($this->pdo, $this->where, $this->orderBy, $this->select, $this->groupBy);
        $this->resetQuery();
        return $row;
    }

    /**
     * Like first(), but returns a hydrated model instance instead of a raw array.
     */
    public function firstModel(bool $dumpSql = false): ?static
    {
        $row = $this->db()->getWhere($this->pdo, $this->where, $this->orderBy, $this->select, $this->groupBy, $dumpSql);
        $this->resetQuery();
        if (!$row) return null;

        // New instance, hydrated with the row
        // Attach field types (including long names) from the table metadata so casting works for aliases
        $types = $this->db()->fieldTypes($this->pdo);
        return static::for($this->pdo, $this->library)->fill($row)->withFieldTypes($types);
    }

    /** @return array<int,array<string,mixed>> */
    public function get(): array
    {
        $rows = $this->db()->listWhere($this->pdo, $this->where, $this->orderBy, $this->limit, $this->select, $this->groupBy);
        $this->resetQuery();
        return $rows;
    }

    /**
     * Like get(), but returns hydrated model instances instead of raw arrays.
     *
     * @return array<int,static>
     */
    public function getModels(bool $dumpSql = false): array
    {
        $rows = $this->db()->listWhere($this->pdo, $this->where, $this->orderBy, $this->limit, $this->select, $this->groupBy, $dumpSql);
        $this->resetQuery();

        $out = [];
        // get the field type map once for this table (includes long names)
        $types = $this->db()->fieldTypes($this->pdo);
        foreach ($rows as $row) {
            $out[] = static::for($this->pdo, $this->library)->fill($row)->withFieldTypes($types);
        }
        return $out;
    }

    // -------------------- CRUD --------------------

    public function insert(array $data): bool
    {
        return $this->db()->insert($this->pdo, $data);
    }

    public function updateBy(array $criteria, array $data): bool
    {
        return $this->db()->updateByCriteria($this->pdo, $criteria, $data);
    }

    public function deleteBy(array $criteria): bool
    {
        return $this->db()->delete($this->pdo, $criteria);
    }

    // -------------------- Internals --------------------

    protected function db(): DbTable
    {
        return new DbTable(
            library: $this->library,
            table: static::$table,
            primaryKey: static::$primaryKey,
            columns: static::$columns,
            uniqueKeys: static::$uniqueKeys,
        );
    }

    private function resetQuery(): void
    {
        $this->where = [];
        $this->orderBy = [];
        $this->groupBy = [];
        $this->limit = null;
        $this->select = ['*'];
    }
}

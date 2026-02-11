<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use InvalidArgumentException;

final class DbTable
{
    /**
     * Cache of field metadata per table (library.table => short => ['long'=>..., 'type'=>...])
     * @var array<string,array<string,array<string,string>>> 
     */
    private static array $fieldMetaCache = [];

    /**
     * Cache of index metadata per table (library.table => indexName => ['fields'=>string[], 'unique'=>bool])
     * @var array<string,array<string,array<string,mixed>>>
     */
    private static array $indexMetaCache = [];

    /**
     * @param string[] $primaryKey
     * @param string[][] $uniqueKeys  ex: [ ['U_COL1','U_COL2'], ['U_COL3'] ]
     * @param string[] $columns
     */
    public function __construct(
        private string $library,
        private string $table,
        private array $primaryKey,
        private array $columns = [],
        private array $uniqueKeys = [],
    ) {}

    private function fqtn(): string
    {
        return "{$this->library}.{$this->table}";
    }

    /** @param array<string, mixed> $key */
    public function get(PDO $pdo, array $key, array $columns = ['*']): ?array
    {
        if ($columns === ['*']) {
            $cols = $this->fqtn() .'.*';
        } else {
            $cols = implode(','.$this->fqtn() .'.', array_map([$this, 'ident'], $columns));
        }
        // Toujours ajouter le RRN(library.table) en dernière colonne pour permettre l'accès au numéro d'enregistrement
        $cols .= ', RRN(' . $this->fqtn() . ') AS NUMENREG';
        [$whereSql, $params] = $this->whereFromKey($key);

        $sql  = "SELECT {$cols} FROM {$this->fqtn()} WHERE {$whereSql} FETCH FIRST 1 ROWS ONLY";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $p => $v) $stmt->bindValue($p, $v);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        return $row;
    }

    /** @param array<string, mixed> $key */
    public function delete(PDO $pdo, array $key): bool
    {
        [$whereSql, $params] = $this->whereFromKey($key);
        $sql  = "DELETE FROM {$this->fqtn()} WHERE {$whereSql}";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $p => $v) $stmt->bindValue($p, $v);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /** @param array<string, mixed> $key @param array<string,mixed> $data */
    public function update(PDO $pdo, array $key, array $data): bool
    {
        $data = $this->filterColumns($data, allowPk: false);
        if (!$data) return false;

        // protection : on interdit de modifier les colonnes de PK
        foreach ($this->primaryKey as $pk) unset($data[$pk]);

        $set = implode(', ', array_map(
            fn($c) => $this->ident($c) . " = :" . $c,
            array_keys($data)
        ));

        [$whereSql, $params] = $this->whereFromKey($key);

        $sql  = "UPDATE {$this->fqtn()} SET {$set} WHERE {$whereSql}";
        $stmt = $pdo->prepare($sql);

        foreach ($data as $k => $v) $stmt->bindValue(':' . $k, $v);
        foreach ($params as $p => $v) $stmt->bindValue($p, $v);

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /** @param array<string,mixed> $data */
    public function insert(PDO $pdo, array $data): bool
    {
        $data = $this->filterColumns($data, allowPk: true);
        if (!$data) return false;

        $cols = array_keys($data);
        $sqlCols = implode(', ', array_map([$this, 'ident'], $cols));
        $sqlVals = implode(', ', array_map(fn($c) => ':' . $c, $cols));

        $sql  = "INSERT INTO {$this->fqtn()} ({$sqlCols}) VALUES ({$sqlVals})";
        $stmt = $pdo->prepare($sql);
        foreach ($data as $k => $v) $stmt->bindValue(':' . $k, $v);
        $stmt->execute();
        return true;
    }

    /**
     * Trouve 1 ligne par contrainte unique (composée ou non).
     * @param array<string,mixed> $uniqueKeyValues ex: ['W1FOUR'=>'12345','W1BIC'=>'...']
     */
    public function findOneBy(PDO $pdo, array $uniqueKeyValues, array $columns = ['*']): ?array
    {
        [$whereSql, $params] = $this->whereFromCriteria($uniqueKeyValues);

        if ($columns === ['*']) {
            $cols = $this->fqtn() .'.*';
        } else {
           $cols = implode(','.$this->fqtn() .'.', array_map([$this, 'ident'], $columns));
        }
        $cols .= ', RRN(' . $this->fqtn() . ') AS NUMENREG';
        $sql  = "SELECT {$cols} FROM {$this->fqtn()} WHERE {$whereSql} FETCH FIRST 1 ROWS ONLY";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $p => $v) $stmt->bindValue($p, $v);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Optionnel : upsert logique basé sur une unique key (composée).
     * - Si une ligne existe (selon $uniqueKeyCols), update
     * - Sinon insert
     *
     * @param string[] $uniqueKeyCols ex: ['SOC','CODE']
     * @param array<string,mixed> $data
     */
    private function upsertByUnique(PDO $pdo, array $uniqueKeyCols, array $data): bool
    {
        $uk = [];
        foreach ($uniqueKeyCols as $c) {
            if (!array_key_exists($c, $data)) {
                throw new \InvalidArgumentException("Missing unique key column in data: $c");
            }
            $uk[$c] = $data[$c];
        }

        $existing = $this->findOneBy($pdo, $uk, ['*']);
        if ($existing) {
            // update by unique key criteria (pas par PK)
            return $this->updateByCriteria($pdo, $uk, $data);
        }

        return $this->insert($pdo, $data);
    }

    /** @param array<string,mixed> $criteria @param array<string,mixed> $data */
    public function updateByCriteria(PDO $pdo, array $criteria, array $data): bool
    {
        $data = $this->filterColumns($data, allowPk: false);
        if (!$data) return false;

        $set = implode(', ', array_map(
            fn($c) => $this->ident($c) . " = :" . $c,
            array_keys($data)
        ));

        [$whereSql, $params] = $this->whereFromCriteria($criteria);

        $sql  = "UPDATE {$this->fqtn()} SET {$set} WHERE {$whereSql}";
        $stmt = $pdo->prepare($sql);

        foreach ($data as $k => $v) $stmt->bindValue(':' . $k, $v);
        foreach ($params as $p => $v) $stmt->bindValue($p, $v);

        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /** @param array<string,mixed> $key */
    private function whereFromKey(array $key): array
    {
        // Si la PK est composée, on exige que toutes les colonnes soient fournies
        foreach ($this->primaryKey as $pk) {
            if (!array_key_exists($pk, $key)) {
                throw new \InvalidArgumentException("Missing primary key column: $pk");
            }
        }
        $criteria = [];
        foreach ($this->primaryKey as $pk) $criteria[$pk] = $key[$pk];
        return $this->whereFromCriteria($criteria);
    }

    /** @param array<string,mixed> $criteria */
    private function whereFromCriteria(array $criteria): array
    {
        // Backward-compatible: associative array means equality.
        return $this->whereFromFlexible($criteria);
    }

    /**
     * Build WHERE from either associative criteria or a list of conditions.
     *
     * @param array<mixed> $where
     * @return array{0:string,1:array<string,mixed>}
     */
    private function whereFromFlexible(array $where): array
    {
        // Empty criteria means: no filtering (list all rows).
        // This is useful for tables like C2LANGUE::all() that intentionally call get() without where().
        if ($where === []) {
            return ['1=1', []];
        }

        // If it's associative => convert to conditions with inferred operators
        if ($this->isAssoc($where)) {
            $where = $this->assocToWhere($where);
        }

        $parts  = [];
        $params = [];
        $i = 0;

        foreach ($where as $cond) {
            if (!is_array($cond)) {
                throw new InvalidArgumentException('Invalid condition (must be an array)');
            }

            $col = (string)($cond['col'] ?? '');
            $op  = strtoupper(trim((string)($cond['op'] ?? '=')));
            // accept both 'value' and 'val' keys
            $val = array_key_exists('value', $cond) ? $cond['value'] : ($cond['val'] ?? null);

            if ($col === '') throw new InvalidArgumentException('Missing condition column');
            $colId = $this->ident($col);

            // Normalize operator aliases
            if ($op === '<>') $op = '!=';

            // Operators without value
            if ($op === 'IS NULL' || $op === 'IS NOT NULL') {
                $parts[] = $colId . ' ' . $op;
                continue;
            }

            // IN / NOT IN
            if ($op === 'IN' || $op === 'NOT IN') {
                if (!is_array($val) || count($val) === 0) {
                    // IN () is invalid SQL; make it always-false/true depending on operator
                    $parts[] = ($op === 'IN') ? '1=0' : '1=1';
                    continue;
                }

                $placeholders = [];
                foreach (array_values($val) as $j => $v) {
                    $p = ':w' . $i . '_' . $j;
                    $placeholders[] = $p;
                    $params[$p] = $v;
                }
                $parts[] = $colId . ' ' . $op . ' (' . implode(',', $placeholders) . ')';
                $i++;
                continue;
            }

            // Standard binary operators
            $allowed = ['=', '!=', '<', '<=', '>', '>=', 'LIKE'];
            if (!in_array($op, $allowed, true)) {
                throw new InvalidArgumentException('Unsupported operator: ' . $op);
            }

            // NULL value with binary operator => turn into IS NULL / IS NOT NULL for = / !=
            if ($val === null) {
                if ($op === '=') {
                    $parts[] = $colId . ' IS NULL';
                    continue;
                }
                if ($op === '!=') {
                    $parts[] = $colId . ' IS NOT NULL';
                    continue;
                }
                throw new InvalidArgumentException('NULL value not allowed with operator: ' . $op);
            }

            $p = ':w' . $i;
            $parts[] = $colId . ' ' . $op . ' ' . $p;
            $params[$p] = $val;
            $i++;
        }

        return [implode(' AND ', $parts), $params];
    }

    /** @param array<string,mixed> $criteria */
    private function assocToWhere(array $criteria): array
    {
        $where = [];

        foreach ($criteria as $col => $value) {
            if ($value === null) {
                $where[] = ['col' => (string)$col, 'op' => 'IS NULL', 'value' => null];
                continue;
            }
            if (is_array($value)) {
                $where[] = ['col' => (string)$col, 'op' => 'IN', 'value' => $value];
                continue;
            }
            $where[] = ['col' => (string)$col, 'op' => '=', 'value' => $value];
        }

        return $where;
    }

    /** @param array<mixed> $a */
    private function isAssoc(array $a): bool
    {
        // Empty array already handled by caller
        return array_keys($a) !== range(0, count($a) - 1);
    }

    /** @param array<string,string> $orderBy ex: ['C0NUM'=>'ASC'] */
    private function orderBySql(array $orderBy): string
    {
        if (!$orderBy) return '';

        $parts = [];
        foreach ($orderBy as $col => $dir) {
            $colId = $this->ident((string)$col);
            $d = strtoupper(trim((string)$dir));
            if ($d !== 'ASC' && $d !== 'DESC') {
                throw new InvalidArgumentException('Invalid ORDER BY direction for ' . $col);
            }
            $parts[] = $colId . ' ' . $d;
        }

        return ' ORDER BY ' . implode(', ', $parts);
    }

    /** @param array<int,string> $groupBy ex: ['C0NUM','COL2'] */
    private function groupBySql(array $groupBy): string
    {
        if (!$groupBy) return '';

        $parts = [];
        foreach ($groupBy as $col) {
            $parts[] = $this->ident((string)$col);
        }

        return ' GROUP BY ' . implode(', ', $parts);
    }

    /**
     * Build SELECT list.
     * - Standard columns are table-qualified and validated.
     * - Raw expressions can be passed with a leading '#' prefix (e.g. '#COUNT(*) AS NOMBRE').
     */
    private function selectSql(array $columns, bool $appendRrn): string
    {
        if ($columns === ['*']) {
            $cols = $this->fqtn() . '.*';
        } else {
            $parts = [];
            foreach ($columns as $col) {
                if (!is_string($col)) {
                    throw new InvalidArgumentException('Invalid column in select list');
                }
                if (str_starts_with($col, '#')) {
                    $parts[] = substr($col, 1);
                    continue;
                }
                $parts[] = $this->fqtn() . '.' . $this->ident($col);
            }
            $cols = implode(', ', $parts);
        }

        if ($appendRrn) {
            $cols .= ', RRN(' . $this->fqtn() . ') AS NUMENREG';
        }

        return $cols;
    }

    // ----------------------------- Flexible WHERE helpers -----------------------------

    /**
     * Get a single row using a flexible where definition.
     *
     * $where can be:
     *  - associative array: ['COL' => 'value', 'COL2' => ['A','B'], 'COL3' => null]
     *  - list of conditions: [ ['col'=>'COL','op'=>'>=','value'=>10], ... ]
     *
     * Supported operators: =, !=, <>, <, <=, >, >=, LIKE, IN, NOT IN, IS NULL, IS NOT NULL
     */
    public function getWhere(PDO $pdo, array $where, array $orderBy = [], array $columns = ['*'], array $groupBy = [], bool $dumpSql = false): ?array
    {
        $cols = $this->selectSql($columns, appendRrn: empty($groupBy));

        [$whereSql, $params] = $this->whereFromFlexible($where);
        $groupSql = $this->groupBySql($groupBy);
        $orderSql = $this->orderBySql($orderBy);

        $sql  = "SELECT {$cols} FROM {$this->fqtn()} WHERE {$whereSql}{$groupSql}{$orderSql} FETCH FIRST 1 ROWS ONLY";
        if ($dumpSql) {
            var_dump(['sql' => $sql, 'params' => $params]);
        }
        
        $stmt = $pdo->prepare($sql);
        foreach ($params as $p => $v) $stmt->bindValue($p, $v);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        return $row;
    }

    /**
     * List rows using a flexible where definition.
     * @return array<int,array<string,mixed>>
     */
    public function listWhere(PDO $pdo, array $where, array $orderBy = [], ?int $limit = null, array $columns = ['*'], array $groupBy = [], bool $dumpSql = false): array
    {
        $cols = $this->selectSql($columns, appendRrn: empty($groupBy));
        [$whereSql, $params] = $this->whereFromFlexible($where);
        $groupSql = $this->groupBySql($groupBy);
        $orderSql = $this->orderBySql($orderBy);

        $sql  = "SELECT {$cols} FROM {$this->fqtn()} WHERE {$whereSql}{$groupSql}{$orderSql}";
        if ($limit !== null) {
            $limit = max(0, (int)$limit);
            if ($limit > 0) {
                $sql .= " FETCH FIRST {$limit} ROWS ONLY";
            }
        }

        if ($dumpSql) {
            var_dump(['sql' => $sql, 'params' => $params]);
        }

        $stmt = $pdo->prepare($sql);
        foreach ($params as $p => $v) $stmt->bindValue($p, $v);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) return [];
        return $rows;
    }

    /** Convenience: associative criteria -> 1 row */
    public function getBy(PDO $pdo, array $criteria, array $orderBy = [], array $columns = ['*']): ?array
    {
        return $this->getWhere($pdo, $criteria, $orderBy, $columns);
    }

    /** Convenience: associative criteria -> list */
    public function listBy(PDO $pdo, array $criteria, array $orderBy = [], ?int $limit = null, array $columns = ['*']): array
    {
        return $this->listWhere($pdo, $criteria, $orderBy, $limit, $columns);
    }

    /** @param array<string,mixed> $data */
    private function filterColumns(array $data, bool $allowPk): array
    {
        if (empty($this->columns)) {
            if (!$allowPk) {
                foreach ($this->primaryKey as $pk) unset($data[$pk]);
            }
            return $data;
        }

        $allowed = array_flip($this->columns);
        if (!$allowPk) {
            foreach ($this->primaryKey as $pk) unset($allowed[$pk]);
        }

        $out = [];
        foreach ($data as $k => $v) {
            if (isset($allowed[$k])) $out[$k] = $v;
        }
        return $out;
    }

    private function ident(string $name): string
    {
        if (!preg_match('/^[A-Z0-9_#$@]{1,30}$/i', $name)) {
            throw new \InvalidArgumentException("Invalid identifier: $name");
        }
        return $name;
    }

    private function substrSafe(string $str, int $start, int $length): string
    {
        return function_exists('mb_substr')
            ? (string) mb_substr($str, $start, $length)
            : (string) substr($str, $start, $length);
    }

    /**
     * Load and return QADBIFLD metadata for this table.
     * Returns map: SHORT => ['long'=> LONGNAME, 'type' => DBITYP]
     * Caches results per library.table.
     *
     * @return array<string,array{long:string,type:string}>
     */
    public function loadFieldMetadata(PDO $pdo): array
    {
        $key = $this->fqtn();
        if (isset(self::$fieldMetaCache[$key])) return self::$fieldMetaCache[$key];

        $sql = "SELECT DBIFLD, DBILFL, DBITYP FROM hfsql.QADBIFLD WHERE DBILIB = :lib AND (DBIFIL = :file OR DBILFI = :longfile) AND DBIDFI IN ('Y','N','I')";
        $stmt = $pdo->prepare($sql);

        // DBIFIL is a 10-char column; truncate to avoid ODBC 30207 when the table name is longer (e.g. VUE_* views)
        $shortFile = $this->substrSafe($this->table, 0, 10);
        $longFile  = $this->table;

        $stmt->bindValue(':lib', $this->library);
        $stmt->bindValue(':file', $shortFile);
        $stmt->bindValue(':longfile', $longFile);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $meta = [];
        foreach ($rows as $r) {
            $short = strtoupper(trim((string)($r['DBIFLD'] ?? '')));
            $long  = strtoupper(trim((string)($r['DBILFL'] ?? '')));
            $type  = strtoupper(trim((string)($r['DBITYP'] ?? '')));
            if ($short === '') continue;
            $entry = ['short' => $short, 'long' => $long, 'type' => $type];
            $meta[$short] = $entry;
            if ($long !== '' && $long !== $short) {
                $meta[$long] = $entry; // reverse mapping to retrouver short à partir du long
            }
        }

        self::$fieldMetaCache[$key] = $meta;
        return $meta;
    }

    /**
     * Load and return index definitions (logical files) for this table.
     * Returns map: INDEX_NAME => ['fields' => [COL1, COL2, ...], 'unique' => bool]
     *
     * @return array<string,array{fields:array<int,string>,unique:bool}>
     */
    private function loadIndexMetadata(PDO $pdo): array
    {
        $key = $this->fqtn();
        if (isset(self::$indexMetaCache[$key])) return self::$indexMetaCache[$key];

        $sql = <<<SQL
Select dbxlib,
       dbxfil,
       Listagg(Trim(dbkfld), ';') Within Group (Order By dbkpos) as dbkfld,
       dbxunq
 From (
   Select Distinct dbxlib,
                   dbxfil,
                   f.dbkfld,
                   dbkpos,
                   t.dbxunq
    From hfsql.qadbxref t
         Inner Join hfsql.qadbkfld f
          On dbxfil = dbkfil And
           dbxlib = dbklib
         Inner Join hfsql.qadbfdep d
          On d.dbffdp = t.dbxfil And
           d.dbflib = t.dbxlib
    Where d.dbffil = :file And
          d.dbflib = :lib
  )
 Group By dbxlib,
          dbxfil,
          dbxunq
SQL;

        $stmt = $pdo->prepare($sql);

        // DBFFIL is 10 chars max; truncate to avoid ODBC 30207
        $physical = $this->substrSafe($this->table, 0, 10);

        $stmt->bindValue(':lib', $this->library);
        $stmt->bindValue(':file', $physical);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $meta = [];
        foreach ($rows as $r) {
            $indexName = strtoupper(trim((string)($r['DBXFIL'] ?? '')));
            if ($indexName === '') continue;
            $fieldsStr = (string)($r['DBKFLD'] ?? '');
            $fields = array_values(array_filter(array_map(
                fn($c) => strtoupper(trim($c)),
                explode(';', $fieldsStr)
            ), fn($c) => $c !== ''));
            $unique = strtoupper(trim((string)($r['DBXUNQ'] ?? ''))) === 'Y';
            $meta[$indexName] = ['fields' => $fields, 'unique' => $unique];
        }

        self::$indexMetaCache[$key] = $meta;
        return $meta;
    }

    /**
     * Public helper: returns a simple map of columnName => type including long names.
     * Example: ['APART'=>'CHAR','AP_CODE_ARTICLE'=>'CHAR']
     *
     * @return array<string,string>
     */
    public function fieldTypes(PDO $pdo): array
    {
        $meta = $this->loadFieldMetadata($pdo);
        $out = [];
        foreach ($meta as $key => $info) {
            $t       = $info['type'] ?? '';
            if ($t === '') continue;
            $shortUp = strtoupper($info['short'] ?? $key);
            $longUp  = strtoupper($info['long'] ?? '');
            if ($shortUp !== '') $out[$shortUp] = $t;
            if ($longUp !== '')  $out[$longUp]  = $t;
        }
        return array_change_key_case($out, CASE_UPPER);
    }

    /**
     * Retourne une table de correspondance short <-> long.
     * Exemple: ['AEDATA' => 'AE_DATA', 'AE_DATA' => 'AEDATA']
     *
     * @return array<string,string>
     */
    public function shortLong(PDO $pdo): array
    {
        $meta = $this->loadFieldMetadata($pdo);
        $map = [];
        foreach ($meta as $entry) {
            $short = strtoupper($entry['short'] ?? '');
            $long  = strtoupper($entry['long']  ?? '');
            if ($short === '' || $long === '' || $short === $long) continue;
            $map[$short] = $long;
            $map[$long]  = $short;
        }
        return $map;
    }

    /**
     * Public helper: returns index definitions for this table.
     *
     * @return array<string,array{fields:array<int,string>,unique:bool}>
     */
    public function indexes(PDO $pdo): array
    {
        return $this->loadIndexMetadata($pdo);
    }
}

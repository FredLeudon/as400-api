<?php
declare(strict_types=1);

namespace App\Utils;

final class Sql
{
    public static function debugSql(string $sql, array $params): string
    {
        foreach ($params as $key => $value) {
            if (is_string($value)) {
                $value = "'" . addslashes($value) . "'";
            } elseif ($value === null) {
                $value = 'NULL';
            }
            $sql = str_replace((string)$key, (string)$value, $sql);
        }
        return $sql;
    }

    public static function normalizeSearchTerm(string $s): string
    {
        $s = trim($s);
        if ($s === '') return '';

        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false && $t !== null) $s = $t;

        $s = preg_replace('/\s+/', ' ', $s) ?? $s;
        return strtoupper($s);
    }

    public static function sqlAccentFoldExpr(string $colExpr): string
    {
        $from = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÑÒÓÔÕÖŒÙÚÛÜÝŸàáâãäåæçèéêëìíîïñòóôõöœùúûüýÿ';
        $to   = 'AAAAAAACEEEEIIIINOOOOOOEUUUUYYaaaaaaaceeeeiiiinooooooeuuuuyy';

        if (strlen($from) !== strlen($to)) {
            return "UPPER($colExpr)";
        }

        return "UPPER(TRANSLATE($colExpr, '$to', '$from'))";
    }
}

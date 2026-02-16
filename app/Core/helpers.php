<?php
declare(strict_types=1);

use App\Core\Debug;

if (!function_exists('log_var_dump')) {
    function log_var_dump(mixed $value, ?string $label = null, ?string $targetPath = null): void
    {
        Debug::logVarDump($value, $label, $targetPath);
    }
}

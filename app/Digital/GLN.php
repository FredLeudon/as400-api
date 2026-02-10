<?php
declare(strict_types=1);

namespace App\Digital;

use App\Core\clFichier;

final class GLN extends clFichier
{
    protected static string $table = 'GLN';
    protected static array $primaryKey = [];

    protected static array $columns = [];
}

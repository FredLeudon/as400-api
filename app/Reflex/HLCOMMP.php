<?php
declare(strict_types=1);

namespace App\Reflex;

use App\Domain\Company;
use App\Core\clFichier;

final class HLCOMMP extends clFichier
{
    protected static string $table = 'HLCOMMP';
    protected static array $primaryKey = ['CONCOM', 'CONLCO'];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'CONCOM' => ['label' => 'Numero de commentaire',              'type' => 'DECIMAL', 'nullable' => false],
        'CONLCO' => ['label' => 'Numero de ligne commentaire',        'type' => 'DECIMAL', 'nullable' => false],
        'COTXTC' => ['label' => 'Texte du commentaire',               'type' => 'CHAR',    'nullable' => false],
        'CONOCO' => ['label' => "Numero d'ordre ligne commentaire",   'type' => 'DECIMAL', 'nullable' => false],
        'COCFCO' => ['label' => 'Code famille de commentaire',        'type' => 'CHAR',    'nullable' => false],
        'COCFIC' => ['label' => 'Code fichier',                       'type' => 'CHAR',    'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;

        $library = (string)($company['reflex_library'] ?? '');
        return $library !== '' ? $library : null;
    }
}

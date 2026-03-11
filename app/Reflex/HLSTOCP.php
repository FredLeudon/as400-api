<?php
declare(strict_types=1);

namespace App\Reflex;

use App\Domain\Company;
use App\Core\clFichier;

final class HLSTOCP extends clFichier
{
    protected static string $table = 'HLSTOCP';
    protected static array $primaryKey = ['SKCDPO', 'SKCTST', 'SKCACT', 'SKCART', 'SKCVLA', 'SKCPRP', 'SKCQAL'];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'SKCDPO' => ['label' => 'Code depot physique',                         'type' => 'CHAR',    'nullable' => false],
        'SKCTST' => ['label' => 'Code type de stock',                          'type' => 'CHAR',    'nullable' => false],
        'SKCACT' => ['label' => 'Code activite',                               'type' => 'CHAR',    'nullable' => false],
        'SKCART' => ['label' => 'Code article',                                'type' => 'CHAR',    'nullable' => false],
        'SKCVLA' => ['label' => 'Code variante logistique article',            'type' => 'CHAR',    'nullable' => false],
        'SKCPRP' => ['label' => 'Code proprietaire',                           'type' => 'CHAR',    'nullable' => false],
        'SKCQAL' => ['label' => 'Code qualite',                                'type' => 'CHAR',    'nullable' => false],
        'SKQSTK' => ['label' => 'Quantite du stock en VL de base',             'type' => 'DECIMAL', 'nullable' => false],
        'SKPDST' => ['label' => 'Poids du stock',                              'type' => 'DECIMAL', 'nullable' => false],
        'SKPXST' => ['label' => 'Prix du stock',                               'type' => 'DECIMAL', 'nullable' => false],
        'SKMTST' => ['label' => 'Montant du stock',                            'type' => 'DECIMAL', 'nullable' => false],
    ];

    private static function libraryOf(string $companyCode): ?string
    {
        $company = Company::get($companyCode);
        if (!$company) return null;

        $library = (string)($company['reflex_library'] ?? '');
        return $library !== '' ? $library : null;
    }
}

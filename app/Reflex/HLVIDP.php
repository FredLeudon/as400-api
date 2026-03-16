<?php
declare(strict_types=1);

namespace App\Reflex;


use App\Reflex\Dépot;
use App\Core\clFichier;
use DateTimeImmutable;

final class HLVIDP extends clFichier
{
    protected static string $table = 'HLVIDP';
    protected static array $primaryKey = ['VICACT', 'VICART', 'VICVLA', 'VICTYI', 'VICIVL'];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'VICACT' => ['label' => 'Code activite',                           'type' => 'CHAR',    'nullable' => false],
        'VICART' => ['label' => 'Code article',                            'type' => 'CHAR',    'nullable' => false],
        'VICVLA' => ['label' => 'Code variante logistique article',        'type' => 'CHAR',    'nullable' => false],
        'VICTYI' => ["label" => "Code type d'identifiant VL",              'type' => 'CHAR',    'nullable' => false],
        'VICIVL' => ['label' => 'Code identifiant VL',                     'type' => 'CHAR',    'nullable' => false],
        'VINCOM' => ['label' => 'Numero de commentaire',                   'type' => 'DECIMAL', 'nullable' => false],
        'VISCRE' => ['label' => 'Date de creation - siecle',               'type' => 'DECIMAL', 'nullable' => false],
        'VIACRE' => ['label' => 'Date de creation - annee',                'type' => 'DECIMAL', 'nullable' => false],
        'VIMCRE' => ['label' => 'Date de creation - mois',                 'type' => 'DECIMAL', 'nullable' => false],
        'VIJCRE' => ['label' => 'Date de creation - jour',                 'type' => 'DECIMAL', 'nullable' => false],
        'VIHCRE' => ['label' => 'Heure de creation',                       'type' => 'DECIMAL', 'nullable' => false],
        'VICUCR' => ['label' => 'Code utilisateur creation',               'type' => 'CHAR',    'nullable' => false],
        'VISMAJ' => ['label' => 'Date de derniere mise a jour - siecle',   'type' => 'DECIMAL', 'nullable' => false],
        'VIAMAJ' => ['label' => 'Date de derniere mise a jour - annee',    'type' => 'DECIMAL', 'nullable' => false],
        'VIMMAJ' => ['label' => 'Date de derniere mise a jour - mois',     'type' => 'DECIMAL', 'nullable' => false],
        'VIJMAJ' => ['label' => 'Date de derniere mise a jour - jour',     'type' => 'DECIMAL', 'nullable' => false],
        'VIHMAJ' => ['label' => 'Heure de derniere mise a jour',           'type' => 'DECIMAL', 'nullable' => false],
        'VICUMJ' => ['label' => 'Code utilisateur derniere mise a jour',   'type' => 'CHAR',    'nullable' => false],
        'VITOPD' => ['label' => 'Top desactivation',                       'type' => 'CHAR',    'nullable' => false],
    ];

    private static function libraryOf(string $CodeActivité): ?string
    {
        $company = Dépot::get($CodeActivité);
        if (!$company) return null;

        $library = (string)($company['library'] ?? '');
        return $library !== '' ? $library : null;
    }

    
    public static function DonneCodeBarreVL(\PDO $pdo, string $CodeActivité, string $CodeArticle, ? string $CodeConditionnement = '01', ? string $CodeIdentifiant = 'EAN13', ? DateTimeImmutable $DateCAB = null ): ? static
    {
        $library = self::libraryOf($CodeActivité);
        if ($library === null) return null;
        $DateCAB ??= new DateTimeImmutable();
        return null;
    }
}

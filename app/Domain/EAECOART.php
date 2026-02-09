<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\Http;
use App\Core\clFichier;

/**
 * FCMBI.EAECOART — éco-contribution par article.
 *
 * Clé : EAART (EAL0)
 */
final class EAECOART extends clFichier
{
    protected static string $table = 'EAECOART';
    protected static array $primaryKey = ['EAART'];
    /** @var array<int,array<int,string>> */
    protected static array $uniqueKeys = [
        ['EAART'], // EAL0
    ];
    /** @var array<string,array<int,string>> */
    protected static array $indexes = [
        'EAL0' => ['EAART'],
    ];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'EAART'  => ['label' => 'Code article',             'type' => 'CHAR',     'nullable' => false],
        'EATECO' => ['label' => 'Type',                      'type' => 'SMALLINT','nullable' => false],
        'EAPRIX' => ['label' => 'Prix éco-contribution',     'type' => 'NUMERIC', 'nullable' => false],
        'EAHORO' => ['label' => 'Horodatage',                'type' => 'TIMESTMP','nullable' => false],
        'EAUTIL' => ['label' => 'Utilisateur',               'type' => 'CHAR',    'nullable' => false],
        'EAPGM'  => ['label' => 'Programme',                 'type' => 'CHAR',    'nullable' => false],
    ];
   

    /*** Retourne l'enregistrement éco-contribution d'un article. ***/

    public static function getEcoPartArticle(\PDO $pdo, string $articleId): ? static
    {
        try {
            $library = 'FCMBI';            
            $articleId = trim($articleId);
            if ($articleId === '') return null;
            $row = self::for($pdo, $library)
                ->whereEq('EAART', $articleId)
                ->firstModel();
            return $row;
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }    
}
 
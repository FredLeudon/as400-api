<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\Http;
use App\Core\clFichier;
use DateTimeInterface;

/**
 * FCMBI.PXROUGE — prix plancher (prix rouge) par article et société.
 *
 * Clé : PRART, PRSOC, PRDDEB (index PXROUGEL0).
 */
final class PXROUGE extends clFichier
{
    protected static string $table = 'PXROUGE';
    protected static array $primaryKey = ['PRART', 'PRSOC', 'PRDDEB'];
    /** @var array<int,array<int,string>> */
    protected static array $uniqueKeys = [
        ['PRART', 'PRSOC', 'PRDDEB'], // PXROUGEL0
    ];
    /** @var array<string,array<int,string>> */
    protected static array $indexes = [
        'PXROUGEL0' => ['PRART', 'PRSOC', 'PRDDEB'],
    ];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'PRART'  => ['label' => 'Code Article',  'type' => 'CHAR',    'nullable' => false],
        'PRSOC'  => ['label' => 'Code Société',  'type' => 'CHAR',    'nullable' => false],
        'PRNET'  => ['label' => 'Prix plancher', 'type' => 'DECIMAL', 'nullable' => false],
        'PRDDEB' => ['label' => 'Date de départ','type' => 'NUMERIC', 'nullable' => false],
        'PRDFIN' => ['label' => 'Date de fin',   'type' => 'NUMERIC', 'nullable' => false],
    ];

    private static function dateToInt(?string $date): int
    {
        if ($date === null || trim($date) === '') {
            return (int)date('Ymd');
        }
        $clean = preg_replace('/[^0-9]/', '', $date);
        return (int)($clean === '' ? date('Ymd') : $clean);
    }

    /**
     * Prix rouge actif pour un article/société à une date (par défaut aujourd'hui).
     */
    public static function getPrixRougeArticle(\PDO $pdo, string $articleId): ?static
    {
        try {
            $library = 'FCMBI';
            $articleId = trim($articleId);
            if ($articleId === '') return null;            
            $row = self::for($pdo, $library)
                ->whereEq('PRART', $articleId)                
                ->firstModel();
            return $row;

        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
    
}

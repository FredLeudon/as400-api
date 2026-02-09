<?php
declare(strict_types=1);

namespace App\Domain;

use App\Core\Http;
use App\Core\clFichier;

/**
 * MATIS.ARTNOWEB — liste des articles exclus du web.
 *
 * Clef primaire / unique : CODART (ANWL001)
 */
final class ARTNOWEB extends clFichier
{
    protected static string $table = 'ARTNOWEB';
    protected static array $primaryKey = ['CODART'];
    /** @var array<int,array<int,string>> */
    protected static array $uniqueKeys = [
        ['CODART'], // ANWL001
    ];
    /** @var array<string,array<int,string>> */
    protected static array $indexes = [
        'ANWL001' => ['CODART'],
    ];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'CODART' => ['label' => 'Code Article', 'type' => 'CHAR', 'nullable' => false],
    ];

    /**
     * Retourne l'enregistrement ARTNOWEB pour un article.
     **/
    public static function getByArticle(\PDO $pdo, string $articleId): ?static
    {
        try {
            $library = 'MATIS';

            $articleId = trim($articleId);
            if ($articleId === '') null;

            $row = self::for($pdo, $library)
                ->whereEq('CODART', $articleId)
                ->firstModel();

            return $row;
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }

    /**
     * Indique si l'article est marqué "non web".
     */
    public static function isBlocked(\PDO $pdo, string $articleId): bool
    {
        return self::getByArticle($pdo, $articleId) !== null;
    }

    /**
     * Liste complète des articles "non web" ordonnés par code.
     *
     * @return array<int,static>
     */
    public static function allModels(\PDO $pdo): array
    {
        try {
            $library = 'MATIS';

            return self::for($pdo, $library)
                ->orderBy('CODART', 'ASC')
                ->getModels();
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

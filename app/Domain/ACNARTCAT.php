<?php
declare(strict_types=1);

namespace App\Domain;

use PDO;
use App\Core\Http;
use App\Core\clFichier;

final class ACNARTCAT extends clFichier
{
    protected static string $table = 'ACNARTCAT';
    protected static array $primaryKey = ['ACART'];
    /** @var array<string,array<int,string>> */
    protected static array $indexes = [
        'ACL0' => ['ACART','ACSOC'],
    ];
    /** @var array<string,array<string,mixed>> */
    protected static array $columns = [
        'ACART'   => ['label' => 'Article',                    'type' => 'CHAR',    'nullable' => false],
        'ACSOC'   => ['label' => 'N° de société',               'type' => 'CHAR',    'nullable' => false],
        'ACID'    => ['label' => 'Id du fichier catalogue',     'type' => 'DECIMAL', 'nullable' => false],
        'ACPAGC'  => ['label' => 'Numéro page catalogue',       'type' => 'DECIMAL', 'nullable' => false],
        'ACNORD'  => ['label' => 'Numéro d\'ordre',            'type' => 'DECIMAL', 'nullable' => false],
        'ACNAJT'  => ['label' => 'Numéro d\'ajout',            'type' => 'DECIMAL', 'nullable' => false],
        'ACACT'   => ['label' => 'Actif',                       'type' => 'CHAR',    'nullable' => false],
        'ACHOACT' => ['label' => 'Horodatage action',          'type' => 'TIMESTMP','nullable' => false],
        'ACUTIL'  => ['label' => 'Utilisateur',                 'type' => 'CHAR',    'nullable' => false],
        'ACPGM'   => ['label' => 'Programme',                  'type' => 'CHAR',    'nullable' => false],
    ];

    /**
     * Retourne les enregistrements catalogue pour un article et une société.
     *
     * @return ?array<int,static>
     */
    public static function getModelsByCompanyCatalogArticle(PDO $pdo, string $companyCode, string $catalogID, string $articleId): ?array
    {
        try {            
            $library = 'MATIS';

            $articleId = trim($articleId);
            if ($articleId === '') return null;

            // ACID est un entier dans la base — forcer le typage
            $catalogInt = (int) $catalogID;
            /*
            // Debug simple: exécuter la requête brute si APP_DEBUG=1
            $debug = (getenv('APP_DEBUG') === '1');
            if ($debug) {
                $sql = "SELECT * FROM MATIS.ACARTCAT WHERE ACART = :a AND ACSOC = :s AND ACID = :id ORDER BY ACPAGC ASC, ACNORD ASC";
                echo "SQL: " . $sql . "\n";
                echo "Params: a={$articleId} s={$companyCode} id={$catalogInt}\n";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':a', $articleId);
                $stmt->bindValue(':s', $companyCode);
                $stmt->bindValue(':id', $catalogInt, PDO::PARAM_INT);
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "Raw rows found: " . count($rows) . "\n";
                if ($rows === []) {
                    return [];
                }
                // Convert raw rows to model instances for compatibility
                $out = [];
                foreach ($rows as $r) {
                    $out[] = self::for($pdo, $library)->fill($r);
                }
                return $out;
            }
            */
            $models = self::for($pdo, $library)
                ->whereEq('ACART', $articleId)
                ->whereEq('ACSOC', $companyCode)
                ->whereEq('ACID', $catalogInt)
                ->orderBy('ACPAGC', 'ASC')
                ->orderBy('ACNORD', 'ASC')
                ->getModels();

            return $models;
        } catch (\Throwable $e) {
            Http::respond(500, Http::exceptionPayload($e, __METHOD__));
        }
    }
}

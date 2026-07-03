<?php

declare(strict_types=1);



namespace App\Models;



use App\Helpers\IlacRehberImportStore;

use App\Helpers\TrSearchFoldHelper;

/**

 * Scrape edilmiş etken madde (`#__rehber_etken`).

 */

class RehberEtken extends BaseModel

{

    public $id = null;

    public $ad = null;

    public $ad_normalized = null;

    public $source_site = null;

    public $source_key = null;

    public $scraped_at = null;



    public function __construct()

    {

        parent::__construct('#__rehber_etken', 'id');

    }



    public function ensureTables(): void

    {

        $path = ROOT_PATH . '/database/migrate_esh_rehber_ilac_create.sql';

        if (!is_readable($path)) {

            return;

        }

        $sql = file_get_contents($path);

        if ($sql === false) {

            return;

        }

        $sql = preg_replace('/--[^\n]*\n/', "\n", $sql) ?? $sql;

        foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {

            if ($stmt === '' || !preg_match('/^CREATE\s+TABLE/i', $stmt)) {

                continue;

            }

            $this->db->execLogged($stmt);

        }

    }



    /**

     * @return list<object>

     */

    public function searchByQuery(string $q, int $limit = 20): array

    {

        $limit = max(1, min(50, $limit));

        $pattern = TrSearchFoldHelper::likePattern($q);

        $adFold = TrSearchFoldHelper::sqlFoldExpr('ad');



        return $this->db->fetchObjectListPrepared(

            'SELECT id, ad, source_site, scraped_at

             FROM #__rehber_etken

             WHERE ' . $adFold . ' LIKE :q ESCAPE \'\\\\\'

             ORDER BY ad ASC

             LIMIT ' . (int) $limit,

            [':q' => $pattern]

        );

    }



    public function findById(int $id): ?object

    {

        if ($id <= 0) {

            return null;

        }



        return $this->db->fetchObjectPrepared(

            'SELECT id, ad, ad_normalized, source_site, source_key, scraped_at

             FROM #__rehber_etken WHERE id = :id LIMIT 1',

            [':id' => $id]

        );

    }



    public function countAll(): int

    {

        return (int) $this->db->loadResultPrepared('SELECT COUNT(*) FROM #__rehber_etken');

    }



    public function getLatestImportLog(): ?object

    {

        return IlacRehberImportStore::getLatestImportLog();

    }



    /**

     * Açık import işini kapatır; etken/ilaç sayıları güncel tablodan alınır.

     */

    public function finalizeOpenImportLog(string $errorSummary): int

    {

        return IlacRehberImportStore::finalizeOpenImportLog($errorSummary);

    }



    public function getLatestCompletedImportLog(): ?object

    {

        return IlacRehberImportStore::getLatestCompletedImportLog();

    }



    public function countWithoutIlac(): int

    {

        return (int) $this->db->loadResultPrepared(

            'SELECT COUNT(*)

             FROM #__rehber_etken e

             WHERE NOT EXISTS (

                 SELECT 1 FROM #__rehber_ilac i WHERE i.etken_id = e.id LIMIT 1

             )'

        );

    }



    /**

     * @return array{last_scraped_at: ?string, source_sites: list<string>}

     */

    public function getScrapedSummary(): array

    {

        $last = $this->db->loadResultPrepared(

            'SELECT MAX(t.scraped_at) FROM (

                SELECT scraped_at FROM #__rehber_etken

                UNION ALL

                SELECT scraped_at FROM #__rehber_ilac

            ) t'

        );

        $siteRows = $this->db->fetchAllPrepared(

            'SELECT DISTINCT source_site FROM #__rehber_etken ORDER BY source_site ASC'

        );

        $sites = [];

        foreach ($siteRows as $row) {

            $site = $row['source_site'] ?? '';

            if ($site !== '') {

                $sites[] = (string) $site;

            }

        }



        return [

            'last_scraped_at' => $last !== false && $last !== null ? (string) $last : null,

            'source_sites' => $sites,

        ];

    }

}


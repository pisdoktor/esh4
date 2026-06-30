<?php

declare(strict_types=1);



namespace App\Models;



use App\Helpers\TrSearchFoldHelper;



/**

 * Scrape edilmiş ticari ilaç satırı (`#__rehber_ilac`).

 */

class RehberIlac extends BaseModel

{

    public $id = null;

    public $etken_id = null;

    public $ad = null;

    public $firma = null;

    public $recete_turu = null;

    public $source_site = null;

    public $source_url = null;

    public $source_key = null;

    public $scraped_at = null;



    public function __construct()

    {

        parent::__construct('#__rehber_ilac', 'id');

    }



    /**

     * @return list<object>

     */

    public function listByEtkenId(int $etkenId, int $limit, int $offset): array

    {

        if ($etkenId <= 0) {

            return [];

        }

        $limit = max(1, min(200, $limit));

        $offset = max(0, $offset);



        return $this->db->fetchObjectListPrepared(

            'SELECT id, ad, firma, recete_turu, source_url, scraped_at

             FROM #__rehber_ilac

             WHERE etken_id = :eid

             ORDER BY ad ASC

             LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset,

            [':eid' => $etkenId]

        );

    }



    public function countByEtkenId(int $etkenId): int

    {

        if ($etkenId <= 0) {

            return 0;

        }



        return (int) $this->db->loadResultPrepared(

            'SELECT COUNT(*) FROM #__rehber_ilac WHERE etken_id = :eid',

            [':eid' => $etkenId]

        );

    }



    public function countAll(): int

    {

        return (int) $this->db->loadResultPrepared('SELECT COUNT(*) FROM #__rehber_ilac');

    }



    /**

     * @return list<object>

     */

    public function searchByQuery(string $q, int $limit = 20): array

    {

        $limit = max(1, min(50, $limit));

        $pattern = TrSearchFoldHelper::likePattern($q);

        $adFold = TrSearchFoldHelper::sqlFoldExpr('i.ad');



        return $this->db->fetchObjectListPrepared(

            'SELECT i.id, i.ad, i.firma, i.recete_turu, i.etken_id, i.source_url,

                    e.ad AS etken_ad

             FROM #__rehber_ilac i

             INNER JOIN #__rehber_etken e ON e.id = i.etken_id

             WHERE ' . $adFold . ' LIKE :q ESCAPE \'\\\\\'

             ORDER BY i.ad ASC

             LIMIT ' . (int) $limit,

            [':q' => $pattern]

        );

    }

}


<?php
namespace App\Models;

/**
 * Hastalık kategori modeli — platform geneli (tüm kurumlar ortak ICD üst katmanları).
 */
class HastalikCat extends BaseModel {

    public $id = null;
    public $name = null;
    public $icd_range = null;

    public function __construct() {
        parent::__construct('#__hastalikcat', 'id');
    }

    /**
     * Tüm kategorileri listeler (kurum filtresi yok).
     */
    public function getList() {
        return $this->db->fetchObjectListPrepared(
            'SELECT * FROM #__hastalikcat ORDER BY id ASC'
        );
    }
}

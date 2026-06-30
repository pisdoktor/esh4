<?php
namespace App\Models;

/**
 * Tıbbi Branşlar Modeli
 */
class Cache extends BaseModel {
    
    public $id = null;
    public $hash = null;
    public $origin = null;
    public $destination = null;
    public $sure = null;
    public $mesafe = null;
    public $updated_at = null;

    public function __construct() {
        parent::__construct('#__rota_cache', 'id');
    }

    public function getCache($hash) {
        return $this->db->fetchObjectPrepared(
            'SELECT sure, mesafe FROM #__rota_cache WHERE hash = ?',
            [$hash]
        );
    }

    public function saveCache($hash, $origin, $dest, $sure, $mesafe) {
        return $this->db->executePrepared(
            'INSERT IGNORE INTO #__rota_cache (hash, origin, destination, sure, mesafe) VALUES (?, ?, ?, ?, ?)',
            [$hash, $origin, $dest, (int) $sure, (int) $mesafe]
        );
    }
}

<?php
namespace App\Models;

/**
 * Sağlık güvencesi modeli — platform geneli (kurum kapsamı yok).
 */
class Guvence extends BaseModel {
    
    public $id = null;
    public $guvenceadi = null;

    public function __construct() {
        parent::__construct('#__guvence', 'id');
    }

    /**
     * Tüm güvence türlerini alfabetik listeler (platform geneli; kurum filtresi uygulanmaz).
     */
    public function getList(string $orderFragment = 'guvenceadi ASC') {
        $orderFragment = trim($orderFragment) !== '' ? $orderFragment : 'guvenceadi ASC';
        return $this->db->fetchObjectListPrepared(
            'SELECT * FROM #__guvence ORDER BY ' . $orderFragment
        );
    }
    
    /**
     * Hasta kaydındaki güvence id'sine göre görünen adı döndürür.
     * Boş, null veya geçersiz id'de sorgu çalıştırılmaz (boş string).
     */
    public function getUserGuvence($id) {
        $id = (int) $id;
        if ($id < 1) {
            return '';
        }
        $name = $this->db->loadResultPrepared(
            'SELECT guvenceadi FROM #__guvence WHERE id = ?',
            [$id]
        );

        return $name !== null && $name !== false ? (string) $name : '';
    }
}

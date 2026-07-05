<?php
namespace App\Models;
use App\Core\Database;
use App\Core\DbSqlHelper;
use App\Helpers\IdHelper;

class BaseModel {
    public $db;
    protected $_tbl = '';
    protected $_tbl_key = 'id';
    protected $_dirty = [];
    /** @var bool CHAR(36) UUID PK tablolarında insert öncesi id üretir */
    protected $uuidPrimaryKey = false;

    public function __construct($table, $key = 'id') {
        $this->db = Database::getInstance();
        $this->_tbl = $table;
        $this->_tbl_key = $key;
        
    }

    /**
     * Dışarıdan gelen veriyi (POST verisi veya nesne) model özelliklerine bağlar.
     * @param bool $trackDirty true: store() için $_dirty doldurulur; false: DB yüklemesi (load)
     */
    public function bind($data, $trackDirty = true) {
        $data = (array) $data;
        
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                
                $cleanValue = is_string($value) ? trim($value) : $value;

                // GEÇERSİZ TARİH KONTROLÜ: 0000-00-00 veya 1970 gibi değerleri NULL yap
                if (
                    $cleanValue === '0000-00-00' || 
                    $cleanValue === '0000-00-00 00:00:00' || 
                    $cleanValue === '' || 
                    $cleanValue === 'NULL' || 
                    $cleanValue === '1970.01.01'
                ) {
                    $cleanValue = null;
                } elseif (($key === $this->_tbl_key || $key === 'id') && empty($cleanValue)) {
                    $cleanValue = null;
                }

                $this->$key = $cleanValue;

                if ($trackDirty && $key !== $this->_tbl_key) {
                    $this->_dirty[$key] = $cleanValue;
                }
            }
        }
    }

    /**
     * Veriyi veritabanına kaydeder. 
     * ID varsa günceller, yoksa yeni kayıt açar.
     */
    public function store($updateNulls = false) {
        $k = $this->_tbl_key;

        // Eğer hiçbir alan değişmemişse (ve yeni kayıt değilse) işlem yapma
        if (!$this->$k && empty($this->_dirty)) {
             return false; 
        }

        if ($this->$k) {
            $result = $this->db->updatePrepared(
                $this->_tbl,
                $this->_dirty,
                DbSqlHelper::identifier($this->_tbl_key) . ' = ?',
                [$this->$k]
            );
            if ($result) { $this->_dirty = []; }
            return $result;
        }
        $insertData = $this->buildInsertData();
        $id = $this->db->insertPrepared($this->_tbl, $insertData);
        if ($id !== false) {
            $this->$k = $id;
            $this->_dirty = [];
            return true;
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildInsertData(): array
    {
        $data = $this->_dirty;
        $k = $this->_tbl_key;
        if ($this->uuidPrimaryKey && empty($this->$k)) {
            $newId = IdHelper::generateUuidV4();
            $data[$k] = $newId;
            $this->$k = $newId;
        }

        return $data;
    }

    /**
     * bind ve store işlemlerini tek seferde yapar.
     */
    public function save($data) {
        $this->bind($data);
        return $this->store();
    }

    /**
     * Veritabanından belirli bir ID'ye göre veriyi çeker ve modele yükler.
     */
    public function load($id) {
        $res = $this->db->fetchObjectPrepared(
            "SELECT * FROM {$this->_tbl} WHERE {$this->_tbl_key} = ?",
            [$id]
        );

        if ($res) {
            $this->_dirty = [];
            $this->bind($res, false);
            return true;
        }
        return false;
    }

    /**
     * Mevcut kaydı siler.
     */
    public function delete($id = null) {
        $id = ($id !== null) ? $id : ($this->{$this->_tbl_key} ?? null);
        if ($id === null || $id === '' || $id === 0 || $id === '0') {
            return false;
        }

        if ($this->db->executePrepared(
            "DELETE FROM {$this->_tbl} WHERE {$this->_tbl_key} = ?",
            [$id]
        )) {
            $this->reset();
            return true;
        }
        return false;
    }

    /**
     * Nesne içindeki verileri sıfırlar.
     */
    public function reset() {
        foreach (get_object_vars($this) as $k => $v) {
            if ($k[0] != '_' && $k != 'db') {
                $this->$k = null;
            }
        }
        $this->_dirty = [];
    }
    
    /**
     * Modelin bir özelliğine değer atar.
     * Zincirleme kullanım için $this döner.
     */
    public function set($field, $value) {
        if (property_exists($this, $field)) {
            $this->$field = $value;
            // ID alanı değilse, bu alanın değiştiğini işaretle
            if ($field !== $this->_tbl_key) {
                $this->_dirty[$field] = $value;
            }
        }
        return $this;
    }
    
    public function get( $field ) {
        if(isset( $this->$field )) {
            return $this->$field;
        } else {
            return null;
        }
    }
}
<?php
namespace App\Controllers;

use App\Helpers\KurumAdresScope;
use App\Models\Address;

class AddressController {

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonOut(array $payload): void
    {
        if (ob_get_length()) {
            ob_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function getSubAddresses() {
        if (ob_get_length()) {
            ob_clean();
        }
        header('Content-Type: application/json');

        $parentId = isset($_GET['parent_id']) ? $_GET['parent_id'] : '';
        $type = isset($_GET['type']) ? $_GET['type'] : '';

        if (!$parentId || !$type) {
            echo json_encode([]);
            exit;
        }

        $addressModel = new Address();
        $data = $addressModel->getSubs($parentId, $type);
        if (!is_array($data)) {
            $data = [];
        }

        echo json_encode(array_values($data));
        exit;
    }

    private function assertAddressScopeForMutation(string $adresId): ?string
    {
        $kid = KurumAdresScope::effectiveKurumId();
        if ($kid === null || !KurumAdresScope::shouldFilter($kid)) {
            return null;
        }
        if (!KurumAdresScope::isAllowed($kid, $adresId)) {
            return 'Bu adres kurumunuzun yetkili coğrafi kapsamı dışındadır.';
        }

        return null;
    }

    /**
     * AJAX: hasta ilk kayıt / adres formundan yeni kapı no (oturum açmış kullanıcı).
     * POST: sokak_id, adi
     */
    public function saveKapino(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Oturum gerekli.']);
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Geçersiz istek.']);
        }

        $sokakId = isset($_POST['sokak_id']) ? trim((string) $_POST['sokak_id']) : '';
        $adi = isset($_POST['adi']) ? trim((string) $_POST['adi']) : '';

        if ($sokakId === '') {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Önce sokak seçiniz.']);
        }
        if ($adi === '') {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Kapı numarası boş olamaz.']);
        }

        $scopeErr = $this->assertAddressScopeForMutation($sokakId);
        if ($scopeErr !== null) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => $scopeErr]);
        }

        $model = new Address();
        if (!$model->adminValidateParentForChild('kapino', $sokakId)) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Geçersiz sokak seçimi.']);
        }

        $existingId = $model->findKapinoIdByAdiUnderSokak($sokakId, $adi);
        if ($existingId !== null) {
            $this->jsonOut([
                'durum' => 'tamam',
                'id' => $existingId,
                'mesaj' => 'Bu kapı numarası zaten kayıtlı; listeden seçildi.',
            ]);
        }

        $newId = Address::generateUuidV4();
        if (!$model->adminInsertRow($newId, $adi, $sokakId, 'kapino')) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Kapı numarası eklenemedi.']);
        }

        $out = ['durum' => 'tamam', 'id' => $newId, 'mesaj' => 'Kapı numarası eklendi.'];
        $geo = $model->geocodeKapinoById($newId, true);
        if (!empty($geo['coords'])) {
            $out['coords'] = $geo['coords'];
        }
        $this->jsonOut($out);
    }

    /**
     * AJAX: hasta düzenleme / adres formundan yeni sokak (oturum açmış kullanıcı).
     * POST: mahalle_id, adi
     */
    public function saveSokak(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Oturum gerekli.']);
        }
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Geçersiz istek.']);
        }

        $mahalleId = isset($_POST['mahalle_id']) ? trim((string) $_POST['mahalle_id']) : '';
        $adi = isset($_POST['adi']) ? trim((string) $_POST['adi']) : '';

        if ($mahalleId === '') {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Önce mahalle seçiniz.']);
        }
        if ($adi === '') {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Sokak adı boş olamaz.']);
        }

        $scopeErr = $this->assertAddressScopeForMutation($mahalleId);
        if ($scopeErr !== null) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => $scopeErr]);
        }

        $model = new Address();
        if (!$model->adminValidateParentForChild('sokak', $mahalleId)) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Geçersiz mahalle seçimi.']);
        }

        $existingId = $model->findSokakIdByAdiUnderMahalle($mahalleId, $adi);
        if ($existingId !== null) {
            $this->jsonOut([
                'durum' => 'tamam',
                'id' => $existingId,
                'mesaj' => 'Bu sokak zaten kayıtlı; listeden seçildi.',
            ]);
        }

        $newId = Address::generateUuidV4();
        if (!$model->adminInsertRow($newId, $adi, $mahalleId, 'sokak')) {
            $this->jsonOut(['durum' => 'hata', 'mesaj' => 'Sokak eklenemedi.']);
        }

        $this->jsonOut(['durum' => 'tamam', 'id' => $newId, 'mesaj' => 'Sokak eklendi.']);
    }
}

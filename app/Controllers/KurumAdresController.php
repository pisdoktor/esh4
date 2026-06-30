<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\ThemeViewHelper;
use App\Models\Address;
use App\Models\Kurum;
use App\Models\KurumAdres;

/**
 * Kurum ↔ ilçe/mahalle/sokak ataması — yalnızca süper yönetici.
 */
class KurumAdresController
{
    private static array $validTips = ['ilce', 'mahalle', 'sokak'];

    public function __construct()
    {
        AuthHelper::requireSuperAdmin();
    }

    /** @param array<string, mixed> $payload */
    private function jsonOut(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function index(): void
    {
        $kurumId = isset($_GET['kurum_id']) ? (int) $_GET['kurum_id'] : 0;
        $kurumlar = (new Kurum())->getList(true);
        if ($kurumId <= 0 && $kurumlar !== []) {
            $kurumId = (int) ($kurumlar[0]->id ?? 0);
        }

        $assignments = [];
        $selectedKurum = null;
        if ($kurumId > 0) {
            $kurum = new Kurum();
            if ($kurum->load($kurumId)) {
                $selectedKurum = $kurum;
                $assignments = (new KurumAdres())->getForKurum($kurumId);
            }
        }

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'kurum_adres/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function ajaxChildren(): void
    {
        $tip = isset($_GET['tip']) ? trim((string) $_GET['tip']) : 'ilce';
        if (!in_array($tip, self::$validTips, true)) {
            $tip = 'ilce';
        }
        $ustRaw = isset($_GET['ust_id']) ? (string) $_GET['ust_id'] : '0';
        $ustId = $tip === 'ilce' ? '0' : trim($ustRaw);

        $list = (new Address())->adminListByTipUst($tip, $ustId);
        $this->jsonOut(['ok' => true, 'items' => is_array($list) ? $list : []]);
    }

    public function ajaxAssign(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->jsonOut(['ok' => false, 'error' => 'Geçersiz istek.']);
        }
        $kurumId = isset($_POST['kurum_id']) ? (int) $_POST['kurum_id'] : 0;
        $adresId = isset($_POST['adres_id']) ? trim((string) $_POST['adres_id']) : '';

        if ($kurumId <= 0 || $adresId === '') {
            $this->jsonOut(['ok' => false, 'error' => 'Kurum ve adres seçimi zorunludur.']);
        }

        $kurum = new Kurum();
        if (!$kurum->load($kurumId)) {
            $this->jsonOut(['ok' => false, 'error' => 'Kurum bulunamadı.']);
        }

        $model = new KurumAdres();
        if (!$model->assign($kurumId, $adresId)) {
            $this->jsonOut(['ok' => false, 'error' => 'Atama yapılamadı. Geçerli bir ilçe, mahalle veya sokak seçin.']);
        }

        $assignments = $model->getForKurum($kurumId);
        ob_start();
        include ROOT_PATH . '/views/admin/kurum_adres/partials/assignment_rows.php';
        $html = ob_get_clean();

        $this->jsonOut(['ok' => true, 'html' => $html, 'mesaj' => 'Adres ataması kaydedildi.']);
    }

    public function ajaxUnassign(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->jsonOut(['ok' => false, 'error' => 'Geçersiz istek.']);
        }
        $kurumId = isset($_POST['kurum_id']) ? (int) $_POST['kurum_id'] : 0;
        $adresId = isset($_POST['adres_id']) ? trim((string) $_POST['adres_id']) : '';

        if ($kurumId <= 0 || $adresId === '') {
            $this->jsonOut(['ok' => false, 'error' => 'Kurum ve adres zorunludur.']);
        }

        $model = new KurumAdres();
        if (!$model->unassign($kurumId, $adresId)) {
            $this->jsonOut(['ok' => false, 'error' => 'Atama kaldırılamadı.']);
        }

        $assignments = $model->getForKurum($kurumId);
        ob_start();
        include ROOT_PATH . '/views/admin/kurum_adres/partials/assignment_rows.php';
        $html = ob_get_clean();

        $this->jsonOut(['ok' => true, 'html' => $html, 'mesaj' => 'Atama kaldırıldı.']);
    }
}

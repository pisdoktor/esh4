<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\FederationAdresBolgeSync;
use App\Helpers\FederationHelper;
use App\Helpers\ThemeViewHelper;
use App\Models\FederationRegion;

/**
 * Federasyon bölge yönetimi — süper yönetici.
 */
class FederationRegionController
{
    public function __construct()
    {
        AuthHelper::requirePlatformOwner();
        if (!FederationHelper::columnsReady()) {
            $_SESSION['error'] = 'Federasyon tabloları kurulu değil.';
            header('Location: ' . esh_url('Federation', 'index'));
            exit;
        }
    }

    public function index(): void
    {
        FederationAdresBolgeSync::syncMissingLinks();
        $items = (new FederationRegion())->getList(false, 'ad ASC');
        $pageTitle = 'Federasyon bölgeleri';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'federation_region/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function create(): void
    {
        $pageTitle = 'Yeni federasyon bölgesi';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'federation_region/create');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function edit(): void
    {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id < 1) {
            header('Location: ' . esh_url('FederationRegion', 'index'));
            exit;
        }
        $bolge = new FederationRegion();
        if (!$bolge->load($id)) {
            $_SESSION['error'] = 'Bölge bulunamadı.';
            header('Location: ' . esh_url('FederationRegion', 'index'));
            exit;
        }
        $pageTitle = 'Bölge düzenle';
        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'federation_region/edit');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function store(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . esh_url('FederationRegion', 'index'));
            exit;
        }

        $id = !empty($_POST['id']) ? (int) $_POST['id'] : 0;
        $model = new FederationRegion();
        if ($id > 0 && !$model->load($id)) {
            $_SESSION['error'] = 'Bölge bulunamadı.';
            header('Location: ' . esh_url('FederationRegion', 'index'));
            exit;
        }

        $ad = trim((string) ($_POST['ad'] ?? ''));
        $kod = FederationRegion::normalizeKod((string) ($_POST['kod'] ?? ''));
        if ($ad === '') {
            $_SESSION['error'] = 'Bölge adı zorunludur.';
            header('Location: ' . esh_url($id > 0 ? 'FederationRegion' : 'FederationRegion', $id > 0 ? 'edit' : 'create', $id > 0 ? ['id' => $id] : []));
            exit;
        }
        if (!$model->kodUnique($kod, $id > 0 ? $id : null)) {
            $_SESSION['error'] = 'Bu bölge kodu zaten kullanılıyor.';
            header('Location: ' . esh_url($id > 0 ? 'FederationRegion' : 'FederationRegion', $id > 0 ? 'edit' : 'create', $id > 0 ? ['id' => $id] : []));
            exit;
        }

        $model->bind([
            'ad' => $ad,
            'kod' => $kod,
            'il_adi' => trim((string) ($_POST['il_adi'] ?? '')) ?: null,
            'hub_node_ref' => FederationHelper::normalizeRef((string) ($_POST['hub_node_ref'] ?? '')),
            'aktif' => isset($_POST['aktif']) ? 1 : 0,
            'aciklama' => trim((string) ($_POST['aciklama'] ?? '')) ?: null,
        ], true);

        if ($model->store()) {
            FederationAdresBolgeSync::syncFromRegion($model);
            $_SESSION['success'] = 'Bölge kaydedildi; adres ağacına eklendi veya güncellendi.';
        } else {
            $_SESSION['error'] = 'Kayıt sırasında hata oluştu.';
        }

        header('Location: ' . esh_url('FederationRegion', 'index'));
        exit;
    }

    public function delete(): void
    {
        \App\Helpers\CsrfHelper::requirePostMethod(esh_url('FederationRegion', 'index'));
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 1) {
            $_SESSION['error'] = 'Varsayılan bölge silinemez.';
            header('Location: ' . esh_url('FederationRegion', 'index'));
            exit;
        }
        $model = new FederationRegion();
        if ($model->load($id) && $model->delete($id)) {
            $warn = FederationAdresBolgeSync::onFederationRegionDeleted($id);
            $_SESSION['success'] = $warn !== null
                ? 'Bölge silindi. ' . $warn
                : 'Bölge silindi; adres ağacı kaydı da kaldırıldı.';
        } else {
            $_SESSION['error'] = 'Bölge silinemedi.';
        }
        header('Location: ' . esh_url('FederationRegion', 'index'));
        exit;
    }
}

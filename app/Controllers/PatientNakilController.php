<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\IdHelper;
use App\Helpers\AuthHelper;
use App\Helpers\PatientNakilRequest;
use App\Helpers\TenantContext;
use App\Helpers\ThemeViewHelper;
use App\Models\HastaNakil;
use App\Models\Kurum;

class PatientNakilController
{
    public function incoming(): void
    {
        AuthHelper::requireAdmin();
        if (!PatientNakilRequest::tableReady()) {
            $_SESSION['error'] = 'Nakil modülü kurulmamış.';
            header('Location: ' . esh_url('Dashboard', 'index'));
            exit;
        }

        $kurumId = TenantContext::sessionKurumId();
        if (!AuthHelper::sessionIsSuperAdmin() && ($kurumId === null || $kurumId <= 0)) {
            $_SESSION['error'] = 'Kurum bağlamı bulunamadı.';
            header('Location: ' . esh_url('Dashboard', 'index'));
            exit;
        }

        $incomingRowsFetchUrl = esh_url('PatientNakil', 'incomingRows');

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'hasta/nakil_incoming');
        include ThemeViewHelper::resolvePartial('footer');
    }

    /**
     * Gelen nakil talepleri tablo satırları (JSON HTML parçası).
     */
    public function incomingRows(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        AuthHelper::requireAdminJson();
        if (!PatientNakilRequest::tableReady()) {
            http_response_code(503);
            echo json_encode(['ok' => false, 'error' => 'Nakil modülü kurulmamış.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $kurumId = TenantContext::sessionKurumId();
        if (!AuthHelper::sessionIsSuperAdmin() && ($kurumId === null || $kurumId <= 0)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Kurum bağlamı bulunamadı.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $talepler = PatientNakilRequest::getIncomingList($kurumId);
        $kurumlar = [];
        if (Kurum::tableExists()) {
            foreach (TenantContext::kurumListForScope(true) as $k) {
                $kid = (int) ($k->id ?? 0);
                if ($kid > 0) {
                    $kurumlar[$kid] = (string) ($k->ad ?? '');
                }
            }
        }

        ob_start();
        include ROOT_PATH . '/views/site/hasta/partials/nakil_incoming_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function review(): void
    {
        AuthHelper::requireAdmin();
        if (!PatientNakilRequest::tableReady()) {
            $_SESSION['error'] = 'Nakil modülü kurulmamış.';
            header('Location: ' . esh_url('Dashboard', 'index'));
            exit;
        }
        if (!AuthHelper::sessionIsSuperAdmin()) {
            $_SESSION['error'] = 'İl dışı nakil inceleme yalnızca '
                . mb_strtolower(AuthHelper::adminLevelLabel(AuthHelper::ROLE_SUPERADMIN), 'UTF-8')
                . ' tarafından yapılabilir.';
            header('Location: ' . esh_url('PatientNakil', 'incoming'));
            exit;
        }

        $id = IdHelper::normalizeRequestId($_GET['id'] ?? null);
        $userId = AuthHelper::sessionUserId();
        if (IdHelper::isEmptyEntityId($id) || IdHelper::isEmptyEntityId($userId)) {
            $_SESSION['error'] = 'Geçersiz nakil kaydı.';
            header('Location: ' . esh_url('PatientNakil', 'incoming'));
            exit;
        }

        if (!PatientNakilRequest::canManageIncomingNakil($id, $userId, 'approve')) {
            $_SESSION['error'] = 'Bu nakil talebine erişim yetkiniz yok.';
            header('Location: ' . esh_url('PatientNakil', 'incoming'));
            exit;
        }

        $nakilRow = (new HastaNakil())->fetchReviewRowById($id);
        if ($nakilRow === null
            || (string) ($nakilRow->tip ?? '') !== HastaNakil::TIP_IL_DISI
            || (string) ($nakilRow->durum ?? '') !== HastaNakil::DURUM_BEKLEMEDE) {
            $_SESSION['error'] = 'İncelenecek il dışı nakil talebi bulunamadı.';
            header('Location: ' . esh_url('PatientNakil', 'incoming'));
            exit;
        }

        $hedefBolgeId = (int) ($nakilRow->hedef_bolge_id ?? 0);
        $eshReviewKurumlar = PatientNakilRequest::kurumListForIlDisiBolge($hedefBolgeId);
        $eshReviewKurumlar = array_values(array_filter(
            $eshReviewKurumlar,
            static fn (object $k): bool => TenantContext::isKurumInScope((int) ($k->id ?? 0))
        ));

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('site', 'hasta/nakil_review');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function approveIlDisi(): void
    {
        AuthHelper::requireAdmin();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . esh_url('PatientNakil', 'incoming'));
            exit;
        }

        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $hedefKurumId = isset($_POST['hedef_kurum_id']) ? (int) $_POST['hedef_kurum_id'] : 0;
        $userId = AuthHelper::sessionUserId();
        if (IdHelper::isEmptyEntityId($id) || $hedefKurumId < 1 || IdHelper::isEmptyEntityId($userId)) {
            $_SESSION['error'] = 'Geçersiz istek.';
            header('Location: ' . esh_url('PatientNakil', 'incoming'));
            exit;
        }

        $hedefHastaId = PatientNakilRequest::approveIlDisiNakil($id, $hedefKurumId, $userId);
        if ($hedefHastaId === false) {
            $_SESSION['error'] = 'İl dışı nakil talebi onaylanamadı.';
            header('Location: ' . esh_url('PatientNakil', 'review', ['id' => $id]));
            exit;
        }

        $_SESSION['success'] = 'İl dışı nakil onaylandı. Hasta seçilen kurumda bekleyen (ön kayıt) olarak açıldı.';
        header('Location: ' . esh_url('Patient', 'bedit', ['id' => $hedefHastaId]));
        exit;
    }

    public function approve(): void
    {
        AuthHelper::requireAdmin();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . esh_url('PatientNakil', 'incoming'));
            exit;
        }

        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $userId = AuthHelper::sessionUserId();
        if (IdHelper::isEmptyEntityId($id) || IdHelper::isEmptyEntityId($userId)) {
            $_SESSION['error'] = 'Geçersiz istek.';
            header('Location: ' . esh_url('PatientNakil', 'incoming'));
            exit;
        }

        $nakilModel = new HastaNakil();
        $isGeriNakil = $nakilModel->loadPendingById($id)
            && (string) ($nakilModel->tip ?? '') === HastaNakil::TIP_GERI_NAKIL;

        $hedefHastaId = PatientNakilRequest::approve($id, $userId);
        if ($hedefHastaId === false) {
            $_SESSION['error'] = 'Nakil talebi onaylanamadı.';
            header('Location: ' . esh_url('PatientNakil', 'incoming'));
            exit;
        }

        if ($isGeriNakil) {
            $_SESSION['success'] = 'Geri nakil onaylandı. Hasta önceki kurumda bekleyen (ön kayıt) olarak açıldı.';
            header('Location: ' . esh_url('Patient', 'bedit', ['id' => $hedefHastaId]));
        } else {
            $_SESSION['success'] = 'Nakil onaylandı. Hasta kurumunuzda bekleyen (ön kayıt) olarak açıldı.';
            header('Location: ' . esh_url('Patient', 'bedit', ['id' => $hedefHastaId]));
        }
        exit;
    }

    public function reject(): void
    {
        AuthHelper::requireAdmin();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . esh_url('PatientNakil', 'incoming'));
            exit;
        }

        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $userId = AuthHelper::sessionUserId();
        $redNedeni = isset($_POST['red_nedeni']) ? trim((string) $_POST['red_nedeni']) : '';
        $redirect = isset($_POST['redirect']) ? trim((string) $_POST['redirect']) : esh_url('PatientNakil', 'incoming');
        if (IdHelper::isEmptyEntityId($id) || IdHelper::isEmptyEntityId($userId)) {
            $_SESSION['error'] = 'Geçersiz istek.';
            header('Location: ' . $redirect);
            exit;
        }

        if (!PatientNakilRequest::reject($id, $userId, $redNedeni !== '' ? $redNedeni : null)) {
            $_SESSION['error'] = 'Nakil talebi reddedilemedi.';
            header('Location: ' . $redirect);
            exit;
        }

        $_SESSION['success'] = 'Nakil talebi reddedildi.';
        header('Location: ' . $redirect);
        exit;
    }

    public function cancel(): void
    {
        AuthHelper::requireAdmin();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'passive']));
            exit;
        }

        $id = IdHelper::normalizeRequestId($_POST['id'] ?? null);
        $userId = AuthHelper::sessionUserId();
        $redirect = isset($_POST['redirect']) ? trim((string) $_POST['redirect']) : esh_url('Patient', 'unified', ['status' => 'passive']);
        if (IdHelper::isEmptyEntityId($id) || IdHelper::isEmptyEntityId($userId)) {
            $_SESSION['error'] = 'Geçersiz istek.';
            header('Location: ' . $redirect);
            exit;
        }

        if (!PatientNakilRequest::cancel($id, $userId)) {
            $_SESSION['error'] = 'Nakil talebi iptal edilemedi.';
            header('Location: ' . $redirect);
            exit;
        }

        $_SESSION['success'] = 'Bekleyen nakil talebi iptal edildi.';
        header('Location: ' . $redirect);
        exit;
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

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
        if (AuthHelper::sessionIsSuperAdmin() && Kurum::tableExists()) {
            foreach (\App\Helpers\TenantContext::kurumListForScope(true) as $k) {
                $kurumlar[(int) ($k->id ?? 0)] = (string) ($k->ad ?? '');
            }
        }

        ob_start();
        include ROOT_PATH . '/views/site/hasta/partials/nakil_incoming_table_rows.php';
        $html = ob_get_clean();

        echo json_encode(['ok' => true, 'html' => $html], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function approve(): void
    {
        AuthHelper::requireAdmin();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . esh_url('PatientNakil', 'incoming'));
            exit;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        if ($id < 1 || $userId < 1) {
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

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $redNedeni = isset($_POST['red_nedeni']) ? trim((string) $_POST['red_nedeni']) : '';
        if ($id < 1 || $userId < 1) {
            $_SESSION['error'] = 'Geçersiz istek.';
            header('Location: ' . esh_url('PatientNakil', 'incoming'));
            exit;
        }

        if (!PatientNakilRequest::reject($id, $userId, $redNedeni !== '' ? $redNedeni : null)) {
            $_SESSION['error'] = 'Nakil talebi reddedilemedi.';
            header('Location: ' . esh_url('PatientNakil', 'incoming'));
            exit;
        }

        $_SESSION['success'] = 'Nakil talebi reddedildi.';
        header('Location: ' . esh_url('PatientNakil', 'incoming'));
        exit;
    }

    public function cancel(): void
    {
        AuthHelper::requireAdmin();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            header('Location: ' . esh_url('Patient', 'unified', ['status' => 'passive']));
            exit;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $redirect = isset($_POST['redirect']) ? trim((string) $_POST['redirect']) : esh_url('Patient', 'unified', ['status' => 'passive']);
        if ($id < 1 || $userId < 1) {
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

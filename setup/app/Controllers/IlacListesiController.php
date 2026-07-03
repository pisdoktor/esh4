<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\IlacListesiBuilder;
use App\Helpers\ThemeViewHelper;
use RuntimeException;
use Throwable;

/**
 * TİTCK E-Reçete İlaç Listesi (.xlsx) → ilac-listesi.json (yalnızca süper yönetici).
 */
class IlacListesiController
{
    private const MAX_UPLOAD_BYTES = 25 * 1024 * 1024;

    public function __construct()
    {
        AuthHelper::requireSuperAdmin();
    }

    public function index(): void
    {
        $jsonStats = IlacListesiBuilder::jsonStats();
        $titckUrl = IlacListesiBuilder::TITCK_MODULE_43_URL;
        $zipArchiveAvailable = IlacListesiBuilder::hasZipArchive();
        $intlCollatorAvailable = IlacListesiBuilder::hasIntlCollator();
        $pageTitle = 'İlaç listesi (TİTCK)';

        include ThemeViewHelper::resolvePartial('header');
        include ThemeViewHelper::resolveAreaView('admin', 'ilac_listesi/index');
        include ThemeViewHelper::resolvePartial('footer');
    }

    public function upload(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . esh_url('IlacListesi', 'index'));
            exit;
        }

        if (!isset($_FILES['xlsx']) || !is_array($_FILES['xlsx'])) {
            $_SESSION['error'] = 'Excel dosyası seçilmedi.';
            header('Location: ' . esh_url('IlacListesi', 'index'));
            exit;
        }

        $file = $_FILES['xlsx'];
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = $this->uploadErrorMessage($errorCode);
            header('Location: ' . esh_url('IlacListesi', 'index'));
            exit;
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > self::MAX_UPLOAD_BYTES) {
            $_SESSION['error'] = 'Dosya boyutu geçersiz (en fazla '
                . (int) (self::MAX_UPLOAD_BYTES / 1024 / 1024) . ' MB).';
            header('Location: ' . esh_url('IlacListesi', 'index'));
            exit;
        }

        $originalName = (string) ($file['name'] ?? '');
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext !== 'xlsx') {
            $_SESSION['error'] = 'Yalnızca .xlsx dosyaları kabul edilir.';
            header('Location: ' . esh_url('IlacListesi', 'index'));
            exit;
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            $_SESSION['error'] = 'Geçersiz yükleme.';
            header('Location: ' . esh_url('IlacListesi', 'index'));
            exit;
        }

        $includePasif = !empty($_POST['include_pasif']);
        $tempPath = '';

        try {
            $tempPath = $this->storeUploadTemp($tmp);
            $result = IlacListesiBuilder::buildFromXlsx($tempPath, $includePasif);
            $stats = IlacListesiBuilder::jsonStats();
            $when = $stats['mtimeFormatted'] ?? date('Y-m-d H:i:s');
            $msg = 'İlaç listesi güncellendi: ' . $result['count'] . ' benzersiz ad ('
                . $when . ').';
            if ($result['warnings'] !== []) {
                $msg .= ' Uyarı: ' . implode(' ', $result['warnings']);
            }
            if (!empty($result['layoutDebug'])) {
                $msg .= ' [' . $result['layoutDebug'] . ']';
            }
            $_SESSION['success'] = $msg;
        } catch (Throwable $e) {
            $_SESSION['error'] = 'Liste üretilemedi. Lütfen tekrar deneyin veya sistem yöneticisine başvurun.';
            error_log('IlacListesi generate failed: ' . $e->getMessage());
        } finally {
            if ($tempPath !== '' && is_file($tempPath)) {
                @unlink($tempPath);
            }
        }

        header('Location: ' . esh_url('IlacListesi', 'index'));
        exit;
    }

    private function storeUploadTemp(string $uploadedTmp): string
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'esh-ilac-listesi';
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException('Geçici dizin oluşturulamadı.');
        }

        $dest = $dir . DIRECTORY_SEPARATOR . 'upload_' . bin2hex(random_bytes(8)) . '.xlsx';
        if (!move_uploaded_file($uploadedTmp, $dest)) {
            throw new RuntimeException('Dosya geçici dizine taşınamadı.');
        }

        return $dest;
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Dosya sunucu limitini aşıyor.',
            UPLOAD_ERR_PARTIAL => 'Dosya yalnızca kısmen yüklendi.',
            UPLOAD_ERR_NO_FILE => 'Dosya seçilmedi.',
            default => 'Dosya yüklenemedi (kod: ' . $code . ').',
        };
    }
}

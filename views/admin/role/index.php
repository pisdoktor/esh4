<?php

declare(strict_types=1);

use App\Helpers\PageShellHelper;

/** @var string $pageTitle */
/** @var string $indexRowsFetchUrl */

PageShellHelper::pageOpen(['kind' => 'list', 'module' => 'role']);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="h4 mb-1"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="text-muted small mb-0">Platform geneli personel rolleri ve modül CRUD izinleri.</p>
    </div>
    <a href="<?= htmlspecialchars(esh_url('Role', 'create'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-plus me-1"></i> Yeni rol
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Rol</th>
                    <th>Slug</th>
                    <th>Ünvan bağlantısı</th>
                    <th>Açıklama</th>
                    <th class="text-center">Sistem</th>
                    <th class="text-end">İşlem</th>
                </tr>
            </thead>
            <tbody id="esh-role-list-tbody"
                   data-esh-fetch-url="<?= htmlspecialchars($indexRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <tr class="esh-role-list-loading-row">
                    <td colspan="6" class="text-muted text-center py-4 border-0">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        Liste yükleniyor…
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php PageShellHelper::pageClose(); ?>

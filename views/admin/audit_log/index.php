<?php
declare(strict_types=1);

use App\Helpers\AuditLogHelper;
use App\Helpers\AuthHelper;
use App\Helpers\DateHelper;
use App\Helpers\FormHelper;

/** @var array<string, mixed> $filters */
$filters = $filters ?? [];
/** @var list<object> $items */
$items = $items ?? [];
$tableReady = (bool) ($tableReady ?? false);
$total = (int) ($total ?? 0);
$paginationInfo = (string) ($paginationInfo ?? '');
$paginationNav = (string) ($paginationNav ?? '');
$paginationLimit = (string) ($paginationLimit ?? '');
/** @var list<array{value:string,label:string}> $kurumOptions */
$kurumOptions = $kurumOptions ?? [];
/** @var list<array{value:string,label:string}> $actionOptions */
$actionOptions = $actionOptions ?? [];
/** @var list<array{value:string,label:string}> $entityTypeOptions */
$entityTypeOptions = $entityTypeOptions ?? [];

$dateFrom = (string) ($filters['date_from'] ?? '');
$dateTo = (string) ($filters['date_to'] ?? '');
$exportCsvUrl = \App\Helpers\UrlHelper::fromRequestParams(array_merge(
    ['controller' => 'AuditLog', 'action' => 'exportCsv'],
    array_filter($filters, static fn ($v) => $v !== '' && $v !== 0 && $v !== null)
));
?>
<div class="esh-page esh-page--list esh-page-auditlog container-fluid py-4">
    <?php if (!$tableReady): ?>
        <div class="alert alert-warning" role="alert">
            İşlem günlüğü tablosu henüz kurulmamış. Yönetici olarak
            <code>database/migrate_esh_audit_log.sql</code> dosyasını çalıştırın.
        </div>
    <?php endif; ?>

    <header class="esh-page__header mb-4">
        <h1 class="esh-page__heading h4 mb-1">
            <i class="fa-solid fa-clipboard-list me-2" aria-hidden="true"></i>
            İşlem günlüğü (denetim)
        </h1>
        <p class="esh-page__lead small text-muted mb-0">
            Hasta görüntüleme, izlem, dışa aktarma ve oturum işlemleri — KVKK / iç denetim kaydı.
        </p>
        <?php if ($tableReady): ?>
        <div class="mt-2 d-flex flex-wrap gap-2">
            <a href="<?= htmlspecialchars($exportCsvUrl, ENT_QUOTES, 'UTF-8') ?>"
               class="btn btn-sm btn-outline-primary rounded-pill">
                <i class="fa-solid fa-file-csv me-1"></i>CSV dışa aktar
            </a>
            <?php if (AuthHelper::sessionIsPlatformOwner()): ?>
            <form method="post" action="<?= htmlspecialchars(esh_url('AuditLog', 'purgeRetention'), ENT_QUOTES, 'UTF-8') ?>"
                  class="d-inline"
                  onsubmit="return confirm('Saklama süresini aşan tüm denetim kayıtları silinecek. Devam?');">
                <?= \App\Helpers\CsrfHelper::hiddenField() ?>
                <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">
                    <i class="fa-solid fa-broom me-1"></i>Eski kayıtları temizle
                </button>
            </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </header>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <form method="get" action="<?= htmlspecialchars(esh_url('AuditLog', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="row g-2 align-items-end">
                <?= esh_form_route_hiddens('AuditLog', 'index') ?>
                <?php if (AuthHelper::sessionIsSuperAdmin() && $kurumOptions !== []): ?>
                <div class="col-md-3">
                    <?= FormHelper::fieldSelect(
                        'kurum_id',
                        'Kurum',
                        $kurumOptions,
                        (string) ((int) ($filters['kurum_id'] ?? 0) ?: ''),
                        [
                            'labelClass' => 'form-label small text-muted mb-1',
                            'class' => 'form-select-sm',
                            'tomSelect' => false,
                        ]
                    ) ?>
                </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <?= FormHelper::fieldSelect(
                        'action',
                        'İşlem',
                        $actionOptions,
                        (string) ($filters['action'] ?? ''),
                        [
                            'labelClass' => 'form-label small text-muted mb-1',
                            'class' => 'form-select-sm',
                            'tomSelect' => false,
                        ]
                    ) ?>
                </div>
                <div class="col-md-2">
                    <?= FormHelper::fieldSelect(
                        'entity_type',
                        'Varlık',
                        $entityTypeOptions,
                        (string) ($filters['entity_type'] ?? ''),
                        [
                            'labelClass' => 'form-label small text-muted mb-1',
                            'class' => 'form-select-sm',
                            'tomSelect' => false,
                        ]
                    ) ?>
                </div>
                <div class="col-md-2">
                    <?= FormHelper::fieldInput('entity_ref', 'TC / referans', (string) ($filters['entity_ref'] ?? ''), [
                        'labelClass' => 'form-label small text-muted mb-1',
                        'class' => 'form-control-sm',
                        'placeholder' => '11 haneli TC',
                        'maxlength' => 11,
                    ]) ?>
                </div>
                <div class="col-md-2">
                    <?= FormHelper::fieldDateRangeFilter('date_from', 'date_to', $dateFrom, $dateTo, [
                        'labelClass' => 'form-label small text-muted mb-1',
                        'class' => 'form-control-sm',
                    ]) ?>
                </div>
                <div class="col-md-4">
                    <?= FormHelper::fieldInput('q', 'Serbest arama', (string) ($filters['q'] ?? ''), [
                        'labelClass' => 'form-label small text-muted mb-1',
                        'class' => 'form-control-sm',
                        'placeholder' => 'İşlem, URI, referans…',
                    ]) ?>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fa-solid fa-filter me-1"></i>Filtrele
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-2 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
            <span class="small text-muted"><?= $paginationInfo ?></span>
            <div><?= $paginationLimit ?></div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Tarih</th>
                        <th scope="col">Kullanıcı</th>
                        <?php if (AuthHelper::sessionIsSuperAdmin()): ?>
                        <th scope="col">Kurum</th>
                        <?php endif; ?>
                        <th scope="col">İşlem</th>
                        <th scope="col">Varlık</th>
                        <th scope="col">Referans</th>
                        <th scope="col">IP</th>
                        <th scope="col">Detay</th>
                    </tr>
                </thead>
                <tbody>
                    <?php include __DIR__ . '/partials/index_table_rows.php'; ?>
                </tbody>
            </table>
        </div>
        <?php if ($total > 0): ?>
        <div class="card-footer bg-white border-top py-2">
            <?= $paginationNav ?>
        </div>
        <?php endif; ?>
    </div>
</div>

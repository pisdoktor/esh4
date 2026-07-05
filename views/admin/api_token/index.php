<?php
declare(strict_types=1);

use App\Helpers\CsrfHelper;
use App\Helpers\FormHelper;
use App\Services\Api\ApiTokenService;

/** @var list<object> $tokens */
$tokens = $tokens ?? [];
$newTokenPlain = isset($newTokenPlain) ? (string) $newTokenPlain : '';
$tableReady = ApiTokenService::tableReady();
$scopeOptions = [
    FormHelper::makeOption('read', 'Tümü (read)'),
    FormHelper::makeOption('write', 'Tüm kaynaklar (write)'),
    FormHelper::makeOption('patients', 'Yalnızca hastalar'),
    FormHelper::makeOption('visits', 'Yalnızca izlemler'),
    FormHelper::makeOption('plans', 'Yalnızca planlı izlemler'),
    FormHelper::makeOption('visits:write', 'İzlem yazma (checkin/PATCH)'),
    FormHelper::makeOption('patients,visits,plans', 'Okuma — hasta+izlem+plan'),
];
?>
<div class="esh-page container-fluid py-4">
    <?php if (!$tableReady): ?>
        <div class="alert alert-warning">
            API token tablosu kurulu değil. <code>database/migrate_esh_api_tokens.sql</code> dosyasını çalıştırın.
        </div>
    <?php endif; ?>

    <header class="mb-4">
        <h1 class="h4 mb-1"><i class="fa-solid fa-key me-2"></i>REST API tokenları</h1>
        <p class="small text-muted mb-0">
            Harici sistemler için Bearer token. Uçlar: <code>/public/api/v1/patients</code>,
            <code>visits</code>, <code>plans</code> — modül açık olmalıdır.
        </p>
    </header>

    <?php if ($newTokenPlain !== ''): ?>
    <div class="alert alert-success border-success">
        <strong>Yeni token (bir kez gösterilir):</strong>
        <div class="font-monospace small mt-2 user-select-all"><?= htmlspecialchars($newTokenPlain, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Yeni token</div>
                <div class="card-body">
                    <form method="post" action="<?= htmlspecialchars(esh_url('ApiToken', 'store'), ENT_QUOTES, 'UTF-8') ?>">
                        <?= CsrfHelper::hiddenField() ?>
                        <?= FormHelper::fieldInput('user_id', 'Kullanıcı ID', '', [
                            'type' => 'number',
                            'required' => true,
                            'col' => '',
                            'labelClass' => 'form-label small fw-semibold',
                            'class' => 'form-control-sm',
                            'helpText' => 'Token bu kullanıcının kurum kapsamını devralır.',
                        ]) ?>
                        <?= FormHelper::fieldInput('label', 'Etiket', '', [
                            'col' => '',
                            'labelClass' => 'form-label small fw-semibold',
                            'class' => 'form-control-sm',
                            'placeholder' => 'Örn. BI rapor köprüsü',
                        ]) ?>
                        <?= FormHelper::fieldSelect('scopes', 'Kapsam', $scopeOptions, 'read', [
                            'col' => '',
                            'labelClass' => 'form-label small fw-semibold',
                            'class' => 'form-select-sm',
                            'tomSelect' => false,
                        ]) ?>
                        <?= FormHelper::fieldInput('expires_at', 'Bitiş tarihi (isteğe bağlı)', '', [
                            'type' => 'date',
                            'col' => '',
                            'labelClass' => 'form-label small fw-semibold',
                            'class' => 'form-control-sm',
                        ]) ?>
                        <button type="submit" class="btn btn-primary btn-sm mt-2" <?= $tableReady ? '' : 'disabled' ?>>
                            Token oluştur
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Aktif tokenlar</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Etiket</th>
                                <th>Kullanıcı</th>
                                <th>Kapsam</th>
                                <th>Son kullanım</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($tokens === []): ?>
                            <tr><td colspan="5" class="text-muted text-center py-3">Kayıt yok.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($tokens as $t): ?>
                            <tr>
                                <td class="small"><?= htmlspecialchars((string) ($t->label ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small">
                                    <?= htmlspecialchars((string) ($t->user_name ?? $t->user_username ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    <span class="text-muted font-monospace small"><?= htmlspecialchars(substr((string) ($t->user_id ?? ''), 0, 8), ENT_QUOTES, 'UTF-8') ?>…</span>
                                </td>
                                <td class="small font-monospace"><?= htmlspecialchars((string) ($t->scopes ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="small text-muted"><?= htmlspecialchars((string) ($t->last_used_at ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-end">
                                    <form method="post" action="<?= htmlspecialchars(esh_url('ApiToken', 'revoke'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline" data-esh-confirm="Token iptal edilsin mi?">
                                        <?= CsrfHelper::hiddenField() ?>
                                        <input type="hidden" name="id" value="<?= (int) ($t->id ?? 0) ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">İptal</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

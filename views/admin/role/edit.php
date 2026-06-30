<?php

declare(strict_types=1);

use App\Helpers\CsrfHelper;
use App\Helpers\FormHelper;
use App\Helpers\PageShellHelper;
use App\Models\User;
use App\Services\PermissionService;

/** @var object|null $role */
/** @var array<string, list<array{id:int, module_key:string, crud:string, slug:string, label:string}>> $permissionGroups */
/** @var array<int, true> $selectedPermissionIds */
/** @var string $pageTitle */

$roleId = isset($role) && isset($role->id) ? (int) $role->id : 0;
$isSystem = isset($role) && (int) ($role->is_system ?? 0) === 1;
$crudLabels = [
    'read' => 'Okuma',
    'create' => 'Oluşturma',
    'update' => 'Güncelleme',
    'delete' => 'Silme',
    'export' => 'Dışa aktarma',
    'admin' => 'Yönetici',
    'superadmin' => 'Süper yönetici',
];

PageShellHelper::pageOpen(['kind' => 'form', 'module' => 'role']);
?>
<div class="mb-4">
    <h1 class="h4 mb-1"><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
    <a href="<?= htmlspecialchars(esh_url('Role', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="small text-decoration-none">&larr; Rol listesi</a>
</div>

<form method="post" action="<?= htmlspecialchars(esh_url('Role', 'store'), ENT_QUOTES, 'UTF-8') ?>" class="card border-0 shadow-sm">
    <div class="card-body">
        <?= CsrfHelper::hiddenField() ?>
        <?php if ($roleId > 0): ?>
            <input type="hidden" name="id" value="<?= $roleId ?>">
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <?= FormHelper::fieldInput('name', 'Rol adı', (isset($role) && isset($role->name)) ? (string) $role->name : '', ['required' => true]) ?>
            </div>
            <div class="col-md-4">
                <?= FormHelper::fieldInput('slug', 'Slug', (isset($role) && isset($role->slug)) ? (string) $role->slug : '', [
                    'extraAttrs' => $isSystem ? ['readonly' => 'readonly'] : [],
                    'placeholder' => 'hemsire, salt_okuma',
                ]) ?>
            </div>
            <div class="col-md-4">
                <?= FormHelper::fieldInput('sort_order', 'Sıra', (isset($role) && isset($role->sort_order)) ? (string) $role->sort_order : '100', ['type' => 'number']) ?>
            </div>
            <?php if (PermissionService::hasUnvanLinkColumn()): ?>
            <div class="col-md-4">
                <?php
                $eshRoleUnvanOptions = [FormHelper::makeOption('', 'Yok (manuel atama)')];
                foreach (User::unvanChoices() as $val => $label) {
                    if ($val === '') {
                        continue;
                    }
                    $eshRoleUnvanOptions[] = FormHelper::makeOption((string) $val, $label);
                }
                echo FormHelper::fieldSelect(
                    'unvan_code',
                    'Bağlı ünvan',
                    $eshRoleUnvanOptions,
                    (isset($role) && isset($role->unvan_code)) ? (string) $role->unvan_code : '',
                    ['tomSelect' => false]
                );
                ?>
                <div class="form-text">Kullanıcı kaydında bu ünvan seçildiğinde rol otomatik atanır.</div>
            </div>
            <?php endif; ?>
            <div class="col-12">
                <?= FormHelper::textarea('description', 'Açıklama', (isset($role) && isset($role->description)) ? (string) ($role->description ?? '') : '', ['rows' => 2]) ?>
            </div>
        </div>

        <h2 class="h6 mb-2">Modül izinleri</h2>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <span class="small text-muted">Hızlı seçim:</span>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-esh-role-perm-action="all-on">Tümünü seç</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-esh-role-perm-action="all-off">Tümünü kaldır</button>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle esh-role-perm-table">
                <thead class="table-light">
                    <tr>
                        <th>Modül</th>
                        <?php foreach ($crudLabels as $crudKey => $crudLabel): ?>
                            <th class="text-center small">
                                <div><?= htmlspecialchars($crudLabel, ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="mt-1 d-flex justify-content-center gap-1 flex-nowrap">
                                    <button type="button"
                                            class="btn btn-link btn-sm p-0 text-decoration-none"
                                            data-esh-role-perm-col="<?= htmlspecialchars($crudKey, ENT_QUOTES, 'UTF-8') ?>"
                                            data-checked="1"
                                            title="<?= htmlspecialchars($crudLabel . ' — tümünü seç', ENT_QUOTES, 'UTF-8') ?>">Seç</button>
                                    <span class="text-muted" aria-hidden="true">·</span>
                                    <button type="button"
                                            class="btn btn-link btn-sm p-0 text-decoration-none text-muted"
                                            data-esh-role-perm-col="<?= htmlspecialchars($crudKey, ENT_QUOTES, 'UTF-8') ?>"
                                            data-checked="0"
                                            title="<?= htmlspecialchars($crudLabel . ' — tümünü kaldır', ENT_QUOTES, 'UTF-8') ?>">Kaldır</button>
                                </div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($permissionGroups as $moduleKey => $perms): ?>
                        <?php
                        $byCrud = [];
                        foreach ($perms as $perm) {
                            $byCrud[$perm['crud']] = $perm;
                        }
                        ?>
                        <tr>
                            <td class="fw-semibold small">
                                <div title="<?= htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(PermissionService::moduleLabel($moduleKey), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="mt-1">
                                    <button type="button"
                                            class="btn btn-link btn-sm p-0 text-decoration-none"
                                            data-esh-role-perm-row="<?= htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8') ?>"
                                            data-checked="1"
                                            title="Bu modülün tüm izinlerini seç">Tümü</button>
                                    <span class="text-muted" aria-hidden="true">·</span>
                                    <button type="button"
                                            class="btn btn-link btn-sm p-0 text-decoration-none text-muted"
                                            data-esh-role-perm-row="<?= htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8') ?>"
                                            data-checked="0"
                                            title="Bu modülün tüm izinlerini kaldır">Hiçbiri</button>
                                </div>
                            </td>
                            <?php foreach (array_keys($crudLabels) as $crudKey): ?>
                                <td class="text-center">
                                    <?php if (isset($byCrud[$crudKey])): ?>
                                        <?php $perm = $byCrud[$crudKey]; ?>
                                        <input type="checkbox"
                                               name="permissions[]"
                                               value="<?= (int) $perm['id'] ?>"
                                               class="form-check-input esh-role-perm-cb"
                                               data-crud="<?= htmlspecialchars($crudKey, ENT_QUOTES, 'UTF-8') ?>"
                                               data-module="<?= htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8') ?>"
                                               title="<?= htmlspecialchars($perm['label'], ENT_QUOTES, 'UTF-8') ?>"
                                            <?= isset($selectedPermissionIds[(int) $perm['id']]) ? ' checked' : '' ?>>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between">
        <a href="<?= htmlspecialchars(esh_url('Role', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">İptal</a>
        <button type="submit" class="btn btn-primary">Kaydet</button>
    </div>
</form>

<?php if ($roleId > 0 && !$isSystem): ?>
<form method="post" action="<?= htmlspecialchars(esh_url('Role', 'delete'), ENT_QUOTES, 'UTF-8') ?>" class="mt-3"
      onsubmit="return confirm('Bu rol silinsin mi?');">
    <?= CsrfHelper::hiddenField() ?>
    <input type="hidden" name="id" value="<?= $roleId ?>">
    <button type="submit" class="btn btn-outline-danger btn-sm">Rolü sil</button>
</form>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var table = document.querySelector('.esh-role-perm-table');
    if (!table) {
        return;
    }

    function setChecked(selector, checked) {
        table.querySelectorAll(selector).forEach(function (cb) {
            cb.checked = checked;
        });
    }

    table.querySelectorAll('[data-esh-role-perm-action]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            setChecked('.esh-role-perm-cb', btn.getAttribute('data-esh-role-perm-action') === 'all-on');
        });
    });

    table.querySelectorAll('[data-esh-role-perm-col]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var col = btn.getAttribute('data-esh-role-perm-col');
            var checked = btn.getAttribute('data-checked') === '1';
            setChecked('.esh-role-perm-cb[data-crud="' + col + '"]', checked);
        });
    });

    table.querySelectorAll('[data-esh-role-perm-row]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = btn.getAttribute('data-esh-role-perm-row');
            var checked = btn.getAttribute('data-checked') === '1';
            setChecked('.esh-role-perm-cb[data-module="' + row + '"]', checked);
        });
    });
});
</script>

<?php PageShellHelper::pageClose(); ?>

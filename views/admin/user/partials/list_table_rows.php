<?php
/**
 * Admin kullanıcı listesi tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var list<object> $items
 */
use App\Helpers\AuthHelper;

if (!empty($items)) {
    foreach ($items as $item): ?>
        <tr>
            <td class="ps-4">
                <div class="d-flex align-items-center">
                    <img src="<?= \App\Models\User::profileImageWebUrlFromValue($item->image ?? '') ?>"
                         class="rounded-circle me-3" width="40" height="40" style="object-fit: cover;" alt="">
                    <div>
                        <div class="fw-bold"><?= htmlspecialchars((string) ($item->name ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        <small class="text-muted">ID: #<?= htmlspecialchars((string) ($item->id ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                    </div>
                </div>
            </td>
            <td>
                <div class="small fw-semibold"><?= htmlspecialchars((string) ($item->username ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="small text-muted"><?= !empty($item->tckimlikno) ? \App\Helpers\ValidationHelper::formatTc((string) $item->tckimlikno) : '—' ?></div>
            </td>
            <td class="small"><?= htmlspecialchars(\App\Models\User::unvanLabel(isset($item->unvan) ? (string) $item->unvan : null), ENT_QUOTES, 'UTF-8') ?></td>
            <td class="small text-muted">
                <?= htmlspecialchars(\App\Models\User::kurumDisplayLabel($item), ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td><?= htmlspecialchars((string) ($item->email ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td class="small">
                <?= htmlspecialchars(\App\Helpers\ThemeViewHelper::labelForUserUiThemePreference(isset($item->ui_theme) ? (string) $item->ui_theme : null), ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td class="text-center">
                <?php
                $eshRoleLevel = AuthHelper::clampLevel((int) ($item->isadmin ?? 0));
                $eshRoleClass = AuthHelper::adminLevelBadgeClass($eshRoleLevel);
                ?>
                <span class="badge <?= htmlspecialchars($eshRoleClass, ENT_QUOTES, 'UTF-8') ?> px-3"><?= htmlspecialchars(AuthHelper::adminLevelLabel($eshRoleLevel), ENT_QUOTES, 'UTF-8') ?></span>
            </td>
            <td class="text-center">
                <?php if (\App\Helpers\UserKurumTransfer::isArchivedAtSource($item)): ?>
                    <?= \App\Helpers\BadgeHelper::render('Başka kuruma nakil', 'secondary') ?>
                <?php else: ?>
                    <?= \App\Helpers\BadgeHelper::activationStatus($item->activated) ?>
                <?php endif; ?>
            </td>
            <td class="text-end pe-4">
                <?php $eshCanManageRow = AuthHelper::canManageUser($item->id ?? null); ?>
                <div class="btn-group">
                    <a href="<?= htmlspecialchars(esh_url('User', 'stats', ['user_id' => (string) ($item->id ?? '')]), ENT_QUOTES, 'UTF-8') ?>"
                       class="btn btn-sm btn-outline-primary" title="İş özeti istatistikleri">
                        <i class="fa-solid fa-chart-pie"></i>
                    </a>
                    <?php if ($eshCanManageRow): ?>
                    <a href="<?= htmlspecialchars(esh_url('User', 'adminEdit', ['id' => (string) ($item->id ?? '')]), ENT_QUOTES, 'UTF-8') ?>"
                       class="btn btn-sm btn-outline-secondary" title="Düzenle">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    <form method="post" action="<?= htmlspecialchars(esh_url('User', 'delete'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline m-0" data-esh-confirm="Bu kullanıcıyı silmek istediğinize emin misiniz?">
                        <input type="hidden" name="id" value="<?= htmlspecialchars((string) ($item->id ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Sil"><i class="fa-solid fa-trash"></i></button>
                    </form>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endforeach;
} else { ?>
    <tr>
        <td colspan="9" class="text-center py-5 text-muted">Kayıtlı kullanıcı bulunamadı.</td>
    </tr>
<?php } ?>

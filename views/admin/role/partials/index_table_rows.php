<?php
declare(strict_types=1);

/** @var list<array{id:int, slug:string, name:string, description:?string, is_system:int, sort_order:int, unvan_code?:?string}> $roles */

if ($roles === []): ?>
    <tr><td colspan="6" class="text-muted text-center py-4">Henüz rol tanımlı değil. Kurulum seed dosyalarını (seed_esh_rbac.sql) uygulayın.</td></tr>
<?php else:
    foreach ($roles as $role): ?>
    <tr>
        <td class="fw-semibold"><?= htmlspecialchars($role['name'], ENT_QUOTES, 'UTF-8') ?></td>
        <td><code><?= htmlspecialchars($role['slug'], ENT_QUOTES, 'UTF-8') ?></code></td>
        <td class="small">
            <?php if (!empty($role['unvan_code'])): ?>
                <code><?= htmlspecialchars((string) $role['unvan_code'], ENT_QUOTES, 'UTF-8') ?></code>
                <span class="text-muted">→ <?= htmlspecialchars(\App\Models\User::unvanLabel((string) $role['unvan_code']), ENT_QUOTES, 'UTF-8') ?></span>
            <?php else: ?>
                <span class="text-muted">— (manuel / varsayılan)</span>
            <?php endif; ?>
        </td>
        <td class="small text-muted"><?= htmlspecialchars((string) ($role['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
        <td class="text-center">
            <?php if ((int) $role['is_system'] === 1): ?>
                <span class="badge bg-secondary">Sistem</span>
            <?php endif; ?>
        </td>
        <td class="text-end">
            <a href="<?= htmlspecialchars(esh_url('Role', 'edit', ['id' => $role['id']]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm">Düzenle</a>
        </td>
    </tr>
    <?php endforeach;
endif;

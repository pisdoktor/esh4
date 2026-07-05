<?php
declare(strict_types=1);

/** @var list<object> $izinler */

if (empty($izinler)): ?>
    <tr><td colspan="3" class="text-center text-muted py-3 small">Kayıt yok.</td></tr>
<?php else:
    foreach ($izinler as $r): ?>
    <tr>
        <td><?= \App\Helpers\DateHelper::toTr((string) ($r->baslangic_tarihi ?? '')) ?> - <?= \App\Helpers\DateHelper::toTr((string) ($r->bitis_tarihi ?? '')) ?></td>
        <td><?= htmlspecialchars((string) ($r->sebep ?? '')) ?></td>
        <td class="text-end">
            <form method="post" action="<?= htmlspecialchars(esh_url('Nobet', 'deleteMineIzin'), ENT_QUOTES, 'UTF-8') ?>" class="d-inline m-0" data-esh-confirm="Silinsin mi?">
                <input type="hidden" name="id" value="<?= (int) ($r->id ?? 0) ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
            </form>
        </td>
    </tr>
    <?php endforeach;
endif;

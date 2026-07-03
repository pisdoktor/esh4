<?php
/**
 * Hasta plan listesi tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var list<object> $plans
 * @var string $tc
 * @var int $viewerKurumId
 * @var array<string, array{text: string, class: string}> $oncelikConfig
 */
use App\Helpers\AuthHelper;

if (!empty($plans)) {
    $bugun = date('Y-m-d');
    foreach ($plans as $p):
        $gecikme = ((int) ($p->durum ?? 0) === 0 && !empty($p->planlanantarih) && $p->planlanantarih < $bugun);
        $zamanData = \App\Helpers\ZamanDilimiHelper::badgeFor($p->zaman ?? null);
        $ok = (string) (int) ($p->oncelik ?? 1);
        $onc = $oncelikConfig[$ok] ?? ['text' => '—', 'class' => 'secondary'];
        ?>
        <tr class="<?= $gecikme ? 'table-danger' : '' ?>">
            <?php
            $__ppActs = [];
            if ((int) ($p->durum ?? 0) === 0) {
                $__ppActs[] = [
                    'href' => esh_url('Visit', 'create', ['tc' => (string) $tc, 'plan_id' => (int) $p->id]),
                    'title' => 'Gerçekleşen izlem kaydı',
                    'icon' => 'fa-solid fa-check text-success',
                ];
            }
            $__ppActs[] = [
                'href' => esh_url('PlannedVisit', 'create', ['tc' => (string) $tc]),
                'title' => 'Yeni plan (aynı hasta)',
                'icon' => 'fa-solid fa-calendar-plus text-primary',
            ];
            if (AuthHelper::sessionIsAdmin()) {
                $__ppActs[] = [
                    'href' => esh_url('PlannedVisit', 'edit', ['id' => (int) $p->id, 'tc' => (string) $tc]),
                    'title' => 'Planlı izlem düzenle',
                    'icon' => 'fa-solid fa-pen-to-square text-primary',
                ];
                $__ppActs[] = [
                    'action' => esh_url('PlannedVisit', 'delete'),
                    'hidden' => [
                        'id' => (int) $p->id,
                        'tc' => (string) $tc,
                    ],
                    'title' => 'Planı sil',
                    'icon' => 'fa-solid fa-trash text-danger',
                    'variant' => 'danger',
                    'confirm' => 'Bu planlı izlem kaydını kalıcı olarak silmek istediğinize emin misiniz?',
                ];
            }
            $ppDateLabel = !empty($p->planlanantarih) ? date('d-m-Y', strtotime($p->planlanantarih)) : '—';
            ?>
            <td>
                <?= \App\Helpers\FormHelper::listActionsDateDropdown($__ppActs, $ppDateLabel, ['toggleTitle' => 'Plan işlemleri']) ?>
                <?= \App\Helpers\IzlemKurumDisplayHelper::otherKurumHtml((int)($p->kurum_id ?? 0), (int)($viewerKurumId ?? 0), isset($p->kurum_adi) ? (string)$p->kurum_adi : null) ?>
            </td>
            <?php unset($__ppActs); ?>
            <td class="text-center">
                <span class="badge bg-<?= htmlspecialchars($zamanData['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($zamanData['text']) ?></span>
            </td>
            <td class="text-center">
                <span class="badge bg-<?= htmlspecialchars($onc['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($onc['text']) ?></span>
            </td>
            <td class="small"><?= htmlspecialchars($p->yapilacaklar ?: '—') ?></td>
            <td class="small"><i class="fa-solid fa-user-nurse text-muted me-1"></i><?= htmlspecialchars($p->planlayanlar ?: '—') ?></td>
            <td class="text-center">
                <?php if ((int) ($p->durum ?? 0) === 1): ?>
                    <span class="badge bg-success">Yapıldı</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">Bekleyen</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach;
} else { ?>
    <tr>
        <td colspan="6" class="text-center text-muted py-5">Bu hasta için planlı izlem kaydı yok veya filtreye uyan kayıt yok.</td>
    </tr>
<?php } ?>

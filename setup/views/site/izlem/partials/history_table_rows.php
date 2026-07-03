<?php
/**
 * İzlem geçmişi tablo satırları (tbody içi; yalnızca <tr>…</tr>).
 * @var list<object> $visits
 * @var string $tc
 * @var int $viewerKurumId
 */
use App\Helpers\AuthHelper;

if (!empty($visits)) {
    foreach ($visits as $v):
        $zamanData = ($v->zaman === null || $v->zaman === '')
            ? ['text' => '—', 'class' => 'secondary']
            : \App\Helpers\ZamanDilimiHelper::badgeFor($v->zaman);
        $__histActs = [
            [
                'href' => esh_url('Visit', 'edit', ['id' => (int) ($v->id ?? 0)]),
                'title' => 'Düzenle',
                'icon' => 'fa-solid fa-pen text-primary',
            ],
        ];
        if (\App\Helpers\VisitIslemHelper::yapilanCsvContainsIslem(
            (string) ($v->yapilan ?? ''),
            \App\Helpers\VisitIslemHelper::konsultasyonIslemId()
        )) {
            $__histActs[] = [
                'href' => '#',
                'title' => 'EK-3 çıkart',
                'icon' => 'fa-solid fa-file-lines text-info',
                'onclick' => 'if(window.eshOpenEk3FromHistory){window.eshOpenEk3FromHistory(' . (int) ($v->id ?? 0) . ');} return false;',
            ];
        }
        if (AuthHelper::sessionIsAdmin()) {
            $__histActs[] = [
                'action' => esh_url('Visit', 'delete'),
                'hidden' => [
                    'id' => (int) ($v->id ?? 0),
                    'tc' => $tc,
                ],
                'title' => 'Sil',
                'icon' => 'fa-solid fa-trash text-danger',
                'variant' => 'danger',
                'confirm' => 'Bu izlem kaydını kalıcı olarak silmek istediğinize emin misiniz?',
            ];
        }
        $histDateLabel = !empty($v->izlemtarihi) ? date('d-m-Y', strtotime($v->izlemtarihi)) : '—';
        $checkinLat = isset($v->checkin_lat) && is_numeric($v->checkin_lat) ? (float) $v->checkin_lat : null;
        $checkinLon = isset($v->checkin_lon) && is_numeric($v->checkin_lon) ? (float) $v->checkin_lon : null;
        $hasCheckin = $checkinLat !== null && $checkinLon !== null
            && $checkinLat >= -90 && $checkinLat <= 90
            && $checkinLon >= -180 && $checkinLon <= 180;
        ?>
        <tr>
            <td>
                <?= \App\Helpers\FormHelper::listActionsDateDropdown($__histActs, $histDateLabel, ['toggleTitle' => 'İzlem işlemleri']) ?>
                <?php if ($hasCheckin): ?>
                    <a href="https://www.google.com/maps?q=<?= htmlspecialchars((string) $checkinLat . ',' . (string) $checkinLon, ENT_QUOTES, 'UTF-8') ?>"
                       class="badge bg-light text-dark border text-decoration-none ms-1"
                       target="_blank"
                       rel="noopener noreferrer"
                       title="Saha konum kaydı">
                        <i class="fa-solid fa-location-dot text-success me-1"></i>Konum
                    </a>
                <?php endif; ?>
                <?= \App\Helpers\IzlemKurumDisplayHelper::otherKurumHtml((int)($v->kurum_id ?? 0), (int)($viewerKurumId ?? 0), isset($v->kurum_adi) ? (string)$v->kurum_adi : null) ?>
            </td>
            <?php unset($__histActs); ?>
            <td class="text-center">
                <span class="badge bg-<?= htmlspecialchars($zamanData['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($zamanData['text'], ENT_QUOTES, 'UTF-8') ?></span>
            </td>
            <td class="small"><i class="fa-solid fa-user-nurse text-muted me-1"></i><?= htmlspecialchars((string) ($v->yapanlar ?: '—')) ?></td>
            <td>
                <?php if ((int) ($v->yapildimi ?? 0) === 1): ?>
                    <span class="badge bg-success">Yapıldı</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">Yapılmadı</span>
                <?php endif; ?>
            </td>
            <td class="small">
                <?php
                $plaka = trim((string) ($v->aracplaka ?? ''));
                $ab = trim((string) ($v->arac_bilgisi ?? ''));
                if ($plaka === '' && $ab === '') {
                    echo '—';
                } else {
                    if ($plaka !== '') {
                        echo '<span class="font-monospace">' . htmlspecialchars($plaka, ENT_QUOTES, 'UTF-8') . '</span>';
                    }
                    if ($ab !== '') {
                        echo ($plaka !== '' ? '<br>' : '') . '<span class="text-muted">' . htmlspecialchars($ab, ENT_QUOTES, 'UTF-8') . '</span>';
                    }
                }
                ?>
            </td>
            <td class="small"><?= \App\Helpers\VisitIslemHelper::yapilanlarHistoryCellHtml(
                (string) ($v->yapilanlar ?? ''),
                (string) ($v->yapilan ?? ''),
                (string) ($v->kons_brans_istek ?? ''),
                (string) ($v->brans ?? ''),
                (string) ($v->kons_istekler ?? '')
            ) ?></td>
        </tr>
    <?php endforeach;
} else { ?>
    <tr><td colspan="6" class="text-center text-muted py-4">Kayıt bulunamadı.</td></tr>
<?php } ?>

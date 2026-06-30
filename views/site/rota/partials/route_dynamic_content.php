<?php

declare(strict_types=1);



/** @var array<string, mixed> $data */



use App\Helpers\ZamanDilimiHelper;



$vardiyaColors = [

    'sabah' => '#f59e0b',

    'ogle' => '#3b82f6',

    'aksam' => '#6366f1',

];



$tabs = [];

foreach (ZamanDilimiHelper::uiSections() as $sec) {

    $vIdx = ZamanDilimiHelper::toVardiyaIndex((int) $sec['code']);

    $tabs[$vIdx] = [

        'id' => (string) $sec['key'],

        'label' => (string) $sec['label'],

        'icon' => (string) $sec['icon'],

        'mod' => (string) $sec['mod'],

        'color' => $vardiyaColors[(string) $sec['key']] ?? '#6b7280',

    ];

}

?>

<ul class="nav nav-tabs nav-fill esh-route-tabs border-bottom-0 gap-2" id="routeTabs" role="tablist">

    <?php $tabIndex = 0; foreach ($tabs as $key => $t):

        $v_verisi = $data['tum_vardiya_verisi'][$key] ?? [];

        $hasta_sayisi = 0;

        if (!empty($v_verisi)) {

            foreach ($v_verisi as $e) {

                $hasta_sayisi += count($e['hastalar'] ?? []);

            }

        }

        $isActive = ($tabIndex === 0);

        $badgeClass = $hasta_sayisi > 0 ? 'esh-route-tab-badge--on' : 'esh-route-tab-badge--empty';

        $badgeClass .= ' esh-route-tab-badge--' . $t['mod'];

        $tabIndex++;

        ?>

    <li class="nav-item" role="presentation">

        <button class="nav-link esh-route-tab esh-route-tab--<?= $t['mod'] ?> <?= $isActive ? 'active' : '' ?>"

                id="<?= $t['id'] ?>-tab"

                data-bs-toggle="tab"

                data-bs-target="#<?= $t['id'] ?>_pane"

                type="button"

                role="tab"

                aria-selected="<?= $isActive ? 'true' : 'false' ?>">

            <span class="esh-route-tab__inner">

                <i class="fa <?= $t['icon'] ?> esh-route-tab__icon" aria-hidden="true"></i>

                <span class="esh-route-tab__label"><?= htmlspecialchars($t['label'], ENT_QUOTES, 'UTF-8') ?></span>

                <span class="badge rounded-pill esh-route-tab-badge <?= $badgeClass ?>"><?= (int) $hasta_sayisi ?></span>

            </span>

        </button>

    </li>

    <?php endforeach; ?>

</ul>



<div class="tab-content border rounded bg-light-subtle shadow-sm" style="margin-top: -1px; min-height: 400px;">

    <?php $paneIndex = 0; foreach ($tabs as $key => $t):

        $isActive = ($paneIndex === 0);

        $paneIndex++;

        ?>

    <div class="tab-pane fade <?= $isActive ? 'show active' : '' ?> p-3"

         id="<?= $t['id'] ?>_pane"

         role="tabpanel"

         aria-labelledby="<?= $t['id'] ?>-tab">

        <?php if (empty($data['tum_vardiya_verisi'][$key])): ?>

            <div class="text-center py-5 bg-white rounded border border-dashed">

                <i class="fa fa-calendar-times fa-3x mb-3 text-muted opacity-25"></i>

                <p class="text-muted small fw-bold">Bu vardiya için planlanmış bir ziyaret bulunmamaktadır.</p>

            </div>

        <?php else: ?>

            <div class="row g-3">

                <?php foreach ($data['tum_vardiya_verisi'][$key] as $eID => $eData): ?>

                    <div class="col-xl-6 col-lg-12">

                        <?php include __DIR__ . '/../ekip_karti.php'; ?>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>

    <?php endforeach; ?>

</div>



<?php if (!empty($data['ai_analiz'])): ?>

    <div class="alert alert-secondary border-0 shadow-sm mb-4 mt-3">

        <h5 class="fw-bold text-dark mb-3">

            <i class="fa-solid fa-microchip me-2 text-primary"></i>Akıllı Operasyon Analizi

        </h5>

        <div class="row">

            <?php foreach ($data['ai_analiz'] as $uyari):

                $tipRaw = (string) ($uyari['tip'] ?? 'secondary');

                $tipAllowed = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark', 'light'];

                $tip = in_array($tipRaw, $tipAllowed, true) ? $tipRaw : 'secondary';

                $ekipEsc = htmlspecialchars((string) ($uyari['ekip'] ?? ''), ENT_QUOTES, 'UTF-8');

                $mesajEsc = htmlspecialchars((string) ($uyari['mesaj'] ?? ''), ENT_QUOTES, 'UTF-8');

                ?>

            <div class="col-md-6 mb-2">

                <div class="p-2 border-start border-4 border-<?= $tip ?> bg-white small shadow-sm">

                    <span class="badge bg-<?= $tip ?> mb-1"><?= $ekipEsc ?></span><br>

                    <?= $mesajEsc ?>

                </div>

            </div>

            <?php endforeach; ?>

        </div>

    </div>

<?php endif; ?>


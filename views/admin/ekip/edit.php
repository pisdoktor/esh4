<?php

/**

 * @var string $date Y-m-d plan tarihi

 * @var array<int, object> $users

 * @var array<int, object> $mevcutlar

 */

$secili_dizi = [];

$kayitli_saatler = [];

$vardiya_ekip_sayilari = [0 => 1, 1 => 1, 2 => 1];



if (!empty($mevcutlar)) {

    foreach ($mevcutlar as $m) {

        $vRaw = $m->vardiya ?? null;

        if ($vRaw === null || $vRaw === '' || !is_numeric($vRaw)) {

            continue;

        }

        $vKey = (int) $vRaw;

        if (!array_key_exists($vKey, $vardiya_ekip_sayilari)) {

            continue;

        }

        $eNo = max(1, (int) ($m->ekip_no ?? 1));

        $secili_dizi[$vKey][$eNo] = explode(',', (string) ($m->user_ids ?? ''));

        $kayitli_saatler[$vKey] = $m->baslangic_saati ?? null;

        if ($eNo > $vardiya_ekip_sayilari[$vKey]) {

            $vardiya_ekip_sayilari[$vKey] = $eNo;

        }

    }

}



$vardiyaStyle = [
    0 => ['color' => '#f39c12', 'icon' => 'fa-sun', 'bg' => '#fef9f1'],
    1 => ['color' => '#3498db', 'icon' => 'fa-cloud-sun', 'bg' => '#f1f9fe'],
    2 => ['color' => '#2c3e50', 'icon' => 'fa-moon', 'bg' => '#f4f6f7'],
];

$vardiyalar = [];
foreach (\App\Helpers\ZamanDilimiHelper::uiSections() as $sec) {
    $vKey = \App\Helpers\ZamanDilimiHelper::toVardiyaIndex((int) $sec['code']);
    $style = $vardiyaStyle[$vKey] ?? $vardiyaStyle[0];
    $vardiyalar[$vKey] = [
        'label' => mb_strtoupper((string) $sec['label'], 'UTF-8') . ' VARDİYASI',
        'color' => $style['color'],
        'icon' => $style['icon'],
        'bg' => $style['bg'],
        'def_time' => (string) $sec['ekipBaslangic'],
    ];
}



$tarih_ekran = \App\Helpers\DateHelper::toTrDotOrEmpty($date);



$user_options = '';

foreach ($users as $user) {

    $user_options .= '<option value="' . (int) $user->id . '">' . htmlspecialchars((string) $user->name, ENT_QUOTES, 'UTF-8') . '</option>';

}

?>



<div class="esh-page esh-page--form esh-page-ekip container-fluid py-4">

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white border-bottom py-3">

            <h4 class="mb-0 text-primary"><i class="fa-solid fa-arrows-down-to-people me-2"></i>Personel ekip ataması</h4>

        </div>

        <div class="card-body bg-light py-4">

            <form action="<?= htmlspecialchars(esh_url('Ekip', 'saveDaily'), ENT_QUOTES, 'UTF-8') ?>" method="post">

                <div class="text-center mb-4">

                    <div class="datepicker-container">

                        <span class="fw-semibold">Tarih:</span>

                        <input type="text" name="tarih" id="planTarihi" value="<?= htmlspecialchars($tarih_ekran, ENT_QUOTES, 'UTF-8'); ?>"

                               class="border-0 bg-transparent fw-bold text-center" style="min-width: 7.5rem;" autocomplete="off">

                    </div>

                </div>



                <div class="row g-3">

                    <?php foreach ($vardiyalar as $vKey => $vVal):

                        $display_time = isset($kayitli_saatler[$vKey]) ? (string) $kayitli_saatler[$vKey] : $vVal['def_time'];

                        if (strlen($display_time) >= 5) {

                            $display_time = substr($display_time, 0, 5);

                        }

                        include __DIR__ . '/partials/vardiya_column.php';

                    endforeach; ?>

                </div>



                <div class="text-center mt-4">

                    <button type="submit" class="btn btn-success btn-lg px-5"><i class="fa fa-save me-1"></i> KAYDET</button>

                </div>

            </form>

        </div>

    </div>

</div>



<script>

var userOptions = <?= json_encode($user_options, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;



function updateDisabledOptions(vardiyaId) {

    var vardiyaGrubu = jQuery('.vardiya-grubu[data-vardiya-id="' + vardiyaId + '"]');

    var allSelects = vardiyaGrubu.find('.ekip-select');

    var selectedValues = [];

    allSelects.each(function () {

        var vals = jQuery(this).val();

        if (vals) {

            selectedValues = selectedValues.concat(vals);

        }

    });

    allSelects.each(function () {

        var currentSelect = jQuery(this);

        var currentVals = currentSelect.val() || [];

        currentSelect.find('option').each(function () {

            var val = jQuery(this).val();

            if (selectedValues.indexOf(val) !== -1 && currentVals.indexOf(val) === -1) {

                jQuery(this).prop('disabled', true);

            } else {

                jQuery(this).prop('disabled', false);

            }

        });

        if (window.eshRefreshTomSelect && currentSelect.length) {
            window.eshRefreshTomSelect(currentSelect[0], { silent: true });
        }

    });

}



function ekipEkle(vKey) {

    var container = jQuery('#vardiya-container-' + vKey + ' .ekip-listesi');

    var nextNo = container.find('.ekip-box').length + 1;

    var html = '<div class="ekip-box"><i class="fa fa-times remove-ekip" onclick="jQuery(this).closest(\'.ekip-box\').remove(); updateDisabledOptions(' + vKey + ');"></i>' +

        '<div class="small fw-semibold mb-1">' + nextNo + '. Ekip</div>' +

        '<select name="ekipler[' + vKey + '][' + nextNo + '][]" class="form-select form-select-sm esh-tomselect ekip-select" multiple>' + userOptions + '</select></div>';

    container.append(html);

    container.find('.esh-tomselect').last().each(function () {
        if (typeof window.eshInitTomSelectElement === 'function') {
            window.eshInitTomSelectElement(this);
        }
    });

    updateDisabledOptions(vKey);

}



jQuery(document).ready(function ($) {

    if (typeof window.eshInitTomSelectOnPage === 'function') {
        window.eshInitTomSelectOnPage();
    }

    [0, 1, 2].forEach(function (v) {

        updateDisabledOptions(v);

    });

    $(document).on('change', '.ekip-select', function () {

        var vid = $(this).closest('.vardiya-grubu').data('vardiya-id');

        updateDisabledOptions(vid);

    });



    $('#planTarihi').datepicker({

        format: 'dd.mm.yyyy',

        autoclose: true,

        todayHighlight: true,

        language: 'tr',

        weekStart: 1

    }).on('changeDate', function (e) {

        var d = e.date;

        var yil = d.getFullYear();

        var ay = ('0' + (d.getMonth() + 1)).slice(-2);

        var gun = ('0' + d.getDate()).slice(-2);

        var urlTarih = yil + '-' + ay + '-' + gun;

        window.location.href = (typeof window.eshUrl === 'function'

            ? window.eshUrl('Ekip', 'edit', { tarih: urlTarih })

            : 'index.php?controller=Ekip&action=edit&tarih=' + encodeURIComponent(urlTarih));

    });

});

</script>


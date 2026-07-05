<?php use App\Helpers\FormHelper; ?>
<div class="container-fluid py-4 admin-list-page esh-page-pansuman">
    <div class="card border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
        <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span class="rounded-circle bg-info-subtle text-info d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">
                    <i class="fa-solid fa-sliders"></i>
                </span>
                <div class="min-w-0">
                    <span class="fw-semibold text-dark d-block">Liste filtreleri</span>
                    <span class="small text-muted">Arama ve gün seçip «Filtrele» ile uygulayın.</span>
                </div>
            </div>
            <button
                id="pansuman-filter-toggle"
                class="btn btn-outline-secondary btn-sm rounded-pill px-3<?= $filterExpanded ? '' : ' collapsed' ?>"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#pansuman-filter-collapse"
                aria-expanded="<?= $filterExpanded ? 'true' : 'false' ?>"
                aria-controls="pansuman-filter-collapse"
            >
                <i class="fa-solid fa-sliders me-1"></i><span class="js-filter-toggle-text"><?= $filterExpanded ? 'Filtreleri Gizle' : 'Filtreleri Göster' ?></span>
            </button>
            <?php if (\App\Services\Sms\SmsService::canUseSms(\App\Helpers\AuthHelper::sessionUserId())): ?>
            <a href="<?= htmlspecialchars(esh_url('Sms', 'compose', ['segment' => 'pansuman_liste']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="fa-solid fa-comment-sms me-1"></i>Listeye SMS
            </a>
            <?php endif; ?>
        </div>
        <div id="pansuman-filter-collapse" class="collapse<?= $filterExpanded ? ' show' : '' ?>">
            <div class="card-body p-3 p-md-4 bg-body-tertiary bg-opacity-25">
                <form method="get" action="<?= htmlspecialchars(esh_form_action('Pansuman', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 g-xl-4 align-items-end esh-pansuman-filter">
                <?= esh_form_route_hiddens('Pansuman', 'index') ?>
                    <input type="hidden" name="page" value="1">
                    <input type="hidden" name="limit" value="<?= (int) $limit ?>">

                    <?php
                    echo FormHelper::fieldInputGroup('search', 'Arama', $search, [
                        'col' => 'col-12 col-lg-5 col-xl-4',
                        'id' => 'pansuman-filter-search',
                        'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                        'inputGroupSm' => true,
                        'inputGroupExtraClass' => 'shadow-sm',
                        'prefixIcon' => 'fa-solid fa-magnifying-glass',
                        'prefixIconClass' => 'bg-white text-info border-end-0',
                        'class' => 'border-start-0 esh-filter-control',
                        'placeholder' => 'Ad, soyad veya TC…',
                    ]);
                    $eshPansumanDayOptions = [FormHelper::makeOption('', 'Tüm günler')];
                    foreach ($gunler as $val => $label) {
                        $eshPansumanDayOptions[] = FormHelper::makeOption((string) (int) $val, (string) $label);
                    }
                    echo FormHelper::fieldSelect('filter_day', 'Uygulama günü', $eshPansumanDayOptions, (string) $filter_day, [
                        'col' => 'col-12 col-sm-6 col-lg-3 col-xl-2',
                        'id' => 'pansuman-filter-day',
                        'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                        'class' => 'form-select-sm shadow-sm esh-filter-control',
                        'tomSelect' => false,
                    ]);
                    ?>
                    <?php
                    $eshUserListKurum = $eshPansumanListKurum;
                    include ROOT_PATH . '/views/partials/admin/user_list_kurum_filter.php';
                    ?>
                    <div class="col-12 col-sm-6 col-lg-auto d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary btn-sm shadow-sm px-4 rounded-pill esh-filter-control"><i class="fa-solid fa-filter me-1"></i>Filtrele</button>
                        <a href="<?= htmlspecialchars(esh_url('Pansuman', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 esh-filter-control">Sıfırla</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
use App\Helpers\FormHelper;
?>
<div class="row align-items-center mb-4">
    <div class="col-md-3">
        <h4 class="fw-bold text-dark mb-0">
            <i class="fa-solid fa-user-slash text-secondary me-2"></i><?= $pageTitle; ?>
        </h4>
        <small class="text-muted">Pasif kayıtlar üzerinde filtreleme yapın</small>
    </div>

    <div class="col-md-9">
        <form action="<?= htmlspecialchars(esh_form_action('Patient', 'listpassive'), ENT_QUOTES, 'UTF-8') ?>" method="GET" class="row g-2">
            <?= esh_form_route_hiddens('Patient', 'listpassive') ?>

            <div class="col-md-5">
                <div class="d-flex shadow-sm rounded-pill overflow-hidden bg-white p-1 border">
                    <?= FormHelper::fieldInput('search', '', (string) ($search ?? ''), [
                        'col' => '',
                        'noLabel' => true,
                        'class' => 'border-0 px-3',
                        'placeholder' => 'İsim, soyisim veya TC...',
                    ]) ?>
                    <?php
                    $eshPassiveReasonOptions = [FormHelper::makeOption('', 'Tüm Nedenler')];
                    foreach ($pasifListesi as $k => $neden) {
                        $eshPassiveReasonOptions[] = FormHelper::makeOption((string) $k, (string) $neden);
                    }
                    echo FormHelper::fieldSelect('reason', 'Pasif nedeni', $eshPassiveReasonOptions, (string) ($reason ?? ''), [
                        'col' => '',
                        'noLabel' => true,
                        'id' => 'listpassive-reason',
                        'class' => 'border-0 bg-light ms-1 esh-patient-list-reason-select',
                        'tomSelect' => false,
                    ]);
                    ?>
                </div>
            </div>

            <div class="col-md-5">
                <?= FormHelper::fieldDateRangeInline('startDate', 'endDate', $startDate ?? '', $endDate ?? '', [
                    'col' => '',
                    'noLabel' => true,
                    'inputGroupId' => 'datepicker-range',
                    'inputGroupClass' => 'shadow-sm h-100 border rounded-pill overflow-hidden',
                    'prefixIcon' => 'fa-solid fa-calendar-days text-primary',
                    'class' => 'border-0 bg-white',
                ]) ?>
            </div>

            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-primary rounded-pill flex-grow-1 shadow-sm">
                    <i class="fa-solid fa-filter me-1"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($search) || !empty($reason) || !empty($startDate)): ?>
    <div class="alert alert-light border py-2 px-4 d-flex justify-content-between align-items-center rounded-4 mb-3 shadow-sm">
        <div class="small">
            <?php if ($search): ?> <strong>"<?= htmlspecialchars($search) ?>"</strong> araması, <?php endif; ?>
            <?php if ($reason): ?> <strong><?= $pasifListesi[$reason] ?></strong> nedeni, <?php endif; ?>
            <?php if ($startDate): ?> <strong><?= $startDate ?> / <?= $endDate ?></strong> tarihleri arası, <?php endif; ?>
            için <strong><?= $totalPatients ?></strong> sonuç bulundu.
        </div>
        <a href="<?= htmlspecialchars(esh_url('Patient', 'listpassive'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-link text-danger text-decoration-none p-0">
            <i class="fa-solid fa-trash-can me-1"></i>Temizle
        </a>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-primary text-white py-3 rounded-top-4">
        <h2 class="h5 mb-0">
            <?php if ($selectedDate !== ''): ?>
                <?= htmlspecialchars(\App\Helpers\DateHelper::toTrWithWeekday($selectedDate), ENT_QUOTES, 'UTF-8') ?>
            <?php else: ?>
                Gün seçin
            <?php endif; ?>
        </h2>
    </div>
    <div class="card-body p-3 p-md-4">
        <?php if ($selectedDate === ''): ?>
            <p class="text-muted small mb-0">Soldaki takvimden bir güne tıklayarak o güne ait randevuları görüntüleyin ve yeni kayıt ekleyin.</p>
        <?php else: ?>
            <?php if (empty($branslar)): ?>
                <div class="alert alert-warning small">Önce <a href="<?= htmlspecialchars(esh_url('Brans', 'index'), ENT_QUOTES, 'UTF-8') ?>">branş tanımları</a> ekleyin.</div>
            <?php endif; ?>

            <?php include __DIR__ . '/day_appointments_table.php'; ?>

            <?php if (!empty($branslar)): ?>
                <?php include __DIR__ . '/uhds_new_form.php'; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

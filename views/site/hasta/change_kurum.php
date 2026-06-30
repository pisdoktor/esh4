<?php
/** @var object $patient */
/** @var list<object> $kurumlar */
/** @var object|null $currentKurum */
$patientName = trim((string) (($patient->isim ?? '') . ' ' . ($patient->soyisim ?? '')));
?>
<div class="esh-page esh-page--form container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 text-primary">
                        <i class="fa-solid fa-building me-2"></i>Hasta kurumu değiştir
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="alert alert-info border-0 small mb-4">
                        <strong><?= htmlspecialchars($patientName, ENT_QUOTES, 'UTF-8') ?></strong>
                        — TC: <code><?= htmlspecialchars(\App\Helpers\ValidationHelper::formatTc((string) ($patient->tckimlik ?? '')), ENT_QUOTES, 'UTF-8') ?></code>
                        <?php if ($currentKurum): ?>
                            <br>Mevcut kurum: <strong><?= htmlspecialchars((string) ($currentKurum->ad ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php endif; ?>
                    </div>
                    <form method="post" action="<?= htmlspecialchars(esh_url('Patient', 'storeKurum'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
                        <?= esh_csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) ($patient->id ?? 0) ?>">
                        <?php
                        $eshChangeKurumOptions = [];
                        foreach ($kurumlar as $k) {
                            $label = (string) ($k->ad ?? '');
                            if (!empty($k->kod)) {
                                $label .= ' (' . (string) $k->kod . ')';
                            }
                            $eshChangeKurumOptions[] = \App\Helpers\FormHelper::makeOption((string) (int) ($k->id ?? 0), $label);
                        }
                        echo \App\Helpers\FormHelper::fieldSelect('kurum_id', 'Yeni kurum', $eshChangeKurumOptions, (string) (int) ($patient->kurum_id ?? 0), [
                            'col' => 'col-12',
                            'id' => 'eshChangeKurumSelect',
                            'labelClass' => 'form-label fw-semibold',
                            'required' => true,
                            'tomSelect' => false,
                            'helpText' => 'Hasta anında hedef kuruma taşınır; nakil kaydı oluşturulur. İzlem ve planlı izlem kayıtları önceki kurumda kalır.',
                        ]);
                        ?>
                        <div class="col-12 d-flex flex-wrap gap-2 pt-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-floppy-disk me-1"></i>Kurumu kaydet
                            </button>
                            <a href="<?= htmlspecialchars(esh_url('Patient', 'edit', ['id' => (int) ($patient->id ?? 0)]), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Hasta düzenleme</a>
                            <a href="<?= htmlspecialchars(esh_url('Patient', 'unified', ['status' => 'active']), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary">Listeye dön</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

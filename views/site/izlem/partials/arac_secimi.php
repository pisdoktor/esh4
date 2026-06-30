<?php
/**
 * İzlem formu — araç (plaka) seçimi (tek seçim, btn-check ile kart görünümü).
 *
 * @var object[] $araclar esh_araclar satırları
 * @var int $selectedAracId seçili esh_araclar.id (>0)
 * @var string $radiosSuffix benzersiz id öneki
 * @var string $aracPickerAccent 'success' (yeni izlem) veya 'primary' (düzenle)
 */
$araclar = $araclar ?? [];
$selectedAracId = (int) ($selectedAracId ?? 0);
$radiosSuffix = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($radiosSuffix ?? 'izlem-arac'));
$accent = $aracPickerAccent ?? 'success';
if (!in_array($accent, ['success', 'primary'], true)) {
    $accent = 'success';
}
$headBg = $accent === 'primary' ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success';
$chipOutline = $accent === 'primary' ? 'btn-outline-primary' : 'btn-outline-success';
?>
<div class="izlem-arac-picker border rounded-3 shadow-sm overflow-hidden">
    <div class="d-flex align-items-center gap-3 px-3 py-2 border-bottom bg-body-tertiary bg-opacity-50">
        <span class="rounded-3 <?= htmlspecialchars($headBg, ENT_QUOTES, 'UTF-8') ?> d-inline-flex align-items-center justify-content-center flex-shrink-0 izlem-arac-picker__icon-wrap">
            <i class="fa-solid fa-car-side"></i>
        </span>
        <div class="min-w-0">
            <div class="fw-semibold small text-dark mb-0">Kullanılan araç</div>
            <div class="text-muted izlem-arac-picker__hint">Ziyarette kullanılan aracı plaka ile işaretleyin.</div>
        </div>
    </div>

    <?php if (empty($araclar)): ?>
        <div class="p-3">
            <div class="alert alert-warning border-0 small mb-0 py-3 rounded-3 shadow-sm">
                <i class="fa-solid fa-triangle-exclamation me-2"></i>
                Tanımlı araç yok. Yönetim → <strong>Araç tanımları</strong> ile plaka ekleyebilirsiniz.
            </div>
        </div>
    <?php else: ?>
        <div class="px-3 py-2">
            <div class="row g-2 align-items-stretch">
                <div class="col-12 col-sm-6">
                    <input class="btn-check" type="radio" name="arac" id="<?= htmlspecialchars($radiosSuffix, ENT_QUOTES, 'UTF-8') ?>-arac-0"
                           value="" autocomplete="off"<?= $selectedAracId < 1 ? ' checked' : '' ?>>
                    <label class="btn btn-outline-secondary w-100 h-100 text-start rounded-3 py-1 px-2 izlem-arac-picker__choice izlem-arac-picker__choice--none"
                           for="<?= htmlspecialchars($radiosSuffix, ENT_QUOTES, 'UTF-8') ?>-arac-0">
                        <span class="d-flex align-items-center gap-2">
                            <span class="izlem-arac-picker__mini-icon text-secondary opacity-75"><i class="fa-solid fa-ban"></i></span>
                            <span class="fw-semibold small">Belirtilmedi</span>
                        </span>
                    </label>
                </div>
                <?php foreach ($araclar as $a): ?>
                    <?php
                    $aid = (int) ($a->id ?? 0);
                    if ($aid < 1) {
                        continue;
                    }
                    $rid = htmlspecialchars($radiosSuffix . '-arac-' . $aid, ENT_QUOTES, 'UTF-8');
                    $plaka = htmlspecialchars((string) ($a->plaka ?? ''), ENT_QUOTES, 'UTF-8');
                    $bilgiRaw = trim((string) ($a->arac_bilgisi ?? ''));
                    $bilgiAttr = htmlspecialchars($bilgiRaw, ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <input class="btn-check" type="radio" name="arac" id="<?= $rid ?>"
                               value="<?= $aid ?>" autocomplete="off"<?= $selectedAracId === $aid ? ' checked' : '' ?>>
                        <label class="btn <?= $chipOutline ?> w-100 h-100 rounded-3 py-1 px-2 izlem-arac-picker__choice izlem-arac-picker__choice--plate d-flex align-items-center justify-content-center text-center"
                               for="<?= $rid ?>"
                               <?php if ($bilgiRaw !== ''): ?>data-bs-toggle="tooltip" data-bs-placement="top" title="<?= $bilgiAttr ?>"<?php endif; ?>>
                            <span class="izlem-arac-picker__plate-badge font-monospace fw-bold"><?= $plaka ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="form-text small mb-0 mt-2 text-muted">
                <i class="fa-regular fa-circle-question me-1 opacity-75"></i>
                Plakanın üzerine gelince araç bilgisi (marka/model) gösterilir.
            </p>
        </div>
    <?php endif; ?>
</div>

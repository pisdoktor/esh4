<?php
/**
 * EK-3 konsültasyon — branşa özel başvuru amacı kutucukları.
 *
 * @var int $ek3IstekVid izlem id (input id öneki)
 * @var int $ek3IstekBid branş id
 * @var int[] $ek3IstekSelectedIds seçili istek id listesi
 * @var array $isteklerList
 */
$ek3IstekVid = (int) ($ek3IstekVid ?? 0);
$ek3IstekBid = (int) ($ek3IstekBid ?? 0);
$ek3IstekSelectedIds = $ek3IstekSelectedIds ?? [];
if ($ek3IstekBid < 1 || empty($isteklerList)) {
    return;
}
?>
<div class="row g-2 align-items-stretch">
    <?php foreach ($isteklerList as $it): ?>
        <?php
        $iid = (int) ($it->id ?? 0);
        if ($iid < 1) {
            continue;
        }
        $itAd = htmlspecialchars((string) ($it->istek_adi ?? ''), ENT_QUOTES, 'UTF-8');
        $itTitle = htmlspecialchars((string) ($it->istek_adi ?? ''), ENT_QUOTES, 'UTF-8');
        $isid = 'is' . $ek3IstekVid . '_' . $ek3IstekBid . '_' . $iid;
        ?>
        <div class="col-12 col-sm-6">
            <input class="btn-check js-ek3-istek-choice" type="checkbox" name="istek[<?= $ek3IstekBid ?>][]" id="<?= htmlspecialchars($isid, ENT_QUOTES, 'UTF-8') ?>"
                   value="<?= $iid ?>" autocomplete="off" data-brans-id="<?= $ek3IstekBid ?>"<?= in_array($iid, $ek3IstekSelectedIds, true) ? ' checked' : '' ?>>
            <label class="btn btn-outline-primary w-100 h-100 text-start rounded-3 py-2 px-2 izlem-arac-picker__choice d-flex align-items-center"
                   for="<?= htmlspecialchars($isid, ENT_QUOTES, 'UTF-8') ?>"
                   title="<?= $itTitle ?>">
                <span class="izlem-arac-picker__choice-ek3text izlem-arac-picker__choice-ek3text--istek"><?= $itAd ?></span>
            </label>
        </div>
    <?php endforeach; ?>
</div>

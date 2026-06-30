<?php
/**
 * Kart başlığı + PDF düğmesi.
 *
 * @var string $eshStatsCardTitle
 * @var string $eshStatsPdfBlock
 * @var string|null $eshStatsPdfTitle
 * @var string $eshStatsCardHeadingTag h5|h6
 * @var string|null $eshStatsCardHeaderClass
 */
$eshStatsCardTitle = (string) ($eshStatsCardTitle ?? '');
$eshStatsPdfBlock = trim((string) ($eshStatsPdfBlock ?? 'main'));
$eshStatsCardHeadingTag = (string) ($eshStatsCardHeadingTag ?? 'h6');
if (!in_array($eshStatsCardHeadingTag, ['h5', 'h6'], true)) {
    $eshStatsCardHeadingTag = 'h6';
}
$eshStatsCardHeaderClass = (string) ($eshStatsCardHeaderClass ?? 'card-header bg-white py-3');
$eshStatsPdfTitle = trim((string) ($eshStatsPdfTitle ?? ''));
if ($eshStatsPdfTitle === '') {
    $plainTitle = trim(strip_tags($eshStatsCardTitle));
    $eshStatsPdfTitle = $plainTitle !== '' ? $plainTitle . ' — PDF' : 'PDF indir';
}
?>
<div class="<?= htmlspecialchars($eshStatsCardHeaderClass, ENT_QUOTES, 'UTF-8') ?> d-flex justify-content-between align-items-center gap-2">
    <<?= $eshStatsCardHeadingTag ?> class="mb-0 fw-bold min-w-0"><?= $eshStatsCardTitle ?></<?= $eshStatsCardHeadingTag ?>>
    <?php require __DIR__ . '/stats_block_pdf_btn.php'; ?>
</div>

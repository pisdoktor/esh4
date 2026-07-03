<?php
/** @var object $h */
$mahalleAdi = htmlspecialchars((string) ($h->mahalle_adi ?? ''), ENT_QUOTES, 'UTF-8');
$bolgeNo = (int) ($h->bolge_id ?? 0);
?>
<i class="fa fa-map-marker-alt me-1" style="font-size: 0.7rem;"></i><?= $mahalleAdi !== '' ? $mahalleAdi : '—' ?><?php if ($bolgeNo > 0): ?><span class="text-secondary"> · <?= $bolgeNo ?>. bölge</span><?php endif; ?>

<?php
/** @var object $patient */
/** @var list<object> $branslar */
/** @var array<string, string> $hastalikAdByIcd */
/** @var array<string, string> $hastalikOptions */

$bransById = [];
foreach ($branslar as $__b) {
    $bransById[(int) ($__b->id ?? 0)] = (string) ($__b->bransadi ?? '');
}
unset($__b);

$patientId = (string) ($patient->id ?? '');
$tcRaw = (string) ($patient->tckimlik ?? '');
$patientDisplayName = trim((string) ($patient->isim ?? '') . ' ' . (string) ($patient->soyisim ?? ''));
$pasifEtiket = !empty($patient->pasif) ? ' (dosya kapalı / pasif)' : '';
$hastalikAdByIcd = $hastalikAdByIcd ?? [];
$hastalikOptions = $hastalikOptions ?? [];

$cntRaporlu = 0;
$cntExpired = 0;
$cntExpiring = 0;

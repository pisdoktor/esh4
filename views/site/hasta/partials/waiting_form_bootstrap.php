<?php
declare(strict_types=1);

$hasta = $patient;
$dogumYmd = (string) ($hasta->dogumtarihi ?? '');
$hasta->dtarihi = \App\Helpers\DateHelper::toTrOrEmpty($dogumYmd);
$hasta->yas = \App\Helpers\DateHelper::calculateAge($dogumYmd);

$hasta->cinsiyetText = \App\Helpers\CinsiyetHelper::label($hasta->cinsiyet ?? null);

$ikinciAdresMetni = '';
$decodedDigerAdres = json_decode((string) ($hasta->diger_adres ?? ''), true);
if (is_array($decodedDigerAdres) && !empty($decodedDigerAdres)) {
    $normalized = [];
    foreach ($decodedDigerAdres as $a) {
        if (!is_array($a)) {
            continue;
        }
        $normalized[] = [
            'ilce' => (string) ($a['ilce'] ?? ''),
            'mahalle' => (string) ($a['mahalle'] ?? ''),
            'sokak' => (string) ($a['sokak'] ?? ''),
            'kapino' => (string) ($a['kapino'] ?? ''),
            'adres_aciklama' => (string) ($a['adres_aciklama'] ?? ($a['aciklama'] ?? '')),
        ];
    }

    if (!empty($normalized)) {
        $otherRows = (new \App\Models\Address())->getUserOtherAddresses($normalized);
        if (!empty($otherRows[0]) && is_array($otherRows[0])) {
            $r = $otherRows[0];
            $addr = $r['adres'] ?? null;
            $parts = [];
            if (!empty($addr->mahalle)) { $parts[] = trim((string) $addr->mahalle) . ' MAH.'; }
            if (!empty($addr->sokak)) { $parts[] = trim((string) $addr->sokak) . ' SK./CD.'; }
            if (!empty($addr->kapino)) { $parts[] = 'NO: ' . trim((string) $addr->kapino); }
            if (!empty($addr->ilce)) { $parts[] = '/ ' . trim((string) $addr->ilce); }
            if (!empty($r['adres_aciklama'])) { $parts[] = trim((string) $r['adres_aciklama']); }
            $ikinciAdresMetni = trim(implode(' ', array_filter($parts)));
        }
    }
}
if ($ikinciAdresMetni === '') {
    $ikinciAdresMetni = '....................................';
}

$hekimFormBaslik = \App\Helpers\OperationalSettings::hekimDegerlendirmeFormBaslik();

$aileHekimiAdRaw = trim((string) ($hasta->ailehekimi ?? ''));
$aileHekimiTelRaw = trim((string) ($hasta->ailehekimitel ?? ''));
$aileHekimiAdPdf = $aileHekimiAdRaw !== '' ? $aileHekimiAdRaw : '....................................';
$aileHekimiTelPdf = $aileHekimiTelRaw !== ''
    ? \App\Helpers\ValidationHelper::formatPhoneDisplay($aileHekimiTelRaw)
    : '....................................';

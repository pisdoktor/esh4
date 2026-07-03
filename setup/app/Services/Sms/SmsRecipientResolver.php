<?php
declare(strict_types=1);

namespace App\Services\Sms;

use App\Helpers\OperationalSettings;
use App\Helpers\SmsSettings;
use App\Helpers\ZamanDilimiHelper;
use App\Models\SmsOptout;

final class SmsRecipientResolver
{
    /** @var array<string, array{col:string, name_col?:string, label:string}> */
    private const ROLE_MAP = [
        'hasta' => ['col' => 'ceptel1', 'label' => 'Hasta'],
        'hasta2' => ['col' => 'ceptel2', 'label' => 'Hasta (2)'],
        'bakimveren' => ['col' => 'bakimveren_tel', 'name_col' => 'bakimveren_ad', 'label' => 'Bakım veren'],
        'ailehekimi' => ['col' => 'ailehekimitel', 'name_col' => 'ailehekimi', 'label' => 'Aile hekimi'],
    ];

    /**
     * @param object $hasta esh_hastalar row
     * @param list<string> $roles
     * @param array<string, string> $extraVars segment context
     * @return list<array{hasta_id:int,hasta_ad:string,rol:string,rol_label:string,telefon_norm:string,telefon_mask:string,govde:string,skip_reason:string}>
     */
    public function resolveForPatient(object $hasta, array $roles, string $bodyTemplate, array $extraVars = []): array
    {
        $hastaId = (int) ($hasta->id ?? 0);
        $kurumId = (int) ($hasta->kurum_id ?? 0);
        $hastaAd = trim((string) ($hasta->isim ?? '') . ' ' . (string) ($hasta->soyisim ?? ''));
        $vars = array_merge($this->baseVars($hasta, $extraVars), $extraVars);
        $out = [];
        $optout = new SmsOptout();

        foreach ($roles as $role) {
            if (!isset(self::ROLE_MAP[$role])) {
                continue;
            }
            $map = self::ROLE_MAP[$role];
            $rawPhone = (string) ($hasta->{$map['col']} ?? '');
            $norm = SmsPhoneNormalizer::normalize($rawPhone);
            $rolLabel = SmsSettings::roleLabel($role);
            if ($norm === null) {
                $out[] = [
                    'hasta_id' => $hastaId,
                    'hasta_ad' => $hastaAd,
                    'rol' => $role,
                    'rol_label' => $rolLabel,
                    'telefon_norm' => '',
                    'telefon_mask' => '',
                    'govde' => '',
                    'skip_reason' => 'Geçersiz veya boş telefon',
                ];
                continue;
            }
            if ($optout->isOptedOut($norm, $kurumId)) {
                $out[] = [
                    'hasta_id' => $hastaId,
                    'hasta_ad' => $hastaAd,
                    'rol' => $role,
                    'rol_label' => $rolLabel,
                    'telefon_norm' => $norm,
                    'telefon_mask' => SmsPhoneNormalizer::mask($norm),
                    'govde' => '',
                    'skip_reason' => 'SMS ret listesinde',
                ];
                continue;
            }
            if (isset($hasta->sms_bilgilendirme_onay) && (int) $hasta->sms_bilgilendirme_onay === 0) {
                $out[] = [
                    'hasta_id' => $hastaId,
                    'hasta_ad' => $hastaAd,
                    'rol' => $role,
                    'rol_label' => $rolLabel,
                    'telefon_norm' => $norm,
                    'telefon_mask' => SmsPhoneNormalizer::mask($norm),
                    'govde' => '',
                    'skip_reason' => 'Hasta SMS onayı kapalı',
                ];
                continue;
            }
            $roleVars = $vars;
            if (isset($map['name_col'])) {
                $roleVars['alici_ad'] = trim((string) ($hasta->{$map['name_col']} ?? ''));
                if ($role === 'bakimveren') {
                    $roleVars['bakimveren_ad'] = $roleVars['alici_ad'] !== '' ? $roleVars['alici_ad'] : 'Değerli hasta yakını';
                }
            }
            $govde = SmsTemplateEngine::render($bodyTemplate, $roleVars);
            $out[] = [
                'hasta_id' => $hastaId,
                'hasta_ad' => $hastaAd,
                'rol' => $role,
                'rol_label' => $rolLabel,
                'telefon_norm' => $norm,
                'telefon_mask' => SmsPhoneNormalizer::mask($norm),
                'govde' => $govde,
                'skip_reason' => '',
            ];
        }

        return $out;
    }

    /**
     * @param list<array{hasta_id:int,hasta_ad:string,rol:string,rol_label:string,telefon_norm:string,telefon_mask:string,govde:string,skip_reason:string}> $rows
     * @return list<array{hasta_id:int,hasta_ad:string,rol:string,rol_label:string,telefon_norm:string,telefon_mask:string,govde:string,skip_reason:string}>
     */
    public function dedupeByPhone(array $rows): array
    {
        $seen = [];
        $out = [];
        foreach ($rows as $row) {
            $phone = (string) ($row['telefon_norm'] ?? '');
            if ($phone === '') {
                $out[] = $row;
                continue;
            }
            if (isset($seen[$phone])) {
                $row['skip_reason'] = 'Aynı numara zaten listede';
                $row['govde'] = '';
            } else {
                $seen[$phone] = true;
            }
            $out[] = $row;
        }

        return $out;
    }

    /**
     * @param array<string, string> $extraVars
     * @return array<string, string>
     */
    private function baseVars(object $hasta, array $extraVars): array
    {
        $kurumAdi = OperationalSettings::string('corporate', 'esh_app_name', 'Evde Sağlık');
        $bakimverenAd = trim((string) ($hasta->bakimveren_ad ?? ''));
        if ($bakimverenAd === '') {
            $bakimverenAd = 'Değerli hasta yakını';
        }

        return array_merge([
            'hasta_ad_soyad' => trim((string) ($hasta->isim ?? '') . ' ' . (string) ($hasta->soyisim ?? '')),
            'bakimveren_ad' => $bakimverenAd,
            'ailehekimi' => trim((string) ($hasta->ailehekimi ?? '')),
            'mahalle' => (string) ($extraVars['mahalle'] ?? $hasta->mahalle ?? ''),
            'kurum_adi' => $kurumAdi,
            'tarih' => (string) ($extraVars['tarih'] ?? date('d.m.Y')),
            'zaman_dilimi' => (string) ($extraVars['zaman_dilimi'] ?? ''),
            'islem' => (string) ($extraVars['islem'] ?? ''),
            'sonda_tarih' => (string) ($extraVars['sonda_tarih'] ?? ''),
        ], $extraVars);
    }
}

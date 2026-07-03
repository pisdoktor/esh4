<?php
declare(strict_types=1);

namespace App\Services\Sms;

final class SmsTemplateEngine
{
    public const MAX_LEN = 1600;

    /**
     * Şablon gövdesinde kullanılabilecek {{değişken}} tanımları (UI tooltip + dokümantasyon).
     *
     * @return list<array{key:string, label:string, description:string}>
     */
    public static function placeholderCatalog(): array
    {
        return [
            [
                'key' => 'hasta_ad_soyad',
                'label' => 'Hasta adı soyadı',
                'description' => 'Hasta kartındaki isim ve soyisim birleşimi.',
            ],
            [
                'key' => 'bakimveren_ad',
                'label' => 'Bakım veren adı',
                'description' => 'Bakım veren alanı; boşsa «Değerli hasta yakını» yazılır.',
            ],
            [
                'key' => 'alici_ad',
                'label' => 'Alıcı adı',
                'description' => 'Seçilen alıcı rolüne göre bakım veren veya aile hekimi adı.',
            ],
            [
                'key' => 'ailehekimi',
                'label' => 'Aile hekimi',
                'description' => 'Hasta kartındaki aile hekimi adı.',
            ],
            [
                'key' => 'mahalle',
                'label' => 'Mahalle',
                'description' => 'Hasta adresindeki mahalle bilgisi.',
            ],
            [
                'key' => 'kurum_adi',
                'label' => 'Kurum adı',
                'description' => 'Kurumsal ayarlardaki uygulama / kurum adı.',
            ],
            [
                'key' => 'tarih',
                'label' => 'Tarih',
                'description' => 'gg.aa.yyyy biçiminde; segmentte seçilen tarih veya bugün.',
            ],
            [
                'key' => 'zaman_dilimi',
                'label' => 'Zaman dilimi',
                'description' => 'Sabah, Öğle, Akşam vb. (günün planı ve benzeri segmentlerde).',
            ],
            [
                'key' => 'islem',
                'label' => 'İşlem',
                'description' => 'Planlanan işlem metni (ör. «Sonda değişimi»).',
            ],
            [
                'key' => 'sonda_tarih',
                'label' => 'Sonda tarihi',
                'description' => 'Yaklaşan sonda değişim tarihi (sonda segmenti).',
            ],
        ];
    }

    public static function placeholderTooltipHtml(): string
    {
        $rows = '';
        foreach (self::placeholderCatalog() as $item) {
            $key = htmlspecialchars((string) ($item['key'] ?? ''), ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8');
            $desc = htmlspecialchars((string) ($item['description'] ?? ''), ENT_QUOTES, 'UTF-8');
            $rows .= '<tr class="esh-sms-sablon-vars-row">'
                . '<td class="esh-sms-sablon-vars-key pe-2 align-top"><code>{{' . $key . '}}</code></td>'
                . '<td class="esh-sms-sablon-vars-desc align-top">'
                . '<span class="fw-semibold d-block">' . $label . '</span>'
                . '<span class="text-muted">' . $desc . '</span>'
                . '</td></tr>';
        }

        return '<div class="esh-sms-sablon-vars-tt">'
            . '<p class="mb-2">Mesajda çift süslü parantez içinde yazın; gönderimde gerçek değerle değiştirilir. Tanımsız değişkenler boş bırakılır.</p>'
            . '<table class="table table-sm table-borderless mb-0"><tbody>' . $rows . '</tbody></table>'
            . '</div>';
    }

    /**
     * @param array<string, string> $vars
     */
    public static function render(string $template, array $vars): string
    {
        $out = $template;
        foreach ($vars as $key => $value) {
            $out = str_replace('{{' . $key . '}}', $value, $out);
        }
        $out = preg_replace('/\{\{[a-z0-9_]+\}\}/i', '', $out) ?? $out;
        $out = trim(preg_replace('/\s+/u', ' ', $out) ?? $out);
        if (mb_strlen($out) > self::MAX_LEN) {
            $out = mb_substr($out, 0, self::MAX_LEN);
        }

        return $out;
    }

    public static function smsPartCount(string $text): int
    {
        $len = mb_strlen($text);
        if ($len <= 0) {
            return 0;
        }
        if ($len <= 160) {
            return 1;
        }

        return (int) ceil($len / 153);
    }
}

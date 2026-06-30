<?php
namespace App\Helpers;

/**
 * pdfMake ile eski ESH «BAŞVURU FORMU / EK-3» çıktısı (tek sayfa).
 */
final class Ek3PdfHelper {

    /**
     * @param object $hasta loadForEk3 ile gelen satır (ilceadi, kapino_adi, guvenceadi, …)
     * @param string|null $muracaatTarihiTr İmza satırı tarihi (d.m.Y); boşsa bugün
     */
    public static function buildDefinition(object $hasta, string $hastaliklarStr, string $isteklerMetni, ?string $muracaatTarihiTr = null): array {
        $muracaatTarih = trim((string) $muracaatTarihiTr);
        if ($muracaatTarih === '') {
            $muracaatTarih = DateHelper::toTrDotOrEmpty(date('Y-m-d'));
        }
        $dt = DateHelper::toTrOrEmpty((string) ($hasta->dogumtarihi ?? ''));
        $yas = DateHelper::calculateAge((string) ($hasta->dogumtarihi ?? ''));
        $guv = trim((string) ($hasta->guvenceadi ?? ''));
        $yup = trim((string) ($hasta->yupasno ?? ''));
        $guvMetni = $guv;
        if ($guv === 'YUPAS' && $yup !== '') {
            $guvMetni .= ' - YUPAS NO:' . $yup;
        }

        $kapinoMetin = trim((string) ($hasta->kapino_adi ?? ''));

        return [
            'pageSize' => 'A4',
            'pageMargins' => [40, 40, 40, 40],
            'content' => [
                [
                    'text' => OperationalSettings::ek3FormBaslik(),
                    'alignment' => 'center',
                    'bold' => true,
                    'fontSize' => 13,
                ],
                [
                    'table' => [
                        'widths' => [100, '*', 100, '*'],
                        'body' => [
                            [['text' => 'KİMLİK BİLGİLERİ:', 'bold' => true, 'decoration' => 'underline', 'colSpan' => 4], '', '', ''],
                            [['text' => 'Hasta TC Kimlik:', 'bold' => true], ': ' . ((string) ($hasta->tckimlik ?? '')), ['text' => 'İlçe:', 'bold' => true], ': ' . ((string) ($hasta->ilceadi ?? ''))],
                            [['text' => 'Hastanın Adı:', 'bold' => true], ': ' . ((string) ($hasta->isim ?? '')), ['text' => 'Mahalle:', 'bold' => true], ': ' . ((string) ($hasta->mahalleadi ?? ''))],
                            [['text' => 'Hastanın Soyadı:', 'bold' => true], ': ' . ((string) ($hasta->soyisim ?? '')), ['text' => 'Cadde/Sokak:', 'bold' => true], ': ' . ((string) ($hasta->sokakadi ?? ''))],
                            [['text' => 'Doğum Tarihi:', 'bold' => true], ': ' . $dt . ' (' . $yas . ' yaş)', ['text' => 'Kapı No:', 'bold' => true], ': ' . $kapinoMetin],
                            [['text' => 'Anne Adı:', 'bold' => true], ': ' . ((string) ($hasta->anneAdi ?? '')), ['text' => 'Telefon 1:', 'bold' => true], ': ' . ((string) ($hasta->ceptel1 ?? ''))],
                            [['text' => 'Baba Adı:', 'bold' => true], ': ' . ((string) ($hasta->babaAdi ?? '')), ['text' => 'Telefon 2:', 'bold' => true], ': ' . ((string) ($hasta->ceptel2 ?? ''))],
                            [['text' => 'Boy:', 'bold' => true], ': ' . ((string) ($hasta->boy ?? '')) . ' cm', ['text' => 'Kilo:', 'bold' => true], ': ' . ((string) ($hasta->kilo ?? '')) . ' kg'],
                        ],
                    ],
                    'layout' => 'noBorders',
                ],
                [
                    'text' => [
                        ['text' => "\nGüvence Durumu: ", 'bold' => true],
                        $guvMetni,
                        "\n\n",
                        ['text' => 'HASTALIKLARI: ', 'bold' => true],
                        $hastaliklarStr,
                    ],
                    'margin' => [0, 0, 0, 10],
                ],
                [
                    'stack' => [
                        [
                            'text' => [
                                ['text' => 'BAŞVURU AMACI : ', 'bold' => true],
                                ' ' . $isteklerMetni,
                            ],
                        ],
                    ],
                    'margin' => [0, 15, 0, 15],
                ],
                [
                    'stack' => [
                        ['text' => 'HASTALIĞI HAKKINDA BİLGİ (Tanı/Tedavi):', 'bold' => true],
                        ['text' => str_repeat('.', 160), 'margin' => [0, 5, 0, 0]],
                        ['text' => str_repeat('.', 160), 'margin' => [0, 5, 0, 0]],
                    ],
                    'margin' => [0, 10, 0, 15],
                ],
                [
                    'text' => [
                        ['text' => "SÜREKLİ KULLANDIĞI İLAÇ/TIBBİ CİHAZ/ORTEZ/PROTEZ:\n", 'bold' => true],
                        str_repeat(".", 160) . "\n",
                        str_repeat(".", 160) . "\n",
                        str_repeat(".", 160) . "\n",
                        str_repeat(".", 160) . "\n",
                        str_repeat(".", 160) . "\n",
                        str_repeat(".", 160) . "\n",
                        str_repeat(".", 160) . "\n",
                        str_repeat(".", 160) . "\n",
                        str_repeat(".", 160) . "\n",
                        "DİŞ TEDAVİSİ İHTİYACI (var ise) :" . str_repeat('.', 80) . "\n\n",
                        "Yukarıda açık kimliği, adres ve hastalık bilgileri olan şahsın evde sağlık hizmetine ihtiyacı vardır.\nTarafınızdan değerlendirilmesi arz olunur.\n\n",
                    ],
                    'alignment' => 'justify',
                ],
                [
                    'columns' => [
                        ['width' => '*', 'text' => 'Müracaat Yakınlık Derecesi: .................'],
                        ['width' => 'auto', 'stack' => [$muracaatTarih, 'İmza: .....................'], 'alignment' => 'right'],
                    ],
                ],
                [
                    'text' => [
                        ['text' => "\nDEĞERLENDİRME SONUCU:\n", 'bold' => true],
                        str_repeat('.', 160) . "\n",
                        str_repeat('.', 160) . "\n",
                        str_repeat('.', 160) . "\n\n",
                    ],
                ],
                [
                    'columns' => [
                        [
                            'width' => '*',
                            'stack' => [['text' => 'Değerlendiren Tabip', 'bold' => true], 'Kaşe/İmza'],
                            'alignment' => 'left',
                        ],
                        [
                            'width' => 'auto',
                            'stack' => [['text' => 'ONAY', 'bold' => true], 'Kurum/Kuruluş Amiri', 'Kaşe/imza/mühür'],
                            'alignment' => 'center',
                        ],
                    ],
                ],
            ],
            'defaultStyle' => ['fontSize' => 11],
        ];
    }

    /**
     * Birden fazla EK-3 sayfasını tek pdfMake docDefinition içinde birleştirir.
     *
     * @param array<int, array> $pageDefinitions buildDefinition çıktıları
     */
    public static function buildMultiPageDefinition(array $pageDefinitions): array {
        $pages = array_values(array_filter($pageDefinitions, static function ($page) {
            return is_array($page) && !empty($page['content']) && is_array($page['content']);
        }));
        if ($pages === []) {
            return [
                'pageSize' => 'A4',
                'pageMargins' => [40, 40, 40, 40],
                'content' => [],
                'defaultStyle' => ['fontSize' => 11],
            ];
        }
        if (count($pages) === 1) {
            return $pages[0];
        }

        $content = [];
        foreach ($pages as $index => $page) {
            if ($index > 0) {
                $content[] = ['text' => '', 'pageBreak' => 'before'];
            }
            foreach ($page['content'] as $block) {
                $content[] = $block;
            }
        }

        $first = $pages[0];
        return [
            'pageSize' => $first['pageSize'] ?? 'A4',
            'pageMargins' => $first['pageMargins'] ?? [40, 40, 40, 40],
            'content' => $content,
            'defaultStyle' => $first['defaultStyle'] ?? ['fontSize' => 11],
        ];
    }
}

<script>
var dd = {
    pageSize: 'A4',
    pageMargins: [30, 30, 30, 30],
    footer: {
        columns: [
            {
                text: 'BELGE NO:ES.FR.06  YAYIN TARİHİ:13.01.2026  REVİZYON TARİHİ VE NO:14.01.2026',
                fontSize: 8,
                alignment: 'center',
                bold: true,
                margin: [0, 10, 0, 0]
            }
        ]
    },
    content: [
        // ==========================================
        // 1. SAYFA: HEKİM DEĞERLENDİRME FORMU
        // ==========================================

        // --- E-RAPOR UYARISI ---
        {
            text: '<?= !empty($hasta->erapor) ? "E-RAPOR HASTASI" : "" ?>',
            fontSize: 12,
            bold: true,
            alignment: 'right',
            color: 'red',
            decoration: 'underline',
            margin: [0, 0, 0, -15]
        },

        // --- BAŞLIK ---
        {
            text: <?= json_encode($hekimFormBaslik, JSON_UNESCAPED_UNICODE) ?>,
            style: 'header',
            alignment: 'center',
            bold: true,
            fontSize: 12,
            margin: [0, 10, 0, 15]
        },

        // --- KİMLİK BİLGİLERİ ---
        {
            style: 'tableExample',
            table: {
                widths: ['auto', '*', 'auto', '*'],
                body: [
                    [
                        {text: 'KİMLİK BİLGİLERİ:', style: 'tableHeader', bold:true, colSpan: 2, decoration: 'underline'},
                        {},
                        {text: 'Kayıt Tarihi: <?= htmlspecialchars(\App\Helpers\DateHelper::toTrDotOrEmpty($hasta->kayittarihi ?? ''), ENT_QUOTES, "UTF-8") ?>', bold:true, alignment: 'right', colSpan: 2},
                        {}
                    ],
                    [{text: 'TC Kimlik No', bold: true}, ': <?= htmlspecialchars((string) ($hasta->tckimlik ?? ""), ENT_QUOTES, "UTF-8") ?>', {text: 'İLÇE', bold:true}, ': <?= htmlspecialchars((string) ($hasta->ilce_adi ?? ""), ENT_QUOTES, "UTF-8") ?>'],
                    [{text: 'Adı', bold:true}, ': <?= htmlspecialchars((string) ($hasta->isim ?? ""), ENT_QUOTES, "UTF-8") ?>', {text: 'MAHALLE', bold:true}, ': <?= htmlspecialchars((string) ($hasta->mahalle_adi ?? ""), ENT_QUOTES, "UTF-8") ?>'],
                    [{text: 'Soyadı', bold:true}, ': <?= htmlspecialchars((string) ($hasta->soyisim ?? ""), ENT_QUOTES, "UTF-8") ?>', {text: 'CADDE/SOKAK', bold:true}, ': <?= htmlspecialchars((string) ($hasta->sokak_adi ?? ""), ENT_QUOTES, "UTF-8") ?>'],
                    [{text: 'D.Tarihi/Cinsiyet', bold:true}, ': <?= htmlspecialchars((string) $hasta->dtarihi, ENT_QUOTES, "UTF-8") ?> / <?= (int) ($hasta->yas ?? 0) ?> (<?= $hasta->cinsiyetText ?>)', {text: 'KAPI NO', bold:true}, ': <?= htmlspecialchars((string) ($hasta->kapino_adi ?? ""), ENT_QUOTES, "UTF-8") ?>'],
                    [{text: 'Anne Adı', bold:true}, ': <?= htmlspecialchars(strlen((string) ($hasta->anneAdi ?? "")) > 3 ? (string) $hasta->anneAdi : "....................................", ENT_QUOTES, "UTF-8") ?>', {text: 'KAT/DAİRE', bold:true}, ': ....................................'],
                    [{text: 'AİLE HEKİMİ', bold:true}, ': <?= htmlspecialchars($aileHekimiAdPdf, ENT_QUOTES, "UTF-8") ?>', {text: 'AİLE HEK. TEL.', bold:true}, ': <?= htmlspecialchars($aileHekimiTelPdf, ENT_QUOTES, "UTF-8") ?>'],
                    [{text: 'Baba Adı', bold:true}, ': <?= htmlspecialchars(strlen((string) ($hasta->babaAdi ?? "")) > 3 ? (string) $hasta->babaAdi : "....................................", ENT_QUOTES, "UTF-8") ?>', {text: '2. ADRES', bold:true}, ': <?= addslashes($ikinciAdresMetni); ?>'],
                    [{text: 'TELEFON 1', bold:true}, ': <?= htmlspecialchars((string) ($hasta->ceptel1 ?? ""), ENT_QUOTES, "UTF-8") ?>', {text: '<?= $ikinciAdresMetni ? '' : '.............................................................................'; ?>', colSpan: 2}, {}],
                    [{text: 'TELEFON 2', bold:true}, ': <?= htmlspecialchars((string) ($hasta->ceptel2 ?? ""), ENT_QUOTES, "UTF-8") ?>', {text: '.............................................................................', colSpan: 2}, {}],
                    [{text: 'Boy - Kilo', bold:true}, ': ...........cm - ...........kg', {text: 'Sağlık Güvencesi', bold:true}, ': ..................................'],
                    [{text: 'TA - Ateş', bold:true}, ': ........./.......... - .......C', {text: 'SAT - Nabız - KŞ', bold:true}, ': ......... - ......../dk - ..........'],
                ]
            },
            layout: 'noBorders',
            fontSize: 10,
            margin: [0, 0, 0, 10]
        },

        // --- HASTALIKLARI ---
        {
            text: [
                {text: 'HASTALIKLARI: ', bold: true, fontSize: 10},
                {text: '..............................................................................................................................................................................................................................................................................................................................', fontSize: 10}
            ],
            margin: [0, 5, 0, 10]
        },

        // --- SİSTEM MUAYENESİ ---
        {
            style: 'tableExample',
            table: {
                widths: ['auto', '*', 'auto', 'auto'],
                body: [
                    [
                        {text: 'SİSTEM MUAYENESİ', style: 'tableHeader', bold:true, colSpan: 2, decoration: 'underline'},
                        {},
                        {text: 'ÖZELLİKLİ DURUMLARI', style: 'tableHeader', bold:true, colSpan: 2, decoration: 'underline'},
                        {}
                    ],
                    [
                        {text: 'Solunum', bold: true}, ': .....................................................',
                        {text: 'O2 Bağımlı', bold:true}, {text: '[  ]', bold:true}
                    ],
                    [
                        {text: 'Kardiyovasküler', bold:true}, ': .....................................................',
                        {text: 'PEG Takılı', bold:true}, {text: '[  ]', bold:true}
                    ],
                    [
                        {text: 'Sindirim', bold:true}, ': .....................................................',
                        {text: 'Port Takılı', bold:true}, {text: '[  ]', bold:true}
                    ],
                    [
                        {text: 'Nörolojik', bold:true}, ': .....................................................',
                        {text: 'NG Takılı', bold:true}, {text: '[  ]', bold:true}
                    ],
                    [
                        {text: 'Ürogenital', bold:true}, ': .....................................................',
                        {text: 'Mesane Sondası', bold:true}, {text: '[  ]', bold:true}
                    ],
                    [
                        {text: 'Ekstremiteler', bold:true}, ': .....................................................',
                        {text: 'Mama Kullanımı', bold:true}, {text: '[  ]', bold:true}
                    ],
                    [
                        {text: 'Diğer', bold:true}, ': .....................................................',
                        {text: 'Alt Bezi Kullanımı', bold:true}, {text: '[  ]', bold:true}
                    ],
                    [
                        {text: '', bold:true},
                        {text: '', bold:true},
                        {text: 'Hasta Yatağı Var mı?', bold:true}, {text: '[  ]', bold:true}
                    ]
                ]
            },
            layout: 'noBorders',
            fontSize: 10,
            margin: [0, 0, 0, 10]
        },

        // --- BAĞIMLILIK DURUMU ---
        {
            text: [
                {text: 'Bağımlılık Durumu: ', bold:true},
                '   [  ] Bağımsız      [  ] Yarı Bağımlı      [  ] Tam Bağımlı'
            ],
            fontSize: 10,
            margin: [0, 5, 0, 20]
        },

        // --- BARTHEL INDEKSI ÖZETİ ---
        {
            text: [
                 {text: 'Barthel İndeksi Puanı: ', bold:true}, '....................'
            ],
            fontSize: 10,
            margin: [0, 0, 0, 10]
        },

        // --- İLAÇLAR ---
        { text: 'SÜREKLİ KULLANDIĞI İLAÇLAR:', bold: true, fontSize: 10, margin: [0, 0, 0, 2] },
        {
            style: 'tableExample',
            table: {
                widths: ['50%', '50%'],
                heights: [14, 14, 14, 14, 14],
                body: [
                    ['', ''],
                    ['', ''],
                    ['', ''],
                    ['', ''],
                    ['', '']
                ]
            },
            fontSize: 10,
            margin: [0, 0, 0, 10]
        },

        // --- SONUÇ ---
        { text: 'DEĞERLENDİRME SONUCU:', bold: true, fontSize: 10, decoration: 'underline', margin: [0, 0, 0, 5] },
        {
            columns: [
                { width: 'auto', text: 'Takibe Alındı [  ]', bold: true },
                { width: 20, text: '' },
                { width: 'auto', text: 'Geçici Takibe Alındı [  ]', bold: true },
                { width: 20, text: '' },
                { width: 'auto', text: 'Takibe Alınmadı [  ]', bold: true }
            ],
            fontSize: 10,
            margin: [0, 0, 0, 25]
        },

        // --- İMZA ---
        {
            columns: [
                { width: '*', text: '' },
                {
                    width: 'auto',
                    stack: [
                        { text: 'Ziyaret Tarihi: ....../......./.........', alignment: 'right', margin: [0, 0, 0, 15] },
                        { text: 'Değerlendiren', alignment: 'center', bold: true },
                        { text: 'Kaşe/İmza', alignment: 'center' }
                    ]
                }
            ],
            fontSize: 10
        },


        // ==========================================
        // 2. SAYFA: BARTHEL INDEKSİ FORMU
        // ==========================================

        { text: '', pageBreak: 'before' },

        {
            text: 'BARTHEL GÜNLÜK YAŞAM AKTİVİTESİ İNDEKSİ',
            style: 'header',
            alignment: 'center',
            bold: true,
            fontSize: 14,
            margin: [0, 20, 0, 20]
        },

        // --- HASTA BİLGİSİ VE TARİH ---
        {
            columns: [
                {
                    text: 'Hasta Adı Soyadı: <?= addslashes((string) (($hasta->isim ?? "") . " " . ($hasta->soyisim ?? ""))) ?>',
                    bold: true,
                    fontSize: 11,
                    width: '*'
                },
                {
                    text: 'Değerlendirme Tarihi: ....../......./..........',
                    bold: true,
                    fontSize: 11,
                    alignment: 'right',
                    width: 'auto'
                }
            ],
            margin: [0, 0, 0, 10]
        },

        // --- BARTHEL TABLOSU ---
        {
            style: 'tableExample',
            table: {
                widths: ['25%', '60%', '15%'],
                headerRows: 1,
                body: [
                    // BAŞLIKLAR
                    [
                        { text: 'FONKSİYON', style: 'tableHeader', fillColor: '#eeeeee', bold: true, alignment: 'center' },
                        { text: 'AÇIKLAMA / PUANLAMA KRİTERLERİ', style: 'tableHeader', fillColor: '#eeeeee', bold: true },
                        { text: 'PUAN', style: 'tableHeader', fillColor: '#eeeeee', bold: true, alignment: 'center' }
                    ],

                    // 1. BESLENME
                    [
                        { text: 'BESLENME', bold: true, alignment: 'center', margin:[0, 10, 0, 0] },
                        {
                            stack: [
                                '0 = Tam bağımlı',
                                '5 = Yardım gerekir (Örn: Ekmeğine yağ sürmek için)',
                                '10 = Bağımsız'
                            ], fontSize: 10
                        },
                        { text: '', margin: [0, 10, 0, 0] }
                    ],

                    // 2. BANYO
                    [
                        { text: 'BANYO', bold: true, alignment: 'center', margin:[0, 5, 0, 0] },
                        {
                            stack: [
                                '0 = Bağımlı',
                                '5 = Bağımsız (Duşa/küvete girip çıkabilir, liflenebilir)'
                            ], fontSize: 10
                        },
                        { text: '', margin: [0, 5, 0, 0] }
                    ],

                    // 3. KİŞİSEL BAKIM
                    [
                        { text: 'KİŞİSEL BAKIM', bold: true, alignment: 'center', margin:[0, 5, 0, 0] },
                        {
                            stack: [
                                '0 = Yardım gerekir',
                                '5 = Bağımsız (El-yüz yıkama, diş fırçalama, tıraş olma)'
                            ], fontSize: 10
                        },
                        { text: '', margin: [0, 5, 0, 0] }
                    ],

                    // 4. GİYİNME
                    [
                        { text: 'GİYİNME', bold: true, alignment: 'center', margin:[0, 10, 0, 0] },
                        {
                            stack: [
                                '0 = Bağımlı',
                                '5 = Yarı bağımlı (Yardım gerekir ancak işin yarısını yapabilir)',
                                '10 = Bağımsız (Düğme ilikleme, fermuar çekme dahil)'
                            ], fontSize: 10
                        },
                        { text: '', margin: [0, 10, 0, 0] }
                    ],

                    // 5. BAĞIRSAK
                    [
                        { text: 'BAĞIRSAK KONTROLÜ', bold: true, alignment: 'center', margin:[0, 10, 0, 0] },
                        {
                            stack: [
                                '0 = İnkontinans (Tutamıyor)',
                                '5 = Ara sıra kazalar olur (Haftada 1 kez)',
                                '10 = Kontinans (Tutabiliyor)'
                            ], fontSize: 10
                        },
                        { text: '', margin: [0, 10, 0, 0] }
                    ],

                    // 6. MESANE
                    [
                        { text: 'MESANE KONTROLÜ', bold: true, alignment: 'center', margin:[0, 10, 0, 0] },
                        {
                            stack: [
                                '0 = İnkontinans (Tutamıyor veya sonda takılı ve bakamıyor)',
                                '5 = Ara sıra kazalar olur (Günde en çok 1 kez)',
                                '10 = Kontinans (Tutabiliyor - Sondası varsa kendi bakımını yapıyor)'
                            ], fontSize: 10
                        },
                        { text: '', margin: [0, 10, 0, 0] }
                    ],

                    // 7. TUVALET KULLANIMI
                    [
                        { text: 'TUVALET KULLANIMI', bold: true, alignment: 'center', margin:[0, 10, 0, 0] },
                        {
                            stack: [
                                '0 = Bağımlı',
                                '5 = Yardım gerekir (Giysileri indirme-kaldırma, temizlenme)',
                                '10 = Bağımsız'
                            ], fontSize: 10
                        },
                        { text: '', margin: [0, 10, 0, 0] }
                    ],

                    // 8. TEKERLEKLİ SANDALYE TRANSFERİ
                    [
                        { text: 'TRANSFER (Yatak-Sandalye)', bold: true, alignment: 'center', margin:[0, 10, 0, 0] },
                        {
                            stack: [
                                '0 = Tam bağımlı (Denge yok, kaldıramıyor)',
                                '5 = Oturabilir (Yoğun fiziksel yardım gerekli - 1 veya 2 kişi)',
                                '10 = Minör yardım (Sözel veya hafif fiziksel yardım)',
                                '15 = Bağımsız'
                            ], fontSize: 10
                        },
                        { text: '', margin: [0, 10, 0, 0] }
                    ],

                    // 9. MOBİLİTE
                    [
                        { text: 'MOBİLİTE (Hareket)', bold: true, alignment: 'center', margin:[0, 10, 0, 0] },
                        {
                            stack: [
                                '0 = Hareketsiz',
                                '5 = Tekerlekli sandalye ile bağımsız (Köşeleri dönebilir, 50m gidebilir)',
                                '10 = Yürürken yardım gerekir (Fiziksel veya sözel)',
                                '15 = Bağımsız (50m yürüyebilir - baston vb. kullanabilir)'
                            ], fontSize: 10
                        },
                        { text: '', margin: [0, 10, 0, 0] }
                    ],

                    // 10. MERDİVEN
                    [
                        { text: 'MERDİVEN ÇIKMA', bold: true, alignment: 'center', margin:[0, 10, 0, 0] },
                        {
                            stack: [
                                '0 = Yapamaz',
                                '5 = Yardım gerekir (Fiziksel veya sözel)',
                                '10 = Bağımsız'
                            ], fontSize: 10
                        },
                        { text: '', margin: [0, 10, 0, 0] }
                    ]
                ]
            },
            layout: {
                hLineWidth: function (i, node) { return 1; },
                vLineWidth: function (i, node) { return 1; },
                hLineColor: function (i, node) { return '#aaa'; },
                vLineColor: function (i, node) { return '#aaa'; },
                paddingTop: function(i, node) { return 5; },
                paddingBottom: function(i, node) { return 5; }
            },
            fontSize: 10
        },

        // --- PUANLAMA SONUCU VE AÇIKLAMALAR ---
        {
            text: [
                { text: '\nTOPLAM PUAN: ............. / 100', fontSize: 14, bold: true, alignment: 'right' }
            ]
        },

        {
            style: 'tableExample',
            margin: [0, 20, 0, 0],
            table: {
                widths: ['*'],
                body: [
                    [
                        {
                            text: 'DEĞERLENDİRME ANAHTARI:',
                            bold: true, decoration: 'underline', fontSize: 11, margin: [0, 0, 0, 5]
                        }
                    ],
                    [
                        {
                            ul: [
                                '0 - 20 Puan  : Tam Bağımlı',
                                '21 - 61 Puan : İleri Derecede Bağımlı',
                                '62 - 90 Puan : Orta Derecede Bağımlı',
                                '91 - 99 Puan : Hafif Derecede Bağımlı',
                                '100 Puan     : Tam Bağımsız'
                            ],
                            fontSize: 10
                        }
                    ]
                ]
            },
            layout: 'noBorders'
        },

        // --- İMZA 2. SAYFA ---
        {
            text: '\n\n',
        },
        {
            columns: [
                { width: '*', text: '' },
                {
                    width: 'auto',
                    stack: [
                        { text: 'Değerlendiren Personel', alignment: 'center', bold: true },
                        { text: 'Kaşe/İmza', alignment: 'center' }
                    ]
                }
            ],
            fontSize: 10
        }

    ],
    styles: {
        header: {
            fontSize: 12,
            bold: true,
            alignment: 'center'
        },
        tableExample: {
            margin: [0, 2, 0, 2]
        },
        tableHeader: {
            bold: true,
            fontSize: 10,
            color: 'black'
        }
    }
};
</script>

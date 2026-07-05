<?php
$admin_list_title = 'Ekip Yönetimi';
$admin_list_icon = 'fa-solid fa-person-cane';
$admin_list_card_extra_classes = 'border-top border-primary border-3';
$admin_list_skip_body_wrapper = true;
ob_start();
?>
<div class="btn-group">
                        <button type="button" class="btn btn-danger btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa fa-file-pdf me-1"></i> PDF ÇIKTI
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li><a class="dropdown-item" href="javascript:void(0)" data-esh-call="preparePDF" data-esh-call-arg="gunluk">Günlük Plan</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" data-esh-call="preparePDF" data-esh-call-arg="haftalik">Haftalık Plan</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0)" data-esh-call="preparePDF" data-esh-call-arg="aylik">Aylık Plan</a></li>
                        </ul>
                    </div>
                    <a href="<?= htmlspecialchars(esh_url('Ekip', 'edit', ['tarih' => date('Y-m-d')]), ENT_QUOTES, "UTF-8") ?>" class="btn btn-success btn-sm ms-1">
                        <i class="fa fa-plus me-1"></i> YENİ PLAN
                    </a>
<?php
$admin_list_actions = trim(ob_get_clean());
include dirname(__DIR__, 2) . '/partials/admin/list_page_open.php';
?>

        <div class="table-responsive">
            <table class="table table-sm table-hover table-striped align-middle mb-0">
                <thead>
                    <tr class="table-light">
                        <?= \App\Helpers\UIHelper::renderSortTh('Tarih', 'tarih', $ordering, $eshSortCfg) ?>
                        <?= \App\Helpers\UIHelper::renderSortTh('Ekip sayısı', 'ekip_sayisi', $ordering, $eshSortCfg) ?>
                        <th>Saatler</th>
                        <th>Personel özeti</th>
                        <th class="text-center">İşlemler</th>
                    </tr>
                </thead>
                <tbody id="esh-ekip-list-tbody"
                       data-esh-fetch-url="<?= htmlspecialchars($indexRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <tr class="esh-ekip-list-loading-row">
                        <td colspan="5" class="border-0 py-5 text-center text-muted">
                            <div class="d-flex flex-column align-items-center gap-2">
                                <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                <span>Ekip listesi yükleniyor…</span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

<?php include dirname(__DIR__, 2) . '/partials/admin/list_page_close.php'; ?>

<script<?= esh_csp_nonce_attr() ?>>
(function () {
    var pdfJsonUrl = esh_url('Ekip', 'getEkiplerJSON');
    var todayYmd = '<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>';

    function generatePDF(data, title) {
        if (typeof pdfMake === 'undefined') {
            alert('PDF kütüphanesi yüklenemedi. Sayfayı yenileyin.');
            return;
        }
        if (!data || data.length === 0) {
            alert('Yazdırılacak veri bulunamadı!');
            return;
        }

        var body = [[
            { text: 'TARİH', style: 'tableHeader' },
            { text: 'VARDİYA', style: 'tableHeader' },
            { text: 'SAAT', style: 'tableHeader' },
            { text: 'EKİP', style: 'tableHeader' },
            { text: 'PERSONEL LİSTESİ', style: 'tableHeader' }
        ]];

        var sonTarih = '';
        data.forEach(function (row) {
            var v = row.vardiya_label || row.vardiya || '';
            if (sonTarih !== '' && sonTarih !== row.tarih) {
                body.push([
                    { text: ' ', colSpan: 5, fillColor: '#34495e', margin: [0, 2, 0, 2] },
                    {}, {}, {}, {}
                ]);
            }
            body.push([
                { text: row.tarih, alignment: 'center' },
                { text: v, alignment: 'center' },
                { text: row.saat, alignment: 'center' },
                { text: row.ekip, alignment: 'center' },
                { text: row.personeller || '' }
            ]);
            sonTarih = row.tarih;
        });

        var docDefinition = {
            pageSize: 'A4',
            pageOrientation: 'landscape',
            pageMargins: [30, 60, 30, 40],
            header: function () {
                return {
                    text: String(title).toUpperCase(),
                    style: 'pageHeaderStyle',
                    margin: [30, 20, 0, 0]
                };
            },
            content: [
                { text: 'OPERASYONEL EKİP PERSONEL PLANI', style: 'mainTitle' },
                {
                    table: {
                        headerRows: 1,
                        widths: [75, 75, 60, 75, '*'],
                        body: body
                    },
                    layout: {
                        hLineWidth: function (i, node) {
                            return (i === 0 || i === node.table.body.length) ? 2 : 1;
                        },
                        vLineWidth: function () {
                            return 1;
                        },
                        hLineColor: function () {
                            return '#aaa';
                        },
                        vLineColor: function () {
                            return '#aaa';
                        }
                    }
                }
            ],
            styles: {
                pageHeaderStyle: { fontSize: 9, bold: true, color: '#7f8c8d' },
                mainTitle: { fontSize: 18, bold: true, alignment: 'center', margin: [0, 0, 0, 20] },
                tableHeader: { bold: true, fontSize: 11, color: 'white', fillColor: '#2c3e50', alignment: 'center', margin: [0, 5, 0, 5] }
            },
            defaultStyle: { fontSize: 10 }
        };

        pdfMake.createPdf(docDefinition).download(title + '.pdf');
    }

    window.preparePDF = function (mod) {
        var title = '';
        if (mod === 'gunluk') {
            title = 'Gunluk_Ekip_Plani_' + todayYmd;
        } else if (mod === 'haftalik') {
            title = 'Haftalik_Ekip_Plani_' + todayYmd;
        } else if (mod === 'aylik') {
            title = 'Aylik_Ekip_Plani_' + todayYmd;
        } else {
            title = 'Ekip_Plani_' + todayYmd;
        }

        jQuery.getJSON(pdfJsonUrl + '&mod=' + encodeURIComponent(mod) + '&date=' + encodeURIComponent(todayYmd))
            .done(function (response) {
                if (response && response.length > 0) {
                    generatePDF(response, title);
                } else {
                    alert('Bu aralık için veri bulunamadı.');
                }
            })
            .fail(function () {
                alert('PDF verisi alınamadı.');
            });
    };
})();
</script>
</div>
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-bottom py-3">
            <form class="row g-3 align-items-end" method="get" action="<?= htmlspecialchars(esh_form_action('Stats', 'aylikTekIzlemliler'), ENT_QUOTES, 'UTF-8') ?>">
                <?= esh_form_route_hiddens('Stats', 'aylikTekIzlemliler') ?>
                <?php
                $eshAylikTekIzlemMonthOptions = [];
                foreach ($turkce_aylar as $ayNo => $ayAdi) {
                    $eshAylikTekIzlemMonthOptions[] = \App\Helpers\FormHelper::makeOption((string) $ayNo, $ayAdi);
                }
                echo \App\Helpers\FormHelper::fieldSelect('month', 'Ay', $eshAylikTekIzlemMonthOptions, (string) (int) $month, [
                    'col' => 'col-12 col-md-6 col-lg-3',
                    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                    'class' => 'form-select-sm shadow-sm',
                    'tomSelect' => false,
                ]);
                $eshAylikTekIzlemYearOptions = [];
                for ($y = $yearMax; $y >= $yearMin; $y--) {
                    $eshAylikTekIzlemYearOptions[] = \App\Helpers\FormHelper::makeOption((string) $y, (string) $y);
                }
                echo \App\Helpers\FormHelper::fieldSelect('year', 'Yıl', $eshAylikTekIzlemYearOptions, (string) (int) $year, [
                    'col' => 'col-12 col-md-6 col-lg-3',
                    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                    'class' => 'form-select-sm shadow-sm',
                    'tomSelect' => false,
                ]);
                $eshAylikTekIzlemIlceOptions = [\App\Helpers\FormHelper::makeOption('0', 'Tüm ilçeler')];
                foreach ($ilceler as $ic) {
                    $eshAylikTekIzlemIlceOptions[] = \App\Helpers\FormHelper::makeOption((string) ($ic->id ?? ''), (string) ($ic->adi ?? ''));
                }
                echo \App\Helpers\FormHelper::fieldSelect('ilce', 'İlçe', $eshAylikTekIzlemIlceOptions, ($ilce === '' ? '0' : (string) $ilce), [
                    'col' => 'col-12 col-md-6 col-lg-3',
                    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                    'class' => 'form-select-sm shadow-sm',
                    'tomSelect' => false,
                    'extraAttrs' => ['onchange' => "this.form.mahalle.value='0'; this.form.submit();"],
                ]);
                $eshAylikTekIzlemMahalleOptions = [\App\Helpers\FormHelper::makeOption('0', 'Tüm mahalleler')];
                foreach ($mahalleler as $mh) {
                    $eshAylikTekIzlemMahalleOptions[] = \App\Helpers\FormHelper::makeOption((string) ($mh->id ?? ''), (string) ($mh->adi ?? ''));
                }
                echo \App\Helpers\FormHelper::fieldSelect('mahalle', 'Mahalle', $eshAylikTekIzlemMahalleOptions, ($mahalle === '' ? '0' : (string) $mahalle), [
                    'col' => 'col-12 col-md-6 col-lg-3',
                    'labelClass' => 'form-label fw-semibold small text-secondary mb-1',
                    'class' => 'form-select-sm shadow-sm',
                    'tomSelect' => false,
                ]);
                ?>
                <div class="col-12 d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm"><i class="fa-solid fa-filter me-1"></i>Getir</button>
                    <a href="<?= htmlspecialchars(esh_url('Stats', 'aylikTekIzlemliler'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Sıfırla</a>
                </div>
            </form>
        </div>
        <div class="card-header bg-light border-bottom py-3">
            <h5 class="mb-0"><i class="fa-solid fa-chart-gantt text-primary me-2"></i><?= htmlspecialchars($period_label ?? '', ENT_QUOTES, 'UTF-8') ?> — ay içinde tam 1 tamamlanmış izlem</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center text-primary ps-2" style="width:76px">
                                <i class="fa-solid fa-chart-line d-block small"></i><span class="small text-muted">İzlem</span>
                            </th>
                            <?= \App\Helpers\UIHelper::renderSortTh('Hasta adı', 'h.isim', $ordering, $eshSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('TC', 'h.tckimlik', $ordering, $eshSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Mahalle / ilçe', 'h.mahalle', $ordering, $eshSortCfg) ?>
                            <th>Anne adı / Baba adı</th>
                            <?= \App\Helpers\UIHelper::renderSortTh('Kayıt tarihi', 'h.kayittarihi', $ordering, $eshSortCfg) ?>
                            <?= \App\Helpers\UIHelper::renderSortTh('Doğum / yaş', 'h.dogumtarihi', $ordering, $eshSortCfg) ?>
                            <th>Telefon</th>
                            <?= \App\Helpers\UIHelper::renderSortTh('Son izlem', 'sonizlem', $ordering, $eshSortCfg) ?>
                        </tr>
                    </thead>
                    <tbody id="esh-aylik-tek-izlemliler-list-tbody"
                           data-esh-fetch-url="<?= htmlspecialchars($aylikTekIzlemlilerRowsFetchUrl ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <tr class="esh-aylik-tek-izlemliler-list-loading-row">
                            <td colspan="9" class="border-0 py-5 text-center text-muted">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <span>Aylık tek izlem listesi yükleniyor…</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top-0 py-2 px-0">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="small text-muted">
                        <?= \App\Helpers\PaginationHelper::infoText((int) $total, (int) $page, (int) $limit) ?>
                    </div>
                    <div>
                        <?= \App\Helpers\PaginationHelper::limitSelector((int) $limit, $birPagelink) ?>
                    </div>
                </div>
                <div>
                    <?= \App\Helpers\PaginationHelper::render((int) $total, (int) $page, (int) $limit, $birPagelink) ?>
                </div>
            </div>
        </div>
    </div>
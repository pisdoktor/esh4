    <section class="esh-page__panel esh-page__panel--data mb-3 esh-preview-mod-target" id="esh-preview-mod-liste">

        <div class="esh-page__panel-head">

            <span class="esh-page__panel-title">Veri tablosu</span>

            <span class="badge rounded-pill" style="background: color-mix(in srgb, var(--esh-ui-accent) 15%, transparent); color: var(--esh-ui-accent);">3 kayıt</span>

        </div>

        <div class="esh-page__panel-body p-0 p-sm-2">

            <div class="esh-page__table-wrap">

                <table class="table table-sm table-hover align-middle esh-ui-table mb-0">

                    <thead>

                        <tr>

                            <th><?php
                                echo '<span class="esh-ui-table-sort-wrap">'
                                    . '<a class="esh-ui-table-sort" href="#">TC Kimlik</a>'
                                    . '<span class="esh-ui-table-sort-arrows" aria-hidden="true">'
                                    . '<a class="esh-ui-table-sort-dir is-active" href="#" title="Artan sıralama"><i class="fa-solid fa-caret-up"></i></a>'
                                    . '<a class="esh-ui-table-sort-dir" href="#" title="Azalan sıralama"><i class="fa-solid fa-caret-down"></i></a>'
                                    . '</span></span>';
                            ?></th>

                            <th><?php
                                echo '<span class="esh-ui-table-sort-wrap">'
                                    . '<a class="esh-ui-table-sort" href="#">Ad Soyad</a>'
                                    . '<span class="esh-ui-table-sort-arrows" aria-hidden="true">'
                                    . '<a class="esh-ui-table-sort-dir" href="#" title="Artan sıralama"><i class="fa-solid fa-caret-up"></i></a>'
                                    . '<a class="esh-ui-table-sort-dir" href="#" title="Azalan sıralama"><i class="fa-solid fa-caret-down"></i></a>'
                                    . '</span></span>';
                            ?></th>

                            <th><?php
                                echo '<span class="esh-ui-table-sort-wrap">'
                                    . '<a class="esh-ui-table-sort" href="#">Durum</a>'
                                    . '<span class="esh-ui-table-sort-arrows" aria-hidden="true">'
                                    . '<a class="esh-ui-table-sort-dir" href="#" title="Artan sıralama"><i class="fa-solid fa-caret-up"></i></a>'
                                    . '<a class="esh-ui-table-sort-dir is-active" href="#" title="Azalan sıralama"><i class="fa-solid fa-caret-down"></i></a>'
                                    . '</span></span>';
                            ?></th>

                            <th class="text-end">İşlem</th>

                        </tr>

                    </thead>

                    <tbody>

                        <tr>

                            <td><code class="small">12345678901</code></td>

                            <td>Örnek Hasta</td>

                            <td><span class="badge bg-success-subtle text-success">Aktif</span></td>

                            <td class="text-end">

                                <div class="btn-group btn-group-sm esh-list-actions">

                                    <button type="button" class="btn btn-outline-primary btn-sm">Gör</button>

                                    <button type="button" class="btn btn-outline-secondary btn-sm">Düzenle</button>

                                </div>

                            </td>

                        </tr>

                        <tr>

                            <td><code class="small">98765432109</code></td>

                            <td class="text-muted">Pasif kayıt (muted)</td>

                            <td><span class="badge bg-secondary-subtle text-secondary">Pasif</span></td>

                            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary">Gör</button></td>

                        </tr>

                        <tr class="table-warning">

                            <td colspan="4" class="small py-3 text-center text-muted">Hover satırı ve vurgu renkleri tabloda test edilir</td>

                        </tr>

                    </tbody>

                </table>

            </div>

            <footer class="esh-page__footer px-2 pb-2 pt-2">

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 small text-muted">

                    <span>1–3 / 48 kayıt</span>

                    <nav aria-label="Sayfalama örneği">

                        <ul class="pagination pagination-sm mb-0">

                            <li class="page-item disabled"><span class="page-link">Önceki</span></li>

                            <li class="page-item active"><span class="page-link">1</span></li>

                            <li class="page-item"><a class="page-link" href="#">2</a></li>

                            <li class="page-item"><a class="page-link" href="#">Sonraki</a></li>

                        </ul>

                    </nav>

                </div>

            </footer>

        </div>

    </section>




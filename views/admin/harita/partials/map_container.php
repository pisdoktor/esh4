    <div id="esh-harita-map" class="esh-harita-map rounded-bottom">
        <?php
        $eshHaritaCounts = $eshHaritaCounts ?? ['all' => 0, 'erkek' => 0, 'kadin' => 0];
        $eshHaritaPatientCount = (int) ($eshHaritaPatientCount ?? ($eshHaritaCounts['all'] ?? 0));
        ?>
        <div class="esh-harita-legend-host">
        <aside class="map-overlay" id="legend" aria-label="Filtre ve görünüm">
            <button type="button" class="esh-harita-legend-toggle" id="eshHaritaLegendToggle" aria-expanded="true" aria-controls="eshHaritaLegendBody" title="Paneli daralt">
                <i class="fa-solid fa-angles-right esh-harita-legend-toggle__icon esh-harita-legend-toggle__icon--when-open" aria-hidden="true"></i>
                <i class="fa-solid fa-sliders esh-harita-legend-toggle__icon esh-harita-legend-toggle__icon--when-collapsed" aria-hidden="true"></i>
            </button>
            <div id="eshHaritaLegendBody" class="map-overlay__body">
            <div class="map-overlay__title">HIZLI FİLTRE</div>
            <div class="legend-item" role="button" tabindex="0" data-filter="erkek">
                <div class="legend-color esh-legend-swatch esh-legend-swatch--male"></div>
                <span>Erkek</span>
                <span class="count-badge" id="count-erkek"><?= (int) ($eshHaritaCounts['erkek'] ?? 0) ?></span>
            </div>
            <div class="legend-item" role="button" tabindex="0" data-filter="kadin">
                <div class="legend-color esh-legend-swatch esh-legend-swatch--female"></div>
                <span>Kadın</span>
                <span class="count-badge" id="count-kadin"><?= (int) ($eshHaritaCounts['kadin'] ?? 0) ?></span>
            </div>
            <div class="legend-item legend-item--all" role="button" tabindex="0" data-filter="all">
                <div class="legend-color esh-legend-swatch esh-legend-swatch--neutral"></div>
                <span>Tümü</span>
                <span class="count-badge" id="count-all"><?= (int) ($eshHaritaPatientCount ?? ($eshHaritaCounts['all'] ?? 0)) ?></span>
            </div>
            <div class="map-overlay__title mt-2 pt-2 border-top">GÖRÜNÜM</div>
            <div class="esh-harita-mod-group d-grid gap-1" role="radiogroup" aria-label="Harita katmanı">
                <input type="radio" class="btn-check" name="eshHaritaMod" id="eshHaritaModCluster" value="cluster" autocomplete="off" checked>
                <label class="btn btn-outline-secondary btn-sm text-start" for="eshHaritaModCluster"><i class="fa-solid fa-layer-group me-1" aria-hidden="true"></i>Küme</label>
                <input type="radio" class="btn-check" name="eshHaritaMod" id="eshHaritaModHeat" value="heatmap" autocomplete="off">
                <label class="btn btn-outline-secondary btn-sm text-start" for="eshHaritaModHeat"><i class="fa-solid fa-fire me-1" aria-hidden="true"></i>Isı haritası</label>
                <input type="radio" class="btn-check" name="eshHaritaMod" id="eshHaritaModDots" value="dots" autocomplete="off">
                <label class="btn btn-outline-secondary btn-sm text-start" for="eshHaritaModDots"><i class="fa-solid fa-location-dot me-1" aria-hidden="true"></i>Noktalar</label>
                <input type="radio" class="btn-check" name="eshHaritaMod" id="eshHaritaModHybrid" value="hybrid" autocomplete="off">
                <label class="btn btn-outline-secondary btn-sm text-start" for="eshHaritaModHybrid"><i class="fa-solid fa-layer-group me-1" aria-hidden="true"></i>Hibrit (ısı + nokta)</label>
            </div>
            <p id="haritaModHint" class="esh-harita-legend-hint small text-muted mb-0 mt-2">Kümelere tıklayarak yakınlaşın; işaretçiye tıklayınca hasta kartı açılır.</p>
            <?php
            $activeMapProvider = $activeMapProvider ?? \App\Helpers\OperationalSettings::activeMapProviderStatusForAdmin();
            $providerLabel = (string) ($activeMapProvider['label'] ?? 'Harita');
            ?>
            <p class="small text-muted px-1 mb-0 mt-2 border-top pt-2">Harita/rota: <strong><?= htmlspecialchars($providerLabel, ENT_QUOTES, 'UTF-8') ?></strong></p>
            <?php if (!empty($mahalleCombinedUrl)): ?>
            <div class="form-check mt-2 px-1 border-top pt-2">
                <input class="form-check-input" type="checkbox" id="eshHaritaMahalleFill" value="1" autocomplete="off">
                <label class="form-check-label small" for="eshHaritaMahalleFill">Mahalle sınırları (adres tablosundaki Pamukkale + Merkezefendi mahalleleri, OSM geometri)</label>
            </div>
            <p class="small text-muted px-1 mb-0">Veri OpenStreetMap (ODbL); deneysel katman.</p>
            <?php endif; ?>
            <details class="esh-harita-legend-more small text-muted mt-2 mb-0">
                <summary class="cursor-pointer user-select-none">Harita katmanı fikirleri</summary>
                <ul class="mb-0 ps-3 mt-1 small lh-sm">
                    <li><strong>fill</strong> — ilçe/mahalle poligonu boyama (GeoJSON alan)</li>
                    <li><strong>line</strong> — ekip güzergâhı, çoklu rota, kesik çizgi</li>
                    <li><strong>symbol</strong> — ikon + etiket (hasta tipi, öncelik)</li>
                    <li><strong>hillshade</strong> — arazi gölgesi (DEM raster kaynak)</li>
                    <li><strong>fill-extrusion</strong> — 3B bina (vektör karo + yükseklik)</li>
                </ul>
            </details>
            </div>
        </aside>
        </div>
    </div>
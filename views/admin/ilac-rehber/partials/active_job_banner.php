            <div id="activeBanner" class="alert alert-success border-success mb-3<?= $bannerRunning ? '' : ' d-none' ?>">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                    <div>
                        <strong><i class="fa-solid fa-gears fa-spin me-1"></i>Scrape çalışıyor</strong>
                        <div class="small mt-1 mb-0">
                            İş: <code id="activeJobIdShort"><?= htmlspecialchars($bannerJobIdShort !== '' ? $bannerJobIdShort : '—', ENT_QUOTES, 'UTF-8') ?></code>
                            · <span id="activeJobStepEcho"><?= htmlspecialchars($bannerStep !== '' ? $bannerStep : '—', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                </div>
            </div>
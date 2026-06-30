            <?php
            $bannerRunning = !empty($activeJob) && ($activeJob['status'] ?? '') === 'running';
            $bannerJobId = $bannerRunning ? (string) ($activeJob['id'] ?? '') : '';
            $bannerJobIdShort = $bannerJobId;
            if (strlen($bannerJobIdShort) > 14) {
                $bannerJobIdShort = substr($bannerJobIdShort, 0, 14) . '…';
            }
            $bannerPhase = $bannerRunning ? (string) ($activeJob['phase_label'] ?? 'Çalışıyor') : '';
            ?>
            <div id="activeBanner" class="alert alert-success border-success mb-3<?= $bannerRunning ? '' : ' d-none' ?>">
                <div>
                    <strong><i class="fa-solid fa-gears fa-spin me-1"></i>Senkron çalışıyor</strong>
                    <div class="small mt-1 mb-0">
                        İş: <code id="activeJobIdShort"><?= htmlspecialchars($bannerJobIdShort !== '' ? $bannerJobIdShort : '—', ENT_QUOTES, 'UTF-8') ?></code>
                        · <span id="activeJobStepEcho"><?= htmlspecialchars($bannerPhase !== '' ? $bannerPhase : '—', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
            </div>

            <div id="resumeHint" class="alert alert-info py-2 small mb-3 d-none">
                <i class="fa fa-info-circle me-1"></i>
                <span id="resumeHintText">Tamamlanmamış bir senkron bulundu.</span>
            </div>
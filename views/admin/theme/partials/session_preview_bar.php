    <div class="alert alert-light border mb-3 py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2" id="esh-session-preview-bar">
        <div class="small">
            <i class="fa-solid fa-flask me-1 text-info"></i>
            <strong>Oturum önizlemesi</strong>
            <span class="text-muted">— değişiklikler yalnızca bu oturumda görünür; <code>theme.css</code> dosyasına yazılmaz.<?php if ($editorStandalone): ?> Ana uygulama sekmesinde (aynı oturum) görünür; iframe yalnızca canlı önizleme.<?php else: ?> Sayfa görünümü için <strong>Sayfa standardı</strong> (<code>--esh-ui-*</code>) sekmesini kullanın.<?php endif; ?></span>
            <span class="badge ms-1<?= $sessionPreviewActive ? ' bg-info' : ' bg-secondary' ?>" id="esh-session-preview-badge">
                <?= $sessionPreviewActive ? 'Aktif' : 'Kapalı' ?>
            </span>
            <?php if ($editorStandalone): ?>
            <span class="badge bg-light text-secondary border ms-1 d-none" id="esh-session-preview-main-hint">Ana sekmede aktif</span>
            <?php endif; ?>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <?php if ($editorStandalone): ?>
            <button type="button" class="btn btn-sm btn-outline-primary d-none" id="esh-session-preview-focus-main" title="Oturum önizlemesi ana uygulama sekmesinde görünür">
                <i class="fa-solid fa-up-right-from-square me-1"></i>Ana sekmeye geç
            </button>
            <?php endif; ?>
            <div class="form-check form-switch mb-0 small">
                <input class="form-check-input" type="checkbox" role="switch" id="esh-session-preview-auto"<?= !$hasAnyEditorRows ? ' disabled' : '' ?>>
                <label class="form-check-label" for="esh-session-preview-auto">Düzenlerken otomatik uygula</label>
            </div>
            <button type="button" class="btn btn-sm btn-outline-info" id="esh-session-preview-apply"<?= !$hasAnyEditorRows ? ' disabled title="Bu temada düzenlenebilir jeton yok"' : '' ?>>
                <i class="fa-solid fa-play me-1"></i>Oturuma uygula
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="esh-session-preview-clear">
                <i class="fa-solid fa-xmark me-1"></i>Temizle
            </button>
        </div>
    </div>

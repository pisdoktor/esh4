                <div class="card-header esh-theme-editor-tabs-header px-0 py-0 border-top">
                    <div class="esh-theme-editor-tabs-wrap" tabindex="0" aria-label="Tema editörü sekmeleri">
                        <ul class="nav nav-tabs esh-theme-editor-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="esh-tab-esh-ui" data-bs-toggle="tab" data-bs-target="#esh-pane-esh-ui" type="button" role="tab" aria-controls="esh-pane-esh-ui" aria-selected="true">
                                <span class="esh-theme-editor-tabs__label">Sayfa standardı</span>
                                <span class="badge rounded-pill esh-theme-editor-tabs__badge"><?= count($eshUiTokens) ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link<?= $colorTokens === [] ? ' disabled' : '' ?>" id="esh-tab-colors" data-bs-toggle="tab" data-bs-target="#esh-pane-colors" type="button" role="tab" aria-controls="esh-pane-colors"<?= $colorTokens === [] ? ' tabindex="-1" aria-disabled="true"' : '' ?>>
                                <span class="esh-theme-editor-tabs__label">Renk jetonları</span>
                                <span class="badge rounded-pill esh-theme-editor-tabs__badge"><?= count($colorTokens) ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link<?= !$hasTypography ? ' disabled' : '' ?>" id="esh-tab-typography" data-bs-toggle="tab" data-bs-target="#esh-pane-typography" type="button" role="tab" aria-controls="esh-pane-typography"<?= !$hasTypography ? ' tabindex="-1" aria-disabled="true"' : '' ?>>
                                <span class="esh-theme-editor-tabs__label">Tipografi</span>
                                <span class="badge rounded-pill esh-theme-editor-tabs__badge"><?= count($eshUiTypographyTokens) + count($typographyVarTokens) + count($typographyPropEntries) ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link<?= !$hasGradients ? ' disabled' : '' ?>" id="esh-tab-gradients" data-bs-toggle="tab" data-bs-target="#esh-pane-gradients" type="button" role="tab" aria-controls="esh-pane-gradients"<?= !$hasGradients ? ' tabindex="-1" aria-disabled="true"' : '' ?>>
                                <span class="esh-theme-editor-tabs__label">Gradyanlar</span>
                                <span class="badge rounded-pill esh-theme-editor-tabs__badge"><?= count($gradientVarTokens) + count($gradientPropEntries) ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link<?= !$hasOther ? ' disabled' : '' ?>" id="esh-tab-other" data-bs-toggle="tab" data-bs-target="#esh-pane-other" type="button" role="tab" aria-controls="esh-pane-other"<?= !$hasOther ? ' tabindex="-1" aria-disabled="true"' : '' ?>>
                                <span class="esh-theme-editor-tabs__label">Diğer</span>
                                <span class="badge rounded-pill esh-theme-editor-tabs__badge"><?= count($otherVarTokens) + count($otherPropEntries) ?></span>
                            </button>
                        </li>
                        </ul>
                    </div>
                </div>

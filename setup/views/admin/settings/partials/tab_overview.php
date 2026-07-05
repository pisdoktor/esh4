            <div class="row g-3 g-lg-4">
                <?php
                $overviewCards = $overviewCards ?? [];
                $categoryLabels = \App\Helpers\SettingsNavCatalog::CATEGORY_LABELS;
                $lastCategory = '';
                foreach ($overviewCards as $card):
                    $cat = (string) ($card['category'] ?? '');
                    if ($cat !== '' && $cat !== 'overview' && $cat !== $lastCategory):
                        $lastCategory = $cat;
                        ?>
                        <div class="col-12">
                            <h6 class="text-muted text-uppercase small fw-bold mb-0 mt-2"><?= htmlspecialchars($categoryLabels[$cat] ?? $cat, ENT_QUOTES, 'UTF-8') ?></h6>
                        </div>
                    <?php endif; ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <a href="<?= htmlspecialchars((string) ($card['href'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>"
                           class="card shadow-sm border-0 h-100 text-decoration-none esh-settings-overview-card">
                            <div class="card-body">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="esh-settings-overview-card__icon rounded-3 d-flex align-items-center justify-content-center">
                                        <i class="fa-solid <?= htmlspecialchars((string) ($card['icon'] ?? 'fa-circle'), ENT_QUOTES, 'UTF-8') ?> text-primary"></i>
                                    </span>
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                            <span class="fw-semibold text-body"><?= htmlspecialchars((string) ($card['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if (!empty($card['badge'])): ?>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><?= htmlspecialchars((string) $card['badge'], ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="small text-muted mb-0"><?= htmlspecialchars((string) ($card['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

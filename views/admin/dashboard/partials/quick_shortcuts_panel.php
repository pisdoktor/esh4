<?php
/**
 * Hızlı kısayollar paneli (kart + bağlantı ızgarası).
 *
 * @var array<int, array<string, string>> $quickCards
 * @var string $shortcutsPanelOuterClass
 * @var string $shortcutCardClass
 * @var string $shortcutItemColClass
 */
$shortcutsPanelOuterClass = $shortcutsPanelOuterClass ?? 'card shadow-sm border-0 h-100';
$shortcutCardClass = $shortcutCardClass ?? 'card h-100 shadow-sm border-0';
$shortcutItemColClass = $shortcutItemColClass ?? 'col-12';
?>
<div class="<?= htmlspecialchars($shortcutsPanelOuterClass, ENT_QUOTES, 'UTF-8') ?>">
    <div class="card-header bg-white py-3">
        <h6 class="m-0 fw-bold text-dark"><i class="fa-solid fa-bolt me-2"></i>Hızlı kısayollar</h6>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php foreach ($quickCards as $card): ?>
                <div class="<?= htmlspecialchars($shortcutItemColClass, ENT_QUOTES, 'UTF-8') ?>">
                    <a href="<?= htmlspecialchars($card['href'], ENT_QUOTES, 'UTF-8') ?>" class="text-decoration-none">
                        <div class="<?= htmlspecialchars($shortcutCardClass, ENT_QUOTES, 'UTF-8') ?> border-start border-4 border-<?= htmlspecialchars($card['color'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="rounded-3 bg-<?= htmlspecialchars($card['color'], ENT_QUOTES, 'UTF-8') ?> bg-opacity-10 text-<?= htmlspecialchars($card['color'], ENT_QUOTES, 'UTF-8') ?> p-3">
                                    <i class="fa-solid <?= htmlspecialchars($card['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                </div>
                                <div class="fw-semibold text-dark small"><?= htmlspecialchars($card['title'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

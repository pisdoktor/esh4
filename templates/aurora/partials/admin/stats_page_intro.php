<?php
declare(strict_types=1);
/** @var string $statsIntro */
$statsIntro = trim((string) ($statsIntro ?? ''));
if ($statsIntro === '') {
    return;
}
?>
<p class="text-muted small mb-3"><?= htmlspecialchars($statsIntro, ENT_QUOTES, 'UTF-8') ?></p>

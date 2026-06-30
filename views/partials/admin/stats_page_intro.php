<?php
declare(strict_types=1);
/** @var string $statsIntro */
$statsIntro = trim((string) ($statsIntro ?? ''));
if ($statsIntro === '') {
    return;
}
?>
<div class="esh-page__intro-banner" role="status"><?= htmlspecialchars($statsIntro, ENT_QUOTES, 'UTF-8') ?></div>

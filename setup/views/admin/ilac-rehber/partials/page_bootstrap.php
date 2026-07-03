<?php
$defaults = $defaults ?? \App\Helpers\IlacRehberScrapeSync::defaultOptions();
$initialStatus = is_array($initialStatus ?? null) ? $initialStatus : [];
$initialActivePayload = $initialActivePayload ?? null;
$stats = is_array($stats ?? null) ? $stats : [];
$etkenCount = (int) ($stats['etken'] ?? 0);
$ilacCount = (int) ($stats['ilac'] ?? 0);
$withoutCount = (int) ($stats['etken_without_ilac'] ?? 0);
$statusMessage = (string) ($initialStatus['status_message'] ?? 'Hazır — scrape başlatılabilir');

$bannerRunning = is_array($initialActivePayload)
    && !empty($initialActivePayload['job'])
    && in_array((string) ($initialActivePayload['job']['status'] ?? ''), ['queued', 'running'], true);
$bannerJobId = $bannerRunning ? (string) ($initialActivePayload['job_id'] ?? '') : '';
$bannerJobIdShort = $bannerJobId;
if (strlen($bannerJobIdShort) > 14) {
    $bannerJobIdShort = substr($bannerJobIdShort, 0, 14) . '…';
}
$bannerStep = $bannerRunning ? (string) ($initialActivePayload['job']['step'] ?? '—') : '';
?>
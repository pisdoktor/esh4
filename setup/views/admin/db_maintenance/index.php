<div class="esh-page esh-page--list esh-page-db_maintenance container-fluid py-4">
<?php
/**
 * @var array $tools
 * @var array $backups
 * @var array $tables
 * @var array $summary
 * @var array $backupGroups
 * @var string $token
 * @var array|null $maintResults
 * @var string $activeTab
 */
$dbLabel = defined('DB_NAME') ? DB_NAME : '';
$backupPathLabel = defined('BACKUP_STORAGE_PATH') ? BACKUP_STORAGE_PATH : '';
$activeTab = $activeTab ?? 'ozet';
$summary = $summary ?? [];
$backupGroups = $backupGroups ?? [];
?>
<?php include __DIR__ . '/partials/header_meta.php'; ?>
<?php include __DIR__ . '/partials/summary_cards.php'; ?>
<?php include __DIR__ . '/partials/tab_nav.php'; ?>

<div class="tab-content" id="dbMaintTabContent">
    <?php if ($activeTab === 'ozet'): ?>
        <?php include __DIR__ . '/partials/tab_ozet.php'; ?>
    <?php elseif ($activeTab === 'tablolar'): ?>
        <?php include __DIR__ . '/partials/tables_overview_card.php'; ?>
    <?php elseif ($activeTab === 'bakim'): ?>
        <?php include __DIR__ . '/partials/maintenance_actions_card.php'; ?>
    <?php elseif ($activeTab === 'yedekler'): ?>
        <?php include __DIR__ . '/partials/backups_card.php'; ?>
    <?php elseif ($activeTab === 'geri'): ?>
        <?php include __DIR__ . '/partials/restore_warning.php'; ?>
        <?php include __DIR__ . '/partials/backup_restore_card.php'; ?>
    <?php endif; ?>
</div>
<script>
window.ESH_DB_MAINT = {
    mysqlAvailable: <?= !empty($summary['mysql_available']) ? 'true' : 'false' ?>,
    restoreLimitBytes: 52428800
};
</script>
</div>

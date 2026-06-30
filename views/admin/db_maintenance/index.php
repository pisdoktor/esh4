<div class="esh-page esh-page--list esh-page-db_maintenance container-fluid py-4">
<?php
/**
 * @var array $tools
 * @var array $backups
 * @var array $tables
 * @var string $token
 * @var array|null $maintResults
 */
$dbLabel = defined('DB_NAME') ? DB_NAME : '';
$backupPathLabel = defined('BACKUP_STORAGE_PATH') ? BACKUP_STORAGE_PATH : '';
?>
<?php include __DIR__ . '/partials/header_meta.php'; ?>
<?php include __DIR__ . '/partials/restore_warning.php'; ?>
<?php include __DIR__ . '/partials/maintenance_actions_card.php'; ?>
<?php include __DIR__ . '/partials/tables_overview_card.php'; ?>
<?php include __DIR__ . '/partials/backups_card.php'; ?>
<?php include __DIR__ . '/partials/backup_restore_card.php'; ?>

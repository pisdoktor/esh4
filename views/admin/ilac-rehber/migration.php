<?php include __DIR__ . '/partials/page_bootstrap.php'; ?>
<div class="esh-page esh-page--list esh-page-ilac-rehber-migration container-fluid py-4">
    <div class="card shadow-sm border-0">
<?php include __DIR__ . '/partials/header_actions.php'; ?>
        <div class="card-body">
<?php include __DIR__ . '/partials/info_alert.php'; ?>
<?php include __DIR__ . '/partials/active_job_banner.php'; ?>
<?php include __DIR__ . '/partials/stats_cards.php'; ?>
            <p class="small text-muted mb-3" id="statsMeta" aria-live="polite">—</p>
<?php include __DIR__ . '/partials/progress_panel.php'; ?>
<?php include __DIR__ . '/partials/options_form.php'; ?>
<?php include __DIR__ . '/partials/checkpoint_options.php'; ?>
<?php include __DIR__ . '/partials/log_panel.php'; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/page_config.php'; ?>

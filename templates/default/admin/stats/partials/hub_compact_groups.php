<?php
/*
 * ESH Default tema — view sözleşmesi (yeni tema yazımı için)
 * Yol: templates/default/admin/stats/partials/hub_compact_groups.php
 *
 * Controller : StatsController
 * Action     : —
 * Rota       : index.php?controller=Stats&action=—
 * Canonical  : views/admin/stats/partials/hub_compact_groups.php (bu dosya genelde include eder)
 *
 * Değişkenler (include öncesi controller kapsamı):
 *   (StatsController::render — extract ile kapsama alınır)
 *  $pageTitle (string)
 *  $statsIntro (string) — StatsIntroHelper::forAction
 *  $statsBreadcrumb (list) — StatsNavHelper::breadcrumbTrail
 *  $eshStatsHubCss (bool) — true
 *  + aşağıdaki rapora özel değişkenler
 *  $groups — hub.php içinden; default tema views/partials/hub_compact_groups.php require eder
 *
 * Ortak: $_SESSION['user_id'], SITEURL, ROOT_PATH, UPLOADS_URL (tanımlıysa)
 * Not: Include partial; canonical views/admin/stats/partials/hub_compact_groups.php
 */
require ROOT_PATH . '/views/admin/stats/partials/hub_compact_groups.php';

<?php
declare(strict_types=1);
/**
 * Hasta kartı — Tıbbi İşlemler mega menü (İzlem | Hasta sütunları).
 *
 * @var object $hasta
 */
$eshMenuRows = \App\Helpers\BadgeHelper::patientDetailTibbiMenuRows($hasta);
require dirname(__DIR__, 2) . '/partials/esh_dropdown_menu.php';

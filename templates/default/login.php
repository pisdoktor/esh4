<?php
/*
 * ESH Default tema — view sözleşmesi (yeni tema yazımı için)
 * Yol: templates/default/login.php
 *
 * Controller : AuthController
 * Action     : login
 * Rota       : Auth/login
 * Canonical  : views/login_template.php (bu dosya genelde include eder)
 *
 * Değişkenler (include öncesi controller kapsamı):
 *   (controller kapsamında özel değişken yok)
 *  $GLOBALS['ESH_LOGIN_THEME'] — login.php içinde 'default' atanır
 *  $_SESSION['error'] — hata mesajı (varsa)
 *  Sabitler: SITEURL, ThemeViewHelper::siteThemeSlug()
 *
 * Ortak: $_SESSION['user_id'], SITEURL, ROOT_PATH, UPLOADS_URL (tanımlıysa)
 */
$GLOBALS['ESH_LOGIN_THEME'] = 'default';
require __DIR__ . '/login_body.php';

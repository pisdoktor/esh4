<?php
declare(strict_types=1);
/** Login sayfası — şifre göster/gizle (CSS + JS). </body> öncesinde include edin. */
$__eshPwToggleCss = rtrim((string) SITEURL, '/') . '/public/assets/login-password-toggle.css';
$__eshPwToggleJs = rtrim((string) SITEURL, '/') . '/public/assets/login-password-toggle.js';
?>
<link rel="stylesheet" href="<?= htmlspecialchars($__eshPwToggleCss, ENT_QUOTES, 'UTF-8'); ?>">
<?= esh_csp_script_src_tag($__eshPwToggleJs, 'defer') ?>

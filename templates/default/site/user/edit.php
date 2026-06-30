<?php
/*
 * ESH Default tema — view sözleşmesi (yeni tema yazımı için)
 * Yol: templates/default/site/user/edit.php
 *
 * Controller : UserController
 * Action     : edit
 * Canonical  : views/site/user/edit.php (bu dosya genelde include eder)
 *
 * Değişkenler (include öncesi controller kapsamı):
 *   $user (User)
 *  $themesMeta (list<array>) — kurulu tema listesi (_theme.json)
 *
 * Ortak: $_SESSION['user_id'], SITEURL, ROOT_PATH, UPLOADS_URL (tanımlıysa)
 */
?>
<div class="container py-4 py-lg-5 esh-page-user-profile esh-page-user-profile-edit">
    <?php
    $eshUiThemePartial = ROOT_PATH . '/templates/default/partials/ui_theme_select_field.php';
    include ROOT_PATH . '/views/site/user/partials/profile_edit_page.php';
    ?>
</div>

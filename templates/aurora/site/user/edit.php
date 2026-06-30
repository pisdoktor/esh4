<div class="container py-4 py-lg-5 esh-page-user-profile esh-page-user-profile-edit au-page-user-edit">
    <?php
    $eshUiThemePartial = \App\Helpers\ThemeViewHelper::resolvePartial('ui_theme_select_field');
    include \App\Helpers\ThemeViewHelper::resolveAreaView('site', 'user/partials/profile_edit_page');
    ?>
</div>

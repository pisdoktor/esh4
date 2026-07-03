<?php
use App\Helpers\PageShellHelper;

PageShellHelper::pageOpen(['kind' => 'form', 'module' => 'user']);
PageShellHelper::formPageOpen();
PageShellHelper::panelOpen('Personel Düzenle', [
    'icon' => 'fa-solid fa-user-gear text-primary',
]);
?>
<?php include __DIR__ . '/partials/edit_meta.php'; ?>
<?php include __DIR__ . '/partials/edit_form.php'; ?>
<?php
PageShellHelper::panelClose();
PageShellHelper::formPageClose();
PageShellHelper::pageClose();

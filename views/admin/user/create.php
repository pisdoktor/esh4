<?php
use App\Helpers\PageShellHelper;

PageShellHelper::pageOpen(['kind' => 'form', 'module' => 'user']);
PageShellHelper::formPageOpen();
PageShellHelper::panelOpen((string) ($pageTitle ?? 'Yeni Kullanıcı'), [
    'icon' => 'fa-solid fa-user-plus text-success',
]);
?>
<?php include __DIR__ . '/partials/create_form.php'; ?>
<?php
PageShellHelper::panelClose();
PageShellHelper::formPageClose();
PageShellHelper::pageClose();

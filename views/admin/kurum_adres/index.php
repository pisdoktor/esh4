<?php
/** @var list<object> $kurumlar */
/** @var list<object> $assignments */
/** @var \App\Models\Kurum|null $selectedKurum */
$kurumId = (int) ($selectedKurum->id ?? 0);
$admin_list_title = 'Kurum Adres Ataması';
$admin_list_icon = 'fa-solid fa-map-location-dot';
$admin_list_container_id = 'kurum-adres-atama';
$admin_list_container_extra_classes = 'mt-3 py-4';
$admin_list_header_aside = 'Kuruma ilçe, mahalle veya sokak düzeyinde coğrafi kapsam atayın. Atanan bölgeler kurum kullanıcılarının adres listelerinde görünür.';
include dirname(__DIR__, 2) . '/partials/admin/list_page_open.php';
?>

<?php include __DIR__ . '/partials/kurum_filter_form.php'; ?>

            <?php if ($kurumId > 0): ?>
<?php include __DIR__ . '/partials/hierarchy_picker.php'; ?>
<?php include __DIR__ . '/partials/assignments_table.php'; ?>
            <?php else: ?>
            <div class="alert alert-warning">Aktif kurum bulunamadı.</div>
            <?php endif; ?>

<?php include dirname(__DIR__, 2) . '/partials/admin/list_page_close.php'; ?>

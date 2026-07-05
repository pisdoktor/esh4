    <script<?= esh_csp_nonce_attr() ?>>
    $(function () {
        $('.datepicker').datepicker({
            format: 'dd-mm-yyyy',
            autoclose: true,
            language: 'tr'
        });
        var $collapse = $('#stats-randevu-kayit-gap-filter-collapse');
        var $toggleText = $('#stats-randevu-kayit-gap-filter-toggle .js-filter-toggle-text');
        if ($collapse.length && $toggleText.length) {
            $collapse.on('shown.bs.collapse', function () {
                $toggleText.text('Filtreleri Gizle');
            });
            $collapse.on('hidden.bs.collapse', function () {
                $toggleText.text('Filtreleri Göster');
            });
        }
    });
    </script>
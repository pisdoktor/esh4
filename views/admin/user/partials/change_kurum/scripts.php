<script<?= esh_csp_nonce_attr() ?>>
(function () {
    var copyCb = document.getElementById('eshUserCopyRole');
    var roleWrap = document.getElementById('eshUserNakilRoleWrap');
    if (!copyCb || !roleWrap) {
        return;
    }
    function sync() {
        roleWrap.style.display = copyCb.checked ? 'none' : 'block';
    }
    copyCb.addEventListener('change', sync);
    sync();
})();
</script>

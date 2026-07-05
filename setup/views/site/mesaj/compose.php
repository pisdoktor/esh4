<?php use App\Helpers\FormHelper; ?>
<div class="esh-page esh-page-mesaj-compose container-fluid py-4">
    <div class="d-flex align-items-center gap-2 mb-3">
        <a href="<?= htmlspecialchars(esh_url('Mesaj', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i>Geri
        </a>
        <h1 class="h4 mb-0">Yeni Mesaj</h1>
    </div>

    <div class="card shadow-sm border-0" style="max-width: 520px;">
        <div class="card-body">
            <form method="post" action="<?= htmlspecialchars($startDmUrl ?? '', ENT_QUOTES, 'UTF-8') ?>" id="esh-mesaj-compose-form">
                <?= esh_csrf_field() ?>
                <div class="mb-3">
                    <?= FormHelper::fieldSelect('user_id', 'Alıcı', [
                        FormHelper::makeOption('', 'Kullanıcı seçin…'),
                    ], '', [
                        'col' => '',
                        'id' => 'esh-mesaj-user-select',
                        'labelClass' => 'form-label',
                        'tomSelect' => false,
                        'required' => true,
                        'afterInput' => '<div id="esh-mesaj-users-loading" class="small text-muted mt-1">Kullanıcı listesi yükleniyor…</div>',
                    ]) ?>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-comment me-1"></i>Konuşmayı başlat
                </button>
            </form>
        </div>
    </div>
</div>
<script<?= esh_csp_nonce_attr() ?>>
window.ESH_PAGE = window.ESH_PAGE || {};
window.ESH_PAGE.mesajCompose = {
    usersUrl: <?= json_encode($usersForDmUrl ?? esh_url('Mesaj', 'usersForDm'), JSON_UNESCAPED_UNICODE) ?>
};
</script>

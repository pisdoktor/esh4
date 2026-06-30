<?php
declare(strict_types=1);
$__action = htmlspecialchars(esh_url('PublicHastaarama', 'sonuc', [], true), ENT_QUOTES, 'UTF-8');
?>
<article class="pha-card">
    <header class="pha-card__hero">
        <div class="pha-card__icon" aria-hidden="true">
            <i class="fa-solid fa-fingerprint"></i>
        </div>
        <h1 class="pha-card__title">Kayıtlı hasta sorgulama</h1>
        <p class="pha-card__lead">11 haneli TC ile dosya var mı ve durumunu (aktif, pasif, bekleyen vb.) anında görün; oturum açmanız gerekmez.</p>
    </header>
    <div class="pha-card__body">
        <div class="pha-pills" role="list">
            <span class="pha-pill" role="listitem"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Güvenli</span>
            <span class="pha-pill" role="listitem"><i class="fa-solid fa-user-check" aria-hidden="true"></i> Kayıt durumu</span>
            <span class="pha-pill" role="listitem"><i class="fa-solid fa-clock" aria-hidden="true"></i> Anlık sonuç</span>
        </div>

        <form action="<?= $__action ?>" method="post" autocomplete="off" novalidate id="phaTcForm">
            <div class="pha-tc-wrap">
                <label class="form-label" for="guestHastaTc">Hasta TC kimlik numarası</label>
                <div class="input-group pha-tc-input-group">
                    <span class="input-group-text" aria-hidden="true"><i class="fa-solid fa-id-card"></i></span>
                    <input
                        name="tckimlik"
                        id="guestHastaTc"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="11"
                        class="form-control"
                        placeholder="00000000000"
                        required
                        autofocus
                        autocomplete="off"
                        aria-describedby="phaTcHint phaTcMeter"
                    >
                </div>
                <div class="pha-tc-meter" id="phaTcMeter" aria-hidden="true">
                    <?php for ($__i = 0; $__i < 11; $__i++): ?>
                    <span class="pha-tc-meter__dot"></span>
                    <?php endfor; unset($__i); ?>
                </div>
                <p class="pha-tc-hint" id="phaTcHint">Yalnızca rakam; toplam 11 hane.</p>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary pha-btn-submit" disabled>
                    <i class="fa-solid fa-magnifying-glass me-2" aria-hidden="true"></i> Sorgula
                </button>
            </div>
        </form>
    </div>
</article>

<?php

declare(strict_types=1);



use App\Helpers\OperationalSettings;



if (!OperationalSettings::isPublicHastaaramaEnabled()) {

    return;

}



$__hastaaramaHref = esh_url('PublicHastaarama', 'index', [], true);

?>

<p class="esh-login-hastaarama small text-center mt-3 mb-0">

    <a href="<?= htmlspecialchars($__hastaaramaHref, ENT_QUOTES, 'UTF-8'); ?>">Kayıtlı hasta sorgulama (TC)</a>

</p>


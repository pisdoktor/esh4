<?php
declare(strict_types=1);

use App\Services\Sms\SmsTemplateEngine;

$eshSmsSablonVarsB64 = base64_encode(SmsTemplateEngine::placeholderTooltipHtml());
?>
<button type="button"
    class="btn btn-link btn-sm p-0 ms-1 align-baseline esh-sms-sablon-vars-tt-trigger text-muted"
    tabindex="0"
    aria-label="Şablon değişkenleri açıklaması"
    title="Şablon değişkenleri"
    data-esh-sms-sablon-vars-b64="<?= htmlspecialchars($eshSmsSablonVarsB64, ENT_QUOTES, 'UTF-8') ?>">
    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
</button>

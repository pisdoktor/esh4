jQuery(function ($) {
    var $allUsers = $('#mesaj-tum-kullanicilar');
    var $recipients = $('#mesaj-alicilar');
    if (!$recipients.length) {
        return;
    }

    function syncRecipientField() {
        var disabled = $allUsers.is(':checked');
        var el = $recipients[0];
        $recipients.prop('disabled', disabled);
        if (!el) {
            return;
        }
        if (el.tomselect) {
            if (disabled) {
                el.tomselect.disable();
            } else {
                el.tomselect.enable();
            }
            return;
        }
        if (!disabled && typeof window.eshInitTomSelectElement === 'function') {
            window.eshInitTomSelectElement(el);
        }
    }

    $allUsers.on('change', syncRecipientField);
});

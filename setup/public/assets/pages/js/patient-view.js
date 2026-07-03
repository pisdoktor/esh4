$(document).ready(function () {
    var cfg = document.getElementById('patient-detail-config');
    var cfgHasNotes = cfg ? String(cfg.getAttribute('data-has-notes') || '') === '1' : false;
    if ((window.ESH_PAGE && window.ESH_PAGE.detailHasNotes) || cfgHasNotes) {
        var noteModal = new bootstrap.Modal(document.getElementById('shownotes'));
        noteModal.show();
    }

    var openModalKey = cfg ? String(cfg.getAttribute('data-open-modal') || '').trim() : '';
    if (openModalKey !== '') {
        var editModalEl = document.getElementById('patientEditModal-' + openModalKey);
        if (editModalEl) {
            bootstrap.Modal.getOrCreateInstance(editModalEl).show();
        }
    }

    var clinicalToggleEnabled = cfg && String(cfg.getAttribute('data-clinical-toggle') || '') === '1';
    var patientId = cfg ? parseInt(cfg.getAttribute('data-patient-id') || '0', 10) : 0;
    if (clinicalToggleEnabled && patientId > 0) {
        $(document).on('click', '.esh-clinical-flag-toggle', function () {
            var $btn = $(this);
            if ($btn.prop('disabled')) {
                return;
            }

            var field = String($btn.data('field') || '');
            var current = parseInt($btn.data('value'), 10) === 1 ? 1 : 0;
            var next = current ? 0 : 1;
            var label = String($btn.data('label') || field);
            var currentText = current ? 'Evet' : 'Hayır';
            var nextText = next ? 'Evet' : 'Hayır';
            var confirmMsg = '«' + label + '» alanını «' + currentText + '» → «' + nextText + '» olarak güncellemek istiyor musunuz?';

            if (!window.confirm(confirmMsg)) {
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: eshUrl('Patient', 'toggleClinicalFlag'),
                type: 'POST',
                dataType: 'json',
                data: {
                    id: patientId,
                    field: field,
                    value: next
                },
                success: function (response) {
                    if (response && response.success) {
                        if (response.badge_html) {
                            $btn.replaceWith(response.badge_html);
                        }
                        if (typeof toastr !== 'undefined') {
                            toastr.success(response.message || 'Kaydedildi.');
                        }
                        if (response.reload) {
                            setTimeout(function () {
                                window.location.reload();
                            }, 500);
                        }
                    } else {
                        if (typeof toastr !== 'undefined') {
                            toastr.error((response && response.message) ? response.message : 'Kayıt yapılamadı.');
                        }
                        $btn.prop('disabled', false);
                    }
                },
                error: function () {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Sunucu hatası oluştu.');
                    }
                    $btn.prop('disabled', false);
                }
            });
        });
    }
});

function clearNotesArea() {
    if (confirm('Notun tamamını silmek istediğinize emin misiniz?')) {
        document.querySelector('textarea[name="new_note"]').value = '';
    }
}

$('#changenotes').on('shown.bs.modal', function () {
    const textarea = $(this).find('textarea');
    textarea.focus();
    const val = textarea.val();
    textarea.val('').val(val);
});

function tekliMernisSorgula(tc) {
    if (!tc) return;
    const btn = event.currentTarget;
    const oldHtml = btn.innerHTML;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sorgulanıyor...';
    btn.disabled = true;

    $.ajax({
        url: eshUrl('Patient', 'died'),
        type: 'GET',
        dataType: 'json',
        data: { tc: tc },
        success: function (response) {
            if (response.oldu > 0) {
                var vd = response.olumTarihi || '—';
                toastr.error(
                    "MERNİS: Hastanın vefat ettiği tespit edildi.<br><b>Vefat Tarihi: " + vd + "</b>",
                    "Sistem Uyarısı",
                    { timeOut: 5000, closeButton: true, progressBar: true }
                );
                setTimeout(function () {
                    window.location.reload();
                }, 1500);
            } else {
                toastr.info(response.mesaj || "Durum değişikliği yok.", "Sorgu Tamamlandı");
            }
        },
        complete: function () {
            btn.innerHTML = oldHtml;
            btn.disabled = false;
        }
    });
}

function deleteNote(btn, hastaId, noteIndex) {
    if (!confirm('Bu notu kalıcı olarak silmek istediğinize emin misiniz?')) return;
    const $button = $(btn);
    const $noteBox = $button.closest('.note-item');
    $button.prop('disabled', true);

    $.ajax({
        url: eshUrl('Patient', 'deleteNote'),
        type: 'POST',
        data: { id: hastaId, index: noteIndex },
        dataType: 'json',
        success: function (response) {
            if (response.success) {
                toastr.success("Not başarıyla silindi.");
                $noteBox.fadeOut(400, function () {
                    $(this).remove();
                    if ($('.note-item').length === 0) {
                        $('.modal-body').html('<div class="text-center py-5 text-muted"><i class="fa-solid fa-note-sticky fa-3x mb-3 opacity-25"></i><p>Tüm notlar silindi.</p></div>');
                    }
                });
            } else {
                toastr.error("Hata: " + response.message);
                $button.prop('disabled', false);
            }
        },
        error: function () {
            toastr.error("Sunucu hatası oluştu.");
            $button.prop('disabled', false);
        }
    });
}

/**
 * jQuery 4 uyumluluk: eski eklentiler için kaldırılan API'ler.
 */
(function ($) {
    'use strict';
    if (!$) {
        return;
    }
    if (typeof $.trim !== 'function') {
        $.trim = function (text) {
            return text == null ? '' : String(text).trim();
        };
    }
})(window.jQuery);

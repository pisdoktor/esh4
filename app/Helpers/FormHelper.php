<?php
namespace App\Helpers;

use stdClass;

/**
 * FormHelper - Form bileşenlerini standardize eden yardımcı sınıf.
 * Bootstrap 5, Tom Select ve Datepicker standartlarına uygun çıktı üretir.
 *
 * Liste tabloları işlem sütunu: {@see listActionButton()}, {@see listActionsGroup()}, {@see listActionsCell()},
 * {@see listActionDropdownItem()}, {@see listActionDropdownPostItem()}, {@see listActionsDropdown()},
 * {@see listActionsDateDropdown()}
 * — `.esh-list-actions.btn-group.btn-group-sm` + `public/assets/global.css`.
 */
class FormHelper {

    /**
     * Nitelik dizisini HTML formatına çevirir.
     */
    private static function parseAttributes(array $attr): string {
        $str = '';
        foreach ($attr as $key => $val) {
            if ($key === 'rows') continue;
            $str .= " " . htmlspecialchars($key) . "='" . htmlspecialchars((string)$val) . "'";
        }
        return $str;
    }

    /**
     * Veritabanı nesne listesini (Object List) key => value dizisine çevirir.
     */
    public static function makeOption(string $value, string $text = '', string $value_name = 'value', string $text_name = 'text'): stdClass {
        $obj = new stdClass();
        $obj->$value_name = $value;
        $obj->$text_name = trim($text) !== '' ? $text : $value;
        return $obj;
    }

    /**
     * Standart Input Alanı (Floating Label desteği eklendi)
     */
    public static function input($name, $label, $value = '', $type = 'text', $attr = []) {
        $attributes = self::parseAttributes($attr);
        $class = 'form-control ' . ($attr['class'] ?? '');
        return "
        <div class='form-floating mb-3'>
            <input type='{$type}' name='{$name}' id='{$name}' class='{$class}' value='{$value}' placeholder='{$label}' {$attributes}>
            <label for='{$name}' class='small fw-bold text-muted'>{$label}</label>
        </div>";
    }

    /**
     * Select (Açılır Menü) Alanı — Tom Select (.esh-tomselect) işaretçi sınıfı
     */
    public static function selectList(array $arr, string $tag_name, string $tag_attribs, string $key='value', string $text='text', mixed $selected = null, ?string $domId = null, bool $useTomSelect = true): string {
        if ($useTomSelect) {
            if (strpos($tag_attribs, 'class=') === false) {
                $tag_attribs .= ' class="form-select esh-tomselect"';
            } else {
                $tag_attribs = str_replace('class="', 'class="esh-tomselect ', $tag_attribs);
            }
        } elseif (strpos($tag_attribs, 'class=') === false) {
            $tag_attribs .= ' class="form-select"';
        }

        $idAttr = ($domId !== null && $domId !== '') ? $domId : $tag_name;
        $html = "\n<select name=\"" . htmlspecialchars($tag_name, ENT_QUOTES, 'UTF-8') . "\" id=\"" . htmlspecialchars($idAttr, ENT_QUOTES, 'UTF-8') . "\" $tag_attribs>";
            
        foreach ($arr as $obj) {
            $k = (string)$obj->$key;
            $t = (string)$obj->$text;
            $extra = '';
            
            if (is_array($selected)) {
                if (in_array($k, array_map('strval', $selected), true)) {
                    $extra = ' selected="selected"';
                }
            } else {
                if ($selected !== null && $selected !== '' && (string)$k === (string)$selected) {
                    $extra = ' selected="selected"';
                } elseif (($selected === '' || $selected === null) && $k === '') {
                    $extra = ' selected="selected"';
                }
            }

            $html .= "\n\t<option value=\"" . htmlspecialchars($k) . "\"$extra>" . htmlspecialchars($t) . "</option>";
        }
        $html .= "\n</select>\n";

        return $html;
    }

    /**
     * Evet/Hayır select listesi.
     */
    public static function yesnoSelectList(string $tag_name, string $tag_attribs, mixed $selected, string $yes = 'Evet', string $no = 'Hayır'): string {
        $arr = [
            self::makeOption('0', $no),
            self::makeOption('1', $yes),
        ];
        return self::selectList($arr, $tag_name, $tag_attribs, 'value', 'text', $selected);
    }

    /**
     * Radio buton listesi.
     */
    public static function radioList(array $arr, string $tag_name, string $tag_attribs, string $key = 'value', string $text = 'text', mixed $selected = null): string {
        $html = "";
        foreach ($arr as $obj) {
            $k = (string)$obj->$key;
            $t = (string)$obj->$text;
            $id = $obj->id ?? $tag_name . $k;

            $extra = '';
            if (is_array($selected)) {
                $extra = in_array($k, $selected) ? ' checked="checked"' : '';
            } else {
                $extra = ((string)$k === (string)$selected ? ' checked="checked"' : '');
            }

            $html .= "\n\t<div class='form-check form-check-inline'>";
            $html .= "\n\t<input type=\"radio\" class=\"form-check-input\" name=\"$tag_name\" id=\"" . htmlspecialchars((string)$id) . "\" value=\"" . htmlspecialchars($k) . "\"$extra $tag_attribs />";
            $html .= "\n\t<label class=\"form-check-label small\" for=\"" . htmlspecialchars((string)$id) . "\">" . htmlspecialchars($t) . "</label>";
            $html .= "\n\t</div>";
        }
        return $html;
    }

    /**
     * Textarea (Uzun Metin Alanı)
     */
    public static function textarea($name, $label, $value = '', $attr = []) {
        $attributes = self::parseAttributes($attr);
        $rows = $attr['rows'] ?? 3;
        return "
        <div class='form-group mb-3'>
            <label class='form-label small fw-bold text-muted' for='{$name}'>{$label}</label>
            <textarea name='{$name}' id='{$name}' class='form-control' rows='{$rows}' {$attributes}>{$value}</textarea>
        </div>";
    }

    /**
     * Bootstrap 5 Switch (Aç-Kapat Düğmesi)
     */
    public static function switch($name, $label, $checked = false, $value = '1') {
        $is_checked = $checked ? 'checked' : '';
        return "
        <div class='form-check form-switch mb-3 custom-switch'>
            <input class='form-check-input' type='checkbox' role='switch' name='{$name}' id='{$name}' value='{$value}' {$is_checked}>
            <label class='form-check-label small fw-bold text-muted' for='{$name}'>{$label}</label>
        </div>";
    }

    /* ---------------------------------------------------------------------
       ESH hasta formları — label üstte, btn-check, datepicker, switch+hidden
       --------------------------------------------------------------------- */

    private static function esc(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private static function wrapCol(string $html, string $col): string {
        if ($col === '') {
            return $html;
        }

        return '<div class="' . self::esc($col) . '">' . $html . '</div>';
    }

    private static function isTruthy(mixed $value): bool {
        return $value !== null && $value !== '' && $value !== false && $value !== 0 && $value !== '0';
    }

    /**
     * Label üstte standart metin girişi (ESH hasta formları).
     *
     * @param array<string,mixed> $opts col?, id?, idPrefix?, type?, required?, class?, placeholder?,
     *        invalidFeedback?, invalidFeedbackClass?, labelClass?, labelFor?, extraAttrs?
     */
    public static function fieldInput(string $name, string $label, mixed $value = '', array $opts = []): string {
        $col = (string) ($opts['col'] ?? '');
        $type = (string) ($opts['type'] ?? 'text');
        $id = (string) ($opts['id'] ?? '');
        if ($id === '' && !empty($opts['idPrefix'])) {
            $id = (string) $opts['idPrefix'] . $name;
        }
        $inputClass = trim('form-control ' . (string) ($opts['class'] ?? ''));
        $labelClass = (string) ($opts['labelClass'] ?? 'form-label small fw-bold text-muted');
        $labelFor = (string) ($opts['labelFor'] ?? $id);
        $valueStr = self::esc((string) $value);

        $attrs = ' type="' . self::esc($type) . '"';
        if (empty($opts['omitName'])) {
            $attrs .= ' name="' . self::esc($name) . '"';
        }
        $attrs .= ' class="' . self::esc($inputClass) . '" value="' . $valueStr . '"';
        if ($id !== '') {
            $attrs .= ' id="' . self::esc($id) . '"';
        }
        if (!empty($opts['required'])) {
            $attrs .= ' required';
        }
        if (isset($opts['placeholder']) && (string) $opts['placeholder'] !== '') {
            $attrs .= ' placeholder="' . self::esc((string) $opts['placeholder']) . '"';
        }
        if (isset($opts['minlength'])) {
            $attrs .= ' minlength="' . self::esc((string) $opts['minlength']) . '"';
        }
        if (isset($opts['maxlength'])) {
            $attrs .= ' maxlength="' . self::esc((string) $opts['maxlength']) . '"';
        }
        if (isset($opts['min'])) {
            $attrs .= ' min="' . self::esc((string) $opts['min']) . '"';
        }
        if (isset($opts['max'])) {
            $attrs .= ' max="' . self::esc((string) $opts['max']) . '"';
        }
        if (isset($opts['pattern'])) {
            $attrs .= ' pattern="' . self::esc((string) $opts['pattern']) . '"';
        }
        if (isset($opts['inputmode'])) {
            $attrs .= ' inputmode="' . self::esc((string) $opts['inputmode']) . '"';
        }
        if (array_key_exists('autocomplete', $opts)) {
            $attrs .= ' autocomplete="' . self::esc((string) $opts['autocomplete']) . '"';
        }
        if (!empty($opts['spellcheck'])) {
            $attrs .= ' spellcheck="' . (!empty($opts['spellcheck']) ? 'true' : 'false') . '"';
        } elseif (array_key_exists('spellcheck', $opts) && $opts['spellcheck'] === false) {
            $attrs .= ' spellcheck="false"';
        }
        if (isset($opts['title'])) {
            $attrs .= ' title="' . self::esc((string) $opts['title']) . '"';
        }
        if (isset($opts['ariaDescribedby'])) {
            $attrs .= ' aria-describedby="' . self::esc((string) $opts['ariaDescribedby']) . '"';
        }
        if (!empty($opts['extraAttrs']) && is_array($opts['extraAttrs'])) {
            foreach ($opts['extraAttrs'] as $ak => $av) {
                $attrs .= ' ' . self::esc((string) $ak) . '="' . self::esc((string) $av) . '"';
            }
        }

        $html = '';
        if (empty($opts['noLabel'])) {
            $html .= '<label class="' . self::esc($labelClass) . '"';
            if ($labelFor !== '') {
                $html .= ' for="' . self::esc($labelFor) . '"';
            }
            $html .= '>';
            if (!empty($opts['labelHtml'])) {
                $html .= (string) $opts['labelHtml'];
            } else {
                $html .= self::esc($label);
            }
            $html .= '</label>';
        }
        $html .= '<input' . $attrs . '>';

        if (isset($opts['invalidFeedback']) && (string) $opts['invalidFeedback'] !== '') {
            $fbClass = (string) ($opts['invalidFeedbackClass'] ?? 'invalid-feedback');
            $html .= '<div class="' . self::esc($fbClass) . '">' . self::esc((string) $opts['invalidFeedback']) . '</div>';
        }
        if (isset($opts['afterInput']) && (string) $opts['afterInput'] !== '') {
            $html .= (string) $opts['afterInput'];
        }

        return self::wrapCol($html, $col);
    }

    /**
     * Datepicker alanı — DateHelper::toTrOrEmpty ile değer formatlar.
     *
     * @param array<string,mixed> $opts fieldInput seçenekleri + fallbackToday?, rawValue? (zaten formatlıysa)
     */
    public static function fieldDate(string $name, string $label, mixed $rawDate, array $opts = []): string {
        $display = (string) ($opts['rawValue'] ?? DateHelper::toTrOrEmpty($rawDate));
        if ($display === '' && !empty($opts['fallbackToday'])) {
            $display = DateHelper::todayTr();
        }
        $opts['class'] = trim('datepicker ' . (string) ($opts['class'] ?? ''));
        if (!isset($opts['placeholder'])) {
            $opts['placeholder'] = 'GG-AA-YYYY';
        }
        if (!array_key_exists('autocomplete', $opts)) {
            $opts['autocomplete'] = 'off';
        }

        return self::fieldInput($name, $label, $display, $opts);
    }

    /**
     * Telefon maskeli metin girişi.
     */
    public static function fieldPhone(string $name, string $label, mixed $value = '', array $opts = []): string {
        $opts['class'] = trim('phone-mask ' . (string) ($opts['class'] ?? ''));

        return self::fieldInput($name, $label, $value, $opts);
    }

    /**
     * İkonlu input-group içinde datepicker (ör. sonda tarihi).
     *
     * @param array<string,mixed> $opts fieldDate seçenekleri + inputGroupSm?, icon?, labelClass?
     */
    public static function fieldDateGroup(string $name, string $label, mixed $rawDate, array $opts = []): string {
        $col = (string) ($opts['col'] ?? '');
        $labelClass = (string) ($opts['labelClass'] ?? 'x-small text-muted d-block');
        $labelFor = (string) ($opts['labelFor'] ?? ($opts['id'] ?? $name));
        $icon = (string) ($opts['icon'] ?? 'fa-solid fa-calendar-day');
        $prefixIconClass = (string) ($opts['prefixIconClass'] ?? '');
        $inputGroupExtra = trim((string) ($opts['inputGroupExtraClass'] ?? ''));
        $inputGroupClass = trim('input-group' . (!empty($opts['inputGroupSm']) ? ' input-group-sm' : '') . ($inputGroupExtra !== '' ? ' ' . $inputGroupExtra : ''));
        $inputClass = trim('form-control datepicker ' . (string) ($opts['class'] ?? ''));

        $display = (string) ($opts['rawValue'] ?? DateHelper::toTrOrEmpty($rawDate));
        if ($display === '' && !empty($opts['fallbackToday'])) {
            $display = DateHelper::todayTr();
        }

        $attrs = ' type="text" name="' . self::esc($name) . '" autocomplete="off" class="' . self::esc($inputClass) . '" value="' . self::esc($display) . '"';
        if (!empty($opts['id'])) {
            $attrs .= ' id="' . self::esc((string) $opts['id']) . '"';
        }
        if (!empty($opts['required'])) {
            $attrs .= ' required';
        }
        if (!isset($opts['placeholder'])) {
            $attrs .= ' placeholder="GG-AA-YYYY"';
        } else {
            $attrs .= ' placeholder="' . self::esc((string) $opts['placeholder']) . '"';
        }
        if (!empty($opts['extraAttrs']) && is_array($opts['extraAttrs'])) {
            foreach ($opts['extraAttrs'] as $ak => $av) {
                if ($av === null) {
                    continue;
                }
                $attrs .= ' ' . self::esc((string) $ak) . '="' . self::esc((string) $av) . '"';
            }
        }

        $html = '<label class="' . self::esc($labelClass) . '"';
        if ($labelFor !== '') {
            $html .= ' for="' . self::esc($labelFor) . '"';
        }
        $html .= '>';
        if (!empty($opts['labelHtml'])) {
            $html .= (string) $opts['labelHtml'];
        } else {
            $html .= self::esc($label);
        }
        $html .= '</label>';
        $html .= '<div class="' . self::esc($inputGroupClass) . '">';
        $prefixSpanClass = trim('input-group-text ' . $prefixIconClass);
        $html .= '<span class="' . self::esc($prefixSpanClass) . '"><i class="' . self::esc($icon) . '"></i></span>';
        $html .= '<input' . $attrs . '>';
        $html .= '</div>';
        if (isset($opts['afterInput']) && (string) $opts['afterInput'] !== '') {
            $html .= (string) $opts['afterInput'];
        }

        return self::wrapCol($html, $col);
    }

    /**
     * Başlangıç–bitiş tarih filtreleri (stats / izlem listeleri).
     *
     * @param array<string,mixed> $opts col?, label?, labelClass?, prefixIconClass?, class?, fromId?, toId?,
     *        fromPlaceholder?, toPlaceholder?, fromRawValue?, toRawValue?
     */
    public static function fieldDateRangeFilter(string $fromName, string $toName, mixed $fromValue, mixed $toValue, array $opts = []): string {
        $col = (string) ($opts['col'] ?? 'col-12 col-md-6');
        $label = (string) ($opts['label'] ?? 'Tarih aralığı');
        $labelClass = (string) ($opts['labelClass'] ?? 'form-label fw-semibold small text-secondary mb-2');
        $prefixIconClass = (string) ($opts['prefixIconClass'] ?? 'bg-white text-primary border-end-0');
        $inputClass = (string) ($opts['class'] ?? 'form-control-sm border-start-0');
        $fromPlaceholder = (string) ($opts['fromPlaceholder'] ?? 'Başlangıç');
        $toPlaceholder = (string) ($opts['toPlaceholder'] ?? 'Bitiş');
        $fromId = (string) ($opts['fromId'] ?? $fromName);
        $toId = (string) ($opts['toId'] ?? $toName);
        $fromDisplay = (string) ($opts['fromRawValue'] ?? $fromValue);
        $toDisplay = (string) ($opts['toRawValue'] ?? $toValue);

        $dateOpts = [
            'col' => '',
            'inputGroupSm' => true,
            'inputGroupExtraClass' => 'shadow-sm',
            'prefixIconClass' => $prefixIconClass,
            'class' => $inputClass,
            'labelClass' => 'visually-hidden',
        ];

        $html = '<label class="' . self::esc($labelClass) . '">' . self::esc($label) . '</label>';
        $html .= '<div class="d-flex flex-wrap align-items-stretch gap-2">';
        $html .= '<div style="min-width: 200px; max-width: 260px;">';
        $html .= self::fieldDateGroup($fromName, $fromPlaceholder, $fromValue, $dateOpts + [
            'id' => $fromId,
            'labelFor' => $fromId,
            'placeholder' => $fromPlaceholder,
            'rawValue' => $fromDisplay,
        ]);
        $html .= '</div>';
        $html .= '<span class="align-self-center text-muted small px-1">—</span>';
        $html .= '<div style="min-width: 200px; max-width: 260px;">';
        $html .= self::fieldDateGroup($toName, $toPlaceholder, $toValue, $dateOpts + [
            'id' => $toId,
            'labelFor' => $toId,
            'placeholder' => $toPlaceholder,
            'rawValue' => $toDisplay,
        ]);
        $html .= '</div>';
        $html .= '</div>';

        return self::wrapCol($html, $col);
    }

    /**
     * Tek input-group içinde başlangıç–bitiş tarih (birleşik hasta listesi filtreleri).
     *
     * @param array<string,mixed> $opts col?, label?, labelClass?, class?, inputGroupClass?, inputGroupId?, prefixIcon?, prefixIconClass?, fromPlaceholder?, toPlaceholder?
     */
    public static function fieldDateRangeInline(string $fromName, string $toName, mixed $fromValue, mixed $toValue, array $opts = []): string {
        $col = (string) ($opts['col'] ?? '');
        $label = (string) ($opts['label'] ?? '');
        $labelClass = (string) ($opts['labelClass'] ?? 'form-label small text-muted mb-1');
        $inputGroupClass = trim('input-group input-group-sm ' . (string) ($opts['inputGroupClass'] ?? ''));
        $inputClass = trim('form-control form-control-sm datepicker esh-filter-control ' . (string) ($opts['class'] ?? ''));
        $fromPlaceholder = (string) ($opts['fromPlaceholder'] ?? 'Başlangıç');
        $toPlaceholder = (string) ($opts['toPlaceholder'] ?? 'Bitiş');
        $fromDisplay = self::esc((string) ($opts['fromRawValue'] ?? $fromValue));
        $toDisplay = self::esc((string) ($opts['toRawValue'] ?? $toValue));
        $inputGroupId = (string) ($opts['inputGroupId'] ?? '');
        $prefixIcon = (string) ($opts['prefixIcon'] ?? '');

        $html = '';
        if ($label !== '' && empty($opts['noLabel'])) {
            $html .= '<label class="' . self::esc($labelClass) . '">' . self::esc($label) . '</label>';
        }
        $groupAttrs = ' class="' . self::esc($inputGroupClass) . '"';
        if ($inputGroupId !== '') {
            $groupAttrs .= ' id="' . self::esc($inputGroupId) . '"';
        }
        $html .= '<div' . $groupAttrs . '>';
        if ($prefixIcon !== '') {
            $prefixIconClass = (string) ($opts['prefixIconClass'] ?? 'bg-white border-0 px-3');
            $html .= '<span class="input-group-text ' . self::esc($prefixIconClass) . '"><i class="' . self::esc($prefixIcon) . '"></i></span>';
        }
        $html .= '<input type="text" name="' . self::esc($fromName) . '" class="' . self::esc(trim($inputClass . ' border-0')) . '" placeholder="' . self::esc($fromPlaceholder) . '" autocomplete="off" value="' . $fromDisplay . '">';
        $html .= '<span class="input-group-text bg-white border-0 small px-1 text-muted">-</span>';
        $html .= '<input type="text" name="' . self::esc($toName) . '" class="' . self::esc(trim($inputClass . ' border-0')) . '" placeholder="' . self::esc($toPlaceholder) . '" autocomplete="off" value="' . $toDisplay . '">';
        $html .= '</div>';

        return self::wrapCol($html, $col);
    }

    /**
     * input-group ile sayısal/ondalık alan (ör. boy/kilo ölçümleri).
     *
     * @param array<string,mixed> $opts col?, prefixIcon?, prefixIconClass?, suffixText?, inputGroupSm?,
     *        invalidFeedback?, labelClass?, class?, required?, placeholder?, id?
     */
    public static function fieldInputGroup(string $name, string $label, mixed $value, array $opts = []): string {
        $col = (string) ($opts['col'] ?? '');
        $labelClass = (string) ($opts['labelClass'] ?? 'small fw-bold text-muted mb-1');
        $labelFor = (string) ($opts['labelFor'] ?? ($opts['id'] ?? ''));
        $type = (string) ($opts['type'] ?? 'text');
        $inputGroupExtra = trim((string) ($opts['inputGroupExtraClass'] ?? ''));
        $inputGroupClass = trim('input-group' . (!empty($opts['inputGroupSm']) ? ' input-group-sm' : '') . ($inputGroupExtra !== '' ? ' ' . $inputGroupExtra : ''));
        $inputClass = trim('form-control ' . (string) ($opts['class'] ?? ''));
        $prefixIcon = (string) ($opts['prefixIcon'] ?? '');
        $prefixText = (string) ($opts['prefixText'] ?? '');
        $prefixHtml = (string) ($opts['prefixHtml'] ?? '');
        $prefixIconClass = (string) ($opts['prefixIconClass'] ?? 'bg-light text-info');
        $suffixText = (string) ($opts['suffixText'] ?? '');
        $id = (string) ($opts['id'] ?? '');

        $attrs = ' type="' . self::esc($type) . '" name="' . self::esc($name) . '" class="' . self::esc($inputClass) . '" value="' . self::esc((string) $value) . '"';
        if ($id !== '') {
            $attrs .= ' id="' . self::esc($id) . '"';
        }
        if (!empty($opts['required'])) {
            $attrs .= ' required';
        }
        if (isset($opts['placeholder'])) {
            $attrs .= ' placeholder="' . self::esc((string) $opts['placeholder']) . '"';
        }
        if (isset($opts['minlength'])) {
            $attrs .= ' minlength="' . self::esc((string) $opts['minlength']) . '"';
        }
        if (isset($opts['maxlength'])) {
            $attrs .= ' maxlength="' . self::esc((string) $opts['maxlength']) . '"';
        }
        if (isset($opts['min'])) {
            $attrs .= ' min="' . self::esc((string) $opts['min']) . '"';
        }
        if (isset($opts['max'])) {
            $attrs .= ' max="' . self::esc((string) $opts['max']) . '"';
        }
        if (isset($opts['pattern'])) {
            $attrs .= ' pattern="' . self::esc((string) $opts['pattern']) . '"';
        }
        if (isset($opts['inputmode'])) {
            $attrs .= ' inputmode="' . self::esc((string) $opts['inputmode']) . '"';
        }
        if (array_key_exists('autocomplete', $opts)) {
            $attrs .= ' autocomplete="' . self::esc((string) $opts['autocomplete']) . '"';
        }
        if (!empty($opts['spellcheck']) || (array_key_exists('spellcheck', $opts) && $opts['spellcheck'] === false)) {
            $attrs .= ' spellcheck="false"';
        }
        if (isset($opts['title'])) {
            $attrs .= ' title="' . self::esc((string) $opts['title']) . '"';
        }
        if (isset($opts['ariaDescribedby'])) {
            $attrs .= ' aria-describedby="' . self::esc((string) $opts['ariaDescribedby']) . '"';
        }
        if (!empty($opts['extraAttrs']) && is_array($opts['extraAttrs'])) {
            foreach ($opts['extraAttrs'] as $ak => $av) {
                if ($av === null) {
                    continue;
                }
                $attrs .= ' ' . self::esc((string) $ak) . '="' . self::esc((string) $av) . '"';
            }
        }

        if (empty($opts['noLabel'])) {
            $html = '<label class="' . self::esc($labelClass) . '"';
            if ($labelFor !== '') {
                $html .= ' for="' . self::esc($labelFor) . '"';
            }
            $html .= '>';
            if (!empty($opts['labelHtml'])) {
                $html .= (string) $opts['labelHtml'];
            } else {
                $html .= self::esc($label);
            }
            $html .= '</label>';
        } else {
            $html = '';
        }
        $html .= '<div class="' . self::esc($inputGroupClass) . '">';
        if ($prefixIcon !== '' || $prefixText !== '' || $prefixHtml !== '') {
            $prefixSpanAttrs = '';
            if (!empty($opts['prefixExtraAttrs']) && is_array($opts['prefixExtraAttrs'])) {
                foreach ($opts['prefixExtraAttrs'] as $pak => $pav) {
                    if ($pav === null) {
                        continue;
                    }
                    $prefixSpanAttrs .= ' ' . self::esc((string) $pak) . '="' . self::esc((string) $pav) . '"';
                }
            }
            $html .= '<span class="input-group-text ' . self::esc($prefixIconClass) . '"' . $prefixSpanAttrs . '>';
            if ($prefixHtml !== '') {
                $html .= $prefixHtml;
            } else {
                if ($prefixIcon !== '') {
                    $html .= '<i class="' . self::esc($prefixIcon) . '"></i>';
                }
                if ($prefixText !== '') {
                    $html .= self::esc($prefixText);
                }
            }
            $html .= '</span>';
        }
        $html .= '<input' . $attrs . '>';
        if (isset($opts['invalidFeedback']) && (string) $opts['invalidFeedback'] !== '') {
            $html .= '<div class="invalid-feedback">' . self::esc((string) $opts['invalidFeedback']) . '</div>';
        }
        if ($suffixText !== '') {
            $html .= '<span class="input-group-text bg-light small">' . self::esc($suffixText) . '</span>';
        }
        $html .= '</div>';
        if (isset($opts['afterInput']) && (string) $opts['afterInput'] !== '') {
            $html .= (string) $opts['afterInput'];
        }

        return self::wrapCol($html, $col);
    }

    /**
     * Evet/Hayır btn-check radio grubu.
     *
     * @param array<string,mixed> $opts label?, col?, noSuffix?, yesSuffix?, noLabel?, yesLabel?,
     *        labelClass?, groupClass?, groupMb?, btnSize?, ariaLabel?, required?
     */
    public static function btnCheckYesNo(string $name, mixed $value, string $idPrefix, array $opts = []): string {
        $label = (string) ($opts['label'] ?? '');
        $col = (string) ($opts['col'] ?? '');
        $noSuffix = (string) ($opts['noSuffix'] ?? 'Yok');
        $yesSuffix = (string) ($opts['yesSuffix'] ?? 'Var');
        $noLabel = (string) ($opts['noLabel'] ?? 'Hayır');
        $yesLabel = (string) ($opts['yesLabel'] ?? 'Evet');
        $labelClass = (string) ($opts['labelClass'] ?? 'form-label small fw-bold text-muted d-block');
        $groupClass = (string) ($opts['groupClass'] ?? 'btn-group w-100');
        $groupMb = (string) ($opts['groupMb'] ?? '');
        $btnSize = (string) ($opts['btnSize'] ?? 'btn-sm');
        $ariaLabel = (string) ($opts['ariaLabel'] ?? '');
        $required = !empty($opts['required']) ? ' required' : '';

        $isYes = self::isTruthy($value);
        $noId = $idPrefix . $noSuffix;
        $yesId = $idPrefix . $yesSuffix;

        $html = '';
        if ($label !== '') {
            $html .= '<label class="' . self::esc($labelClass) . '">' . self::esc($label) . '</label>';
        }
        $roleAttr = $ariaLabel !== '' ? ' aria-label="' . self::esc($ariaLabel) . '"' : '';
        $mbAttr = $groupMb !== '' ? ' ' . self::esc($groupMb) : '';
        $html .= '<div class="' . self::esc($groupClass) . $mbAttr . '" role="group"' . $roleAttr . '>';
        $html .= '<input type="radio" class="btn-check" name="' . self::esc($name) . '" id="' . self::esc($noId) . '" value="0"' . (!$isYes ? ' checked' : '') . $required . '>';
        $html .= '<label class="btn btn-outline-secondary ' . self::esc($btnSize) . '" for="' . self::esc($noId) . '">' . self::esc($noLabel) . '</label>';
        $html .= '<input type="radio" class="btn-check" name="' . self::esc($name) . '" id="' . self::esc($yesId) . '" value="1"' . ($isYes ? ' checked' : '') . $required . '>';
        $html .= '<label class="btn btn-outline-primary ' . self::esc($btnSize) . '" for="' . self::esc($yesId) . '">' . self::esc($yesLabel) . '</label>';
        $html .= '</div>';

        return self::wrapCol($html, $col);
    }

    /**
     * Özel seçenekli btn-check radio grubu.
     *
     * @param list<array{value:string,id:string,label?:string,labelHtml?:string,btnClass?:string,checked?:bool,extraInputAttrs?:string}> $options
     * @param array<string,mixed> $opts label?, col?, labelClass?, groupClass?, groupMb?, ariaLabel?, name required attrs
     */
    public static function btnCheckRadioGroup(string $name, array $options, array $opts = []): string {
        $label = (string) ($opts['label'] ?? '');
        $col = (string) ($opts['col'] ?? '');
        $labelClass = (string) ($opts['labelClass'] ?? 'form-label small fw-bold text-muted d-block');
        $groupClass = (string) ($opts['groupClass'] ?? 'btn-group w-100');
        $groupMb = (string) ($opts['groupMb'] ?? '');
        $ariaLabel = (string) ($opts['ariaLabel'] ?? '');
        $required = !empty($opts['required']) ? ' required' : '';
        $autocomplete = array_key_exists('autocomplete', $opts) ? ' autocomplete="' . self::esc((string) $opts['autocomplete']) . '"' : '';

        $html = '';
        if ($label !== '') {
            $html .= '<label class="' . self::esc($labelClass) . '">' . self::esc($label) . '</label>';
        }
        $roleAttr = $ariaLabel !== '' ? ' aria-label="' . self::esc($ariaLabel) . '"' : '';
        $mbAttr = $groupMb !== '' ? ' ' . self::esc($groupMb) : '';
        $html .= '<div class="' . self::esc($groupClass) . $mbAttr . '" role="group"' . $roleAttr . '>';

        foreach ($options as $opt) {
            $val = (string) ($opt['value'] ?? '');
            $id = (string) ($opt['id'] ?? '');
            $btnClass = (string) ($opt['btnClass'] ?? 'btn btn-outline-secondary btn-sm');
            $checked = !empty($opt['checked']) ? ' checked' : '';
            $extraInput = (string) ($opt['extraInputAttrs'] ?? '');

            $html .= '<input type="radio" class="btn-check" name="' . self::esc($name) . '" id="' . self::esc($id) . '" value="' . self::esc($val) . '"' . $checked . $required . $autocomplete . $extraInput . '>';
            $html .= '<label class="' . self::esc($btnClass) . '" for="' . self::esc($id) . '">';
            if (!empty($opt['labelHtml'])) {
                $html .= (string) $opt['labelHtml'];
            } else {
                $html .= self::esc((string) ($opt['label'] ?? $val));
            }
            $html .= '</label>';
        }

        $html .= '</div>';

        return self::wrapCol($html, $col);
    }

    /**
     * Hidden 0 + checkbox switch (ESH tıbbi cihaz deseni).
     *
     * @param array<string,mixed> $opts col?, id?, switchClass?, labelClass?
     */
    public static function switchWithHidden(string $name, string $label, bool $checked, string $idPrefix = '', array $opts = []): string {
        $col = (string) ($opts['col'] ?? 'col-md-4 mb-3');
        $id = (string) ($opts['id'] ?? '');
        if ($id === '') {
            $id = $idPrefix !== '' ? $idPrefix . 'dev-' . $name : 'dev-' . $name;
        }
        $switchClass = (string) ($opts['switchClass'] ?? 'form-check form-switch custom-switch');
        $labelClass = (string) ($opts['labelClass'] ?? 'form-check-label small fw-bold');
        $checkedAttr = $checked ? ' checked' : '';

        $html = '<div class="' . self::esc($switchClass) . '">';
        $html .= '<input type="hidden" name="' . self::esc($name) . '" value="0">';
        $html .= '<input class="form-check-input" type="checkbox" name="' . self::esc($name) . '" id="' . self::esc($id) . '" value="1"' . $checkedAttr . '>';
        $html .= '<label class="' . self::esc($labelClass) . '" for="' . self::esc($id) . '">' . self::esc($label) . '</label>';
        $html .= '</div>';

        return self::wrapCol($html, $col);
    }

    /**
     * Label + textarea + opsiyonel yardım metni.
     *
     * @param array<string,mixed> $opts col?, rows?, maxlength?, placeholder?, class?, labelClass?,
     *        labelHtml?, helpText?, helpClass?
     */
    public static function fieldTextarea(string $name, string $label, string $value = '', array $opts = []): string {
        $col = (string) ($opts['col'] ?? '');
        $rows = (int) ($opts['rows'] ?? 3);
        $textareaClass = trim('form-control ' . (string) ($opts['class'] ?? ''));
        $labelClass = (string) ($opts['labelClass'] ?? 'form-label small fw-bold text-muted');
        $labelFor = (string) ($opts['labelFor'] ?? $name);
        $maxlength = isset($opts['maxlength']) ? ' maxlength="' . self::esc((string) $opts['maxlength']) . '"' : '';
        $placeholder = isset($opts['placeholder']) ? ' placeholder="' . self::esc((string) $opts['placeholder']) . '"' : '';
        $id = (string) ($opts['id'] ?? '');
        if ($id !== '') {
            $idAttr = ' id="' . self::esc($id) . '"';
            if ($labelFor === $name) {
                $labelFor = $id;
            }
        } else {
            $idAttr = '';
        }
        if (!empty($opts['required'])) {
            $idAttr .= ' required';
        }

        $html = '';
        if (empty($opts['noLabel'])) {
            $html .= '<label class="' . self::esc($labelClass) . '"';
            if ($labelFor !== '') {
                $html .= ' for="' . self::esc($labelFor) . '"';
            }
            $html .= '>';
            if (!empty($opts['labelHtml'])) {
                $html .= (string) $opts['labelHtml'];
            } else {
                $html .= self::esc($label);
            }
            $html .= '</label>';
        }
        $html .= '<textarea name="' . self::esc($name) . '" class="' . self::esc($textareaClass) . '" rows="' . $rows . '"' . $idAttr . $maxlength . $placeholder . '>' . self::esc($value) . '</textarea>';

        if (isset($opts['helpText']) && (string) $opts['helpText'] !== '') {
            $helpClass = (string) ($opts['helpClass'] ?? 'text-muted mt-2 d-block');
            $helpStyle = isset($opts['helpStyle']) ? ' style="' . self::esc((string) $opts['helpStyle']) . '"' : '';
            $html .= '<small class="' . self::esc($helpClass) . '"' . $helpStyle . '>' . self::esc((string) $opts['helpText']) . '</small>';
        }

        return self::wrapCol($html, $col);
    }

    /**
     * Label + select (selectList sarmalayıcı).
     *
     * @param list<stdClass> $options makeOption dizisi
     * @param array<string,mixed> $opts col?, id?, labelClass?, labelFor?, placeholder?, tomSelect?, required?, class?, helpText?, helpClass?
     */
    public static function fieldSelect(string $name, string $label, array $options, mixed $selected = null, array $opts = []): string {
        $col = (string) ($opts['col'] ?? '');
        $labelClass = (string) ($opts['labelClass'] ?? 'form-label fw-bold text-secondary');
        $id = (string) ($opts['id'] ?? $name);
        $labelFor = (string) ($opts['labelFor'] ?? $id);
        $tomSelect = array_key_exists('tomSelect', $opts) ? (bool) $opts['tomSelect'] : true;
        $extraClass = trim((string) ($opts['class'] ?? ''));

        $arr = $options;
        if (isset($opts['placeholder']) && (string) $opts['placeholder'] !== '') {
            array_unshift($arr, self::makeOption('', (string) $opts['placeholder']));
        }

        $tagClass = trim('form-select ' . $extraClass);
        $tagAttribs = 'class="' . $tagClass . '"';
        if (!empty($opts['required'])) {
            $tagAttribs .= ' required';
        }
        if (array_key_exists('autocomplete', $opts)) {
            $tagAttribs .= ' autocomplete="' . self::esc((string) $opts['autocomplete']) . '"';
        }
        if (!empty($opts['extraAttrs']) && is_array($opts['extraAttrs'])) {
            foreach ($opts['extraAttrs'] as $ak => $av) {
                $tagAttribs .= ' ' . self::esc((string) $ak) . '="' . self::esc((string) $av) . '"';
            }
        }

        $html = '';
        if (empty($opts['noLabel'])) {
            $html .= '<label class="' . self::esc($labelClass) . '" for="' . self::esc($labelFor) . '">';
            if (!empty($opts['labelHtml'])) {
                $html .= (string) $opts['labelHtml'];
            } else {
                $html .= self::esc($label);
            }
            $html .= '</label>';
        }
        $html .= self::selectList($arr, $name, $tagAttribs, 'value', 'text', $selected, $id, $tomSelect);

        if (isset($opts['helpText']) && (string) $opts['helpText'] !== '') {
            $helpClass = (string) ($opts['helpClass'] ?? 'form-text text-muted small');
            $html .= '<div class="' . self::esc($helpClass) . '">' . self::esc((string) $opts['helpText']) . '</div>';
        }
        if (isset($opts['afterInput']) && (string) $opts['afterInput'] !== '') {
            $html .= (string) $opts['afterInput'];
        }

        return self::wrapCol($html, $col);
    }

    /**
     * Standart form-check checkbox.
     *
     * @param array<string,mixed> $opts col?, id?, value?, wrapClass?, labelClass?, checked?
     */
    public static function fieldCheckbox(string $name, string $label, bool $checked, array $opts = []): string {
        $col = (string) ($opts['col'] ?? '');
        $id = (string) ($opts['id'] ?? $name);
        $value = (string) ($opts['value'] ?? '1');
        $wrapClass = (string) ($opts['wrapClass'] ?? 'form-check');
        $labelClass = (string) ($opts['labelClass'] ?? 'form-check-label');
        $checkedAttr = $checked ? ' checked' : '';

        $html = '<div class="' . self::esc($wrapClass) . '">';
        $html .= '<input class="form-check-input" type="checkbox" name="' . self::esc($name) . '" id="' . self::esc($id) . '" value="' . self::esc($value) . '"' . $checkedAttr . '>';
        $html .= '<label class="' . self::esc($labelClass) . '" for="' . self::esc($id) . '">' . self::esc($label) . '</label>';
        $html .= '</div>';

        return self::wrapCol($html, $col);
    }

    /**
     * Bootstrap form-switch (checkbox); isteğe bağlı sarmalayıcı sınıfı.
     *
     * @param array<string,mixed> $opts col?, id?, value?, wrapClass?, labelClass?, switchInputClass?
     */
    public static function fieldSwitch(string $name, string $label, bool $checked, array $opts = []): string {
        $col = (string) ($opts['col'] ?? '');
        $id = (string) ($opts['id'] ?? $name);
        $value = (string) ($opts['value'] ?? '1');
        $wrapClass = (string) ($opts['wrapClass'] ?? 'form-check form-switch');
        $labelClass = (string) ($opts['labelClass'] ?? 'form-check-label');
        $inputClass = trim('form-check-input ' . (string) ($opts['switchInputClass'] ?? ''));
        $checkedAttr = $checked ? ' checked' : '';
        $roleAttr = !empty($opts['roleSwitch']) ? ' role="switch"' : '';

        $html = '<div class="' . self::esc($wrapClass) . '">';
        $html .= '<input class="' . self::esc($inputClass) . '" type="checkbox" name="' . self::esc($name) . '" id="' . self::esc($id) . '" value="' . self::esc($value) . '"' . $checkedAttr . $roleAttr . '>';
        $html .= '<label class="' . self::esc($labelClass) . '" for="' . self::esc($id) . '">' . self::esc($label) . '</label>';
        $html .= '</div>';
        if (isset($opts['afterInput']) && (string) $opts['afterInput'] !== '') {
            $html .= (string) $opts['afterInput'];
        }

        return self::wrapCol($html, $col);
    }

    /* ---------------------------------------------------------------------
       Liste tabloları — işlem sütunu (site geneli standart)
       Bootstrap: .esh-list-actions.btn-group.btn-group-sm + .btn / .btn-outline-*
       --------------------------------------------------------------------- */

    /**
     * İkon sınıfları — güvenli küçük whitelist (Font Awesome ailesi).
     */
    private static function sanitizeListActionIconClasses(string $icon): string {
        $icon = preg_replace('/[^a-zA-Z0-9\s\-]/', '', trim($icon));

        return $icon !== '' ? $icon : 'fa-solid fa-circle';
    }

    /**
     * Tek işlem linki (ikonlu düğme).
     *
     * @param array<string,mixed> $opts href (zorunlu), icon (zorunlu), title?, variant? (primary|secondary|…),
     *        outline? (varsayılan true; false → dolgu düğme örn. success),
     *        confirm? (metin verilirse onclick confirm eklenir),
     *        onclick? (confirm ile birlikte verilirse öncelik confirm birleşimi: önce confirm),
     *        extraAttrs? (örn. ['target'=>'_blank'])
     */
    public static function listActionButton(array $opts): string {
        if (!empty($opts['postAction']) || (isset($opts['action']) && !isset($opts['href']))) {
            return self::listPostActionButton($opts);
        }

        $href = htmlspecialchars((string) ($opts['href'] ?? '#'), ENT_QUOTES, 'UTF-8');
        $title = isset($opts['title']) ? htmlspecialchars((string) $opts['title'], ENT_QUOTES, 'UTF-8') : '';
        $icon = self::sanitizeListActionIconClasses((string) ($opts['icon'] ?? ''));

        $variant = strtolower(preg_replace('/[^a-z]/', '', (string) ($opts['variant'] ?? 'primary')));
        $allowed = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
        if (!in_array($variant, $allowed, true)) {
            $variant = 'primary';
        }

        $outline = array_key_exists('outline', $opts) ? (bool) $opts['outline'] : true;
        $btnVariant = $outline ? ('btn-outline-' . $variant) : ('btn-' . $variant);

        $onclickJs = '';
        if (!empty($opts['confirm'])) {
            $q = json_encode((string) $opts['confirm'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
            $onclickJs = 'return confirm(' . $q . ');';
        } elseif (!empty($opts['onclick'])) {
            $onclickJs = trim((string) $opts['onclick']);
        }
        $onclickAttr = $onclickJs !== '' ? ' onclick="' . htmlspecialchars($onclickJs, ENT_QUOTES, 'UTF-8') . '"' : '';

        $extra = '';
        if (!empty($opts['extraAttrs']) && is_array($opts['extraAttrs'])) {
            foreach ($opts['extraAttrs'] as $ak => $av) {
                $ak = preg_replace('/[^a-zA-Z0-9\-]/', '', (string) $ak);
                if ($ak === '') {
                    continue;
                }
                $extra .= ' ' . htmlspecialchars($ak, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $av, ENT_QUOTES, 'UTF-8') . '"';
            }
        }

        $titleAttr = $title !== '' ? ' title="' . $title . '"' : '';

        return '<a class="btn ' . htmlspecialchars($btnVariant, ENT_QUOTES, 'UTF-8') . '" href="' . $href . '"' . $titleAttr . $onclickAttr . $extra
            . '><i class="' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></i></a>';
    }

    /**
     * POST form ile silme / mutasyon düğmesi (CSRF korumalı).
     *
     * @param array<string,mixed> $opts action (zorunlu), hidden? (name=>value), icon?, title?, variant?, confirm?
     */
    public static function listPostActionButton(array $opts): string {
        $action = htmlspecialchars((string) ($opts['action'] ?? ''), ENT_QUOTES, 'UTF-8');
        $title = isset($opts['title']) ? htmlspecialchars((string) $opts['title'], ENT_QUOTES, 'UTF-8') : '';
        $icon = self::sanitizeListActionIconClasses((string) ($opts['icon'] ?? 'fa-solid fa-trash'));

        $variant = strtolower(preg_replace('/[^a-z]/', '', (string) ($opts['variant'] ?? 'danger')));
        $allowed = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
        if (!in_array($variant, $allowed, true)) {
            $variant = 'danger';
        }

        $outline = array_key_exists('outline', $opts) ? (bool) $opts['outline'] : true;
        $btnVariant = $outline ? ('btn-outline-' . $variant) : ('btn-' . $variant);

        $onsubmit = '';
        if (!empty($opts['confirm'])) {
            $q = json_encode((string) $opts['confirm'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
            $onsubmit = ' onsubmit="return confirm(' . $q . ');"';
        }

        $hiddenHtml = '';
        $hidden = $opts['hidden'] ?? [];
        if (is_array($hidden)) {
            foreach ($hidden as $name => $value) {
                $name = preg_replace('/[^a-zA-Z0-9_\[\]]/', '', (string) $name);
                if ($name === '') {
                    continue;
                }
                $hiddenHtml .= '<input type="hidden" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="'
                    . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '">';
            }
        }

        $titleAttr = $title !== '' ? ' title="' . $title . '"' : '';

        return '<form method="post" action="' . $action . '" class="d-inline m-0"' . $onsubmit . '>'
            . $hiddenHtml
            . '<button type="submit" class="btn ' . htmlspecialchars($btnVariant, ENT_QUOTES, 'UTF-8') . '"' . $titleAttr . '>'
            . '<i class="' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></i>'
            . '</button></form>';
    }

    /**
     * İşlem düğmelerini btn-group içinde sarar (tek başına da kullanılabilir).
     */
    public static function listActionsGroup(string $buttonsInnerHtml): string {
        return '<div class="esh-list-actions btn-group btn-group-sm" role="group" aria-label="İşlemler">'
            . $buttonsInnerHtml . '</div>';
    }

    /**
     * Tam işlem hücresi: &lt;td&gt; + grup + dizi ile tanımlı düğmeler.
     *
     * @param array<int,array<string,mixed>|false|null> $buttons listActionButton seçenekleri; null/false atlanır
     * @param array<string,mixed> $cellOpts smallTd (bool, varsayılan true), align: end|center|start
     */
    public static function listActionsCell(array $buttons, array $cellOpts = []): string {
        $small = array_key_exists('smallTd', $cellOpts) ? (bool) $cellOpts['smallTd'] : true;
        $align = (string) ($cellOpts['align'] ?? 'end');
        $alignClass = match ($align) {
            'center' => 'text-center',
            'start' => 'text-start',
            default => 'text-end',
        };
        $smallClass = $small ? ' small' : '';

        $inner = '';
        foreach ($buttons as $b) {
            if ($b === null || $b === false) {
                continue;
            }
            if (!is_array($b)) {
                continue;
            }
            $inner .= self::listActionButton($b);
        }

        return '<td class="' . $alignClass . $smallClass . '">' . self::listActionsGroup($inner) . '</td>';
    }

    /**
     * Dropdown menü öğesi (liste tabloları).
     *
     * @param array<string,mixed> $opts href (zorunlu), icon?, title? veya label?, variant?, confirm?, onclick?, extraAttrs?
     */
    public static function listActionDropdownItem(array $opts): string {
        if (!empty($opts['postAction']) || (isset($opts['action']) && !isset($opts['href']))) {
            return self::listActionDropdownPostItem($opts);
        }

        $href = htmlspecialchars((string) ($opts['href'] ?? '#'), ENT_QUOTES, 'UTF-8');
        $labelRaw = (string) ($opts['label'] ?? ($opts['title'] ?? ''));
        $label = htmlspecialchars($labelRaw, ENT_QUOTES, 'UTF-8');
        $icon = self::sanitizeListActionIconClasses((string) ($opts['icon'] ?? ''));

        $variant = strtolower(preg_replace('/[^a-z]/', '', (string) ($opts['variant'] ?? 'primary')));
        $itemClass = 'dropdown-item';
        if ($variant === 'danger') {
            $itemClass .= ' text-danger';
        }

        $onclickJs = '';
        if (!empty($opts['confirm'])) {
            $q = json_encode((string) $opts['confirm'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
            $onclickJs = 'return confirm(' . $q . ');';
        } elseif (!empty($opts['onclick'])) {
            $onclickJs = trim((string) $opts['onclick']);
        }
        $onclickAttr = $onclickJs !== '' ? ' onclick="' . htmlspecialchars($onclickJs, ENT_QUOTES, 'UTF-8') . '"' : '';

        $extra = '';
        if (!empty($opts['extraAttrs']) && is_array($opts['extraAttrs'])) {
            foreach ($opts['extraAttrs'] as $ak => $av) {
                $ak = preg_replace('/[^a-zA-Z0-9\-]/', '', (string) $ak);
                if ($ak === '') {
                    continue;
                }
                $extra .= ' ' . htmlspecialchars($ak, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $av, ENT_QUOTES, 'UTF-8') . '"';
            }
        }

        $iconHtml = $icon !== '' && $icon !== 'fa-solid fa-circle'
            ? '<i class="' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . ' me-2 opacity-75" aria-hidden="true"></i>'
            : '';

        return '<li><a class="' . htmlspecialchars($itemClass, ENT_QUOTES, 'UTF-8') . '" href="' . $href . '"' . $onclickAttr . $extra . '>'
            . $iconHtml . $label . '</a></li>';
    }

    /**
     * Dropdown menü öğesi — POST form (silme vb.).
     *
     * @param array<string,mixed> $opts action (zorunlu), hidden?, icon?, title? veya label?, variant?, confirm?
     */
    public static function listActionDropdownPostItem(array $opts): string {
        $action = htmlspecialchars((string) ($opts['action'] ?? ''), ENT_QUOTES, 'UTF-8');
        $labelRaw = (string) ($opts['label'] ?? ($opts['title'] ?? ''));
        $label = htmlspecialchars($labelRaw, ENT_QUOTES, 'UTF-8');
        $icon = self::sanitizeListActionIconClasses((string) ($opts['icon'] ?? ''));

        $variant = strtolower(preg_replace('/[^a-z]/', '', (string) ($opts['variant'] ?? 'danger')));
        $itemClass = 'dropdown-item';
        if ($variant === 'danger') {
            $itemClass .= ' text-danger';
        }

        $onsubmit = '';
        if (!empty($opts['confirm'])) {
            $q = json_encode((string) $opts['confirm'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
            $onsubmit = ' onsubmit="return confirm(' . $q . ');"';
        }

        $hiddenHtml = '';
        $hidden = $opts['hidden'] ?? [];
        if (is_array($hidden)) {
            foreach ($hidden as $name => $value) {
                $name = preg_replace('/[^a-zA-Z0-9_\[\]]/', '', (string) $name);
                if ($name === '') {
                    continue;
                }
                $hiddenHtml .= '<input type="hidden" name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '" value="'
                    . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '">';
            }
        }

        $iconHtml = $icon !== '' && $icon !== 'fa-solid fa-circle'
            ? '<i class="' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . ' me-2 opacity-75" aria-hidden="true"></i>'
            : '';

        return '<li><form method="post" action="' . $action . '" class="m-0"' . $onsubmit . '>'
            . $hiddenHtml
            . '<button type="submit" class="' . htmlspecialchars($itemClass, ENT_QUOTES, 'UTF-8') . ' w-100 text-start border-0 bg-transparent">'
            . $iconHtml . $label
            . '</button></form></li>';
    }

    /**
     * İşlem düğmeleri — Bootstrap dropdown (⋮).
     *
     * @param array<int,array<string,mixed>|false|null> $buttons
     * @param array<string,mixed> $opts toggleTitle?, toggleIcon?, btnClass?, menuAlign?: end|start
     */
    public static function listActionsDropdown(array $buttons, array $opts = []): string {
        $items = '';
        foreach ($buttons as $b) {
            if ($b === null || $b === false || !is_array($b)) {
                continue;
            }
            $items .= self::listActionDropdownItem($b);
        }
        if ($items === '') {
            return '';
        }

        $toggleTitle = htmlspecialchars((string) ($opts['toggleTitle'] ?? 'İşlemler'), ENT_QUOTES, 'UTF-8');
        $toggleIcon = self::sanitizeListActionIconClasses((string) ($opts['toggleIcon'] ?? 'fa-solid fa-ellipsis-vertical'));
        $btnClass = trim((string) ($opts['btnClass'] ?? 'btn btn-sm btn-outline-secondary esh-list-actions-dropdown-toggle'));
        $menuAlign = (string) ($opts['menuAlign'] ?? 'end');
        $menuClass = 'dropdown-menu shadow-sm' . ($menuAlign === 'start' ? '' : ' dropdown-menu-end');

        return '<div class="dropdown esh-list-actions-dropdown">'
            . '<button type="button" class="' . htmlspecialchars($btnClass, ENT_QUOTES, 'UTF-8') . '"'
            . ' data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" title="' . $toggleTitle . '">'
            . '<i class="' . htmlspecialchars($toggleIcon, ENT_QUOTES, 'UTF-8') . '" aria-hidden="true"></i>'
            . '</button>'
            . '<ul class="' . htmlspecialchars($menuClass, ENT_QUOTES, 'UTF-8') . '">' . $items . '</ul>'
            . '</div>';
    }

    /**
     * İşlem menüsü — plan tarihi (veya benzeri etiket) tıklanınca açılır dropdown.
     *
     * @param array<int,array<string,mixed>|false|null> $buttons listActionButton / listActionDropdownItem seçenekleri
     * @param array<string,mixed> $opts toggleClass?, menuAlign?: end|start, emptyLabel?: string
     */
    public static function listActionsDateDropdown(array $buttons, string $dateLabel, array $opts = []): string {
        $items = '';
        foreach ($buttons as $b) {
            if ($b === null || $b === false || !is_array($b)) {
                continue;
            }
            $items .= self::listActionDropdownItem($b);
        }
        if ($items === '') {
            return htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8');
        }

        $toggleClass = trim((string) ($opts['toggleClass'] ?? 'dropdown-toggle text-decoration-none fw-semibold esh-row-date-dropdown-toggle'));
        $menuAlign = (string) ($opts['menuAlign'] ?? 'start');
        $menuClass = 'dropdown-menu shadow-sm border-0 py-2' . ($menuAlign === 'end' ? ' dropdown-menu-end' : '');
        $dateEsc = htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8');
        $toggleTitle = htmlspecialchars((string) ($opts['toggleTitle'] ?? 'İşlemler'), ENT_QUOTES, 'UTF-8');

        return '<div class="dropdown d-inline-block esh-row-date-dropdown">'
            . '<a href="#" class="' . htmlspecialchars($toggleClass, ENT_QUOTES, 'UTF-8') . '"'
            . ' role="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false"'
            . ' title="' . $toggleTitle . '">' . $dateEsc . '</a>'
            . '<ul class="' . htmlspecialchars($menuClass, ENT_QUOTES, 'UTF-8') . '">' . $items . '</ul>'
            . '</div>';
    }
}
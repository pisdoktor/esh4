<?php
namespace App\Helpers;

/**
 * Tema theme.css — renk, gradyan, diğer jetonlar ve oturum önizlemesi.
 */
class ThemeCssColorHelper
{
    private const SESSION_KEY = 'esh_theme_preview';

    /** @var list<string> */
    private const ESH_UI_TYPOGRAPHY_TOKEN_NAMES = [
        '--esh-ui-font-family',
        '--esh-ui-font-size-base',
        '--esh-ui-line-height',
        '--esh-ui-heading-size',
        '--esh-ui-lead-size',
        '--esh-ui-font-weight-heading',
    ];

    /** body kurallarında düzenlenebilir tipografi özellikleri */
    private const TYPOGRAPHY_BODY_PROPERTIES = [
        'font-family',
        'font-size',
        'line-height',
        'letter-spacing',
        'font-feature-settings',
        'font-weight',
    ];

    /** body kurallarında düzenlenebilir efekt / yüzey özellikleri (tipografi hariç) */
    private const OTHER_BODY_PROPERTIES = [
        'backdrop-filter',
        'box-shadow',
        'border-radius',
        'color',
        'background-color',
    ];

    /**
     * @return list<array{name:string,value:string,line:int,picker_hex:?string,kind:string,label:string,group:string}>
     */
    public static function parseColorTokens(string $themeSlug): array
    {
        $rows = self::parseVariableTokens($themeSlug, static fn (string $value): bool => self::isColorCSSValue($value));

        $out = array_values(array_filter(
            $rows,
            static fn (array $row): bool => !self::isEshUiTokenName((string) ($row['name'] ?? ''))
        ));

        foreach ($out as &$row) {
            $name = (string) ($row['name'] ?? '');
            $row['label'] = ThemeColorTokenCatalog::label($name);
            $row['group'] = ThemeColorTokenCatalog::group($name);
        }
        unset($row);

        return $out;
    }

    /**
     * Renk jetonları — editörde grup başlıklarıyla.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public static function parseColorTokenGroups(string $themeSlug): array
    {
        $grouped = [];
        foreach (self::parseColorTokens($themeSlug) as $row) {
            $group = (string) ($row['group'] ?? 'Diğer');
            $grouped[$group][] = $row;
        }
        $sorted = [];
        foreach (ThemeColorTokenCatalog::groupOrder() as $groupName) {
            if (!empty($grouped[$groupName])) {
                $sorted[$groupName] = $grouped[$groupName];
            }
        }
        foreach ($grouped as $groupName => $rows) {
            if (!isset($sorted[$groupName])) {
                $sorted[$groupName] = $rows;
            }
        }

        return $sorted;
    }

    /**
     * Sayfa standardı jetonları — `docs/ESH_PAGE_LANGUAGE.md` (--esh-ui-*).
     *
     * @return list<array{id:string,type:string,name:string,value:string,line:int,label:string,group:string,hint:string,missing_in_file:bool,picker_hex:?string}>
     */
    public static function parseEshUiVariableTokens(string $themeSlug): array
    {
        $slug = ThemeViewHelper::sanitizeThemeSlug($themeSlug);
        $fromFile = self::parseEshUiVariablesFromCss($slug);
        $meta = EshUiTokenCatalog::tokenMeta();
        $defaults = EshUiTokenCatalog::defaultValues($slug);
        $out = [];
        $seen = [];

        foreach ($meta as $name => $info) {
            $seen[$name] = true;
            $inFile = array_key_exists($name, $fromFile);
            $value = $inFile ? $fromFile[$name]['value'] : ($defaults[$name] ?? '');
            $out[] = [
                'id' => 'var:' . $name,
                'type' => 'var',
                'name' => $name,
                'value' => $value,
                'line' => $inFile ? (int) $fromFile[$name]['line'] : 0,
                'label' => $info['label'],
                'group' => $info['group'],
                'module' => EshUiTokenCatalog::moduleForGroup($info['group']),
                'hint' => 'sayfa-standardı',
                'missing_in_file' => !$inFile,
                'picker_hex' => self::colorValueToPickerHex($value),
            ];
        }

        foreach ($fromFile as $name => $fileMeta) {
            if (isset($seen[$name])) {
                continue;
            }
            $value = (string) $fileMeta['value'];
            $out[] = [
                'id' => 'var:' . $name,
                'type' => 'var',
                'name' => $name,
                'value' => $value,
                'line' => (int) $fileMeta['line'],
                'label' => 'Yalnızca bu tema — özel jeton',
                'group' => 'Tema dosyasında ek',
                'module' => EshUiTokenCatalog::moduleForGroup('Tema dosyasında ek'),
                'hint' => 'sayfa-standardı',
                'missing_in_file' => false,
                'picker_hex' => self::colorValueToPickerHex($value),
            ];
        }

        return $out;
    }

    /**
     * Sayfa standardı jetonları — editörde grup başlıklarıyla (tipografi hariç).
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public static function parseEshUiVariableTokenGroups(string $themeSlug): array
    {
        $grouped = [];
        foreach (self::parseEshUiVariableTokens($themeSlug) as $row) {
            if (self::isEshUiTypographyTokenName((string) ($row['name'] ?? ''))) {
                continue;
            }
            $group = (string) ($row['group'] ?? 'Diğer');
            $grouped[$group][] = $row;
        }
        $order = EshUiTokenCatalog::groupOrder();
        $sorted = [];
        foreach ($order as $groupName) {
            if (!empty($grouped[$groupName])) {
                $sorted[$groupName] = $grouped[$groupName];
            }
        }
        foreach ($grouped as $groupName => $rows) {
            if (!isset($sorted[$groupName])) {
                $sorted[$groupName] = $rows;
            }
        }

        return $sorted;
    }

    public static function themeHasEshUiBridgeInCss(string $themeSlug): bool
    {
        $path = ThemeViewHelper::themeCssPath(ThemeViewHelper::sanitizeThemeSlug($themeSlug));
        if (!is_file($path)) {
            return false;
        }

        return (bool) preg_match('/--esh-ui-text\s*:/', (string) file_get_contents($path));
    }

    /**
     * @return array<string, string>
     */
    public static function eshUiTokenCatalog(): array
    {
        return EshUiTokenCatalog::flatLabels();
    }

    /**
     * Gradyan CSS değişkenleri (--*-gradient vb.).
     *
     * @return list<array{id:string,type:string,name:string,value:string,line:int,label:string}>
     */
    public static function parseGradientVariableTokens(string $themeSlug): array
    {
        $rows = self::parseVariableTokens($themeSlug, static fn (string $value): bool => self::isGradientCSSValue($value));
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'id' => 'var:' . $row['name'],
                'type' => 'var',
                'name' => $row['name'],
                'value' => $row['value'],
                'line' => $row['line'],
                'label' => $row['name'],
            ];
        }

        return $out;
    }

    /**
     * Gradyan dışı CSS değişkenleri (radius, shadow, motion, var() referansları vb.).
     *
     * @return list<array{id:string,type:string,name:string,value:string,line:int,label:string,hint:string}>
     */
    public static function parseTypographyVariableTokens(string $themeSlug): array
    {
        $rows = self::parseVariableTokens(
            $themeSlug,
            static fn (string $value): bool => self::isTypographyCssVariableValue($value)
        );
        $out = [];
        foreach ($rows as $row) {
            if (!self::isThemeDesignTokenName($row['name']) || self::isEshUiTokenName($row['name'])) {
                continue;
            }
            if (!self::isTypographyTokenName($row['name'])) {
                continue;
            }
            $out[] = [
                'id' => 'var:' . $row['name'],
                'type' => 'var',
                'name' => $row['name'],
                'value' => $row['value'],
                'line' => $row['line'],
                'label' => $row['name'],
                'hint' => 'yazı',
            ];
        }

        return $out;
    }

    /**
     * body kurallarındaki tipografi satırları (font-family, font-size vb.).
     *
     * @return list<array{id:string,type:string,selector:string,property:string,value:string,line:int,label:string,hint:string}>
     */
    public static function parseTypographyPropertyEntries(string $themeSlug): array
    {
        $entries = self::parseBodyPropertyEntries(
            $themeSlug,
            self::TYPOGRAPHY_BODY_PROPERTIES,
            static fn (string $value): bool => self::isTypographyPropertyValue($value)
        );
        foreach ($entries as &$entry) {
            $entry['hint'] = 'yazı';
        }
        unset($entry);

        return $entries;
    }

    public static function parseOtherVariableTokens(string $themeSlug): array
    {
        $rows = self::parseVariableTokens(
            $themeSlug,
            static fn (string $value): bool => self::isOtherCssVariableValue($value)
        );
        $out = [];
        foreach ($rows as $row) {
            if (!self::isThemeDesignTokenName($row['name']) || self::isEshUiTokenName($row['name'])) {
                continue;
            }
            if (self::isTypographyTokenName($row['name'])) {
                continue;
            }
            $out[] = [
                'id' => 'var:' . $row['name'],
                'type' => 'var',
                'name' => $row['name'],
                'value' => $row['value'],
                'line' => $row['line'],
                'label' => $row['name'],
                'hint' => self::tokenValueHint($row['name'], $row['value']),
            ];
        }

        return $out;
    }

    /**
     * body kurallarındaki gradyan dışı özellik satırları (tipografi, gölge vb.).
     *
     * @return list<array{id:string,type:string,selector:string,property:string,value:string,line:int,label:string,hint:string}>
     */
    public static function parseOtherPropertyEntries(string $themeSlug): array
    {
        return self::parseBodyPropertyEntries(
            $themeSlug,
            self::OTHER_BODY_PROPERTIES,
            static fn (string $value): bool => self::isOtherPropertyValue($value)
        );
    }

    /**
     * body kurallarındaki background / background-image gradyan satırları.
     *
     * @return list<array{id:string,type:string,selector:string,property:string,value:string,line:int,label:string}>
     */
    public static function parseGradientPropertyEntries(string $themeSlug): array
    {
        return self::parseBodyPropertyEntries(
            $themeSlug,
            ['background', 'background-image'],
            static fn (string $value): bool => self::isGradientCSSValue($value)
        );
    }

    /**
     * @param list<string> $properties
     * @param callable(string):bool $valueFilter
     * @return list<array{id:string,type:string,selector:string,property:string,value:string,line:int,label:string,hint:string}>
     */
    private static function parseBodyPropertyEntries(string $themeSlug, array $properties, callable $valueFilter): array
    {
        $path = ThemeViewHelper::themeCssPath($themeSlug);
        if (!is_file($path)) {
            return [];
        }

        $css = (string) file_get_contents($path);
        $entries = [];
        $seen = [];
        $propPattern = '(?:' . implode('|', array_map(static fn (string $p): string => preg_quote($p, '/'), $properties)) . ')';

        foreach (self::shellBodyBlockLineRanges($css) as $block) {
            $selector = (string) $block['selector'];
            $chunk = (string) $block['chunk'];
            $lineOffset = (int) $block['start_line'] - 1;

            if (!preg_match_all('/(' . $propPattern . ')\s*:\s*([^;]+);/s', $chunk, $props, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                continue;
            }

            foreach ($props as $prop) {
                $property = trim((string) $prop[1][0]);
                $value = trim(preg_replace('/\s*\n\s*/', ' ', (string) $prop[2][0]));
                if (!$valueFilter($value)) {
                    continue;
                }

                $line = $lineOffset + substr_count(substr($chunk, 0, (int) $prop[0][1]), "\n") + 1;
                $id = 'prop:' . $line . ':' . $property;
                if (isset($seen[$id])) {
                    continue;
                }
                $seen[$id] = true;

                $shortSelector = strlen($selector) > 48 ? substr($selector, 0, 45) . '…' : $selector;
                $entries[] = [
                    'id' => $id,
                    'type' => 'property',
                    'selector' => $selector,
                    'property' => $property,
                    'value' => $value,
                    'line' => $line,
                    'label' => $property . ' · ' . $shortSelector,
                    'hint' => self::tokenValueHint($property, $value),
                ];
            }
        }

        usort($entries, static fn (array $a, array $b): int => ($a['line'] <=> $b['line']) ?: strcmp($a['id'], $b['id']));

        return $entries;
    }

    /**
     * @return list<array{selector:string,chunk:string,start_line:int}>
     */
    private static function shellBodyBlockLineRanges(string $css): array
    {
        $lines = explode("\n", $css);
        $ranges = [];
        $n = count($lines);

        for ($i = 0; $i < $n; $i++) {
            $trim = trim($lines[$i]);
            if (!preg_match('/^(body[^\s{]+)\s*\{/', $trim, $m)) {
                continue;
            }
            $selector = trim($m[1]);
            if (!self::isThemeShellSelector($selector)) {
                continue;
            }

            $depth = substr_count($lines[$i], '{') - substr_count($lines[$i], '}');
            $start = $i;
            $j = $i;
            while ($depth > 0 && ++$j < $n) {
                $depth += substr_count($lines[$j], '{') - substr_count($lines[$j], '}');
            }
            if ($depth !== 0) {
                continue;
            }

            $ranges[] = [
                'selector' => $selector,
                'chunk' => implode("\n", array_slice($lines, $start, $j - $start + 1)),
                'start_line' => $start + 1,
            ];
            $i = $j;
        }

        return $ranges;
    }

    /**
     * @param array<string, string> $colorUpdates
     * @param array<string, string> $gradientVarUpdates id or name => value
     * @param array<string, string> $gradientPropUpdates id => value
     * @param array<string, string> $otherVarUpdates id or name => value
     * @param array<string, string> $otherPropUpdates id => value
     * @param array<string, string> $typographyVarUpdates id or name => value
     * @param array<string, string> $typographyPropUpdates id => value
     * @return array{ok:bool,message:string,css:?string,original_css:?string,changed:int,changes:list<array{label:string,before:string,after:string}>}
     */
    public static function computeThemeCssAfterEdits(
        string $themeSlug,
        array $colorUpdates,
        array $gradientVarUpdates = [],
        array $gradientPropUpdates = [],
        array $otherVarUpdates = [],
        array $otherPropUpdates = [],
        array $eshUiUpdates = [],
        array $typographyVarUpdates = [],
        array $typographyPropUpdates = []
    ): array {
        $slug = ThemeViewHelper::sanitizeThemeSlug($themeSlug);
        if ($slug === '' || !ThemeViewHelper::isInstalledThemeSlug($slug)) {
            return [
                'ok' => false,
                'message' => 'Geçersiz tema.',
                'css' => null,
                'original_css' => null,
                'changed' => 0,
                'changes' => [],
            ];
        }

        $path = ThemeViewHelper::themeCssPath($slug);
        $originalCss = (string) file_get_contents($path);
        $css = $originalCss;

        $allowedColors = [];
        foreach (self::parseColorTokens($slug) as $row) {
            $allowedColors[$row['name']] = true;
        }

        $allowedGradVars = [];
        foreach (self::parseGradientVariableTokens($slug) as $row) {
            $allowedGradVars[$row['name']] = true;
            $allowedGradVars[$row['id']] = $row['name'];
        }

        $allowedGradProps = [];
        foreach (self::parseGradientPropertyEntries($slug) as $row) {
            $allowedGradProps[$row['id']] = $row;
        }

        $allowedOtherVars = [];
        foreach (self::parseOtherVariableTokens($slug) as $row) {
            $allowedOtherVars[$row['name']] = true;
            $allowedOtherVars[$row['id']] = $row['name'];
        }

        $allowedOtherProps = [];
        foreach (self::parseOtherPropertyEntries($slug) as $row) {
            $allowedOtherProps[$row['id']] = $row;
        }

        $allowedTypographyVars = [];
        foreach (self::parseTypographyVariableTokens($slug) as $row) {
            $allowedTypographyVars[$row['name']] = true;
            $allowedTypographyVars[$row['id']] = $row['name'];
        }

        $allowedTypographyProps = [];
        foreach (self::parseTypographyPropertyEntries($slug) as $row) {
            $allowedTypographyProps[$row['id']] = $row;
        }

        $allowedEshUi = [];
        foreach (array_keys(EshUiTokenCatalog::tokenMeta()) as $name) {
            $allowedEshUi[$name] = true;
        }

        $changed = 0;
        $changes = [];

        foreach ($colorUpdates as $name => $value) {
            if (!is_string($name) || !is_string($value)) {
                continue;
            }
            $name = trim($name);
            $value = trim($value);
            if ($name === '' || $value === '' || !isset($allowedColors[$name])) {
                continue;
            }
            if (!self::isColorCSSValue($value)) {
                return [
                    'ok' => false,
                    'message' => 'Geçersiz renk değeri: ' . $name,
                    'css' => null,
                    'original_css' => $originalCss,
                    'changed' => 0,
                    'changes' => [],
                ];
            }
            $before = self::readCssDeclarationValue($css, $name) ?? '';
            $prevChanged = $changed;
            $css = self::replaceCssDeclaration($css, $name, $value, $changed);
            if ($changed > $prevChanged) {
                $changes[] = ['label' => $name, 'before' => $before, 'after' => $value];
            }
        }

        foreach ($gradientVarUpdates as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                continue;
            }
            $key = trim($key);
            $value = trim($value);
            $varName = str_starts_with($key, 'var:') ? $key : ('var:' . $key);
            $name = $allowedGradVars[$key] ?? ($allowedGradVars[$varName] ?? null);
            if (!is_string($name) || $value === '' || !isset($allowedGradVars[$name])) {
                continue;
            }
            if (!self::isGradientCSSValue($value)) {
                return [
                    'ok' => false,
                    'message' => 'Geçersiz gradyan değeri: ' . $name,
                    'css' => null,
                    'original_css' => $originalCss,
                    'changed' => 0,
                    'changes' => [],
                ];
            }
            $before = self::readCssDeclarationValue($css, $name) ?? '';
            $prevChanged = $changed;
            $css = self::replaceCssDeclaration($css, $name, $value, $changed);
            if ($changed > $prevChanged) {
                $changes[] = ['label' => $name, 'before' => $before, 'after' => $value];
            }
        }

        foreach ($gradientPropUpdates as $id => $value) {
            if (!is_string($id) || !is_string($value)) {
                continue;
            }
            $id = trim($id);
            $value = trim($value);
            if ($id === '' || $value === '' || !isset($allowedGradProps[$id])) {
                continue;
            }
            if (!self::isGradientCSSValue($value)) {
                return [
                    'ok' => false,
                    'message' => 'Geçersiz gradyan satırı: ' . $id,
                    'css' => null,
                    'original_css' => $originalCss,
                    'changed' => 0,
                    'changes' => [],
                ];
            }

            $meta = $allowedGradProps[$id];
            $before = (string) ($meta['value'] ?? '');
            $label = trim((string) ($meta['label'] ?? $id)) . ' · ' . (string) ($meta['property'] ?? '');
            $prevChanged = $changed;
            $css = self::replacePropertyInRule($css, $meta, $value, $changed);
            if ($changed > $prevChanged) {
                $changes[] = ['label' => $label, 'before' => $before, 'after' => $value];
            }
        }

        foreach ($otherVarUpdates as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                continue;
            }
            $key = trim($key);
            $value = trim($value);
            $varName = str_starts_with($key, 'var:') ? $key : ('var:' . $key);
            $name = $allowedOtherVars[$key] ?? ($allowedOtherVars[$varName] ?? null);
            if (!is_string($name) || $value === '' || !isset($allowedOtherVars[$name])) {
                continue;
            }
            if (!self::isOtherCssVariableValue($value)) {
                return [
                    'ok' => false,
                    'message' => 'Geçersiz jeton değeri: ' . $name,
                    'css' => null,
                    'original_css' => $originalCss,
                    'changed' => 0,
                    'changes' => [],
                ];
            }
            $before = self::readCssDeclarationValue($css, $name) ?? '';
            $prevChanged = $changed;
            $css = self::replaceCssDeclaration($css, $name, $value, $changed);
            if ($changed > $prevChanged) {
                $changes[] = ['label' => $name, 'before' => $before, 'after' => $value];
            }
        }

        foreach ($otherPropUpdates as $id => $value) {
            if (!is_string($id) || !is_string($value)) {
                continue;
            }
            $id = trim($id);
            $value = trim($value);
            if ($id === '' || $value === '' || !isset($allowedOtherProps[$id])) {
                continue;
            }
            if (!self::isOtherPropertyValue($value)) {
                return [
                    'ok' => false,
                    'message' => 'Geçersiz kural satırı: ' . $id,
                    'css' => null,
                    'original_css' => $originalCss,
                    'changed' => 0,
                    'changes' => [],
                ];
            }

            $meta = $allowedOtherProps[$id];
            $before = (string) ($meta['value'] ?? '');
            $label = trim((string) ($meta['label'] ?? $id)) . ' · ' . (string) ($meta['property'] ?? '');
            $prevChanged = $changed;
            $css = self::replacePropertyInRule($css, $meta, $value, $changed);
            if ($changed > $prevChanged) {
                $changes[] = ['label' => $label, 'before' => $before, 'after' => $value];
            }
        }

        foreach ($typographyVarUpdates as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                continue;
            }
            $key = trim($key);
            $value = trim($value);
            $varName = str_starts_with($key, 'var:') ? $key : ('var:' . $key);
            $name = $allowedTypographyVars[$key] ?? ($allowedTypographyVars[$varName] ?? null);
            if (!is_string($name) || $value === '' || !isset($allowedTypographyVars[$name])) {
                continue;
            }
            if (!self::isTypographyCssVariableValue($value)) {
                return [
                    'ok' => false,
                    'message' => 'Geçersiz tipografi jetonu: ' . $name,
                    'css' => null,
                    'original_css' => $originalCss,
                    'changed' => 0,
                    'changes' => [],
                ];
            }
            $before = self::readCssDeclarationValue($css, $name) ?? '';
            $prevChanged = $changed;
            $css = self::replaceCssDeclaration($css, $name, $value, $changed);
            if ($changed > $prevChanged) {
                $changes[] = ['label' => $name, 'before' => $before, 'after' => $value];
            }
        }

        foreach ($typographyPropUpdates as $id => $value) {
            if (!is_string($id) || !is_string($value)) {
                continue;
            }
            $id = trim($id);
            $value = trim($value);
            if ($id === '' || $value === '' || !isset($allowedTypographyProps[$id])) {
                continue;
            }
            if (!self::isTypographyPropertyValue($value)) {
                return [
                    'ok' => false,
                    'message' => 'Geçersiz tipografi satırı: ' . $id,
                    'css' => null,
                    'original_css' => $originalCss,
                    'changed' => 0,
                    'changes' => [],
                ];
            }

            $meta = $allowedTypographyProps[$id];
            $before = (string) ($meta['value'] ?? '');
            $label = trim((string) ($meta['label'] ?? $id)) . ' · ' . (string) ($meta['property'] ?? '');
            $prevChanged = $changed;
            $css = self::replacePropertyInRule($css, $meta, $value, $changed);
            if ($changed > $prevChanged) {
                $changes[] = ['label' => $label, 'before' => $before, 'after' => $value];
            }
        }

        foreach ($eshUiUpdates as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                continue;
            }
            $key = trim($key);
            $value = trim($value);
            $name = str_starts_with($key, 'var:') ? substr($key, 4) : $key;
            if ($name === '' || $value === '' || !isset($allowedEshUi[$name])) {
                continue;
            }
            if (!self::isEshUiVariableValue($value)) {
                return [
                    'ok' => false,
                    'message' => 'Geçersiz sayfa standardı değeri: ' . $name,
                    'css' => null,
                    'original_css' => $originalCss,
                    'changed' => 0,
                    'changes' => [],
                ];
            }
            $before = self::readCssDeclarationValue($css, $name);
            $prevChanged = $changed;
            $css = self::upsertCssVariableOnThemeBody($css, $slug, $name, $value, $changed);
            if ($changed > $prevChanged) {
                $changes[] = [
                    'label' => $name,
                    'before' => $before === null ? '(dosyada yok)' : $before,
                    'after' => $value,
                ];
            }
        }

        return [
            'ok' => true,
            'message' => '',
            'css' => $css,
            'original_css' => $originalCss,
            'changed' => $changed,
            'changes' => $changes,
        ];
    }

    /**
     * @return array{ok:bool,message:string,changed:int,changes:list<array{label:string,before:string,after:string}>}
     */
    public static function previewThemeEdits(
        string $themeSlug,
        array $colorUpdates,
        array $gradientVarUpdates = [],
        array $gradientPropUpdates = [],
        array $otherVarUpdates = [],
        array $otherPropUpdates = [],
        array $eshUiUpdates = [],
        array $typographyVarUpdates = [],
        array $typographyPropUpdates = []
    ): array {
        $result = self::computeThemeCssAfterEdits(
            $themeSlug,
            $colorUpdates,
            $gradientVarUpdates,
            $gradientPropUpdates,
            $otherVarUpdates,
            $otherPropUpdates,
            $eshUiUpdates,
            $typographyVarUpdates,
            $typographyPropUpdates
        );

        if (!$result['ok']) {
            return [
                'ok' => false,
                'message' => $result['message'],
                'changed' => 0,
                'changes' => [],
            ];
        }

        $changed = (int) $result['changed'];

        return [
            'ok' => true,
            'message' => $changed === 0
                ? 'Kaydedilecek değişiklik yok.'
                : ($changed . ' stil girdisi güncellenecek.'),
            'changed' => $changed,
            'changes' => $result['changes'],
        ];
    }

    /**
     * @param array<string, string> $typographyPropUpdates id => value
     * @return array{ok:bool,message:string,backup:?string}
     */
    public static function applyThemeEdits(
        string $themeSlug,
        array $colorUpdates,
        array $gradientVarUpdates = [],
        array $gradientPropUpdates = [],
        array $otherVarUpdates = [],
        array $otherPropUpdates = [],
        array $eshUiUpdates = [],
        array $typographyVarUpdates = [],
        array $typographyPropUpdates = []
    ): array {
        $result = self::computeThemeCssAfterEdits(
            $themeSlug,
            $colorUpdates,
            $gradientVarUpdates,
            $gradientPropUpdates,
            $otherVarUpdates,
            $otherPropUpdates,
            $eshUiUpdates,
            $typographyVarUpdates,
            $typographyPropUpdates
        );

        if (!$result['ok']) {
            return ['ok' => false, 'message' => $result['message'], 'backup' => null];
        }

        if ($result['changed'] === 0) {
            return ['ok' => false, 'message' => 'Kaydedilecek değişiklik yok.', 'backup' => null];
        }

        $slug = ThemeViewHelper::sanitizeThemeSlug($themeSlug);
        $path = ThemeViewHelper::themeCssPath($slug);
        $css = (string) $result['css'];

        $backup = $path . '.bak-' . date('Ymd-His');
        if (@copy($path, $backup) === false) {
            return ['ok' => false, 'message' => 'Yedek oluşturulamadı.', 'backup' => null];
        }

        self::pruneThemeCssBackups($path, 20);

        if (file_put_contents($path, $css) === false) {
            @copy($backup, $path);
            return ['ok' => false, 'message' => 'theme.css yazılamadı.', 'backup' => $backup];
        }

        self::bumpThemeManifest($slug);
        self::clearSessionPreview();

        return [
            'ok' => true,
            'message' => $result['changed'] . ' stil girdisi güncellendi.',
            'backup' => $backup,
        ];
    }

    public static function pruneThemeCssBackups(string $cssPath, int $keep = 20): void
    {
        if ($keep < 1) {
            return;
        }

        $pattern = $cssPath . '.bak-*';
        $files = glob($pattern) ?: [];
        usort($files, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        foreach (array_slice($files, $keep) as $old) {
            if (is_file($old)) {
                @unlink($old);
            }
        }
    }

    /** @deprecated use applyThemeEdits */
    public static function applyColorTokens(string $themeSlug, array $updates): array
    {
        return self::applyThemeEdits($themeSlug, $updates);
    }

    /**
     * @param array<string, string> $vars
     * @param array<string, array{property:string,value:string}> $properties
     */
    public static function setSessionPreview(string $themeSlug, array $vars, array $properties): void
    {
        $slug = ThemeViewHelper::sanitizeThemeSlug($themeSlug);
        if ($slug === '') {
            return;
        }

        $_SESSION[self::SESSION_KEY] = [
            'theme' => $slug,
            'vars' => $vars,
            'properties' => $properties,
            'at' => time(),
        ];
    }

    public static function clearSessionPreview(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    /**
     * @return array{theme:string,vars:array<string,string>,properties:array<string,array{property:string,value:string}>,at:int}|null
     */
    public static function getSessionPreview(): ?array
    {
        $raw = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($raw) || empty($raw['theme'])) {
            return null;
        }

        return [
            'theme' => (string) $raw['theme'],
            'vars' => is_array($raw['vars'] ?? null) ? $raw['vars'] : [],
            'properties' => is_array($raw['properties'] ?? null) ? $raw['properties'] : [],
            'at' => (int) ($raw['at'] ?? 0),
        ];
    }

    public static function sessionPreviewAppliesToSlug(string $stylesheetSlug): bool
    {
        $preview = self::getSessionPreview();
        if ($preview === null) {
            return false;
        }

        $stack = ThemeViewHelper::themeStylesheetSlugStack($preview['theme']);
        if ($stack === []) {
            return false;
        }

        return $stylesheetSlug === $stack[count($stack) - 1];
    }

    public static function renderSessionPreviewCssBlock(string $stylesheetSlug): string
    {
        if (!self::sessionPreviewAppliesToSlug($stylesheetSlug)) {
            return '';
        }

        $preview = self::getSessionPreview();
        if ($preview === null) {
            return '';
        }

        $selector = self::bodySelectorForTheme($preview['theme']);
        if ($selector === '') {
            return '';
        }

        $lines = ["/* ESH oturum önizlemesi — dosyaya yazılmaz */", $selector . ' {'];
        foreach ($preview['vars'] as $name => $value) {
            $name = trim((string) $name);
            $value = trim((string) $value);
            if ($name === '' || $value === '' || !str_starts_with($name, '--')) {
                continue;
            }
            if (!self::isSessionPreviewVarValue($value)) {
                continue;
            }
            $lines[] = '  ' . $name . ': ' . $value . ';';
        }
        foreach ($preview['properties'] as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $property = trim((string) ($entry['property'] ?? ''));
            $value = trim((string) ($entry['value'] ?? ''));
            if ($property === '' || $value === '' || !self::isSessionPreviewPropertyValue($value)) {
                continue;
            }
            $lines[] = '  ' . $property . ': ' . $value . ' !important;';
        }
        $lines[] = '}';

        if (count($lines) <= 3) {
            return '';
        }

        return "\n" . implode("\n", $lines) . "\n";
    }

    public static function previewBodyClasses(string $themeSlug): string
    {
        return html_entity_decode(
            ThemeViewHelper::themeBodyClassAttribute($themeSlug),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    /**
     * @return list<string>
     */
    public static function previewStylesheetUrls(string $themeSlug): array
    {
        return ThemeViewHelper::previewStylesheetUrlsForTheme($themeSlug);
    }

    public static function isColorCSSValue(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || stripos($value, 'var(') !== false || self::isGradientCSSValue($value)) {
            return false;
        }
        if (preg_match('/calc\(|cubic-bezier|\d+(?:\.\d+)?(?:px|rem|em|vh|vw|%|ms|s)\b/i', $value)) {
            return false;
        }

        return (bool) preg_match(
            '/^#([0-9a-fA-F]{3,8})$|^rgba?\(|^hsla?\(|^(?:transparent|currentColor)$/i',
            $value
        );
    }

    public static function isGradientCSSValue(string $value): bool
    {
        $value = trim($value);
        if ($value === '') {
            return false;
        }
        if (preg_match('/[{}<>]/', $value)) {
            return false;
        }

        return (bool) preg_match('/(?:linear|radial|conic)-gradient\s*\(/i', $value);
    }

    public static function isEditableCssValue(string $value): bool
    {
        $value = trim($value);
        if ($value === '' || strlen($value) > 1000) {
            return false;
        }

        return !preg_match('/[{}<>@]/', $value);
    }

    public static function isOtherCssVariableValue(string $value): bool
    {
        return self::isEditableCssValue($value)
            && !self::isColorCSSValue($value)
            && !self::isGradientCSSValue($value);
    }

    public static function isEshUiVariableValue(string $value): bool
    {
        return self::isColorCSSValue($value)
            || self::isOtherCssVariableValue($value)
            || self::isGradientCSSValue($value);
    }

    public static function isEshUiTokenName(string $name): bool
    {
        return str_starts_with($name, '--esh-ui-');
    }

    public static function isEshUiTypographyTokenName(string $name): bool
    {
        return in_array($name, self::ESH_UI_TYPOGRAPHY_TOKEN_NAMES, true);
    }

    public static function isTypographyTokenName(string $name): bool
    {
        $lower = strtolower($name);
        if (self::isEshUiTypographyTokenName($name)) {
            return true;
        }

        return str_contains($lower, 'font')
            || str_contains($lower, 'line-height')
            || str_contains($lower, 'letter-spacing')
            || str_contains($lower, 'typography');
    }

    public static function isTypographyCssVariableValue(string $value): bool
    {
        return self::isOtherCssVariableValue($value);
    }

    public static function isTypographyPropertyValue(string $value): bool
    {
        return self::isEditableCssValue($value) && !self::isGradientCSSValue($value);
    }

    public static function isOtherPropertyValue(string $value): bool
    {
        return self::isEditableCssValue($value)
            && !self::isGradientCSSValue($value);
    }

    public static function isSessionPreviewVarValue(string $value): bool
    {
        return self::isColorCSSValue($value)
            || self::isGradientCSSValue($value)
            || self::isOtherCssVariableValue($value)
            || self::isEshUiVariableValue($value);
    }

    public static function isSessionPreviewPropertyValue(string $value): bool
    {
        return self::isGradientCSSValue($value) || self::isOtherPropertyValue($value);
    }

    public static function tokenValueHint(string $name, string $value): string
    {
        $name = strtolower($name);
        $value = trim($value);
        if (str_starts_with($value, 'var(')) {
            return 'referans';
        }
        if (str_contains($name, 'radius')) {
            return 'köşe';
        }
        if (str_contains($name, 'shadow')) {
            return 'gölge';
        }
        if (str_contains($name, 'motion') || str_contains($name, 'duration') || str_contains($value, 'cubic-bezier')) {
            return 'animasyon';
        }
        if (str_contains($name, 'font') || $name === 'font-family') {
            return 'tipografi';
        }
        if (str_contains($name, 'glass') || str_contains($name, 'backdrop')) {
            return 'cam';
        }
        if (preg_match('/^\d+(?:\.\d+)?(?:px|rem|em|%)$/', $value)) {
            return 'ölçü';
        }

        return 'jeton';
    }

    public static function tokenHintLabel(string $hint): string
    {
        return match ($hint) {
            'referans' => 'Referans',
            'köşe' => 'Köşe',
            'gölge' => 'Gölge',
            'animasyon' => 'Animasyon',
            'yazı' => 'Yazı',
            'cam' => 'Cam',
            'ölçü' => 'Ölçü',
            'gradient' => 'Gradyan',
            'sayfa-standardı' => 'Sayfa standardı',
            'tipografi' => 'Tipografi',
            default => 'Jeton',
        };
    }

    public static function colorValueToPickerHex(string $value): ?string
    {
        $value = trim($value);
        if (preg_match('/^#([0-9a-fA-F]{3})$/', $value, $m)) {
            $h = $m[1];
            return '#' . $h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2];
        }
        if (preg_match('/^#([0-9a-fA-F]{6})$/', $value, $m)) {
            return '#' . strtolower($m[1]);
        }
        if (preg_match('/^#([0-9a-fA-F]{8})$/', $value, $m)) {
            return '#' . strtolower(substr($m[1], 0, 6));
        }
        if (preg_match('/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i', $value, $m)) {
            return sprintf('#%02x%02x%02x', (int) $m[1], (int) $m[2], (int) $m[3]);
        }

        return null;
    }

    /**
     * @param callable(string):bool $valueFilter
     * @return list<array{name:string,value:string,line:int,picker_hex:?string,kind:string}>
     */
    private static function parseVariableTokens(string $themeSlug, callable $valueFilter): array
    {
        $path = ThemeViewHelper::themeCssPath($themeSlug);
        if (!is_file($path)) {
            return [];
        }

        $css = (string) file_get_contents($path);
        $tokens = [];
        $seen = [];

        if (preg_match_all('/^\s*(--[a-zA-Z0-9_-]+)\s*:\s*([^;]+);/m', $css, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $match) {
                $name = trim((string) $match[1][0]);
                $value = trim((string) $match[2][0]);
                if (isset($seen[$name]) || !$valueFilter($value)) {
                    continue;
                }
                $line = substr_count(substr($css, 0, (int) $match[0][1]), "\n") + 1;
                $seen[$name] = true;
                $tokens[] = [
                    'name' => $name,
                    'value' => $value,
                    'line' => $line,
                    'picker_hex' => self::colorValueToPickerHex($value),
                    'kind' => self::colorValueKind($value),
                ];
            }
        }

        usort($tokens, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $tokens;
    }

    private static function readCssDeclarationValue(string $css, string $name): ?string
    {
        if (preg_match('/' . preg_quote($name, '/') . '\s*:\s*([^;]+);/', $css, $match)) {
            return trim((string) $match[1]);
        }

        return null;
    }

    private static function replaceCssDeclaration(string $css, string $name, string $value, int &$changed): string
    {
        $pattern = '/(' . preg_quote($name, '/') . '\s*:\s*)([^;]+)(;)/';
        $newCss = preg_replace($pattern, '$1' . $value . '$3', $css);
        if ($newCss !== null && $newCss !== $css) {
            $css = $newCss;
            $changed++;
        }

        return $css;
    }

    /**
     * @param array{selector:string,property:string,value:string} $meta
     */
    private static function isThemeShellSelector(string $selector): bool
    {
        $selector = trim(preg_replace('/\s+/', ' ', $selector));
        if ($selector === '' || !preg_match('/^body(?:[.:#[]|$)/i', $selector)) {
            return false;
        }

        return !preg_match('/\s/', $selector);
    }

    /** Bileşen düzeyi Bootstrap override jetonlarını (tablo vb.) hariç tutar. */
    private static function isThemeDesignTokenName(string $name): bool
    {
        return !str_starts_with($name, '--bs-');
    }

    private static function replacePropertyInRule(string $css, array $meta, string $newValue, int &$changed): string
    {
        $oldValue = (string) ($meta['value'] ?? '');
        $property = preg_quote((string) ($meta['property'] ?? ''), '/');
        $selector = preg_quote((string) ($meta['selector'] ?? ''), '/');
        $oldQuoted = preg_quote($oldValue, '/');
        $pattern = '/(' . $selector . '\s*\{[^}]*?' . $property . '\s*:\s*)' . $oldQuoted . '(\s*;)/s';
        $newCss = preg_replace($pattern, '$1' . $newValue . '$2', $css, 1);
        if ($newCss !== null && $newCss !== $css) {
            $css = $newCss;
            $changed++;
        }

        return $css;
    }

    private static function bodySelectorForTheme(string $themeSlug): string
    {
        $classes = self::themeShellClassNames($themeSlug);
        if ($classes === []) {
            return 'body';
        }

        return 'body.' . implode('.', $classes);
    }

    /**
     * @return list<string>
     */
    private static function themeShellClassNames(string $themeSlug): array
    {
        $classes = preg_split('/\s+/', trim(self::previewBodyClasses($themeSlug))) ?: [];
        $layoutSkip = ['d-flex', 'flex-column', 'min-vh-100', 'flex-grow-1', 'py-4'];
        $out = [];
        foreach ($classes as $class) {
            $class = trim($class);
            if ($class === '' || in_array($class, $layoutSkip, true)) {
                continue;
            }
            if ($class === 'app-shell'
                || str_starts_with($class, 'theme-')
                || in_array($class, ['aurora-theme', 'fluent-winui', 'cpp-shell', 'ev-shell'], true)
            ) {
                $out[] = $class;
            }
        }

        return $out;
    }

    /**
     * @return array<string, array{value:string,line:int}>
     */
    private static function parseEshUiVariablesFromCss(string $themeSlug): array
    {
        $path = ThemeViewHelper::themeCssPath($themeSlug);
        if (!is_file($path)) {
            return [];
        }

        $css = (string) file_get_contents($path);
        $found = [];

        foreach (self::shellBodyBlockLineRanges($css) as $block) {
            $chunk = (string) $block['chunk'];
            $lineOffset = (int) $block['start_line'] - 1;
            if (!preg_match_all('/^\s*(--esh-ui-[a-zA-Z0-9_-]+)\s*:\s*([^;]+);/m', $chunk, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
                continue;
            }
            foreach ($matches as $match) {
                $name = trim((string) $match[1][0]);
                $value = trim((string) $match[2][0]);
                if ($name === '') {
                    continue;
                }
                $line = $lineOffset + substr_count(substr($chunk, 0, (int) $match[0][1]), "\n") + 1;
                $found[$name] = ['value' => $value, 'line' => $line];
            }
        }

        if ($found === [] && preg_match_all('/^\s*(--esh-ui-[a-zA-Z0-9_-]+)\s*:\s*([^;]+);/m', $css, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $match) {
                $name = trim((string) $match[1][0]);
                $value = trim((string) $match[2][0]);
                if ($name === '') {
                    continue;
                }
                $line = substr_count(substr($css, 0, (int) $match[0][1]), "\n") + 1;
                $found[$name] = ['value' => $value, 'line' => $line];
            }
        }

        return $found;
    }

    private static function upsertCssVariableOnThemeBody(string $css, string $themeSlug, string $name, string $value, int &$changed): string
    {
        if (preg_match('/' . preg_quote($name, '/') . '\s*:\s*[^;]+;/', $css)) {
            return self::replaceCssDeclaration($css, $name, $value, $changed);
        }

        $selector = self::bodySelectorForTheme($themeSlug);
        $declaration = '  ' . $name . ': ' . $value . ';';
        $selQuoted = preg_quote($selector, '/');

        if (preg_match('/' . $selQuoted . '\s*\{([^}]*)\}/s', $css, $m)) {
            $inner = rtrim((string) $m[1]);
            $replacement = $selector . ' {' . $inner . ($inner === '' ? '' : "\n") . $declaration . "\n}";
            $newCss = preg_replace('/' . $selQuoted . '\s*\{[^}]*\}/s', $replacement, $css, 1);
            if ($newCss !== null && $newCss !== $css) {
                $changed++;

                return $newCss;
            }
        }

        $block = "\n/* ESH sayfa dili (--esh-ui-*) */\n" . $selector . " {\n" . $declaration . "\n}\n";
        $changed++;

        return rtrim($css) . $block;
    }

    /**
     * Tema ailesine göre önerilen --esh-ui-* köprü metni (kullanıcı dosyaya yapıştırabilir).
     */
    public static function suggestedEshUiBridgeCss(string $themeSlug): string
    {
        $slug = ThemeViewHelper::sanitizeThemeSlug($themeSlug);
        $selector = self::bodySelectorForTheme($slug);
        $bodyClass = html_entity_decode(ThemeViewHelper::themeBodyClassAttribute($slug), ENT_QUOTES, 'UTF-8');

        $lines = [$selector . ' {'];
        if (str_contains($bodyClass, 'fluent-winui')) {
            $map = [
                '--esh-ui-text' => 'var(--fluent-text-primary)',
                '--esh-ui-text-muted' => 'var(--fluent-text-secondary)',
                '--esh-ui-accent' => 'var(--fluent-accent)',
                '--esh-ui-surface' => 'var(--fluent-bg-layer-base)',
                '--esh-ui-surface-muted' => 'var(--fluent-bg-layer-alt)',
                '--esh-ui-border' => 'var(--fluent-stroke-card)',
                '--esh-ui-radius' => 'var(--fluent-radius-card)',
                '--esh-ui-shadow' => 'var(--fluent-shadow-rest)',
                '--esh-ui-table-head-bg' => 'var(--fluent-bg-layer-alt)',
                '--esh-ui-table-head-color' => 'var(--fluent-text-secondary)',
                '--esh-ui-row-hover' => 'var(--fluent-accent-subtle)',
                '--esh-ui-filter-control-height' => '38px',
            ];
        } elseif (str_contains($bodyClass, 'theme-old')) {
            $map = EshUiTokenCatalog::defaultValues($slug);
        } elseif (str_contains($bodyClass, 'theme-default')) {
            $map = [
                '--esh-ui-text' => '#1e293b',
                '--esh-ui-text-muted' => '#64748b',
                '--esh-ui-accent' => 'var(--esh-blue, #0d6efd)',
                '--esh-ui-surface' => '#ffffff',
                '--esh-ui-surface-muted' => '#f8fafc',
                '--esh-ui-border' => '#e2e8f0',
                '--esh-ui-radius' => '12px',
                '--esh-ui-shadow' => '0 2px 12px rgba(15, 23, 42, 0.06)',
                '--esh-ui-table-head-bg' => '#f8fafc',
                '--esh-ui-table-head-color' => '#64748b',
                '--esh-ui-row-hover' => 'rgba(13, 110, 253, 0.04)',
                '--esh-ui-filter-control-height' => '38px',
            ];
        } elseif (str_contains($bodyClass, 'aurora-theme')) {
            $map = [
                '--esh-ui-text' => 'var(--au-text)',
                '--esh-ui-text-muted' => 'var(--au-text-muted)',
                '--esh-ui-accent' => 'var(--au-accent-strong)',
                '--esh-ui-surface' => 'var(--au-surface)',
                '--esh-ui-surface-muted' => 'var(--au-surface-2)',
                '--esh-ui-border' => 'var(--au-border-strong)',
                '--esh-ui-radius' => 'var(--au-radius-sm)',
                '--esh-ui-shadow' => 'var(--au-shadow-sm)',
                '--esh-ui-table-head-bg' => 'var(--au-surface-2)',
                '--esh-ui-table-head-color' => 'var(--au-text-muted)',
                '--esh-ui-row-hover' => 'rgba(13, 148, 136, 0.08)',
                '--esh-ui-filter-control-height' => '38px',
            ];
        } else {
            $map = [
                '--esh-ui-text' => 'var(--mr-text)',
                '--esh-ui-text-muted' => 'var(--mr-muted)',
                '--esh-ui-accent' => 'var(--mr-accent2)',
                '--esh-ui-surface' => 'var(--mr-surface)',
                '--esh-ui-surface-muted' => 'var(--mr-surface2)',
                '--esh-ui-border' => 'var(--mr-border)',
                '--esh-ui-radius' => 'var(--mr-radius-sm, 0.65rem)',
                '--esh-ui-shadow' => 'var(--mr-shadow)',
                '--esh-ui-table-head-bg' => 'var(--mr-surface2)',
                '--esh-ui-table-head-color' => 'var(--mr-muted)',
                '--esh-ui-row-hover' => 'rgba(56, 189, 248, 0.08)',
                '--esh-ui-filter-control-height' => '38px',
            ];
        }

        foreach ($map as $name => $val) {
            $lines[] = '  ' . $name . ': ' . $val . ';';
        }
        $lines[] = '}';

        return implode("\n", $lines);
    }

    private static function colorValueKind(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^#([0-9a-fA-F]{3,8})$/', $value)) {
            return 'hex';
        }
        if (preg_match('/^rgba?\(/i', $value)) {
            return stripos($value, 'rgba') === 0 ? 'rgba' : 'rgb';
        }
        if (preg_match('/^hsla?\(/i', $value)) {
            return stripos($value, 'hsla') === 0 ? 'hsla' : 'hsl';
        }

        return 'named';
    }

    private static function bumpThemeManifest(string $slug): void
    {
        $manifestPath = rtrim((string) ROOT_PATH, '/\\') . '/templates/' . $slug . '/_theme.json';
        if (!is_file($manifestPath)) {
            return;
        }

        $decoded = json_decode((string) file_get_contents($manifestPath), true);
        if (!is_array($decoded)) {
            $decoded = [];
        }

        $surum = trim((string) ($decoded['surum'] ?? $decoded['version'] ?? ''));
        if ($surum !== '' && preg_match('/^(\d+)\.(\d+)\.(\d+)$/', $surum, $m)) {
            $decoded['surum'] = $m[1] . '.' . $m[2] . '.' . ((int) $m[3] + 1);
        } elseif ($surum !== '') {
            $decoded['surum'] = $surum;
        } else {
            $decoded['surum'] = '1.0.1';
        }

        $decoded['guncelleme_tarihi'] = date('Y-m-d');

        file_put_contents(
            $manifestPath,
            json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
        );
    }
}

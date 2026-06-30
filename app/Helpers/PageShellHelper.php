<?php

namespace App\Helpers;



/**

 * Ortak sayfa markup'ı — docs/ESH_PAGE_LANGUAGE.md

 */

class PageShellHelper

{

    private static bool $panelOpen = false;

    private static bool $formPageOpen = false;



    /**

     * @param array{

     *   kind?: string,

     *   module?: string,

     *   id?: string,

     *   class?: string,

     *   attrs?: array<string, string|int|bool>

     * } $options

     */

    public static function pageOpen(array $options = []): void

    {

        $kind = self::sanitizeKind((string) ($options['kind'] ?? 'list'));

        $module = self::sanitizeModule((string) ($options['module'] ?? ''));

        $id = trim((string) ($options['id'] ?? ''));

        $extraClass = trim((string) ($options['class'] ?? ''));

        $attrs = is_array($options['attrs'] ?? null) ? $options['attrs'] : [];



        $classes = ['esh-page', 'esh-page--' . $kind, 'container-fluid', 'py-4'];

        if ($module !== '') {

            $classes[] = 'esh-page-' . $module;

        }

        if ($extraClass !== '') {

            $classes[] = $extraClass;

        }



        $attrParts = ['class="' . htmlspecialchars(implode(' ', $classes), ENT_QUOTES, 'UTF-8') . '"'];

        if ($id !== '') {

            $attrParts[] = 'id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"';

        }

        foreach ($attrs as $key => $value) {

            $key = preg_replace('/[^a-z0-9_-]/i', '', (string) $key);

            if ($key === '') {

                continue;

            }

            if ($value === false || $value === null) {

                continue;

            }

            if ($value === true) {

                $attrParts[] = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');

                continue;

            }

            $attrParts[] = htmlspecialchars($key, ENT_QUOTES, 'UTF-8')

                . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';

        }



        echo '<article ' . implode(' ', $attrParts) . ' lang="tr">' . "\n";

    }



    public static function pageClose(): void

    {

        if (self::$panelOpen) {

            self::panelClose();

        }

        if (self::$formPageOpen) {

            self::formPageClose();

        }

        echo "</article>\n";

    }



    /**

     * @param array{icon?: string, headingClass?: string} $options

     */

    public static function pageHeader(string $title, ?string $lead = null, ?string $toolbarHtml = null, array $options = []): void

    {

        $icon = trim((string) ($options['icon'] ?? ''));

        $headingClass = trim((string) ($options['headingClass'] ?? ''));

        $headingClasses = 'esh-page__heading';

        if ($headingClass !== '') {

            $headingClasses .= ' ' . $headingClass;

        }



        echo '<header class="esh-page__header">' . "\n";

        echo '<div class="esh-page__intro">' . "\n";

        echo '<h1 class="' . htmlspecialchars($headingClasses, ENT_QUOTES, 'UTF-8') . '">';

        if ($icon !== '') {

            echo '<i class="' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . ' me-2" aria-hidden="true"></i>';

        }

        echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

        echo "</h1>\n";

        if ($lead !== null && $lead !== '') {

            echo '<p class="esh-page__lead">' . htmlspecialchars($lead, ENT_QUOTES, 'UTF-8') . "</p>\n";

        }

        echo "</div>\n";

        if ($toolbarHtml !== null && trim($toolbarHtml) !== '') {

            echo '<div class="esh-page__toolbar">' . $toolbarHtml . "</div>\n";

        }

        echo "</header>\n";

    }



    /**

     * Form sayfaları — tek container, merkezlenmiş kolon.

     *

     * @param array{col?: string, class?: string} $options

     */

    public static function formPageOpen(array $options = []): void

    {

        $col = trim((string) ($options['col'] ?? 'col-lg-9'));

        $extraClass = trim((string) ($options['class'] ?? ''));

        $rowClass = 'row justify-content-center' . ($extraClass !== '' ? ' ' . htmlspecialchars($extraClass, ENT_QUOTES, 'UTF-8') : '');



        echo '<div class="' . $rowClass . '">' . "\n";

        echo '<div class="' . htmlspecialchars($col, ENT_QUOTES, 'UTF-8') . '">' . "\n";

        self::$formPageOpen = true;

    }



    public static function formPageClose(): void

    {

        if (!self::$formPageOpen) {

            return;

        }

        echo "</div>\n</div>\n";

        self::$formPageOpen = false;

    }



    /**

     * @param array{

     *   icon?: string,

     *   headingClass?: string,

     *   cardExtra?: string,

     *   bodyClass?: string,

     *   headerClass?: string

     * } $options

     */

    public static function panelOpen(string $title, array $options = []): void

    {

        $icon = trim((string) ($options['icon'] ?? ''));

        $headingClass = trim((string) ($options['headingClass'] ?? 'h5 mb-0 fw-bold'));

        $cardExtra = trim((string) ($options['cardExtra'] ?? ''));

        $bodyClass = trim((string) ($options['bodyClass'] ?? 'p-4'));

        $headerClass = trim((string) ($options['headerClass'] ?? 'bg-white py-3 border-bottom'));



        $cardClasses = 'esh-page__panel card shadow-sm border-0';

        if ($cardExtra !== '') {

            $cardClasses .= ' ' . $cardExtra;

        }



        echo '<section class="' . htmlspecialchars($cardClasses, ENT_QUOTES, 'UTF-8') . '">' . "\n";

        echo '<header class="esh-page__panel-head card-header ' . htmlspecialchars($headerClass, ENT_QUOTES, 'UTF-8') . '">' . "\n";

        echo '<h2 class="esh-page__heading ' . htmlspecialchars($headingClass, ENT_QUOTES, 'UTF-8') . '">';

        if ($icon !== '') {

            echo '<i class="' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . ' me-2" aria-hidden="true"></i>';

        }

        echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

        echo "</h2>\n</header>\n";

        echo '<div class="esh-page__panel-body card-body ' . htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') . '">' . "\n";

        self::$panelOpen = true;

    }



    public static function panelClose(): void

    {

        if (!self::$panelOpen) {

            return;

        }

        echo "</div>\n</section>\n";

        self::$panelOpen = false;

    }



    private static function sanitizeKind(string $kind): string

    {

        $kind = strtolower(preg_replace('/[^a-z0-9-]/', '', $kind));



        return in_array($kind, ['list', 'form', 'detail', 'dashboard'], true) ? $kind : 'list';

    }



    private static function sanitizeModule(string $module): string

    {

        return strtolower(preg_replace('/[^a-z0-9_-]/', '', $module));

    }

}


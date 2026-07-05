<?php
namespace App\Helpers;

class PaginationHelper {

    /**
     * Bootstrap 5 uyumlu limit (sayfa başına kayıt) seçici üretir
     * * @param int $current_limit Mevcut seçili limit
     * @param string $base_url Linklerin başına gelecek URL
     * @return string HTML Çıktısı
     */
    public static function limitSelector($current_limit, $base_url, array $opts = []) {
    $options = $opts['limits'] ?? [5, 10, 15, 20, 25, 50, 100];
    $ajax = !empty($opts['ajax']);
    $selectClass = trim((string) ($opts['select_class'] ?? 'form-select form-select-sm'));
    
    // URL'deki mevcut limit ve page kısımlarını temizleyelim
    $clean_url = preg_replace('/([&?])(limit|page)=[^&]*/', '', $base_url);
    $sep = (strpos($clean_url, '?') === false) ? '?' : '&';
    $final_url = rtrim($clean_url, '&?') . $sep;

    $html = '<div class="d-flex align-items-center small text-muted">';
    $html .= '<span class="me-2 text-nowrap">Satır:</span>';
    if ($ajax) {
        $html .= '<select class="' . htmlspecialchars($selectClass, ENT_QUOTES, 'UTF-8') . '" style="width: auto;">';
        foreach ($options as $opt) {
            $selected = ((int) $current_limit === (int) $opt) ? 'selected' : '';
            $html .= "<option value='{$opt}' {$selected}>{$opt}</option>";
        }
        $html .= '</select></div>';
        return $html;
    }
    $html .= '<select class="form-select form-select-sm" style="width: auto;" data-esh-navigate>';
    
    foreach ($options as $opt) {
        $selected = ($current_limit == $opt) ? 'selected' : '';
        // Yeni limit seçildiğinde her zaman 1. sayfaya atsın
        $url = "{$final_url}limit={$opt}&page=1";
        $html .= "<option value='{$url}' {$selected}>{$opt}</option>";
    }
    
    $html .= '</select></div>';
    return $html;
}

    /**
     * Özet Bilgi Metni (Örn: 50 kayıt arasından 11-20 gösteriliyor)
     */
    public static function infoText($total, $current_page, $limit) {
        if ($total == 0) return "Kayıt bulunamadı.";
        
        $from = (($current_page - 1) * $limit) + 1;
        $to = min($current_page * $limit, $total);
        
        return "Toplam <strong>{$total}</strong> kayıttan <strong>{$from}-{$to}</strong> arası gösteriliyor.";
    }

    /**
     * Gelişmiş Sayfalama (Özet bilgi ile birlikte)
     */
    /**
     * Bootstrap 5 uyumlu ve URL parametrelerini koruyan sayfalama render metodu
     */
    public static function render($total_items, $current_page, $limit, $base_url, array $opts = []) {
        $total_pages = (int) ceil($total_items / max(1, (int) $limit));
        if ($total_pages <= 1) {
            return '';
        }

        $ajax = !empty($opts['ajax']);
        $linkClass = htmlspecialchars(trim((string) ($opts['link_class'] ?? 'page-link')), ENT_QUOTES, 'UTF-8');

        // 1. Sayfalama tabanı: $base_url sorgu dizesi (controller'ın ürettiği filtre/sıralama)
        $params = [];
        $queryString = parse_url($base_url, PHP_URL_QUERY);
        if (is_string($queryString) && $queryString !== '') {
            parse_str($queryString, $params);
        }

        // 2. Sayfa numarası hariç; limit güncel
        unset($params['page']);
        $params['limit'] = $limit;

        // 3. Sıralama: istek URL'sinde varsa güncelle; yoksa $base_url'deki değer korunur (varsayılan enjekte edilmez)
        if (array_key_exists('orderby', $_GET)) {
            $params['orderby'] = (string) $_GET['orderby'];
        }
        if (array_key_exists('orderdir', $_GET)) {
            $dir = strtoupper(trim((string) $_GET['orderdir']));
            $params['orderdir'] = in_array($dir, ['ASC', 'DESC'], true) ? $dir : 'ASC';
        }

        // 4. Temel URL (sayfa numarası hariç)
        $path = parse_url($base_url, PHP_URL_PATH);
        if ($path === null || $path === '') {
            $path = 'index.php';
        }
        $query = http_build_query($params);
        $final_base_url = $path . ($query !== '' ? '?' . $query : '?');

        $html = '<div class="d-flex align-items-center">';
        $html .= '<nav aria-label="Page navigation"><ul class="pagination pagination-sm mb-0 shadow-sm">';

        // İlk ve Geri
        $disabled = ($current_page <= 1) ? 'disabled' : '';
        $prev_page = $current_page - 1;
        if ($ajax) {
            $html .= "<li class='page-item {$disabled}'><a class='{$linkClass}' href='#' data-page='1' title='İlk Sayfa'><i class='fa-solid fa-angles-left'></i></a></li>";
            $html .= "<li class='page-item {$disabled}'><a class='{$linkClass}' href='#' data-page='{$prev_page}' title='Geri'><i class='fa-solid fa-angle-left'></i></a></li>";
        } else {
            $html .= "<li class='page-item {$disabled}'><a class='page-link' href='{$final_base_url}&page=1' title='İlk Sayfa'><i class='fa-solid fa-angles-left'></i></a></li>";
            $html .= "<li class='page-item {$disabled}'><a class='page-link' href='{$final_base_url}&page={$prev_page}' title='Geri'><i class='fa-solid fa-angle-left'></i></a></li>";
        }

        // Sayfa Numaraları (Mevcut sayfanın 3 öncesi ve 3 sonrasını gösterir)
        $start = max(1, $current_page - 3);
        $end = min($total_pages, $current_page + 3);

        for ($i = $start; $i <= $end; $i++) {
            $active = ($current_page == $i) ? 'active' : '';
            if ($ajax) {
                $html .= "<li class='page-item {$active}'><a class='{$linkClass}' href='#' data-page='{$i}'>{$i}</a></li>";
            } else {
                $html .= "<li class='page-item {$active}'><a class='page-link' href='{$final_base_url}&page={$i}'>{$i}</a></li>";
            }
        }

        // İleri ve Son
        $disabled = ($current_page >= $total_pages) ? 'disabled' : '';
        $next_page = $current_page + 1;
        if ($ajax) {
            $html .= "<li class='page-item {$disabled}'><a class='{$linkClass}' href='#' data-page='{$next_page}' title='İleri'><i class='fa-solid fa-angle-right'></i></a></li>";
            $html .= "<li class='page-item {$disabled}'><a class='{$linkClass}' href='#' data-page='{$total_pages}' title='Son Sayfa'><i class='fa-solid fa-angles-right'></i></a></li>";
        } else {
            $html .= "<li class='page-item {$disabled}'><a class='page-link' href='{$final_base_url}&page={$next_page}' title='İleri'><i class='fa-solid fa-angle-right'></i></a></li>";
            $html .= "<li class='page-item {$disabled}'><a class='page-link' href='{$final_base_url}&page={$total_pages}' title='Son Sayfa'><i class='fa-solid fa-angles-right'></i></a></li>";
        }

        $html .= '</ul></nav>';
        
        // Hızlı Sayfaya Git (Jump to Page)
        if ($total_pages > 5) {
            $html .= self::jumpToPage($total_pages, $final_base_url, $opts);
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Sayfaya Git (Jump to Page) - Özellikle çok kayıtlı tablolarda hayat kurtarır
     */
    /**
     * Hızlı sayfa geçiş girişi
     */
    private static function jumpToPage($total_pages, $url, array $opts = []) {
        $jumpId = htmlspecialchars((string) ($opts['jump_id'] ?? 'jump_page'), ENT_QUOTES, 'UTF-8');
        $jumpBtnClass = htmlspecialchars(trim((string) ($opts['jump_btn_class'] ?? 'btn btn-outline-primary')), ENT_QUOTES, 'UTF-8');
        if (!empty($opts['ajax'])) {
            return '
        <div class="input-group input-group-sm ms-3" style="width: 130px;">
            <input type="number" id="' . $jumpId . '" class="form-control orphan-jump-page-input" placeholder="Sfy No" min="1" max="' . (int) $total_pages . '">
            <button class="' . $jumpBtnClass . ' orphan-jump-page-btn" type="button" data-max="' . (int) $total_pages . '">Git</button>
        </div>';
        }
        $urlEsc = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        return '
        <div class="input-group input-group-sm ms-3" style="width: 130px;">
            <input type="number" id="jump_page" class="form-control" placeholder="Sfy No" min="1" max="'.$total_pages.'">
            <button class="btn btn-outline-primary" type="button" data-esh-pagination-jump data-esh-jump-input="#jump_page" data-esh-jump-url-base="' . $urlEsc . '" data-esh-jump-max="' . (int) $total_pages . '">Git</button>
        </div>';
    }
}
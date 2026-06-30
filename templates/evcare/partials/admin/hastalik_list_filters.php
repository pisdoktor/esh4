<?php
declare(strict_types=1);
/**
 * Hastalık kütüphanesi listesi — kategori süzgeci (collapse kart).
 * @var array $categories
 * @var string $eshHastalikListCat ''|'0'|kategori id
 * @var bool $filterExpanded
 */
$catVal = isset($eshHastalikListCat) ? (string) $eshHastalikListCat : '';
if ($catVal !== '0' && $catVal !== '') {
    $catVal = (string) (int) $catVal;
    if ($catVal === '0') {
        $catVal = '';
    }
}
$filterExpanded = !empty($filterExpanded);
$categories = is_array($categories ?? null) ? $categories : [];
?>
<link rel="stylesheet" href="<?= htmlspecialchars(ASSETS_URL . '/pages/css/hastalik-index.css', ENT_QUOTES, 'UTF-8') ?>">
<div class="container-fluid py-4 admin-list-page esh-page-hastalik-list">
    <div class="card border-0 shadow-sm rounded-3 mb-3 overflow-hidden">
        <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span class="rounded-circle bg-info-subtle text-info d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:42px;height:42px;">
                    <i class="fa-solid fa-sliders"></i>
                </span>
                <div class="min-w-0">
                    <span class="fw-semibold text-dark d-block">Liste filtreleri</span>
                    <span class="small text-muted">Kategori seçip «Filtrele» ile uygulayın.</span>
                </div>
            </div>
            <button
                id="hastalik-filter-toggle"
                class="btn btn-outline-secondary btn-sm rounded-pill px-3<?= $filterExpanded ? '' : ' collapsed' ?>"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#hastalik-filter-collapse"
                aria-expanded="<?= $filterExpanded ? 'true' : 'false' ?>"
                aria-controls="hastalik-filter-collapse"
            >
                <i class="fa-solid fa-sliders me-1"></i><span class="js-filter-toggle-text"><?= $filterExpanded ? 'Filtreleri Gizle' : 'Filtreleri Göster' ?></span>
            </button>
        </div>
        <div id="hastalik-filter-collapse" class="collapse<?= $filterExpanded ? ' show' : '' ?>">
            <div class="card-body p-3 p-md-4 bg-body-tertiary bg-opacity-25">
                <form method="get" action="<?= htmlspecialchars(esh_form_action('Hastalik', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 g-xl-4 align-items-end esh-hastalik-filter">
                <?= esh_form_route_hiddens('Hastalik', 'index') ?>

                    <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
                        <label for="hastalik-filter-cat" class="form-label fw-semibold small text-secondary mb-1">Kategori</label>
                        <select name="cat" id="hastalik-filter-cat" class="form-select form-select-sm shadow-sm esh-filter-control">
                            <option value=""<?= $catVal === '' ? ' selected' : '' ?>>Tüm kategoriler</option>
                            <option value="0"<?= $catVal === '0' ? ' selected' : '' ?>>Kategorisiz</option>
                            <?php foreach ($categories as $cat): ?>
                                <?php $cid = (string) (int) ($cat->id ?? 0); ?>
                                <?php if ($cid === '0') {
                                    continue;
                                } ?>
                                <option value="<?= htmlspecialchars($cid, ENT_QUOTES, 'UTF-8') ?>"<?= $catVal === $cid ? ' selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($cat->name ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-6 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary btn-sm shadow-sm px-4 rounded-pill esh-filter-control"><i class="fa-solid fa-filter me-1"></i>Filtrele</button>
                        <a href="<?= htmlspecialchars(esh_url('Hastalik', 'index'), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3 esh-filter-control">Sıfırla</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

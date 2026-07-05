<div class="esh-page esh-page--list esh-page-harita container-fluid py-4">
<div class="container-fluid esh-admin-harita py-0">
<?php include __DIR__ . '/partials/search_bar.php'; ?>
<?php
$activeMapProvider = $activeMapProvider ?? \App\Helpers\OperationalSettings::activeMapProviderStatusForAdmin();
$mapProviderConfigured = $mapProviderConfigured ?? \App\Helpers\MapRoutingGeocodeHelper::isActiveProviderConfigured();
$keyStatus = $keyStatus ?? \App\Services\MapRouting\MapRoutingProviderFactory::keyStatusForProvider($activeMapProvider['code'] ?? 'tomtom');
$providerLabel = (string) ($activeMapProvider['label'] ?? 'Harita');
?>
<?php if (empty($mapProviderConfigured)): ?>
<div class="alert alert-danger py-2 small mb-0 rounded-0 border-0">
    Aktif harita sağlayıcısı (<?= htmlspecialchars($providerLabel, ENT_QUOTES, 'UTF-8') ?>) için API anahtarı tanımlı değil.
    <code><?= htmlspecialchars((string) ($keyStatus['config_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code>
    veya ortam değişkeni <code><?= htmlspecialchars((string) ($keyStatus['env_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code>
    ile <code>config/config.local.php</code> içinde ayarlayın; ardından Ayarlar → Harita bölümünden sağlayıcıyı doğrulayın.
</div>
<?php endif; ?>
<?php
$eshHaritaPatientCount = (int) ($eshHaritaPatientCount ?? 0);
$eshHaritaScopeLabel = $eshHaritaScopeLabel ?? 'Tüm bölgeler';
?>
<?php if ($eshHaritaPatientCount === 0): ?>
<div class="alert alert-warning py-2 small mb-0 rounded-0 border-0">
    Seçili kapsamda (<strong><?= htmlspecialchars($eshHaritaScopeLabel, ENT_QUOTES, 'UTF-8') ?></strong>) koordinatlı aktif hasta bulunamadı.
    <?php if (\App\Helpers\AuthHelper::sessionIsPlatformOwner()): ?>
        Üst menüdeki bölge filtresini <strong>Tüm bölgeler</strong> yapın; kurum filtresi haritayı daraltmaz.
    <?php elseif (\App\Helpers\TenantContext::sessionIsBolgeLockedSuperAdmin()): ?>
        Bu bölgede koordinatlı hasta yok veya bölge atamanız kontrol edilmeli.
    <?php else: ?>
        Bölge/kurum kapsamını veya hasta koordinatlarını kontrol edin.
    <?php endif; ?>
</div>
<?php endif; ?>
<?php include __DIR__ . '/partials/map_container.php'; ?>
</div>
</div>

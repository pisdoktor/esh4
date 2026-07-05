<?php
/**
 * @var list<array{code:string,label:string,configured:bool,masked:string,source:string,config_key:string,env_key:string}> $mapProviderStatuses
 * @var array{code:string,label:string,configured:bool} $activeMapProvider
 */
use App\Helpers\CdnAssetHelper;

$mapProviderStatuses = $mapProviderStatuses ?? [];
$activeMapProvider = $activeMapProvider ?? ['code' => '', 'label' => '—', 'configured' => false];
$activeCode = (string) ($activeMapProvider['code'] ?? '');
$sdkConstMap = CdnAssetHelper::mapProviderSdkConstMap();
?>
<div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th scope="col">Sağlayıcı</th>
                <th scope="col">Tarayıcı SDK</th>
                <th scope="col">API anahtarı</th>
                <th scope="col" class="text-end">Durum</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mapProviderStatuses as $row): ?>
                <?php
                $code = (string) ($row['code'] ?? '');
                $isActive = $code !== '' && $code === $activeCode;
                $configured = !empty($row['configured']);
                $sdkConst = $sdkConstMap[$code] ?? null;
                ?>
                <tr<?= $isActive ? ' class="table-primary"' : '' ?>>
                    <td>
                        <span class="fw-semibold"><?= htmlspecialchars((string) ($row['label'] ?? $code), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php if ($isActive): ?>
                            <span class="badge text-bg-primary ms-1">Aktif</span>
                        <?php endif; ?>
                        <code class="d-block small text-muted mt-1"><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></code>
                    </td>
                    <td>
                        <?php if ($code === 'google'): ?>
                            <span class="text-muted small">Google CDN (sürüm sabitlenmez)</span>
                        <?php elseif (is_string($sdkConst) && $sdkConst !== ''): ?>
                            <code><?= htmlspecialchars($sdkConst, ENT_QUOTES, 'UTF-8') ?></code>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($configured): ?>
                            <code class="small"><?= htmlspecialchars((string) ($row['masked'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code>
                            <?php if (!empty($row['source'])): ?>
                                <span class="d-block small text-muted"><?= htmlspecialchars((string) $row['source'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-danger small">Tanımlı değil</span>
                            <span class="d-block small text-muted">
                                <code><?= htmlspecialchars((string) ($row['config_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code>
                                · <code><?= htmlspecialchars((string) ($row['env_key'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php if ($isActive && !$configured): ?>
                            <span class="badge text-bg-danger" title="Aktif sağlayıcı için anahtar gerekli">Aktif · anahtar yok</span>
                        <?php elseif ($isActive): ?>
                            <span class="badge text-bg-primary">Aktif · hazır</span>
                        <?php elseif ($configured): ?>
                            <span class="badge text-bg-success">Anahtar var</span>
                        <?php else: ?>
                            <span class="badge text-bg-secondary">Pasif · anahtar yok</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="small text-muted mb-0 px-3 py-2 border-top bg-light">
    Bu sayfa yalnızca <?= htmlspecialchars(mb_strtolower(\App\Helpers\AuthHelper::adminLevelLabel(\App\Helpers\AuthHelper::ROLE_PLATFORM_OWNER), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>ne görünür; tüm sağlayıcı SDK sürümleri ve CDN URL’leri kontrol edilir (yalnızca aktif olan değil).
    Aktif sağlayıcı: <strong><?= htmlspecialchars((string) ($activeMapProvider['label'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong>
    · Ayarlar → Harita sekmesinden değiştirilir.
</p>

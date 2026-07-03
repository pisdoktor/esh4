<?php
/**
 * @var array<string, mixed> $profileStats
 * @var list<array> $hubGroups
 * @var string $statsHubUrl
 * @var string $profileUrl
 * @var bool $statsAdminView
 * @var string $statsSubjectName
 * @var int|null $statsQueryUserId
 */
use App\Helpers\UserProfileStatsHelper;

$ps = is_array($profileStats ?? null) ? $profileStats : [];
$statsAdminView = !empty($statsAdminView);
$statsQueryUserId = isset($statsQueryUserId) && (int) $statsQueryUserId > 0 ? (int) $statsQueryUserId : null;
$statsSubjectName = trim((string) ($statsSubjectName ?? ''));
?>
<div class="container py-4 esh-page-user-stats-hub">
    <nav aria-label="breadcrumb" class="mb-3 small">
        <ol class="breadcrumb mb-0">
            <?php if ($statsAdminView): ?>
                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') ?>">Kullanıcılar</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($statsSubjectName !== '' ? $statsSubjectName : 'Personel', ENT_QUOTES, 'UTF-8') ?></li>
                <li class="breadcrumb-item active">İş özeti</li>
            <?php else: ?>
                <li class="breadcrumb-item"><a href="<?= htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') ?>">Profilim</a></li>
                <li class="breadcrumb-item active">İş özeti istatistikleri</li>
            <?php endif; ?>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="fa-solid fa-chart-pie text-primary me-2"></i>
                <?php if ($statsAdminView && $statsSubjectName !== ''): ?>
                    <?= htmlspecialchars($statsSubjectName, ENT_QUOTES, 'UTF-8') ?> — iş özeti
                <?php else: ?>
                    İş özeti istatistikleri
                <?php endif; ?>
            </h4>
            <p class="text-muted small mb-0">
                <?php if ($statsAdminView): ?>
                    Seçili personelin iş özetindeki sayıların kayıt listelerine erişim.
                <?php else: ?>
                    Profilinizdeki sayıların hangi kayıtlara karşılık geldiğini buradan listeleyebilirsiniz.
                <?php endif; ?>
            </p>
        </div>
        <a href="<?= htmlspecialchars($profileUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fa-solid fa-arrow-left me-1"></i><?= $statsAdminView ? 'Kullanıcı listesi' : 'Profile dön' ?>
        </a>
    </div>

    <?php if ($statsAdminView): ?>
        <div class="alert alert-info border-0 shadow-sm small mb-4">
            <i class="fa-solid fa-user-shield me-1"></i>
            Yönetici görünümü — bu istatistikler <strong><?= htmlspecialchars($statsSubjectName !== '' ? $statsSubjectName : 'seçili personel', ENT_QUOTES, 'UTF-8') ?></strong> kullanıcısına aittir.
        </div>
    <?php endif; ?>

    <?php foreach ($hubGroups as $group): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h2 class="h6 fw-bold mb-0 text-primary">
                    <i class="<?= htmlspecialchars((string) $group['icon'], ENT_QUOTES, 'UTF-8') ?> me-2"></i>
                    <?= htmlspecialchars((string) $group['label'], ENT_QUOTES, 'UTF-8') ?>
                </h2>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($group['items'] as $item): ?>
                    <?php
                    $key = (string) ($item['key'] ?? '');
                    $count = (int) ($ps[$item['stat_key'] ?? ''] ?? 0);
                    $detailUrl = UserProfileStatsHelper::statsDetailUrl($key, $statsQueryUserId);
                    ?>
                    <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2 py-3">
                        <div class="min-w-0">
                            <div class="fw-semibold"><?= htmlspecialchars((string) ($item['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="small text-muted"><?= htmlspecialchars((string) ($item['hint'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                            <span class="badge rounded-pill bg-primary-subtle text-primary fs-6 px-3"><?= number_format($count, 0, ',', '.') ?></span>
                            <?php if ($count > 0): ?>
                                <a href="<?= htmlspecialchars($detailUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-sm btn-outline-primary rounded-pill">
                                    Listele <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            <?php else: ?>
                                <span class="btn btn-sm btn-outline-secondary rounded-pill disabled" aria-disabled="true">Kayıt yok</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

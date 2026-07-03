<?php
declare(strict_types=1);

use App\Helpers\ValidationHelper;
use App\Helpers\ZamanDilimiHelper;
use App\Helpers\PatientPortalHelper;
use App\Helpers\DateHelper;

/** @var object $eshPortalPatient */
/** @var string $eshPortalRole */
/** @var string $eshPortalRoleLabel */
/** @var list<object> $eshPortalPlanned */
/** @var list<object> $eshPortalVisits */
/** @var list<object> $eshPortalUhds */
/** @var array<int,string> $eshPortalUhdsJoinUrls */
/** @var list<object> $eshPortalAppointmentRequests */
/** @var bool $eshPortalSmsOnay */
/** @var bool $eshPortalTelehealthEnabled */

$portalSuccess = $_SESSION['portal_success'] ?? '';
$portalError = $_SESSION['portal_error'] ?? '';
unset($_SESSION['portal_success'], $_SESSION['portal_error']);

$hastaAd = trim((string) ($eshPortalPatient->isim ?? '') . ' ' . (string) ($eshPortalPatient->soyisim ?? ''));
?>
<article class="pha-card portal-dashboard">
    <header class="pha-card__hero portal-dashboard__hero">
        <div class="pha-card__icon" aria-hidden="true">
            <i class="fa-solid fa-house-medical-circle-check"></i>
        </div>
        <h1 class="pha-card__title h4 mb-1">Hoş geldiniz</h1>
        <p class="pha-card__lead mb-0">
            <?= htmlspecialchars($hastaAd, ENT_QUOTES, 'UTF-8') ?>
            <span class="text-white-50">·</span>
            <?= htmlspecialchars($eshPortalRoleLabel, ENT_QUOTES, 'UTF-8') ?>
        </p>
    </header>
    <div class="pha-card__body">
        <?php if ($portalSuccess !== ''): ?>
            <div class="alert alert-success small"><?= htmlspecialchars((string) $portalSuccess, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($portalError !== ''): ?>
            <div class="alert alert-danger small"><?= htmlspecialchars((string) $portalError, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h2 class="h6 fw-bold mb-0"><i class="fa-solid fa-comment-sms text-primary me-2"></i>SMS bilgilendirme onayı</h2>
                    </div>
                    <div class="card-body">
                        <p class="small text-muted">Randevu ve ziyaret hatırlatmaları için SMS gönderimine onay verebilir veya kaldırabilirsiniz.</p>
                        <form method="post" action="<?= htmlspecialchars(esh_url('PatientPortal', 'updateSmsConsent', [], true), ENT_QUOTES, 'UTF-8') ?>">
                            <?= esh_csrf_field() ?>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="sms_bilgilendirme_onay" value="1" id="portalSmsOnay" <?= $eshPortalSmsOnay ? 'checked' : '' ?>>
                                <label class="form-check-label" for="portalSmsOnay">SMS bilgilendirme mesajlarını kabul ediyorum</label>
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill">Onayı kaydet</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($eshPortalPlanned)): ?>
        <section class="mb-4">
            <h2 class="h6 fw-bold border-bottom pb-2 mb-3"><i class="fa-solid fa-calendar-day text-success me-2"></i>Yaklaşan planlı ziyaretler</h2>
            <div class="list-group list-group-flush shadow-sm rounded-3 border">
                <?php foreach ($eshPortalPlanned as $row):
                    $tarih = (string) ($row->planlanantarih ?? '');
                    $tarihTr = $tarih !== '' ? DateHelper::toTr($tarih) : '—';
                    ?>
                <div class="list-group-item">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <strong><?= htmlspecialchars($tarihTr, ENT_QUOTES, 'UTF-8') ?></strong>
                        <span class="badge text-bg-light border"><?= htmlspecialchars(ZamanDilimiHelper::label((int) ($row->zaman ?? 0)), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <?php if (!empty($row->yapilacaklar)): ?>
                        <div class="small text-muted mt-1"><?= htmlspecialchars((string) $row->yapilacaklar, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($eshPortalUhds)): ?>
        <section class="mb-4">
            <h2 class="h6 fw-bold border-bottom pb-2 mb-3"><i class="fa-solid fa-video text-primary me-2"></i>UHDS randevuları</h2>
            <div class="list-group list-group-flush shadow-sm rounded-3 border">
                <?php foreach ($eshPortalUhds as $row):
                    $tarih = (string) ($row->randevu_tarihi ?? '');
                    $tarihTr = $tarih !== '' ? DateHelper::toTr($tarih) : '—';
                    ?>
                <div class="list-group-item">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div>
                            <strong><?= htmlspecialchars($tarihTr, ENT_QUOTES, 'UTF-8') ?></strong>
                            <span class="text-muted small ms-2"><?= htmlspecialchars((string) ($row->bransadi ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <span class="badge text-bg-light border"><?= htmlspecialchars(ZamanDilimiHelper::label((int) ($row->zaman ?? 0)), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="small text-muted mt-1">Durum: <?= htmlspecialchars(PatientPortalHelper::uhdsStatusLabel($row->hasta_geldi ?? null), ENT_QUOTES, 'UTF-8') ?></div>
                    <?php if ($eshPortalTelehealthEnabled): ?>
                        <?php $joinUrl = (string) ($eshPortalUhdsJoinUrls[(int) ($row->id ?? 0)] ?? ''); ?>
                        <?php if ($joinUrl !== ''): ?>
                            <a class="btn btn-sm btn-outline-primary rounded-pill mt-2"
                               href="<?= htmlspecialchars($joinUrl, ENT_QUOTES, 'UTF-8') ?>"
                               target="_blank" rel="noopener">
                                <i class="fa-solid fa-video me-1"></i>Görüşmeye katıl
                            </a>
                        <?php else: ?>
                            <p class="small mb-0 mt-2 text-muted">Görüntülü görüşme bağlantısı kurumunuz tarafından SMS veya mesajla iletilir.</p>
                        <?php endif; ?>
                    <?php endif; ?>
                    <details class="mt-2">
                        <summary class="small text-primary" style="cursor:pointer;">Randevu değişiklik talebi</summary>
                        <form method="post" action="<?= htmlspecialchars(esh_url('PatientPortal', 'requestAppointmentChange', [], true), ENT_QUOTES, 'UTF-8') ?>" class="mt-2">
                            <?= esh_csrf_field() ?>
                            <input type="hidden" name="uhds_id" value="<?= (int) ($row->id ?? 0) ?>">
                            <div class="row g-2">
                                <div class="col-12 col-md-4">
                                    <input type="date" class="form-control form-control-sm" name="talep_tarih" required>
                                </div>
                                <div class="col-12 col-md-4">
                                    <select class="form-select form-select-sm" name="talep_zaman">
                                        <option value="">Zaman dilimi</option>
                                        <option value="0">Sabah</option>
                                        <option value="1">Ogle</option>
                                        <option value="2">Aksam</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill w-100">Talep gonder</button>
                                </div>
                            </div>
                            <textarea class="form-control form-control-sm mt-2" name="neden" rows="2" maxlength="500" placeholder="Kisa aciklama" required></textarea>
                        </form>
                    </details>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($eshPortalAppointmentRequests)): ?>
        <section class="mb-4">
            <h2 class="h6 fw-bold border-bottom pb-2 mb-3"><i class="fa-solid fa-inbox text-info me-2"></i>Randevu talep kuyrugu</h2>
            <div class="table-responsive rounded-3 border shadow-sm">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tarih</th>
                            <th>Mevcut</th>
                            <th>Talep</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eshPortalAppointmentRequests as $req): ?>
                        <tr>
                            <td><?= htmlspecialchars(DateHelper::toTr((string) ($req->talep_tarihi ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(DateHelper::toTr((string) ($req->mevcut_tarih ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(DateHelper::toTr((string) ($req->talep_tarih ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($req->durum ?? 'queued'), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($eshPortalVisits)): ?>
        <section class="mb-2">
            <h2 class="h6 fw-bold border-bottom pb-2 mb-3"><i class="fa-solid fa-clipboard-check text-secondary me-2"></i>Son ziyaret özeti</h2>
            <div class="table-responsive rounded-3 border shadow-sm">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tarih</th>
                            <th>Zaman</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($eshPortalVisits as $row):
                            $tarih = (string) ($row->izlemtarihi ?? '');
                            $tarihTr = $tarih !== '' ? DateHelper::toTr($tarih) : '—';
                            ?>
                        <tr>
                            <td><?= htmlspecialchars($tarihTr, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(ZamanDilimiHelper::label((int) ($row->zaman ?? 0)), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(PatientPortalHelper::visitStatusLabel($row->yapildimi ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mt-2 mb-0">Klinik detay ve tedavi bilgileri yalnızca sağlık personeli tarafından görüntülenebilir.</p>
        </section>
        <?php endif; ?>

        <?php if (empty($eshPortalPlanned) && empty($eshPortalUhds) && empty($eshPortalVisits)): ?>
            <div class="alert alert-light border text-center small mb-0">Şu an gösterilecek yaklaşan plan veya ziyaret özeti bulunmuyor.</div>
        <?php endif; ?>
    </div>
</article>

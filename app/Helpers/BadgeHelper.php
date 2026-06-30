<?php
namespace App\Helpers;

class BadgeHelper {
    public static function patientHasGeciciFlag(object $patient): bool
    {
        return !empty($patient->gecici);
    }

    /** Hasta notu (N rozeti) — boş / yalnızca `[]` sayılmaz */
    public static function patientHasNotesFlag(object $patient): bool
    {
        $notes = trim((string) ($patient->notes ?? ''));
        return $notes !== '' && $notes !== '[]' && strlen($notes) > 2;
    }

    /** e-Rapor (E rozeti) */
    public static function patientHasEraporFlag(object $patient): bool
    {
        return !empty($patient->erapor);
    }

    /**
     * Birleşik liste — admin özellik filtresi seçenekleri (BadgeHelper rozetleri ile uyumlu).
     *
     * @return array<string, string>
     */
    public static function patientFeatureFilterChoices(): array
    {
        return [
            '' => 'Tüm özellikler',
            'gecici' => 'Geçici takipliler (G)',
            'notes' => 'Hasta notu olanlar (N)',
            'erapor' => 'e-Rapor hastası (E)',
        ] + PatientClinicalFlagsHelper::listBadgeFilterChoices();
    }

    public static function patientFeatureFilterLabel(string $key): string
    {
        $choices = self::patientFeatureFilterChoices();
        return $choices[$key] ?? $key;
    }

    /**
     * Hastanın özellik badge'lerini (G, N, E) toplu olarak render eder.
     *
     * @param object $patient Hasta nesnesi
     */
    public static function patientFeatures($patient) {
    $badges = "";
    
    if (self::patientHasGeciciFlag($patient)) {
        $badges .= '<span class="badge bg-warning text-dark x-small me-1" title="Geçici Hasta" data-bs-toggle="tooltip">G</span>';
    }
    
    if (self::patientHasNotesFlag($patient)) {
        $badges .= '<span class="badge bg-info x-small me-1" title="Not Mevcut" data-bs-toggle="tooltip">N</span>';
    }
    
    if (self::patientHasEraporFlag($patient)) {
        $badges .= '<span class="badge bg-success x-small me-1" title="E-Raporlu" data-bs-toggle="tooltip">E</span>';
    }

    $badges .= self::patientClinicalListBadgesHtml($patient);

    return $badges;
}

    /** Liste satırı klinik rozetleri (O/V/T/S/A/I). */
    public static function patientClinicalListBadgesHtml(object $patient): string
    {
        $badges = '';
        $defs = [
            'o2bagimli' => ['O', 'Oksijen bağımlı', 'bg-info'],
            'ventilator' => ['V', 'Ventilatör bağımlı', 'bg-info'],
            'trakeostomi' => ['T', 'Trakeostomi', 'bg-info'],
            'sonda' => ['S', 'Mesane sonda', 'bg-secondary'],
            'izolasyon' => ['I', 'İzolasyon / enfeksiyon önlemi', 'bg-warning text-dark'],
        ];
        foreach ($defs as $key => [$letter, $title, $cls]) {
            if (!empty($patient->$key)) {
                $badges .= '<span class="badge ' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8')
                    . ' x-small me-1" title="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
                    . '" data-bs-toggle="tooltip">' . htmlspecialchars($letter, ENT_QUOTES, 'UTF-8') . '</span>';
            }
        }
        if (trim((string) ($patient->alerji ?? '')) !== '') {
            $badges .= '<span class="badge bg-danger x-small me-1" title="Alerji kayıtlı" data-bs-toggle="tooltip">A</span>';
        }

        return $badges;
    }

    /**
     * Genel bir Badge (Etiket) oluşturur
     */
    public static function render($text, $type = 'secondary', $pill = false) {
        $class = $pill ? 'rounded-pill' : '';
        return "<span class='badge bg-{$type} {$class} text-uppercase' style='font-size: 0.75rem;'>{$text}</span>";
    }

    /**
     * Evet / Hayır rozet çifti (hasta detay vb.; truthy = Evet).
     */
    public static function yesNoEvetHayir($val): string {
        if (!empty($val)) {
            return '<span class="badge bg-danger">Evet</span>';
        }
        return '<span class="badge bg-success">Hayır</span>';
    }

    /**
     * Genel sekme — tıklanabilir Evet/Hayır (aktif dosyada).
     */
    public static function yesNoEvetHayirToggleable($val, string $field, string $fieldLabel, bool $editable = false): string
    {
        if (!$editable) {
            return self::yesNoEvetHayir($val);
        }
        $isYes = !empty($val);
        $cls = $isYes ? 'bg-danger' : 'bg-success';
        $text = $isYes ? 'Evet' : 'Hayır';

        return '<button type="button" class="badge ' . $cls . ' border-0 esh-clinical-flag-toggle" style="cursor:pointer;"'
            . ' data-field="' . htmlspecialchars($field, ENT_QUOTES, 'UTF-8') . '"'
            . ' data-value="' . ($isYes ? '1' : '0') . '"'
            . ' data-label="' . htmlspecialchars($fieldLabel, ENT_QUOTES, 'UTF-8') . '"'
            . ' title="Değiştirmek için tıklayın" aria-label="' . htmlspecialchars($fieldLabel . ': ' . $text, ENT_QUOTES, 'UTF-8') . ' — değiştir">'
            . $text . '</button>';
    }

    /**
     * Hesap Aktivasyon Durumu (activated)
     */
    public static function activationStatus($val) {
        if ($val == 1) {
            return self::render('Onaylı', 'success');
        }
        return self::render('Beklemede', 'warning');
    }

    /**
     * Hasta kaydı pasif alanından normalize durum anahtarı (birleşik liste).
     */
    public static function patientPasifKey($p) {
        $v = $p->pasif ?? null;
        $n = is_numeric($v) ? (int) $v : null;
        if ($n === null) {
            return 'unknown';
        }
        switch ($n) {
            case 0:
                return 'active';
            case 1:
                return 'passive';
            case -3:
                return 'waiting';
            case 5:
                return 'deleted';
            case 4:
                return 'araf';
            case -1:
                return 'probable';
            default:
                return 'unknown';
        }
    }

    public static function patientStatusBadgeHtml($p) {
        if (PatientKurumTransfer::isWaitingFromNakil($p)) {
            return '<span class="badge bg-info x-small">Nakil bekleyen</span>';
        }

        $pid = (int) ($p->id ?? 0);
        if ($pid > 0 && PatientNakilRequest::hasPending($pid)) {
            $pending = PatientNakilRequest::findPendingForPatient($pid);
            if ($pending !== null && (string) ($pending->tip ?? '') === \App\Models\HastaNakil::TIP_GERI_NAKIL) {
                return '<span class="badge bg-warning text-dark x-small">Geri nakil talebi</span>';
            }

            return '<span class="badge bg-warning text-dark x-small">Nakil talebi bekliyor</span>';
        }

        $k = self::patientPasifKey($p);
        $map = [
            'active' => ['Aktif', 'success'],
            'passive' => ['Pasif', 'secondary'],
            'waiting' => ['Bekleyen', 'info'],
            'deleted' => ['Silinen', 'dark'],
            'araf' => ['Araf', 'warning'],
            'probable' => ['Muhtemel ölen', 'warning'],
        ];
        if (!isset($map[$k])) {
            return '<span class="badge bg-light text-dark border">?</span>';
        }
        [$text, $cls] = $map[$k];
        return '<span class="badge bg-' . $cls . ' x-small">' . htmlspecialchars($text) . '</span>';
    }

    public static function patientViewUrl($p) {
        $id = (int) $p->id;
        return esh_url('Patient', 'view', ['id' => $id]);
    }

    public static function patientEditUrl($p) {
        $id = (int) $p->id;
        if (PatientKurumTransfer::isWaitingFromNakil($p)) {
            return esh_url('Patient', 'edit', ['id' => $id]);
        }
        $k = self::patientPasifKey($p);
        if ($k === 'waiting') {
            return esh_url('Patient', 'bedit', ['id' => $id]);
        }
        return esh_url('Patient', 'edit', ['id' => $id]);
    }

    /**
     * Randevu takvimi kısayolu (bugün + hasta TC).
     */
    public static function patientAppointmentCalendarUrl(string $tc, string $controller = 'Randevu'): string
    {
        $tcDigits = preg_replace('/\D/', '', $tc);
        $today = new \DateTimeImmutable('today');

        return \App\Helpers\UrlHelper::fromRequestParams([
            'controller' => $controller,
            'action' => 'index',
            'y' => (int) $today->format('Y'),
            'm' => (int) $today->format('n'),
            'date' => $today->format('Y-m-d'),
            'tc' => $tcDigits,
        ]);
    }

    /**
     * Birleşik hasta listesi isim menüsü için dosya durumu açıklaması (kısa).
     */
    public static function patientUnifiedMenuStatusLine($p): string {
        $k = self::patientPasifKey($p);
        $map = [
            'active' => 'Aktif dosya',
            'passive' => 'Pasif dosya (kapalı)',
            'waiting' => 'Bekleyen (ön kayıt)',
            'deleted' => 'Silinen dosya (manuel)',
            'araf' => 'Araf',
            'probable' => 'Muhtemel ölen',
            'unknown' => 'Durum belirsiz',
        ];
        return $map[$k] ?? $map['unknown'];
    }

    /**
     * TC ajax / API yanıtı: hasta dosya durumu (pasif alanı → aktif, pasif, bekleyen, …).
     *
     * @return array{hastaid: int, status_key: string, status_text: string, status_badge: string, view_url: string}
     */
    public static function patientFileStatusForApi($p): array {
        $key = self::patientPasifKey($p);
        $badgeByKey = [
            'active' => 'success',
            'passive' => 'secondary',
            'waiting' => 'info text-dark',
            'deleted' => 'dark',
            'araf' => 'warning text-dark',
            'probable' => 'warning text-dark',
            'unknown' => 'light text-dark border',
        ];

        return [
            'hastaid' => (int) ($p->id ?? 0),
            'status_key' => $key,
            'status_text' => self::patientPublicLookupStatusLabel($p),
            'status_badge' => $badgeByKey[$key] ?? 'light text-dark border',
            'view_url' => self::patientViewUrl($p),
        ];
    }

    /** Girişsiz hasta TC sorgusu sonuç kartı için kısa durum etiketi. */
    public static function patientPublicLookupStatusLabel($p): string {
        $k = self::patientPasifKey($p);
        $map = [
            'active' => 'Aktif',
            'passive' => 'Pasif',
            'waiting' => 'Bekleyen',
            'deleted' => 'Silinen',
            'araf' => 'Araf',
            'probable' => 'Muhtemel ölen',
            'unknown' => 'Belirsiz',
        ];
        return $map[$k] ?? $map['unknown'];
    }

    /** Girişsiz sorgu sonuç kartı CSS sınıfı (`pha-result--…`). */
    public static function patientPublicLookupResultCssMod($p): string {
        $k = self::patientPasifKey($p);
        $map = [
            'active' => 'found',
            'passive' => 'status-passive',
            'waiting' => 'status-waiting',
            'deleted' => 'status-deleted',
            'araf' => 'status-araf',
            'probable' => 'status-probable',
            'unknown' => 'status-unknown',
        ];
        return $map[$k] ?? 'status-unknown';
    }

    /**
     * Birleşik liste isim dropdown menü satırları (dosya durumuna göre).
     *
     * @return list<array<string, mixed>>
     */
    public static function patientUnifiedMenuEntries($p, bool $isAdmin): array {
        $id = (int) ($p->id ?? 0);
        $tcRaw = (string) ($p->tckimlik ?? '');
        $tcQ = rawurlencode($tcRaw);
        $key = self::patientPasifKey($p);

        $out = [];
        $out[] = ['type' => 'status', 'text' => self::patientUnifiedMenuStatusLine($p)];

        if ($key !== 'deleted') {
            $out[] = [
                'type' => 'item',
                'href' => self::patientViewUrl($p),
                'label' => 'Bilgileri göster',
                'icon' => 'fa-solid fa-circle-info text-primary',
            ];
        }

        switch ($key) {
            case 'active':
                $out[] = [
                    'type' => 'item',
                    'href' => self::patientEditUrl($p),
                    'label' => 'Dosyayı düzenle',
                    'icon' => 'fa-solid fa-user-pen text-warning',
                ];
                if ($tcRaw !== '') {
                    $out[] = ['type' => 'divider'];
                    $out[] = [
                        'type' => 'item',
                        'href' => esh_url('Visit', 'create', ['tc' => $tcRaw]),
                        'label' => 'Yeni izlem gir',
                        'icon' => 'fa-solid fa-plus-circle text-success',
                    ];
                    $out[] = [
                        'type' => 'item',
                        'href' => esh_url('PlannedVisit', 'create', ['tc' => $tcRaw]),
                        'label' => 'Yeni izlem planla',
                        'icon' => 'fa-solid fa-calendar-plus text-primary',
                    ];
                    $out[] = ['type' => 'divider'];
                    $out[] = [
                        'type' => 'item',
                        'href' => esh_url('Visit', 'history', ['tc' => $tcRaw]),
                        'label' => 'İzlem geçmişi',
                        'icon' => 'fa-solid fa-list-check text-info',
                    ];
                    $out[] = [
                        'type' => 'item',
                        'href' => esh_url('PlannedVisit', 'patient', ['tc' => $tcRaw]),
                        'label' => 'Planlı izlemler',
                        'icon' => 'fa-solid fa-calendar-week text-warning',
                    ];
                    if ($id > 0) {
                        foreach (self::patientWoundsBarthelMenuItems($p) as $clinicalRow) {
                            $out[] = $clinicalRow;
                        }
                    }
                    $tcDigits = preg_replace('/\D/', '', $tcRaw);
                    if (strlen($tcDigits) === 11) {
                        $eshRandevuLinks = [];
                        if (\App\Helpers\AppSettings::isModuleEnabled('randevu')) {
                            $eshRandevuLinks[] = [
                                'type' => 'item',
                                'href' => self::patientAppointmentCalendarUrl($tcDigits, 'Randevu'),
                                'label' => 'Branş randevusu ekle',
                                'icon' => 'fa-solid fa-calendar-days text-info',
                            ];
                        }
                        if (\App\Helpers\AppSettings::isModuleEnabled('uhds')) {
                            $eshRandevuLinks[] = [
                                'type' => 'item',
                                'href' => self::patientAppointmentCalendarUrl($tcDigits, 'Uhds'),
                                'label' => 'Uhds ekle',
                                'icon' => 'fa-solid fa-video text-info',
                            ];
                        }
                        if ($eshRandevuLinks !== []) {
                            $out[] = ['type' => 'divider'];
                            foreach ($eshRandevuLinks as $eshRandevuRow) {
                                $out[] = $eshRandevuRow;
                            }
                        }
                    }
                }
                break;

            case 'passive':
                if ($tcRaw !== '') {
                    $out[] = ['type' => 'divider'];
                    $out[] = [
                        'type' => 'item',
                        'href' => esh_url('Visit', 'history', ['tc' => $tcRaw]),
                        'label' => 'İzlem geçmişi',
                        'icon' => 'fa-solid fa-list-check text-info',
                    ];
                    $out[] = [
                        'type' => 'item',
                        'href' => esh_url('PlannedVisit', 'patient', ['tc' => $tcRaw]),
                        'label' => 'Planlı izlemler',
                        'icon' => 'fa-solid fa-calendar-week text-warning',
                    ];
                }
                $out[] = ['type' => 'divider'];
                $out[] = [
                    'type' => 'item',
                    'href' => esh_url('Patient', 'passiveToWaiting', ['id' => $id]),
                    'label' => 'Bekleyene al',
                    'icon' => 'fa-solid fa-hourglass-half text-info',
                    'confirm' => 'Bu pasif dosyayı bekleyen (ön kayıt) listesine almak istediğinize emin misiniz?',
                ];
                $out[] = [
                    'type' => 'item',
                    'href' => esh_url('Patient', 'changeactive', ['id' => $id]),
                    'label' => 'Aktif dosyaya al',
                    'icon' => 'fa-solid fa-user-check text-success',
                    'confirm' => 'Bu hastayı tekrar aktif dosyaya almak istiyor musunuz?',
                ];
                break;

            case 'waiting':
                $out[] = [
                    'type' => 'item',
                    'href' => self::patientEditUrl($p),
                    'label' => 'Ön kaydı düzenle',
                    'icon' => 'fa-solid fa-user-pen text-warning',
                ];
                $out[] = [
                    'type' => 'item',
                    'href' => esh_url('Patient', 'firstSave', ['id' => $id]),
                    'label' => 'Hastayı kayda al',
                    'icon' => 'fa-solid fa-user-check text-success',
                ];
                $out[] = [
                    'type' => 'item',
                    'href' => esh_url('Patient', 'waitingForm', ['id' => $id]),
                    'label' => 'Başvuru formu (PDF)',
                    'icon' => 'fa-solid fa-file-pdf text-danger',
                ];
                $out[] = ['type' => 'divider'];
                $out[] = [
                    'type' => 'item',
                    'href' => esh_url('Patient', 'deletewaiting', ['id' => $id]),
                    'label' => 'Bekleyen listeden sil',
                    'icon' => 'fa-solid fa-trash text-danger',
                    'confirm' => 'Bu bekleyen kaydı silmek istediğinize emin misiniz?',
                ];
                break;

            case 'deleted':
                if ($isAdmin) {
                    $out[] = [
                        'type' => 'item',
                        'href' => esh_url('Patient', 'deletedToWaiting', ['id' => $id]),
                        'label' => 'Bekleyene al',
                        'icon' => 'fa-solid fa-hourglass-half text-info',
                        'confirm' => 'Bu kaydı bekleyen (ön kayıt) listesine almak istediğinize emin misiniz?',
                    ];
                }
                break;

            case 'araf':
                $out[] = [
                    'type' => 'item',
                    'href' => self::patientEditUrl($p),
                    'label' => 'Dosyayı düzenle',
                    'icon' => 'fa-solid fa-user-pen text-warning',
                ];
                if ($isAdmin) {
                    $out[] = ['type' => 'divider'];
                    $out[] = [
                        'type' => 'item',
                        'href' => esh_url('Patient', 'deletedied', ['id' => $id]),
                        'label' => 'Pasife al',
                        'icon' => 'fa-solid fa-user-slash text-secondary',
                        'confirm' => 'Bu araftaki kaydı pasif dosyaya almak istediğinize emin misiniz?',
                    ];
                }
                break;
            case 'probable':
                $out[] = [
                    'type' => 'item',
                    'href' => self::patientEditUrl($p),
                    'label' => 'Dosyayı düzenle',
                    'icon' => 'fa-solid fa-user-pen text-warning',
                ];
                if ($isAdmin) {
                    $out[] = ['type' => 'divider'];
                    $out[] = [
                        'type' => 'item',
                        'href' => esh_url('Patient', 'deletedied', ['id' => $id]),
                        'label' => 'Pasife al',
                        'icon' => 'fa-solid fa-user-slash text-secondary',
                        'confirm' => 'Bu muhtemel ölen kaydı pasif dosyaya almak istediğinize emin misiniz?',
                    ];
                }
                break;

            default:
                $out[] = [
                    'type' => 'item',
                    'href' => self::patientEditUrl($p),
                    'label' => 'Dosyayı düzenle',
                    'icon' => 'fa-solid fa-user-pen text-warning',
                ];
                break;
        }

        if (\App\Helpers\AuthHelper::sessionIsSuperAdmin() && $id > 0) {
            foreach (self::patientSuperadminManagementMenuEntries($id) as $adminRow) {
                $out[] = $adminRow;
            }
        }

        return $out;
    }

    /**
     * Süper yönetici — hasta mega menü «Hasta yönetimi» sütunu.
     *
     * @return list<array<string, mixed>>
     */
    public static function patientSuperadminManagementMenuEntries(int $patientId): array
    {
        if (!\App\Helpers\AuthHelper::sessionIsSuperAdmin() || $patientId <= 0 || !\App\Models\Kurum::tableExists()) {
            return [];
        }

        return [
            ['type' => 'header', 'text' => 'HASTA YÖNETİMİ'],
            [
                'type' => 'item',
                'href' => esh_url('Patient', 'changeKurum', ['id' => $patientId]),
                'label' => 'Kurum değiştir',
                'icon' => 'fa-solid fa-building text-dark',
            ],
        ];
    }

    /**
     * Yara fotoğrafları ve Barthel indeksi menü satırları (aktif hasta menüleri).
     *
     * @return list<array<string, mixed>>
     */
    public static function patientWoundsBarthelMenuItems(object $patient): array {
        $id = (int) ($patient->id ?? 0);
        if ($id <= 0) {
            return [];
        }

        $out = [];
        if (\App\Helpers\PatientClinicalFlagsHelper::isWoundPhotosModuleEnabled($patient)) {
            $out[] = [
                'type' => 'item',
                'href' => esh_url('Patient', 'wounds', ['id' => $id]),
                'label' => 'Yara fotoğrafları',
                'icon' => 'fa-solid fa-camera text-danger',
            ];
        }

        $out[] = [
            'type' => 'item',
            'href' => esh_url('Patient', 'barthel', ['id' => $id]),
            'label' => 'Barthel indeksi',
            'icon' => 'fa-solid fa-chart-line text-primary',
        ];

        return $out;
    }

    /**
     * Pasif hasta bekleyen plan listesi — hasta dosyası + plan sil (yönetici).
     *
     * @return list<array<string, mixed>>
     */
    public static function plannedVisitPassivePendingMenuEntries(object $planRow, string $planDeleteRetq): array
    {
        $planId = (int) ($planRow->id ?? 0);
        $out = [];

        $tcRaw = (string) ($planRow->hastatckimlik ?? '');
        $out[] = ['type' => 'header', 'text' => 'PLAN İŞLEMLERİ'];
        $out[] = [
            'type' => 'item',
            'href' => esh_url('PlannedVisit', 'edit', ['id' => $planId, 'tc' => $tcRaw]),
            'label' => 'Planı düzenle',
            'icon' => 'fa-solid fa-pen-to-square text-primary',
        ];
        $out[] = [
            'type' => 'item',
            'href' => esh_url('PlannedVisit', 'delete', ['id' => $planId, 'tc' => $tcRaw, 'return' => 'passivePending', 'retq' => $planDeleteRetq]),
            'label' => 'Planı sil',
            'icon' => 'fa-solid fa-trash text-danger',
            'danger' => true,
            'confirm' => 'Bu planlı izlem kaydını kalıcı olarak silmek istediğinize emin misiniz?',
        ];

        $out[] = ['type' => 'divider'];

        $patientStub = (object) [
            'id' => (int) ($planRow->hid ?? 0),
            'tckimlik' => $tcRaw,
            'isim' => (string) ($planRow->isim ?? ''),
            'soyisim' => (string) ($planRow->soyisim ?? ''),
            'cinsiyet' => (string) ($planRow->cinsiyet ?? 'E'),
            'pasif' => (int) ($planRow->pasif ?? 1),
        ];
        $patientRows = self::patientUnifiedMenuEntries($patientStub, true);
        $out[] = ['type' => 'header', 'text' => 'HASTA İŞLEMLERİ'];
        foreach ($patientRows as $row) {
            $out[] = $row;
        }

        return $out;
    }

    /**
     * Planlı izlem genel listesi — hasta adı dropdown (plan satırı + hasta işlemleri).
     *
     * @return list<array<string, mixed>>
     */
    public static function plannedVisitIndexMenuEntries(object $planRow, bool $isAdmin, string $planDeleteRetq): array {
        $tcRaw = (string) ($planRow->hastatckimlik ?? '');
        $planId = (int) ($planRow->id ?? 0);
        $out = [];

        $out[] = ['type' => 'header', 'text' => 'PLAN İŞLEMLERİ'];
        if ((int) ($planRow->durum ?? 0) === 0) {
            $out[] = [
                'type' => 'item',
                'href' => esh_url('Visit', 'create', ['tc' => $tcRaw, 'plan_id' => $planId]),
                'label' => 'Gerçekleşen izlem kaydı',
                'icon' => 'fa-solid fa-check text-success',
            ];
        }
        if ($isAdmin) {
            $out[] = [
                'type' => 'item',
                'href' => esh_url('PlannedVisit', 'edit', ['id' => $planId, 'tc' => $tcRaw]),
                'label' => 'Planı düzenle',
                'icon' => 'fa-solid fa-pen-to-square text-primary',
            ];
            $out[] = [
                'type' => 'item',
                'href' => esh_url('PlannedVisit', 'delete', ['id' => $planId, 'return' => 'index', 'retq' => $planDeleteRetq]),
                'label' => 'Planı sil',
                'icon' => 'fa-solid fa-trash text-danger',
                'danger' => true,
                'confirm' => 'Bu planlı izlem kaydını kalıcı olarak silmek istediğinize emin misiniz?',
            ];
        }

        $out[] = ['type' => 'divider'];

        $patientStub = (object) [
            'id' => (int) ($planRow->hid ?? 0),
            'tckimlik' => (string) ($planRow->hastatckimlik ?? ''),
            'isim' => (string) ($planRow->isim ?? ''),
            'soyisim' => (string) ($planRow->soyisim ?? ''),
            'cinsiyet' => (string) ($planRow->cinsiyet ?? 'E'),
            'pasif' => 0,
        ];
        $patientRows = self::patientUnifiedMenuEntries($patientStub, $isAdmin);
        $out[] = ['type' => 'header', 'text' => 'HASTA İŞLEMLERİ'];
        foreach ($patientRows as $row) {
            $out[] = $row;
        }

        return $out;
    }

    /**
     * Tek hastanın planlı izlem sayfası — hasta adı mega menü (plan düzenle + hasta işlemleri).
     *
     * @param list<object> $plans Mevcut sayfadaki plan satırları
     * @return list<array<string, mixed>>
     */
    public static function plannedVisitPatientPageMenuEntries(object $patient, array $plans, bool $isAdmin): array
    {
        $tcRaw = (string) ($patient->tckimlik ?? '');
        $out = [];
        $out[] = ['type' => 'status', 'text' => self::patientUnifiedMenuStatusLine($patient)];

        if ($isAdmin && $plans !== []) {
            $out[] = ['type' => 'header', 'text' => 'PLAN İŞLEMLERİ'];
            $planCount = 0;
            foreach ($plans as $plan) {
                $planId = (int) ($plan->id ?? 0);
                if ($planId < 1) {
                    continue;
                }
                ++$planCount;
                $label = 'Planlı izlem düzenle';
                if (count($plans) > 1) {
                    $dateLabel = !empty($plan->planlanantarih)
                        ? DateHelper::toTrDotOrEmpty($plan->planlanantarih)
                        : '';
                    $zamanData = \App\Helpers\ZamanDilimiHelper::badgeFor($plan->zaman ?? null);
                    $zamanText = trim((string) ($zamanData['text'] ?? ''));
                    $suffix = trim($dateLabel . ($zamanText !== '' ? ' · ' . $zamanText : ''));
                    if ($suffix !== '') {
                        $label .= ' (' . $suffix . ')';
                    }
                }
                $out[] = [
                    'type' => 'item',
                    'href' => esh_url('PlannedVisit', 'edit', ['id' => $planId, 'tc' => $tcRaw]),
                    'label' => $label,
                    'icon' => 'fa-solid fa-pen-to-square text-primary',
                ];
            }
            if ($planCount > 0) {
                $out[] = ['type' => 'divider'];
            }
        }

        $patientRows = self::patientUnifiedMenuEntries($patient, $isAdmin);
        foreach ($patientRows as $row) {
            if (($row['type'] ?? '') === 'status') {
                continue;
            }
            $out[] = $row;
        }

        return $out;
    }

    /**
     * Aktif izlem genel listesi — hasta adı dropdown (izlem satırı + hasta işlemleri).
     *
     * @return list<array<string, mixed>>
     */
    public static function visitIndexMenuEntries(object $visitRow, bool $isAdmin): array {
        $visitId = (int) ($visitRow->id ?? 0);
        $tcQ = rawurlencode((string) ($visitRow->hastatckimlik ?? ''));
        $out = [];

        $out[] = ['type' => 'header', 'text' => 'İZLEM İŞLEMLERİ'];
        $out[] = [
            'type' => 'item',
            'href' => esh_url('Visit', 'edit', ['id' => $visitId]),
            'label' => 'İzlemi düzenle',
            'icon' => 'fa-solid fa-pen text-primary',
        ];
        if (VisitIslemHelper::yapilanCsvContainsIslem(
            (string) ($visitRow->yapilan ?? ''),
            VisitIslemHelper::konsultasyonIslemId()
        )) {
            $out[] = [
                'type' => 'item',
                'href' => esh_url('Visit', 'ek3Consult', ['id' => $visitId, 'tc' => (string) ($visitRow->hastatckimlik ?? '')]),
                'label' => 'EK-3 Formu çıkart',
                'icon' => 'fa-solid fa-file-lines text-info',
            ];
        }
        if ($isAdmin) {
            $out[] = [
                'type' => 'item',
                'href' => esh_url('Visit', 'delete', ['id' => $visitId, 'tc' => (string) ($visitRow->hastatckimlik ?? ''), 'return' => 'index']),
                'label' => 'İzlemi sil',
                'icon' => 'fa-solid fa-trash text-danger',
                'danger' => true,
                'confirm' => 'Bu izlem kaydını kalıcı olarak silmek istediğinize emin misiniz?',
            ];
        }

        $out[] = ['type' => 'divider'];

        $patientStub = (object) [
            'id' => (int) ($visitRow->hid ?? 0),
            'tckimlik' => (string) ($visitRow->hastatckimlik ?? ''),
            'isim' => (string) ($visitRow->isim ?? ''),
            'soyisim' => (string) ($visitRow->soyisim ?? ''),
            'cinsiyet' => (string) ($visitRow->cinsiyet ?? 'E'),
            'pasif' => 0,
        ];
        $patientRows = self::patientUnifiedMenuEntries($patientStub, $isAdmin);
        $out[] = ['type' => 'header', 'text' => 'HASTA İŞLEMLERİ'];
        foreach ($patientRows as $row) {
            $out[] = $row;
        }

        return $out;
    }

    /**
     * Hasta kartı — parçalı düzenleme dropdown öğeleri (modal tetikleyici).
     *
     * @return list<array{key: string, label: string, icon: string}>
     */
    public static function patientDetailEditSectionMenuItems(): array
    {
        return [
            ['key' => 'kimlik_iletisim', 'label' => 'Kimlik ve İletişim Bilgisi', 'icon' => 'fa-solid fa-id-card text-primary'],
            ['key' => 'dosya_secenekleri', 'label' => 'Dosya Seçenekleri', 'icon' => 'fa-solid fa-folder-open text-dark'],
            ['key' => 'fiziksel_olcumler', 'label' => 'Fiziksel Ölçümler', 'icon' => 'fa-solid fa-weight-scale text-info'],
            ['key' => 'adres', 'label' => 'Adres Bilgisi', 'icon' => 'fa-solid fa-map-location-dot text-success'],
            ['key' => 'klinik_tanilar', 'label' => 'Klinik Tanılar', 'icon' => 'fa-solid fa-notes-medical text-primary'],
            ['key' => 'klinik_uyarilar', 'label' => 'Klinik Uyarılar', 'icon' => 'fa-solid fa-triangle-exclamation text-danger'],
            ['key' => 'tibbi_cihaz', 'label' => 'Tıbbi Cihaz ve Destek', 'icon' => 'fa-solid fa-microchip text-danger'],
            ['key' => 'bakim_sarf', 'label' => 'Bakım ve Sarf Malzeme', 'icon' => 'fa-solid fa-box-open text-warning'],
            ['key' => 'dosya_durumu', 'label' => 'Dosya Durumu', 'icon' => 'fa-solid fa-file-circle-exclamation text-danger'],
        ];
    }

    /**
     * Hasta kartı — Tıbbi İşlemler menü satırları (mega menü sütunlarına ayrılır).
     *
     * @return list<array<string, mixed>>
     */
    public static function patientDetailTibbiMenuRows(object $hasta): array {
        $aktif = \App\Models\Patient::isAktif($hasta->pasif ?? null);
        $tcQ = rawurlencode((string) ($hasta->tckimlik ?? ''));
        $tcRaw = (string) ($hasta->tckimlik ?? '');
        $id = (int) ($hasta->id ?? 0);
        $out = [];

        if ($aktif) {
            $out[] = [
                'type' => 'item',
                'href' => esh_url('Visit', 'create', ['tc' => $tcRaw]),
                'label' => 'İzlem gir',
                'icon' => 'fa-solid fa-stethoscope text-primary',
            ];
            $out[] = [
                'type' => 'item',
                'href' => esh_url('PlannedVisit', 'create', ['tc' => $tcRaw]),
                'label' => 'İzlem planla',
                'icon' => 'fa-solid fa-calendar-plus text-success',
            ];
            $out[] = [
                'type' => 'item',
                'href' => esh_url('PlannedVisit', 'patient', ['tc' => $tcRaw]),
                'label' => 'Planlı izlemler',
                'icon' => 'fa-solid fa-calendar-week text-warning',
            ];
            if ($id > 0) {
                foreach (self::patientWoundsBarthelMenuItems($hasta) as $clinicalRow) {
                    $out[] = $clinicalRow;
                }
            }
        }
        $out[] = [
            'type' => 'item',
            'href' => esh_url('Visit', 'history', ['tc' => $tcRaw]),
            'label' => 'İzlem geçmişi',
            'icon' => 'fa-solid fa-list-check text-info',
        ];
        if (\App\Helpers\AppSettings::isModuleEnabled('hasta_ilac_rapor') && $id > 0) {
            $out[] = [
                'type' => 'item',
                'href' => esh_url('HastaIlacRapor', 'index', ['id' => $id]),
                'label' => 'İlaç / tanı raporu',
                'icon' => 'fa-solid fa-pills text-secondary',
            ];
        }
        if (
            $aktif
            && $id > 0
            && \App\Helpers\AppSettings::isModuleEnabled('stok')
            && \App\Services\Stok\StokService::moduleReady()
            && (\App\Helpers\AuthHelper::can('stok.create') || \App\Helpers\AuthHelper::can('stok.admin'))
        ) {
            $out[] = [
                'type' => 'item',
                'href' => esh_url('Stok', 'cikis', ['hasta_id' => $id]),
                'label' => 'Stok çıkışı',
                'icon' => 'fa-solid fa-boxes-stacked text-warning',
            ];
        }
        if (
            $id > 0
            && \App\Helpers\AppSettings::isModuleEnabled('stok')
            && \App\Services\Stok\StokService::moduleReady()
            && (\App\Helpers\AuthHelper::can('stok.read') || \App\Helpers\AuthHelper::can('stok.create') || \App\Helpers\AuthHelper::can('stok.admin'))
        ) {
            $out[] = [
                'type' => 'item',
                'href' => esh_url('Stok', 'hastaOzet', ['hasta_id' => $id]),
                'label' => 'Stok tüketim geçmişi',
                'icon' => 'fa-solid fa-clock-rotate-left text-secondary',
            ];
        }
        if ($aktif) {
            $tcDigits = preg_replace('/\D/', '', (string) ($hasta->tckimlik ?? ''));
            if (strlen($tcDigits) === 11) {
                if (\App\Helpers\AppSettings::isModuleEnabled('randevu')) {
                    $out[] = [
                        'type' => 'item',
                        'href' => self::patientAppointmentCalendarUrl($tcDigits, 'Randevu'),
                        'label' => 'Branş randevusu ekle',
                        'icon' => 'fa-solid fa-calendar-days text-info',
                    ];
                }
                if (\App\Helpers\AppSettings::isModuleEnabled('uhds')) {
                    $out[] = [
                        'type' => 'item',
                        'href' => self::patientAppointmentCalendarUrl($tcDigits, 'Uhds'),
                        'label' => 'Uhds ekle',
                        'icon' => 'fa-solid fa-video text-info',
                    ];
                }
            }
        }

        return $out;
    }

    /**
     * Düz menü satırlarını iki sütunlu mega menü yapısına ayırır.
     *
     * @param list<array<string, mixed>> $rows
     * @return array{status: ?string, visitTitle: string, patientTitle: string, visit: list<array<string, mixed>>, patient: list<array<string, mixed>>, adminTitle: string, admin: list<array<string, mixed>>}
     */
    public static function menuRowsToMegaColumns(array $rows): array {
        $status = null;
        $visit = [];
        $patient = [];
        $admin = [];
        $visitTitle = 'İzlem işlemleri';
        $patientTitle = 'Hasta işlemleri';
        $adminTitle = 'Hasta yönetimi';
        $section = null;

        foreach ($rows as $row) {
            $type = (string) ($row['type'] ?? '');
            if ($type === 'status') {
                $status = (string) ($row['text'] ?? '');
                continue;
            }
            if ($type === 'header') {
                $text = mb_strtoupper(trim((string) ($row['text'] ?? '')), 'UTF-8');
                if (str_contains($text, 'PLAN')) {
                    $visitTitle = 'Plan işlemleri';
                    $section = 'visit';
                } elseif (str_contains($text, 'İZLEM') || str_contains($text, 'IZLEM')) {
                    $visitTitle = 'İzlem işlemleri';
                    $section = 'visit';
                } elseif (str_contains($text, 'YÖNET') || str_contains($text, 'YNET')) {
                    $section = 'admin';
                } elseif (str_contains($text, 'HASTA')) {
                    $section = 'patient';
                }
                continue;
            }
            if ($type === 'divider') {
                continue;
            }
            if ($type !== 'item') {
                continue;
            }
            // İzlem/plan linkleri her zaman sol sütunda (HASTA İŞLEMLERİ başlığı altında birleşik menüden gelse bile)
            if (self::menuRowIsVisitColumn($row)) {
                $visit[] = $row;
            } elseif ($section === 'visit') {
                $visit[] = $row;
            } elseif ($section === 'admin') {
                $admin[] = $row;
            } else {
                $patient[] = $row;
            }
        }

        return [
            'status' => $status,
            'visitTitle' => $visitTitle,
            'patientTitle' => $patientTitle,
            'adminTitle' => $adminTitle,
            'visit' => $visit,
            'patient' => $patient,
            'admin' => $admin,
        ];
    }

    /** @param array<string, mixed> $row */
    private static function menuRowIsVisitColumn(array $row): bool {
        $href = (string) ($row['href'] ?? '');
        if ($href === '' || $href === '#') {
            return false;
        }
        if (preg_match('/controller=(Visit|PlannedVisit)(?:&|$)/i', $href)) {
            return true;
        }

        return (bool) preg_match('#/(Visit|PlannedVisit)(?:/|\?|$)#', $href);
    }
}
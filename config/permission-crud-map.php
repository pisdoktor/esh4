<?php
declare(strict_types=1);

/**
 * Modül bazlı CRUD → controller action eşlemesi.
 * İzin slug: {module_key}.{crud}
 *
 * admin_bypass: isadmin=1 yöneticiler bu modülde tam CRUD bypass alır.
 * rbac: false ise personel için izin kontrolü uygulanmaz (her zaman izinli).
 */
return [
    'dashboard' => [
        'label' => 'Ana panel',
        'admin_bypass' => true,
        'rbac' => true,
        'crud' => [
            'read' => [
                'index', 'admin', 'calendarMonth', 'getDailyEvents', 'getTomTomMatrixData',
                'planla', 'showRoute', 'tcLookupAjax', 'tomtomGeocodeAjax', 'tomtomMapKeyAjax',
                'tomtomRouteAjax', 'dailyPlanMernisScan',
            ],
        ],
    ],
    'patient' => [
        'label' => 'Hasta yönetimi',
        'admin_bypass' => true,
        'rbac' => true,
        'crud' => [
            'read' => [
                'unified', 'unifiedRows', 'bview', 'listactive', 'listwaiting', 'listpassive', 'listaraf',
                'checkTC', 'scan', 'scanWaiting', 'barthel', 'prepareNotes', 'unifiedPdfData',
                'resolvePatientCoords', 'randevu_yogunluk_kontrol', 'getSubAddresses',
                'view', 'waitingForm', 'wounds',
            ],
            'create' => ['ilkkayit', 'firstSave', 'fsave', 'store'],
            'update' => [
                'edit', 'bedit', 'saveBarthel', 'changeactive', 'passiveToWaiting',
                'deletedToWaiting', 'saveKapino', 'saveSokak',
                'updateNotes', 'uploadPatientPhoto', 'uploadWoundPhoto',
                'toggleClinicalFlag',
            ],
            'delete' => [
                'deletewaiting', 'deletedied', 'deleteNote', 'deletePatientPhoto',
                'deleteWoundPhoto', 'died',
            ],
            'admin' => [
                'listdeleted', 'listdied', 'listall', 'bulkDiedScan', 'changeKurum',
                'storeKurum',
            ],
        ],
    ],
    'visit' => [
        'label' => 'Ev ziyareti (izlem)',
        'admin_bypass' => true,
        'rbac' => true,
        'crud' => [
            'read' => [
                'index', 'history', 'historyRows', 'missed', 'missedRows', 'ek3Consult', 'ek3Document', 'indexPdfData',
                'indexRows', 'checkVisitSameDay',
            ],
            'create' => ['create', 'store'],
            'update' => ['edit', 'update', 'ek3SavePrint'],
            'delete' => ['delete'],
        ],
    ],
    'planned_visit' => [
        'label' => 'Planlanan ziyaret',
        'admin_bypass' => true,
        'rbac' => true,
        'crud' => [
            'read' => [
                'index', 'indexRows', 'patient', 'patientRows', 'passivePendingPlans', 'passivePendingPlansRows',
                'indexPdfData', 'checkPlanSameSlot', 'plan_yogunluk_kontrol',
            ],
            'create' => ['create', 'store'],
            'update' => ['edit', 'update'],
            'delete' => [
                'delete', 'deletePassivePendingBulk', 'deletePassivePendingAfterBulk',
                'markPassivePendingMissedBulk',
            ],
        ],
    ],
    'pansuman' => [
        'label' => 'Pansuman planı',
        'admin_bypass' => true,
        'rbac' => true,
        'crud' => [
            'read' => ['index', 'indexRows'],
            'update' => ['saveDays'],
        ],
    ],
    'planning' => [
        'label' => 'Günlük planlama',
        'admin_bypass' => true,
        'rbac' => true,
        'crud' => [
            'read' => ['index', 'indexRows', 'table'],
            'update' => ['save'],
        ],
    ],
    'stats' => [
        'label' => 'İstatistikler',
        'admin_bypass' => true,
        'rbac' => true,
        'crud' => [
            'read' => [
                'index', 'overview', 'charts', 'adresFilterOptions', 'adresPatientFilter',
                'adresPatientFilterRows', 'ageGenderBands', 'ageSummary', 'anthroCoverage',
                'ayMovement', 'aylikTekIzlemliler', 'aylikTekIzlemlilerRows', 'bagimlilikDist',
                'barthel', 'birIzlemliler', 'birIzlemlilerRows', 'birthdays', 'bmiVki',
                'chronologyIssues', 'clinicalProfile', 'dataHealth', 'dataHealthContent',
                'dataHealthPatients', 'dataHealthPatientsRows', 'demographicCompleteness',
                'eraporHastaUyum', 'eraporHastaUyumContent', 'eraporHastaUyumList',
                'eraporHastaUyumListRows', 'eraporHastaUyumMetrics', 'eraporList',
                'eraporListRows', 'exitReasons', 'fieldCoverage', 'fieldCoveragePatients',
                'fieldCoveragePatientsRows', 'followKpi', 'geoDistribution', 'guvenceAgeBands',
                'guvenceDist', 'hastalik', 'hastalikCountDist', 'hastalikPatients',
                'kayitKohortAge', 'kayitMonths', 'kayitTenure', 'monthlyFollowFreq',
                'monthlyPool', 'operationsPulse', 'pansumanProfile', 'passiveReasons',
                'patientStatus', 'plannedVisitStats', 'randevuKayitGap', 'randevuTakvim',
                'regionalPerformance', 'sondaChanges', 'sondaChangesRows', 'specialDevices',
                'specialDevicesRows', 'supplyReports', 'supplyReportsRows', 'topVisits',
                'visitConsultationMonthly', 'visitPersonnel', 'visitProcedures', 'visitStats',
                'waitingPoolProfile', 'workload', 'workloadRows', 'yearlyFollow',
                'xTab_ageMonthVisited', 'xTab_bagimlilikAge', 'xTab_bagimlilikVisitYear',
                'xTab_barthelAge', 'xTab_bmiAge', 'xTab_bmiBagimlilik', 'xTab_branchMonthKons',
                'xTab_branchZamanKons', 'xTab_deviceCountAge', 'xTab_exitMonthIlce',
                'xTab_exitReasonTenure', 'xTab_exitReasonYear', 'xTab_guvenceBagimlilik',
                'xTab_guvenceVisitGap', 'xTab_hastalikCountAge', 'xTab_ilceAge',
                'xTab_ilcePlanStatus', 'xTab_ilceVisitDone', 'xTab_kayitYearAge',
                'xTab_monthAttendKons', 'xTab_monthPlanPriority', 'xTab_monthPlanStatus',
                'xTab_monthPlanZaman', 'xTab_monthVisitDone', 'xTab_monthVisitZaman',
                'xTab_pansumanVisitGap', 'xTab_personnelMonth', 'xTab_procedureMonth',
                'xTab_tenureVisitCount', 'xTab_vehicleMonth',
            ],
            'export' => ['reportPdfData'],
        ],
    ],
    'user' => [
        'label' => 'Kullanıcı ve profil',
        'admin_bypass' => true,
        'rbac' => true,
        'crud' => [
            'read' => ['index', 'edit', 'stats', 'statsDetail', 'profileStatsContent', 'image'],
            'update' => ['update', 'upload', 'cropsave', 'removephoto'],
            'admin' => [
                'list', 'listRows', 'create', 'adminEdit', 'store', 'storeKurum',
                'delete', 'changeKurum',
            ],
        ],
    ],
    'erapor' => [
        'label' => 'e-Rapor',
        'admin_bypass' => true,
        'rbac' => true,
        'crud' => [
            'read' => [
                'index', 'view', 'indexRows', 'indexPdfData', 'tcLookupAjax', 'tcGroupRows',
            ],
            'create' => ['create', 'store'],
            'update' => ['edit', 'markAsProcessed'],
            'delete' => ['delete'],
        ],
    ],
    'randevu' => [
        'label' => 'Branş randevu takvimi',
        'admin_bypass' => true,
        'rbac' => true,
        'crud' => [
            'read' => ['index', 'bransKota', 'patientSearch'],
            'create' => ['store'],
            'update' => ['updateGeldi'],
            'delete' => ['delete'],
        ],
    ],
    'uhds' => [
        'label' => 'Uhds',
        'admin_bypass' => true,
        'rbac' => true,
        'crud' => [
            'read' => ['index', 'patientSearch'],
            'create' => ['store'],
            'update' => ['updateGeldi'],
            'delete' => ['delete'],
        ],
    ],
    'hasta_ilac_rapor' => [
        'label' => 'İlaç / tanı raporu',
        'admin_bypass' => true,
        'rbac' => true,
        'crud' => [
            'read' => ['index'],
            'create' => ['store', 'storeIlac'],
            'update' => ['updateIlac'],
            'delete' => ['delete', 'deleteIlac'],
        ],
    ],
    'ilac_rehber' => [
        'label' => 'İlaç rehberi (etken)',
        'admin_bypass' => true,
        'rbac' => true,
        'crud' => [
            'read' => ['search', 'etken', 'etkenAjax', 'ilacAjax', 'index'],
            'admin' => ['about', 'statsAjax'],
            'superadmin' => ['migration', 'scrapeStart', 'scrapeStatus', 'scrapeCancel'],
        ],
    ],
    'mesajlasma' => [
        'label' => 'Mesajlaşma',
        'admin_bypass' => true,
        'rbac' => true,
        'crud' => [
            'read' => [
                'index', 'conversation', 'poll', 'unreadCount',
                'inboxRows', 'thread', 'threadRows', 'sent', 'trash',
                'patientThread', 'pollThread', 'compose', 'usersForDm',
            ],
            'create' => ['send', 'startConversation', 'startDm'],
            'update' => ['markRead', 'restore', 'moveToTrash'],
            'delete' => ['purge'],
            'admin' => ['broadcast', 'broadcastSend'],
        ],
    ],
    'sms_bildirim' => [
        'label' => 'SMS bildirimleri',
        'admin_bypass' => true,
        'rbac' => true,
        'crud' => [
            'admin' => [
                'index',
                'history',
                'historyDetail',
                'compose',
                'quickFromPatient',
                'send',
                'previewRecipients',
                'saveTemplate',
                'templates',
                'testConnection',
            ],
        ],
    ],
    'stok' => [
        'label' => 'Stok takibi',
        'admin_bypass' => true,
        'rbac' => true,
        'crud' => [
            'read' => [
                'index', 'indexRows', 'hareketler', 'hareketlerRows', 'kritikStok', 'kritikStokRows',
                'hastaOzet', 'hastaOzetRows', 'hareketlerExportData',
            ],
            'create' => [
                'cikis', 'cikisStore', 'iade', 'iadeStore', 'hastaLookupAjax',
            ],
            'admin' => [
                'malzemeList', 'malzemeListRows', 'malzemeCreate', 'malzemeEdit',
                'malzemeStore', 'malzemeDelete', 'giris', 'girisStore',
                'sayim', 'sayimStore', 'siparisOneri', 'siparisExportData', 'indexExportData',
            ],
        ],
    ],
    'archive' => [
        'label' => 'Arşiv',
        'admin_bypass' => true,
        'rbac' => true,
        'crud' => [
            'read' => ['index', 'view', 'search'],
            'update' => ['restore'],
            'delete' => ['purge'],
        ],
    ],
    'ekip' => [
        'label' => 'Ekip planlama',
        'admin_bypass' => true,
        'rbac' => true,
        'crud' => [
            'read' => ['index', 'calendar', 'calendarEvents'],
            'create' => ['store'],
            'update' => ['update'],
            'delete' => ['delete'],
        ],
    ],
    'nobet' => [
        'label' => 'Nöbet',
        'admin_bypass' => true,
        'rbac' => true,
        'crud' => [
            'read' => ['index', 'mine', 'calendar'],
            'create' => ['store'],
            'update' => ['update'],
            'delete' => ['delete'],
        ],
    ],
];

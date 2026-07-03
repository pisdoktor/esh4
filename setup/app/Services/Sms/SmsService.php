<?php
declare(strict_types=1);

namespace App\Services\Sms;

use App\Helpers\AppSettings;
use App\Helpers\SmsSettings;
use App\Models\SmsAlici;
use App\Models\SmsGonderim;
use App\Models\SmsSablon;
use App\Services\Sms\Contracts\SmsProviderInterface;

final class SmsService
{
    private SmsSegmentService $segments;
    private SmsRecipientResolver $resolver;
    private SmsGonderim $gonderimModel;
    private SmsAlici $aliciModel;
    private SmsSablon $sablonModel;

    public function __construct()
    {
        $this->segments = new SmsSegmentService();
        $this->resolver = new SmsRecipientResolver();
        $this->gonderimModel = new SmsGonderim();
        $this->aliciModel = new SmsAlici();
        $this->sablonModel = new SmsSablon();
    }

    public static function moduleReady(): bool
    {
        return SmsSablon::tableReady()
            && SmsGonderim::tableReady()
            && SmsAlici::tableReady();
    }

    public static function canUseSms(int $userId): bool
    {
        if ($userId <= 0 || !AppSettings::isModuleEnabled('sms_bildirim') || !self::moduleReady()) {
            return false;
        }

        return \App\Helpers\AuthHelper::sessionIsAdmin();
    }

    /** SMS gönderimi için sağlayıcı / kimlik bilgileri hazır mı (test modu dahil). */
    public static function isSendConfigured(): bool
    {
        if (!AppSettings::isModuleEnabled('sms_bildirim') || !self::moduleReady()) {
            return false;
        }

        return SmsProviderFactory::isReady();
    }

    /**
     * @param array<string, mixed> $segmentParams
     * @param list<string> $roles
     * @return array{ok:bool,mesaj?:string,rows?:list<array<string,mixed>>,stats?:array{toplam:int,gonderecek:int,atlanacak:int}}
     */
    public function preview(string $segment, array $segmentParams, string $bodyTemplate, array $roles): array
    {
        if (!self::moduleReady()) {
            return ['ok' => false, 'mesaj' => 'SMS tabloları kurulu değil.'];
        }
        $bodyTemplate = trim($bodyTemplate);
        if ($bodyTemplate === '') {
            return ['ok' => false, 'mesaj' => 'Mesaj metni boş.'];
        }
        if ($roles === []) {
            $roles = SmsSettings::defaultRoles();
        }
        $patients = $this->segments->resolvePatients($segment, $segmentParams);
        $rows = [];
        foreach ($patients as $entry) {
            $resolved = $this->resolver->resolveForPatient(
                $entry['hasta'],
                $roles,
                $bodyTemplate,
                $entry['meta']
            );
            foreach ($resolved as $r) {
                $rows[] = $r;
            }
        }
        $rows = $this->resolver->dedupeByPhone($rows);
        $gonderecek = 0;
        $atlanacak = 0;
        foreach ($rows as $r) {
            if (($r['skip_reason'] ?? '') === '' && ($r['telefon_norm'] ?? '') !== '') {
                ++$gonderecek;
            } else {
                ++$atlanacak;
            }
        }

        return [
            'ok' => true,
            'rows' => $rows,
            'stats' => ['toplam' => count($rows), 'gonderecek' => $gonderecek, 'atlanacak' => $atlanacak],
        ];
    }

    /**
     * @param array<string, mixed> $segmentParams
     * @param list<string> $roles
     * @return array{ok:bool,mesaj?:string,gonderim_id?:int,stats?:array{toplam:int,basarili:int,basarisiz:int}}
     */
    public function sendBatch(
        int $kurumId,
        int $userId,
        string $segment,
        array $segmentParams,
        string $bodyTemplate,
        array $roles,
        ?int $sablonId = null
    ): array {
        if ($kurumId <= 0 || $userId <= 0) {
            return ['ok' => false, 'mesaj' => 'Geçersiz oturum veya kurum.'];
        }
        if (!SmsProviderFactory::isReady()) {
            return ['ok' => false, 'mesaj' => 'SMS sağlayıcısı yapılandırılmamış.'];
        }
        $sentToday = $this->gonderimModel->countSentToday($kurumId);
        $limit = SmsSettings::dailyLimit();
        $preview = $this->preview($segment, $segmentParams, $bodyTemplate, $roles);
        if (!$preview['ok']) {
            return $preview;
        }
        $rows = $preview['rows'] ?? [];
        $toSend = array_filter($rows, static fn (array $r): bool => ($r['skip_reason'] ?? '') === '' && ($r['telefon_norm'] ?? '') !== '');
        if ($sentToday + count($toSend) > $limit) {
            return ['ok' => false, 'mesaj' => 'Günlük SMS limiti aşılıyor (' . $limit . '). Bugün: ' . $sentToday];
        }
        $gonderimId = $this->gonderimModel->createBatch([
            'kurum_id' => $kurumId,
            'olusturan_id' => $userId,
            'segment_tipi' => $segment,
            'segment_param_json' => json_encode($segmentParams, JSON_UNESCAPED_UNICODE),
            'sablon_id' => $sablonId,
            'govde_ozet' => mb_substr($bodyTemplate, 0, 500),
            'mesaj_turu' => 'bilgilendirme',
            'durum' => 'gonderiliyor',
            'toplam' => count($rows),
            'basarili' => 0,
            'basarisiz' => 0,
        ]);
        if ($gonderimId <= 0) {
            return ['ok' => false, 'mesaj' => 'Gönderim kaydı oluşturulamadı.'];
        }

        $provider = SmsProviderFactory::create();
        $basarili = 0;
        $basarisiz = 0;
        $syncLimit = 50;
        $pendingIds = [];

        foreach ($rows as $r) {
            $aliciId = $this->aliciModel->insertRow([
                'gonderim_id' => $gonderimId,
                'hasta_id' => $r['hasta_id'] ?? null,
                'rol' => $r['rol'] ?? 'hasta',
                'telefon_norm' => $r['telefon_norm'] ?? '',
                'govde' => $r['govde'] ?? '',
                'durum' => 'beklemede',
            ]);
            if ($aliciId <= 0) {
                continue;
            }
            if (($r['skip_reason'] ?? '') !== '' || ($r['telefon_norm'] ?? '') === '') {
                $this->aliciModel->updateRow($aliciId, [
                    'durum' => 'atlandi',
                    'hata_mesaj' => (string) ($r['skip_reason'] ?? 'Atlandı'),
                ]);
                ++$basarisiz;
                continue;
            }
            if (count($toSend) > $syncLimit) {
                $pendingIds[] = $aliciId;
                continue;
            }
            $result = $this->dispatchOne($provider, $aliciId, (string) $r['telefon_norm'], (string) $r['govde']);
            if ($result) {
                ++$basarili;
            } else {
                ++$basarisiz;
            }
        }

        if ($pendingIds !== []) {
            $this->gonderimModel->updateStats($gonderimId, 'beklemede', count($rows), $basarili, $basarisiz);

            return [
                'ok' => true,
                'gonderim_id' => $gonderimId,
                'mesaj' => count($pendingIds) . ' SMS kuyruğa alındı. tools/sms_worker.php çalıştırın.',
                'stats' => ['toplam' => count($rows), 'basarili' => $basarili, 'basarisiz' => $basarisiz, 'kuyruk' => count($pendingIds)],
            ];
        }

        $durum = $basarili > 0 ? 'tamamlandi' : 'hata';
        $this->gonderimModel->updateStats($gonderimId, $durum, count($rows), $basarili, $basarisiz);

        return [
            'ok' => $basarili > 0,
            'gonderim_id' => $gonderimId,
            'mesaj' => $basarili . ' gönderildi, ' . $basarisiz . ' atlandı/hata.',
            'stats' => ['toplam' => count($rows), 'basarili' => $basarili, 'basarisiz' => $basarisiz],
        ];
    }

    public function processQueue(int $limit = 100): int
    {
        if (!self::moduleReady()) {
            return 0;
        }
        $provider = SmsProviderFactory::create();
        $pending = $this->aliciModel->listPending($limit);
        $done = 0;
        foreach ($pending as $row) {
            $id = (int) ($row->id ?? 0);
            if ($id <= 0) {
                continue;
            }
            if ($this->dispatchOne($provider, $id, (string) ($row->telefon_norm ?? ''), (string) ($row->govde ?? ''))) {
                ++$done;
            }
        }

        return $done;
    }

    public function testConnection(): array
    {
        $provider = SmsProviderFactory::create();
        $testPhone = \App\Helpers\SmsCredentialsStore::read()['test_phone'];
        $result = $provider->testConnection($testPhone);

        return [
            'ok' => $result->success,
            'provider' => $provider->getCode(),
            'message_id' => $result->providerMessageId,
            'error' => $result->errorMessage,
            'test_mode' => SmsSettings::testMode(),
        ];
    }

    private function dispatchOne(SmsProviderInterface $provider, int $aliciId, string $phone, string $body): bool
    {
        if ($phone === '' || $body === '') {
            $this->aliciModel->updateRow($aliciId, [
                'durum' => 'atlandi',
                'hata_mesaj' => 'Boş telefon veya mesaj',
            ]);

            return false;
        }
        $result = $provider->send($phone, $body, ['mesaj_turu' => 'bilgilendirme']);
        if ($result->success) {
            $this->aliciModel->updateRow($aliciId, [
                'durum' => 'gonderildi',
                'provider_msg_id' => $result->providerMessageId,
                'gonderim_at' => date('Y-m-d H:i:s'),
            ]);

            return true;
        }
        $this->aliciModel->updateRow($aliciId, [
            'durum' => $result->skipped ? 'atlandi' : 'hata',
            'hata_kodu' => $result->errorCode,
            'hata_mesaj' => mb_substr($result->errorMessage, 0, 255),
            'gonderim_at' => date('Y-m-d H:i:s'),
        ]);

        return false;
    }
}

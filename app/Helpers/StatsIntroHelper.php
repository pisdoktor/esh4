<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * İstatistik sayfaları — üst kısa açıklama metinleri (tek kaynak).
 */
final class StatsIntroHelper {
    /** @var array<string, string> */
    private const INTROS = [
        'index' => 'Tüm istatistik raporlarına tek merkezden erişin; kartlar iş alanına göre gruplanır.',
        'overview' => 'Toplam, aktif ve pasif hasta sayıları ile mahalle ve kayıt yılı dağılımını özetler.',
        'operationsPulse' => 'Günlük izlem, bekleyen hasta ve operasyonel göstergeleri tek ekranda toplar.',
        'ayMovement' => 'Seçilen ayda ulaşılan hasta, yeni kayıt ve takipten çıkanları özetler.',
        'kayitMonths' => 'Aktif hastaların kayıt tarihine göre aylık dağılımını ve trendi gösterir.',
        'monthlyFollowFreq' => 'Cari ayda kaç hastanın izlendiğini ve izlem sıklığını özetler.',
        'monthlyPool' => 'Bu ay izlenen hastaların yaş gruplarına göre dağılımını verir.',
        'dataHealth' => 'Adres, kimlik, doğum ve izlem kayıtlarındaki eksik veya hatalı verileri denetler.',
        'adresPatientFilter' => 'İlçe, mahalle, sokak ve kapı numarasına göre aktif hastaları listeler; hasta özelliği (NG, sonda vb.) ile daraltılabilir.',
        'dataHealthPatients' => 'Seçilen veri sağlığı kriterine uyan aktif hastaları listeler.',
        'followKpi' => 'Son üç ayda en az bir izlemi olan aktif hasta oranını ölçer.',
        'regionalPerformance' => 'İlçe ve mahalle bazında izlem kapsama oranını karşılaştırır.',
        'yearlyFollow' => 'Aylık ve yıllık izlem kapsamasını trend olarak izler.',
        'patientStatus' => 'Hastaları pasif durum kodlarına göre sayar ve oranlar.',
        'ageGenderBands' => 'Aktif hastaların yaş bandı ve cinsiyet dağılımını gösterir.',
        'bmiVki' => 'Boy ve kilo kayıtlı aktif hastalarda VKİ gruplarını özetler.',
        'guvenceDist' => 'Güvence türüne göre aktif hasta dağılımını gösterir.',
        'bagimlilikDist' => 'Aktif hastalarda bağımsızlık düzeyi (bağımlılık kodu) dağılımını gösterir.',
        'geoDistribution' => 'Aktif hastaların ilçe ve mahalle yoğunluğunu sıralar.',
        'anthroCoverage' => 'Boy, kilo ve VKİ hesaplanabilirlik oranlarını özetler; VKİ raporuna tamamlayıcıdır.',
        'kayitTenure' => 'Kayıt tarihinden bugüne geçen süreye göre aktif hasta gruplarını gösterir.',
        'hastalikCountDist' => 'Hasta kartındaki tanı listesi uzunluğuna göre komorbidite yükünü özetler.',
        'clinicalProfile' => 'NG, sonda ve benzeri klinik bayrakların aktif hastadaki dağılımını özetler.',
        'demographicCompleteness' => 'Kimlik, iletişim, ölçüm ve adres alanlarındaki eksikleri oranlar; listeler veri sağlığına bağlanır.',
        'ageSummary' => 'Ortalama ve medyan yaş ile basit yaş gruplarını ve bant dağılımını verir.',
        'waitingPoolProfile' => 'Bekleyen (pasif -3) hastaların ilçe, bağımlılık ve randevu zamanı kırılımını gösterir.',
        'pansumanProfile' => 'Pansuman işaretli aktif hastalarda ziyaret zaman dilimi ve gün bilgisini özetler.',
        'kayitKohortAge' => 'Kayıt yılına göre, kayıt tarihindeki yaş bantlarını (AgeBandHelper: g01–g86) dağılımını tablolaştırır; güncel yaş değil, kayıt anı referans alınır.',
        'guvenceAgeBands' => 'Aktif hastalarda güvence türü ile yaş bandı (ageGenderBands ile aynı bantlar) kesişimini gösterir.',
        'fieldCoverage' => 'Telefon, profil fotoğrafı ve anne/baba adı doluluk oranlarını özetler; ebeveyn adında yalnızca nokta (. / .. / ...) olan kayıtlar ayrı satırda gösterilir.',
        'fieldCoveragePatients' => 'Anne veya baba adında yalnızca «.», «..» veya «...» olan aktif hastaları listeler; alan doluluk özetinden açılır.',
        'birthdays' => 'Bugün doğum günü olan aktif hastaları listeler.',
        'hastalik' => 'Tanılara göre aktif hasta sayısını kategori tablosunda listeler.',
        'charts' => 'Aktif hastalarda tanı kategorileri ve en sık tanıların dağılımını gösterir.',
        'topVisits' => 'En çok tamamlanmış izlemi olan aktif hastaları sıralar.',
        'birIzlemliler' => 'Yalnızca bir tamamlanmış izlemi olan ve belirli işlem kodlu hastaları bulur.',
        'aylikTekIzlemliler' => 'Seçilen ay ve yılda tam olarak bir tamamlanmış izlemi olan aktif hastaları listeler; işlem tipi fark etmez.',
        'workload' => 'Kayıttan bugüne geçen süre ve son izleme göre risk gruplarını listeler.',
        'chronologyIssues' => 'Kayıt tarihinden önce yapılmış izlem gibi kronoloji tutarsızlıklarını listeler.',
        'barthel' => 'Aktif hastalarda Barthel bağımlılık skoru dağılımını gösterir.',
        'visitProcedures' => 'Seçilen dönemde tamamlanmış izlemlerde yapılan işlem adetlerini listeler.',
        'visitPersonnel' => 'Tamamlanmış izlem başına personel yükünü sayar.',
        'visitConsultationMonthly' => 'İzlemlerde branş ve konsültasyon isteklerinin aylık dökümünü verir.',
        'visitStats' => 'Seçilen dönemde kayıtlı izlemlerin yapıldı ve yapılmadı dağılımını; araç, zaman dilimi ve yapılmama nedenine göre özetler.',
        'plannedVisitStats' => 'Seçilen dönemde planlanan izlemlerin tamamlanan, bekleyen ve gecikmiş dağılımını; öncelik ve zaman dilimine göre özetler.',
        'randevuTakvim' => 'Branş ve görüntülü muayene randevularının dönem özetini verir.',
        'randevuKayitGap' => 'Hasta kartındaki kayıt ile randevu tarihi arasındaki gün farkını analiz eder.',
        'specialDevices' => 'NG, PEG, sonda gibi özel durum işaretli aktif hastaları özetler.',
        'eraporList' => 'e-Rapor işaretli aktif hastaları ilçe ve mahalleye göre listeler.',
        'eraporHastaUyum' => 'e-Rapor havuzu ile hasta kartlarını TC üzerinden karşılaştırır; özet kartlar ve metrik tablosu ayrı XHR ile yüklenir.',
        'eraporHastaUyumList' => 'Seçilen uyum kriterine uyan e-Rapor veya hasta kayıtlarını listeler.',
        'supplyReports' => 'Mama veya bez raporu bitiş tarihi yaklaşan veya dolan hastaları listeler.',
        'supplyStokPanel' => 'Mama/bez rapor özetleri ile kritik stok ihtiyacını tek panelde birleştirir.',
        'stokOzet' => 'Kritik malzeme sayısı, son 30 gün çıkış toplamı ve kategori dağılımı.',
        'sondaChanges' => 'Planlanan sonda değişim tarihi seçilen dönemde olan hastaları listeler.',
        'exitReasons' => 'Pasife alınan hastaların çıkış nedenlerinin dağılımını gösterir.',
        'passiveReasons' => 'Pasife alınan hastaların çıkış nedenlerinin dağılımını gösterir.',
        'hastalikPatients' => 'Seçilen tanıya kayıtlı aktif hastaları listeler.',
    ];

    public static function forAction(string $action): string {
        $xId = StatsCrossTabRegistry::idFromAction($action);
        if ($xId !== null) {
            $intro = (string) (StatsCrossTabRegistry::definition($xId)['intro'] ?? '');
            if ($intro !== '') {
                return $intro;
            }
        }

        return self::INTROS[$action] ?? '';
    }
}

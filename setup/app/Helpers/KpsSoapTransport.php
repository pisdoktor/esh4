<?php
declare(strict_types=1);

namespace App\Helpers;

/**
 * KPS SOAP web servis taşıyıcısı — yetki ve resmi WSDL sonrası tamamlanacak.
 */
final class KpsSoapTransport
{
    /**
     * @param array{
     *   wsdl_url:string,
     *   timeout_seconds:int,
     *   username:string,
     *   password:string,
     *   firma_kodu:string
     * } $config
     * @return array{
     *   ok:bool,
     *   status:string,
     *   message:string,
     *   data:array<string, string>|null
     * }
     */
    public static function query(string $tc, array $config): array
    {
        $wsdl = trim((string) ($config['wsdl_url'] ?? ''));
        if ($wsdl === '') {
            return self::fail('error', 'WSDL adresi tanımlı değil.');
        }

        // TODO: SB KPS resmi SOAP isteği — SoapClient + kimlik doğrulama (firma kodu, IP, kullanıcı/şifre).

        return [
            'ok' => false,
            'status' => 'stub',
            'message' => 'KPS SOAP entegrasyonu henüz etkin değil. Yapılandırma tamam; Sağlık Bakanlığı yetkisi ve resmi WSDL dokümanı sonrası sorgu açılacak.',
            'data' => null,
        ];
    }

    /**
     * @param array{
     *   wsdl_url:string,
     *   timeout_seconds:int,
     *   username:string,
     *   password:string,
     *   firma_kodu:string
     * } $config
     * @return array{
     *   ok:bool,
     *   status:string,
     *   message:string,
     *   deceased:bool,
     *   olumTarihi:?string,
     *   yasamDurumu:string,
     *   data:array<string, string>|null
     * }
     */
    public static function queryDeath(string $tc, array $config): array
    {
        $wsdl = trim((string) ($config['wsdl_url'] ?? ''));
        if ($wsdl === '') {
            return self::failDeath('error', 'WSDL adresi tanımlı değil.');
        }

        // TODO: SB KPS resmi SOAP vefat/yaşam durumu sorgusu — WSDL sonrası normalizeDeathFromRaw() ile parse.

        return [
            'ok' => false,
            'status' => 'stub',
            'message' => 'KPS vefat SOAP entegrasyonu henüz etkin değil. Yetki ve WSDL sonrası açılacak.',
            'deceased' => false,
            'olumTarihi' => null,
            'yasamDurumu' => '',
            'data' => null,
        ];
    }

    /**
     * Ham SOAP yanıtını hasta formu alanlarına dönüştürür (ileride kullanılacak).
     *
     * @param mixed $rawResponse
     * @return array<string, string>
     */
    public static function normalizeResponse($rawResponse): array
    {
        $base = self::emptyPersonData();

        return $base;
    }

    /**
     * Ham SOAP yanıtından vefat alanlarını çıkarır (ileride kullanılacak).
     *
     * @param mixed $rawResponse
     * @return array{deceased:bool,olumTarihi:?string,yasamDurumu:string,data:array<string, string>}
     */
    public static function normalizeDeathFromRaw($rawResponse): array
    {
        $data = self::emptyPersonData();

        return [
            'deceased' => false,
            'olumTarihi' => null,
            'yasamDurumu' => '',
            'data' => $data,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function emptyPersonData(): array
    {
        return [
            'ad' => '',
            'soyad' => '',
            'dogumtarihi' => '',
            'cinsiyet' => '',
            'anneAdi' => '',
            'babaAdi' => '',
            'yasamDurumu' => '',
            'vefat' => '0',
            'olumTarihi' => '',
        ];
    }

    /**
     * @return array{ok:bool,status:string,message:string,data:array<string, string>|null}
     */
    private static function fail(string $status, string $message): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'message' => $message,
            'data' => null,
        ];
    }

    /**
     * @return array{ok:bool,status:string,message:string,deceased:bool,olumTarihi:?string,yasamDurumu:string,data:array<string, string>|null}
     */
    private static function failDeath(string $status, string $message): array
    {
        return [
            'ok' => false,
            'status' => $status,
            'message' => $message,
            'deceased' => false,
            'olumTarihi' => null,
            'yasamDurumu' => '',
            'data' => null,
        ];
    }
}

<?php
declare(strict_types=1);

return [
    // Bridge dinleme bilgisi (php -S ile kullanılır)
    'host' => '127.0.0.1',
    'port' => 15873,

    // CORS: login ekranının origin değeri
    'allowed_origin' => 'http://localhost',

    // PKCS#11 araçları
    // OpenSC kuruluysa genelde "pkcs11-tool" PATH içinde olur.
    'pkcs11_tool_bin' => 'pkcs11-tool',

    // Token sürücü DLL yolu (AKİS/SafeNet/Bit4id vb. middleware'e göre değişir)
    // Örn: C:\\Windows\\System32\\akisp11.dll
    'pkcs11_module' => '',

    // İmzalanacak sertifikanın object id değeri (hex)
    // Token içeriğini görmek için: pkcs11-tool --module <dll> -O
    'cert_id_hex' => '',

    // İsteğe bağlı: provider'da çoklu slot varsa slot index sabitlemek için (örn 0)
    // null ise otomatik.
    'slot_index' => null,
];

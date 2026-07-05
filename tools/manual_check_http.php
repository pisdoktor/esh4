<?php
declare(strict_types=1);

/** @var list<string> */
const MANUAL_CHECK_DENY_MARKERS = [
    'Bu alana erişim yetkiniz bulunmamaktadır',
    'Bu sayfaya erişim yetkiniz yok',
    'HTTP 403',
    'Forbidden',
    'Fatal error',
    'Uncaught',
    'SQLSTATE[',
];

final class ManualCheckHttp
{
    private string $cookieFile;
    private ?string $csrf = null;

    public function __construct(private readonly string $base, private readonly string $suffix = '')
    {
        $this->cookieFile = sys_get_temp_dir() . '/esh_manual_check_' . getmypid() . $this->suffix . '.txt';
        @unlink($this->cookieFile);
    }

    public function __destruct()
    {
        @unlink($this->cookieFile);
    }

    /** @return array{code:int,body:string,url:string} */
    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    /** @return array{code:int,body:string,url:string} */
    public function post(string $path, array $fields): array
    {
        return $this->request('POST', $path, $fields);
    }

    /** @param array<string, mixed> $payload */
    public function postJson(string $path, array $payload): array
    {
        $url = str_starts_with($path, 'http') ? $path : $this->base . $path;
        $ch = curl_init($url);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $headers = ['Accept: application/json', 'Content-Type: application/json'];
        if ($this->csrf !== null && $this->csrf !== '') {
            $headers[] = 'X-CSRF-Token: ' . $this->csrf;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 8,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => 'ESH-ManualCheck/1.0',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => is_string($json) ? $json : '{}',
        ]);
        $body = (string) curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        return ['code' => $code, 'body' => $body, 'url' => $finalUrl];
    }

    /** @return array{code:int,body:string,url:string} */
    public function getBearer(string $path, string $token): array
    {
        $url = str_starts_with($path, 'http') ? $path : $this->base . $path;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_USERAGENT => 'ESH-ManualCheck/1.0',
        ]);
        $body = (string) curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        return ['code' => $code, 'body' => $body, 'url' => $finalUrl];
    }

    /** @return array{code:int,body:string,url:string} */
    private function request(string $method, string $path, array $fields = []): array
    {
        $url = str_starts_with($path, 'http') ? $path : $this->base . $path;
        $ch = curl_init($url);
        $headers = ['Accept: text/html,application/json,*/*'];
        if ($this->csrf !== null && $this->csrf !== '') {
            $headers[] = 'X-CSRF-Token: ' . $this->csrf;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 8,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => 'ESH-ManualCheck/1.0',
        ]);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        }
        $body = (string) curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $finalUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        return ['code' => $code, 'body' => $body, 'url' => $finalUrl];
    }

    public function login(string $username, string $password): bool
    {
        $this->csrf = null;
        @unlink($this->cookieFile);
        $login = $this->get('/public/Auth/login');
        if ($login['code'] !== 200) {
            return false;
        }
        $this->extractCsrf($login['body']);
        $post = $this->post('/public/Auth/doLogin', [
            'username' => $username,
            'password' => $password,
            'csrf_token' => $this->csrf ?? '',
        ]);
        if ($post['code'] >= 400) {
            return false;
        }
        $dash = $this->get('/public/Dashboard/index');
        return $dash['code'] === 200
            && !str_contains($dash['body'], 'Auth/login')
            && !str_contains($dash['body'], 'Giriş Yap');
    }

    public function csrfToken(): ?string
    {
        return $this->csrf;
    }

    public function refreshCsrfFromPage(string $path): void
    {
        $this->extractCsrf($this->get($path)['body']);
    }

    public function extractCsrf(string $html): void
    {
        if (preg_match('/name="csrf-token"\s+content="([^"]+)"/', $html, $m)) {
            $this->csrf = $m[1];
        } elseif (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $m)) {
            $this->csrf = $m[1];
        }
    }

    /** @return list<string> */
    public static function scanBody(string $body, int $code): array
    {
        $issues = [];
        if ($code < 200 || $code >= 400) {
            $issues[] = "HTTP {$code}";
        }
        foreach (MANUAL_CHECK_DENY_MARKERS as $marker) {
            if (str_contains($body, $marker)) {
                $issues[] = $marker;
            }
        }
        return $issues;
    }
}

function manual_check_pdo(): PDO
{
    $port = defined('DB_PORT') && DB_PORT !== '' ? ';port=' . DB_PORT : '';
    $dsn = DB_DRIVER . ':host=' . DB_HOST . $port . ';dbname=' . DB_NAME;

    return new PDO($dsn, DB_USER, DB_PASS);
}

/** @return array{id:string,tckimlik:string,phone:string}|null */
function manual_check_portal_patient(): ?array
{
    try {
        $pdo = manual_check_pdo();
        $row = $pdo->query(
            "SELECT id, tckimlik, ceptel1 FROM " . DB_PREFIX . "hastalar WHERE pasif='0' AND TRIM(COALESCE(ceptel1,'')) <> '' ORDER BY kayittarihi DESC LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row) || empty($row['id']) || empty($row['tckimlik'])) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', (string) ($row['ceptel1'] ?? ''));
        if ($digits === '') {
            return null;
        }
        if ($digits[0] !== '0') {
            $digits = '0' . $digits;
        }
        $digits = substr($digits, 0, 11);
        if (strlen($digits) !== 11) {
            return null;
        }

        return [
            'id' => (string) $row['id'],
            'tckimlik' => (string) $row['tckimlik'],
            'phone' => $digits,
        ];
    } catch (Throwable) {
        return null;
    }
}

/** @return array{id:string,tckimlik:string}|null */
function manual_check_demo_patient(): ?array
{
    try {
        $pdo = manual_check_pdo();
        $row = $pdo->query(
            "SELECT id, tckimlik FROM " . DB_PREFIX . "hastalar WHERE pasif='0' ORDER BY kayittarihi DESC LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row) || empty($row['id'])) {
            return null;
        }

        return ['id' => (string) $row['id'], 'tckimlik' => (string) ($row['tckimlik'] ?? '')];
    } catch (Throwable) {
        return null;
    }
}

function manual_check_solve_captcha(string $html): ?int
{
    if (!preg_match('/id="portalCaptchaQuestion"[^>]*>([^<]+)</', $html, $m)) {
        if (!preg_match('/portalCaptchaQuestion[^>]*>([^<]+)</', $html, $m)) {
            return null;
        }
    }
    $q = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $q = str_replace(['−', '–', '—'], '-', $q);
    if (preg_match('/(\d+)\s*\+\s*(\d+)/', $q, $parts)) {
        return (int) $parts[1] + (int) $parts[2];
    }
    if (preg_match('/(\d+)\s*-\s*(\d+)/', $q, $parts)) {
        return (int) $parts[1] - (int) $parts[2];
    }

    return null;
}

function manual_check_db_error_delta(string $root): int
{
    $log = $root . '/logs/db_errors.log';
    $baseline = $root . '/logs/.qa_baseline';
    if (!is_file($log)) {
        return 0;
    }
    $lines = file($log, FILE_IGNORE_NEW_LINES);
    $total = is_array($lines) ? count($lines) : 0;
    $since = 0;
    if (is_file($baseline)) {
        $raw = trim((string) file_get_contents($baseline));
        if ($raw !== '' && ctype_digit($raw)) {
            $since = (int) $raw;
        }
    }
    $count = 0;
    for ($i = $since; $i < $total; $i++) {
        if (str_contains((string) ($lines[$i] ?? ''), 'Sorgu Hatası')) {
            $count++;
        }
    }

    return $count;
}

function manual_check_register_autoload(string $root): void
{
    spl_autoload_register(static function (string $class) use ($root): void {
        $prefix = 'App\\';
        $base = $root . '/app/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $file = $base . str_replace('\\', '/', substr($class, $len)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
}

function manual_check_read_mock_sms_otp(string $root, int $afterBytes): ?string
{
    $log = $root . '/storage/logs/sms_mock.log';
    if (!is_file($log)) {
        return null;
    }
    $chunk = (string) @file_get_contents($log, false, null, max(0, $afterBytes));
    if ($chunk === '' && $afterBytes > 0) {
        return null;
    }
    if ($chunk === '') {
        $chunk = (string) @file_get_contents($log);
    }
    if (preg_match('/SONEV portal giri[sş] kodunuz:\s*(\d{6})/u', $chunk, $m)) {
        return $m[1];
    }

    return null;
}

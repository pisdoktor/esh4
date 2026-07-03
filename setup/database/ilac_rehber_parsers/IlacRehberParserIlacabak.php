<?php
declare(strict_types=1);

require_once __DIR__ . '/IlacRehberParserInterface.php';

/**
 * ilacabak.com — canliArama + etkengoster HTML parser.
 */
final class IlacRehberParserIlacabak implements IlacRehberParserInterface
{
    private const BASE = 'https://www.ilacabak.com/';

    /**
     * canliArama tek harfte yalnızca ticari ilaç döner; etken için ≥3 karakter gerekir.
     *
     * @var list<string>
     */
    private const ETKEN_SEED_QUERIES = [
        'par', 'per', 'met', 'pro', 'sul', 'mab', 'ace', 'ibu', 'amo', 'cil',
        'dex', 'ome', 'lev', 'ator', 'stat', 'pril', 'sart', 'keto', 'pred',
        'dopa', 'cipro', 'azit', 'warf', 'hep', 'meto', 'tram', 'amok', 'seft',
        'gent', 'vanc', 'ritu', 'adal', 'infl', 'etan', 'insu', 'war', 'morf',
    ];

    /** @var callable(string): string */
    private $httpGet;

    private bool $debug;

    /** @var callable(string, string): void|null */
    private $debugSave;

    private int $maxSeedQueries = 0;

    /** @var 'seeds'|'ids'|'both' */
    private string $discoveryMode = 'both';

    private int $idMax = 15000;

    private int $idEmptyStop = 50;

    private int $seedStartIndex = 0;

    private int $idStartCursor = 1;

    /** @var callable(string): void|null */
    private $progressLog = null;

    /** @var callable(array{phase: string, seed_index?: int, seed_total?: int, id_cursor?: int, id_max?: int}): void|null */
    private $onCheckpointTick = null;

    /** @var callable(array{key: string, ad: string, html?: string}): bool|null */
    private $onEtkenDiscovered = null;

    public function __construct(callable $httpGet, bool $debug = false, ?callable $debugSave = null)
    {
        $this->httpGet = $httpGet;
        $this->debug = $debug;
        $this->debugSave = $debugSave;
    }

    public function siteKey(): string
    {
        return 'ilacabak';
    }

    public function baseUrl(): string
    {
        return self::BASE;
    }

    public function setMaxSeedQueries(int $max): void
    {
        $this->maxSeedQueries = max(0, $max);
    }

    /** @param 'seeds'|'ids'|'both' $mode */
    public function setDiscoveryMode(string $mode): void
    {
        if (!in_array($mode, ['seeds', 'ids', 'both'], true)) {
            throw new InvalidArgumentException('discovery mode seeds|ids|both: ' . $mode);
        }
        $this->discoveryMode = $mode;
    }

    public function setIdScanOptions(int $max, int $emptyStop): void
    {
        $this->idMax = max(1, $max);
        $this->idEmptyStop = max(1, $emptyStop);
    }

    public function setSeedStartIndex(int $index): void
    {
        $this->seedStartIndex = max(0, $index);
    }

    public function setIdStartCursor(int $cursor): void
    {
        $this->idStartCursor = max(1, $cursor);
    }

    public function countSeedQueries(): int
    {
        return count($this->buildSeedQueries());
    }

    /** @param callable(array{phase: string, seed_index?: int, seed_total?: int, id_cursor?: int, id_max?: int}): void|null $cb */
    public function setOnCheckpointTick(?callable $cb): void
    {
        $this->onCheckpointTick = $cb;
    }

    /** @param callable(string): void|null $log */
    public function setProgressLog(?callable $log): void
    {
        $this->progressLog = $log;
    }

    /** Keşfedilen her yeni etken için anında upsert tetiklemek üzere (scrape CLI). */
    public function setOnEtkenDiscovered(?callable $cb): void
    {
        $this->onEtkenDiscovered = $cb;
    }

    public function fetchEtkenList(): array
    {
        $seen = [];
        $out = [];

        if ($this->discoveryMode === 'seeds' || $this->discoveryMode === 'both') {
            $out = array_merge($out, $this->fetchEtkenFromSeeds($seen));
        }

        if ($this->discoveryMode === 'ids' || $this->discoveryMode === 'both') {
            $out = array_merge($out, $this->fetchEtkenByIdRange($seen));
        }

        usort($out, static fn (array $a, array $b): int => strcasecmp($a['ad'], $b['ad']));

        return $out;
    }

    /**
     * canliArama ile genişletilmiş seed sorgularından etken keşfi.
     *
     * @param array<string, true> $seen
     * @return list<array{key: string, ad: string}>
     */
    private function fetchEtkenFromSeeds(array &$seen): array
    {
        $out = [];
        $seeds = $this->buildSeedQueries();
        $total = count($seeds);

        if ($this->seedStartIndex > 0 && $this->seedStartIndex < $total) {
            $this->logProgress("Resuming seeds from index {$this->seedStartIndex}");
        }
        $this->logProgress("canliArama seed taraması başladı ({$total} sorgu)");

        foreach ($seeds as $i => $q) {
            if ($i < $this->seedStartIndex) {
                continue;
            }
            try {
                try {
                    $html = ($this->httpGet)(self::BASE . 'canliArama.php?sorgu=' . rawurlencode($q));
                } catch (Throwable $e) {
                    $this->logProgress(
                        'canliArama atlandi sorgu=' . $q . ' (' . ($i + 1) . "/{$total}): " . $e->getMessage()
                    );
                    $this->tickCheckpoint('seed', $i + 1, $total);
                    continue;
                }
                if ($this->debug && $this->debugSave !== null) {
                    ($this->debugSave)('canli_' . $q . '.html', $html);
                }
                $found = 0;
                foreach ($this->parseCanliAramaEtken($html) as $row) {
                    try {
                        if ($this->registerDiscoveredEtken($row, $seen, $out)) {
                            $found++;
                        }
                    } catch (Throwable $e) {
                        $this->logProgress(
                            'Etken isleme atlandi [' . ($row['key'] ?? '?') . ']: ' . $e->getMessage()
                        );
                    }
                }
                if (($i + 1) % 100 === 0 || $i + 1 === $total) {
                    $this->logProgress(
                        'canliArama ' . ($i + 1) . "/{$total} sorgu — toplam etken: " . count($seen) . " (son +{$found})"
                    );
                }
                $this->tickCheckpoint('seed', $i + 1, $total);
            } catch (Throwable $e) {
                $this->logProgress(
                    'canliArama seed hatasi sorgu=' . $q . ' (' . ($i + 1) . "/{$total}): " . $e->getMessage()
                );
                $this->tickCheckpoint('seed', $i + 1, $total);
            }
        }

        $this->tickCheckpoint('seed', $total, $total);
        $this->logProgress('canliArama tamam — etken: ' . count($seen));

        return $out;
    }

    /**
     * etkengoster.php?Id={n} aralık taraması; geçerli sayfa = başlık + ilaç listesi.
     *
     * @param array<string, true> $seen
     * @return list<array{key: string, ad: string}>
     */
    public function fetchEtkenByIdRange(array &$seen = []): array
    {
        $out = [];
        $empty = 0;

        $startId = $this->idStartCursor;
        if ($startId > 1) {
            $this->logProgress("Resuming Id scan from cursor {$startId}");
        }
        $this->logProgress("Id taraması başladı ({$startId}..{$this->idMax}, dur: {$this->idEmptyStop} boş)");

        $lastId = $startId - 1;
        for ($id = $startId; $id <= $this->idMax; $id++) {
            $lastId = $id;
            if ($empty >= $this->idEmptyStop) {
                $this->logProgress("Id taraması durdu Id={$id} ({$this->idEmptyStop} ardışık boş)");
                $this->tickCheckpoint('id', $id);
                break;
            }

            $key = (string) $id;
            if (!isset($seen[$key])) {
                $url = self::BASE . 'etkengoster.php?Id=' . rawurlencode($key);
                try {
                    $html = ($this->httpGet)($url);
                } catch (Throwable $e) {
                    $empty++;
                    if ($id % 500 === 0) {
                        $this->logProgress("Id {$id}/{$this->idMax} — etken: " . count($seen) . " (HTTP hata)");
                    }
                }

                if (isset($html)) {
                    if ($this->debug && $this->debugSave !== null) {
                        ($this->debugSave)('etken_probe_' . $key . '.html', $html);
                    }

                    if (!$this->isValidEtkenPage($html)) {
                        $empty++;
                        if ($id % 500 === 0) {
                            $this->logProgress("Id {$id}/{$this->idMax} — etken: " . count($seen));
                        }
                    } else {
                        $empty = 0;
                        $ad = $this->parseEtkenAdFromPage($html);
                        if ($ad !== '') {
                            $this->registerDiscoveredEtken(['key' => $key, 'ad' => $ad, 'html' => $html], $seen, $out);

                            if ($id % 100 === 0 || count($out) % 50 === 0) {
                                $this->logProgress("Id {$id}/{$this->idMax} — keşfedilen: " . count($seen) . " (+{$ad})");
                            }
                        }
                    }
                    unset($html);
                }
            }

            $this->tickCheckpoint('id', $id + 1);
        }

        if ($lastId >= $startId) {
            $this->tickCheckpoint('id', min($this->idMax + 1, $lastId + 1));
        }
        $this->logProgress('Id taraması tamam — etken: ' . count($seen));

        return $out;
    }

    /**
     * Sabit önekler + tüm 3 harfli a-z kombinasyonları (canliArama ≥3 karakter kuralı).
     *
     * @return list<string>
     */
    private function buildSeedQueries(): array
    {
        $seeds = self::ETKEN_SEED_QUERIES;

        foreach (range('a', 'z') as $a) {
            foreach (range('a', 'z') as $b) {
                foreach (range('a', 'z') as $c) {
                    $seeds[] = $a . $b . $c;
                }
            }
        }

        $seeds = array_values(array_unique($seeds));
        if ($this->maxSeedQueries > 0) {
            $seeds = array_slice($seeds, 0, $this->maxSeedQueries);
        }

        return $seeds;
    }

    private function isValidEtkenPage(string $html): bool
    {
        if ($this->parseEtkenAdFromPage($html) === '') {
            return false;
        }

        return $this->parseIlacListFromEtkenPage($html) !== [];
    }

    private function logProgress(string $msg): void
    {
        if ($this->progressLog !== null) {
            ($this->progressLog)($msg);
        }
    }

    private function tickCheckpoint(string $phase, int $seedIndex = 0, int $seedTotal = 0): void
    {
        if ($this->onCheckpointTick === null) {
            return;
        }
        if ($phase === 'seed') {
            ($this->onCheckpointTick)([
                'phase' => 'seed',
                'seed_index' => $seedIndex,
                'seed_total' => $seedTotal,
            ]);
        } elseif ($phase === 'id') {
            ($this->onCheckpointTick)([
                'phase' => 'id',
                'id_cursor' => $seedIndex,
                'id_max' => $this->idMax,
            ]);
        }
    }

    /**
     * @param array{key: string, ad: string, html?: string} $row
     * @param array<string, true> $seen
     * @param list<array{key: string, ad: string, html?: string}> $out
     */
    private function registerDiscoveredEtken(array $row, array &$seen, array &$out): bool
    {
        $k = $row['key'];
        if (isset($seen[$k])) {
            return false;
        }

        if ($this->onEtkenDiscovered !== null) {
            try {
                $ok = ($this->onEtkenDiscovered)($row);
            } catch (Throwable $e) {
                $this->logProgress('Etken callback atlandi [' . $k . ']: ' . $e->getMessage());

                return false;
            }
            if ($ok === false) {
                return false;
            }
        }

        $seen[$k] = true;
        $out[] = $row;

        return true;
    }

    public function fetchIlaclarForEtken(string $key, ?string $html = null): array
    {
        $key = trim($key);
        if ($key === '' || !ctype_digit($key)) {
            throw new InvalidArgumentException('Geçersiz etken Id: ' . $key);
        }

        if ($html === null) {
            $url = self::BASE . 'etkengoster.php?Id=' . rawurlencode($key);
            $html = ($this->httpGet)($url);
            if ($this->debug && $this->debugSave !== null) {
                ($this->debugSave)('etken_' . $key . '.html', $html);
            }
        }

        $ad = $this->parseEtkenAdFromPage($html);
        $ilaclar = $this->parseIlacListFromEtkenPage($html);

        return ['ad' => $ad, 'ilaclar' => $ilaclar];
    }

    /**
     * @return list<array{key: string, ad: string}>
     */
    private function parseCanliAramaEtken(string $html): array
    {
        $out = [];
        if (!preg_match_all(
            '/<a\s+href=["\']etkengoster\.php\?Id=(\d+)["\'][^>]*title=["\']([^"\']*)["\'][^>]*>([^<]*)</iu',
            $html,
            $m,
            PREG_SET_ORDER
        )) {
            return $out;
        }

        foreach ($m as $row) {
            $id = trim($row[1]);
            $title = html_entity_decode(trim($row[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = html_entity_decode(trim(strip_tags($row[3])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $ad = $title !== '' ? $title : $text;
            $ad = preg_replace('/\s+etkin\s+madde\s*$/iu', '', $ad) ?? $ad;
            $ad = trim($ad);
            if ($id === '' || $ad === '') {
                continue;
            }
            $out[] = ['key' => $id, 'ad' => $ad];
        }

        return $out;
    }

    private function parseEtkenAdFromPage(string $html): string
    {
        if (preg_match('/<meta\s+property=["\']og:title["\']\s+content=["\']([^"\']+)["\']/iu', $html, $m)) {
            $t = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $t = preg_replace('/\s+etkin\s+maddesi\s*$/iu', '', $t) ?? $t;

            return trim($t);
        }
        if (preg_match('/<h1[^>]*>([^<]+)</iu', $html, $m)) {
            return trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return '';
    }

    /**
     * @return list<array{ad: string, firma: string, recete_turu: string, source_key: string, source_url: string}>
     */
    private function parseIlacListFromEtkenPage(string $html): array
    {
        $out = [];
        $section = $html;
        if (preg_match('/içeren\s+ilaçlar\s*<\/h1>\s*<ul>(.*?)<\/ul>/isu', $html, $block)) {
            $section = $block[1];
        } elseif (preg_match('/iceren\s+ilaclar\s*<\/h1>\s*<ul>(.*?)<\/ul>/isu', $html, $block)) {
            $section = $block[1];
        }

        if (!preg_match_all(
            '/<div\s+class=["\']listeilac["\'][^>]*>\s*<a\s+href=["\']([^"\']+)["\'][^>]*title=["\']([^"\']*)["\'][^>]*>([^<]*)</iu',
            $section,
            $m,
            PREG_SET_ORDER
        )) {
            return $out;
        }

        foreach ($m as $row) {
            $href = trim($row[1]);
            $title = html_entity_decode(trim($row[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = html_entity_decode(trim($row[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $ad = $title !== '' ? $title : $text;
            $ad = trim($ad);
            if ($href === '' || $ad === '') {
                continue;
            }
            $slug = ltrim(str_replace('\\', '/', $href), '/');
            $sourceKey = $slug;
            if (preg_match('/-(\d+)$/', $slug, $idm)) {
                $sourceKey = $idm[1];
            }
            $out[] = [
                'ad' => $ad,
                'firma' => '',
                'recete_turu' => '',
                'source_key' => $sourceKey,
                'source_url' => self::BASE . $slug,
            ];
        }

        return $out;
    }
}

<?php
declare(strict_types=1);

/**
 * Site-specific ilaç rehberi parser (CLI scrape).
 */
interface IlacRehberParserInterface
{
    public function siteKey(): string;

    public function baseUrl(): string;

    /**
     * @return list<array{key: string, ad: string}>
     */
    public function fetchEtkenList(): array;

    /**
     * @param string|null $html Önceden indirilmiş etkengoster HTML (Id taramasında tekrar HTTP istememek için).
     * @return array{ad: string, ilaclar: list<array{ad: string, firma: string, recete_turu: string, source_key: string, source_url: string}>}
     */
    public function fetchIlaclarForEtken(string $key, ?string $html = null): array;
}

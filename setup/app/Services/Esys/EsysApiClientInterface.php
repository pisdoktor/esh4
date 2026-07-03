<?php
declare(strict_types=1);

namespace App\Services\Esys;

interface EsysApiClientInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return array{ok:bool,status?:int,error?:string,response?:mixed}
     */
    public function pushBundle(array $payload): array;

    /**
     * @return array{ok:bool,status?:int,error?:string}
     */
    public function ping(): array;
}

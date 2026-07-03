<?php
declare(strict_types=1);

use App\Services\MesajService;
use PHPUnit\Framework\TestCase;

final class MesajServiceFieldTeamTest extends TestCase
{
    public function testNotifyFieldTeamInvalidSender(): void
    {
        $service = new MesajService();
        $result = $service->notifyFieldTeam(0, [1], 'Test', 'Body');
        self::assertFalse($result['ok']);
        self::assertSame(0, $result['sent']);
    }

    public function testNotifyFieldTeamEmptyBody(): void
    {
        $service = new MesajService();
        $result = $service->notifyFieldTeam(1, [2], 'Konu', '   ');
        self::assertFalse($result['ok']);
        self::assertArrayHasKey('error', $result);
    }
}

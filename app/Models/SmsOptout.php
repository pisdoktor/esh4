<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class SmsOptout extends BaseModel
{
    public function __construct()
    {
        parent::__construct('#__sms_optout', 'id');
    }

    public static function tableReady(): bool
    {
        try {
            $db = Database::getInstance();

            return (bool) $db->loadResultPrepared(
                'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1',
                [$db->replacePrefix('#__sms_optout')]
            );
        } catch (\Throwable) {
            return false;
        }
    }

    public function isOptedOut(string $phoneNorm, int $kurumId): bool
    {
        if ($phoneNorm === '' || $kurumId <= 0 || !self::tableReady()) {
            return false;
        }
        $val = $this->db->loadResultPrepared(
            'SELECT 1 FROM #__sms_optout WHERE telefon_norm = ? AND kurum_id = ? LIMIT 1',
            [$phoneNorm, $kurumId]
        );

        return (bool) $val;
    }
}

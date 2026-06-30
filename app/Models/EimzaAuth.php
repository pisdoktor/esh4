<?php

namespace App\Models;



class EimzaAuth extends BaseModel

{

    public function __construct()

    {

        parent::__construct('#__eimza_challenges', 'id');

    }



    public function createChallenge(string $nonce, int $expiresAt, ?int $userId = null): ?array

    {

        $nonceHash = hash('sha256', $nonce);

        $issuedAt = date('Y-m-d H:i:s');

        $expiresAtSql = date('Y-m-d H:i:s', $expiresAt);

        $ip = isset($_SERVER['REMOTE_ADDR']) ? trim((string) $_SERVER['REMOTE_ADDR']) : '';

        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? trim((string) $_SERVER['HTTP_USER_AGENT']) : '';

        $id = $this->db->insertPrepared('#__eimza_challenges', [
            'user_id' => $userId !== null && $userId > 0 ? $userId : null,
            'nonce_hash' => $nonceHash,
            'issued_at' => $issuedAt,
            'expires_at' => $expiresAtSql,
            'ip_address' => $ip,
            'user_agent' => $ua,
        ]);

        if ($id === false) {
            return null;
        }

        $id = (int) $id;

        if ($id < 1) {

            return null;

        }



        return [

            'id' => $id,

            'nonce' => $nonce,

            'expires_at' => $expiresAt,

        ];

    }



    public function consumeChallenge(int $challengeId, string $nonce): ?array

    {

        if ($challengeId < 1) {

            return null;

        }

        $row = $this->db->fetchOnePrepared(

            'SELECT * FROM #__eimza_challenges WHERE id = ? LIMIT 1',

            [$challengeId]

        );

        if (!is_array($row)) {

            return null;

        }

        if (!empty($row['consumed_at'])) {

            return null;

        }

        $expiresAtRaw = (string) ($row['expires_at'] ?? '');

        $expiresAt = strtotime($expiresAtRaw);

        if ($expiresAt === false || $expiresAt < time()) {

            return null;

        }

        $expectedHash = trim((string) ($row['nonce_hash'] ?? ''));

        if ($expectedHash === '' || !hash_equals($expectedHash, hash('sha256', $nonce))) {

            return null;

        }

        $nowSql = date('Y-m-d H:i:s');

        $this->db->executePrepared(

            'UPDATE #__eimza_challenges SET consumed_at = ? WHERE id = ?',

            [$nowSql, $challengeId]

        );



        return $row;

    }



    public function logAttempt(int $userId, string $tc, bool $ok, string $reason, string $certSerial, string $certFingerprint): void

    {

        $ip = isset($_SERVER['REMOTE_ADDR']) ? trim((string) $_SERVER['REMOTE_ADDR']) : '';

        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? trim((string) $_SERVER['HTTP_USER_AGENT']) : '';

        $this->db->insertPrepared('#__eimza_login_logs', [

            'user_id' => $userId > 0 ? $userId : null,

            'tc_kimlikno' => $tc,

            'success' => $ok ? 1 : 0,

            'reason' => $reason,

            'cert_serial' => $certSerial,

            'cert_fingerprint' => $certFingerprint,

            'ip_address' => $ip,

            'user_agent' => $ua,

            'created_at' => date('Y-m-d H:i:s'),

        ]);

    }



    public function failedAttemptsFromIp(string $ip, int $seconds): int

    {

        if ($ip === '' || $seconds < 1) {

            return 0;

        }

        $cutoff = date('Y-m-d H:i:s', time() - $seconds);

        $row = $this->db->fetchOnePrepared(

            'SELECT COUNT(*) AS cnt FROM #__eimza_login_logs

             WHERE success = 0 AND ip_address = ? AND created_at >= ?',

            [$ip, $cutoff]

        );



        return (int) ($row['cnt'] ?? 0);

    }



    public function failedAttemptsFromTc(string $tc, int $seconds): int

    {

        $tc = preg_replace('/\D+/', '', $tc);

        if ($tc === '' || $seconds < 1) {

            return 0;

        }

        $cutoff = date('Y-m-d H:i:s', time() - $seconds);

        $row = $this->db->fetchOnePrepared(

            'SELECT COUNT(*) AS cnt FROM #__eimza_login_logs

             WHERE success = 0 AND tc_kimlikno = ? AND created_at >= ?',

            [$tc, $cutoff]

        );



        return (int) ($row['cnt'] ?? 0);

    }

}



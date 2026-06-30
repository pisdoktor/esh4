<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\KpsTcKimlikClient;
use App\Helpers\PatientVefatCheckHelper;
use App\Helpers\ValidationHelper;
use App\Models\Patient;

/**
 * KPS / TC Kimlik Paylaşım Sistemi AJAX uç noktaları.
 */
class KpsController
{
    public function lookupAjax(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode([
                'ok' => false,
                'status' => 'unauthorized',
                'message' => 'Oturum gerekli.',
                'data' => null,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        AuthHelper::requireAdminJson();

        $tc = ValidationHelper::tcDigitsOnly($_GET['tc'] ?? '');
        $result = KpsTcKimlikClient::lookupByTc($tc);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function deathLookupAjax(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode([
                'ok' => false,
                'status' => 'unauthorized',
                'message' => 'Oturum gerekli.',
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        AuthHelper::requireAdminJson();

        $tc = ValidationHelper::tcDigitsOnly($_GET['tc'] ?? '');
        $patient = (new Patient())->findByTc($tc);
        if ($patient) {
            $query = PatientVefatCheckHelper::queryDeathForPatient($patient);
            echo json_encode([
                'ok' => !empty($query['deceased']),
                'status' => (string) ($query['status'] ?? 'alive'),
                'deceased' => !empty($query['deceased']),
                'olumTarihi' => $query['olumTarihi'] ?? null,
                'source' => (string) ($query['source'] ?? 'none'),
                'message' => (string) ($query['message'] ?? ''),
                'resolved_source' => PatientVefatCheckHelper::resolveSource(),
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $result = KpsTcKimlikClient::checkDeathByTc($tc);
        echo json_encode(array_merge($result, [
            'source' => 'kps',
            'resolved_source' => PatientVefatCheckHelper::resolveSource(),
        ]), JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function testConnection(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        AuthHelper::requireSuperAdmin();

        $result = KpsTcKimlikClient::testConnection();
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

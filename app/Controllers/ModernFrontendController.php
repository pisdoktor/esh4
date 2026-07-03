<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Helpers\AuthHelper;
use App\Helpers\ModernFrontendHelper;
use App\Helpers\TenantSqlHelper;
use App\Models\PlannedVisit;

/**
 * Modern frontend pilot — oturum tabanlı JSON (Bearer token gerekmez).
 */
class ModernFrontendController
{
    public function pilotData(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Oturum gerekli'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $scope = strtolower(trim((string) ($_GET['scope'] ?? 'dashboard')));
        if ($scope === 'dashboard' && !ModernFrontendHelper::dashboardPilotActive()) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Dashboard pilot kapalı.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($scope === 'planning' && !ModernFrontendHelper::planningPilotActive()) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Planlama pilot kapalı.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($scope === 'planning') {
            echo json_encode($this->planningPayload(), JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode($this->dashboardPayload(), JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboardPayload(): array
    {
        $date = trim((string) ($_GET['date'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }
        $model = new PlannedVisit();
        $payload = $model->getDailyPlans($date);
        $sections = is_array($payload['sections'] ?? null) ? $payload['sections'] : [];
        $totalTasks = 0;
        foreach ($sections as $sec) {
            if (!is_array($sec)) {
                continue;
            }
            $tasks = is_array($sec['tasks'] ?? null) ? $sec['tasks'] : [];
            $totalTasks += count($tasks);
        }
        $mernisCount = count($model->getDailyPlanUniquePatients($date));

        return [
            'ok' => true,
            'scope' => 'dashboard',
            'date' => $date,
            'date_label' => date('d.m.Y', strtotime($date)),
            'summary' => [
                'section_count' => count($sections),
                'task_count' => $totalTasks,
                'mernis_patient_count' => $mernisCount,
            ],
            'legacy_url' => esh_url('Dashboard', 'getDailyEvents', ['date' => $date]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function planningPayload(): array
    {
        $db = Database::getInstance();
        $today = date('Y-m-d');
        $weekEnd = date('Y-m-d', strtotime('+7 days'));
        $kurumSql = TenantSqlHelper::andBare('kurum_id');
        $status = strtolower(trim((string) ($_GET['status'] ?? 'all')));
        if (!in_array($status, ['all', 'pending', 'today'], true)) {
            $status = 'all';
        }
        $sort = strtolower(trim((string) ($_GET['sort'] ?? 'date_asc')));
        if (!in_array($sort, ['date_asc', 'date_desc', 'zaman_asc', 'zaman_desc'], true)) {
            $sort = 'date_asc';
        }

        $pending = (int) $db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__pizlemler WHERE COALESCE(durum, 0) = 0' . $kurumSql,
            []
        );
        $todayCount = (int) $db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__pizlemler WHERE planlanantarih = ?' . $kurumSql,
            [$today]
        );
        $weekCount = (int) $db->loadResultPrepared(
            'SELECT COUNT(*) FROM #__pizlemler WHERE planlanantarih >= ? AND planlanantarih <= ?' . $kurumSql,
            [$today, $weekEnd]
        );
        $where = [];
        $params = [];
        if ($status === 'pending') {
            $where[] = 'COALESCE(durum, 0) = 0';
        } elseif ($status === 'today') {
            $where[] = 'planlanantarih = ?';
            $params[] = $today;
        }
        $orderByMap = [
            'date_asc' => 'planlanantarih ASC, zaman ASC',
            'date_desc' => 'planlanantarih DESC, zaman DESC',
            'zaman_asc' => 'zaman ASC, planlanantarih ASC',
            'zaman_desc' => 'zaman DESC, planlanantarih ASC',
        ];
        $whereSql = ' WHERE 1=1';
        if ($where !== []) {
            $whereSql .= ' AND ' . implode(' AND ', $where);
        }
        $sampleSql = 'SELECT id, planlanantarih, zaman, hastatckimlik, durum
            FROM #__pizlemler'
            . $whereSql
            . TenantSqlHelper::andBare('kurum_id')
            . ' ORDER BY ' . ($orderByMap[$sort] ?? $orderByMap['date_asc'])
            . ' LIMIT 20';
        $sampleRows = $db->fetchObjectListPrepared($sampleSql, $params);
        if (!is_array($sampleRows)) {
            $sampleRows = [];
        }

        return [
            'ok' => true,
            'scope' => 'planning',
            'filter' => $status,
            'sort' => $sort,
            'summary' => [
                'pending' => $pending,
                'today' => $todayCount,
                'next_7_days' => $weekCount,
            ],
            'rows' => $sampleRows,
            'list_url' => esh_url('PlannedVisit', 'index'),
        ];
    }
}

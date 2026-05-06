<?php

declare(strict_types=1);

final class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();

        $recipientModel = new Recipient();
        $campaignModel = new Campaign();
        $sendSessionModel = new SendSession();
        $analytics = new CampaignAnalytics();

        $search = trim((string) $this->get('campaign_q', ''));
        $status = trim((string) $this->get('campaign_status', ''));
        $allowedStatuses = ['', 'draft', 'active', 'completed', 'cancelled'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = '';
        }

        // Detalle por campaña: paginado fijo de 15 por página.
        // No se expone selector de cantidad en la interfaz para evitar ruido operativo.
        $perPage = 15;

        $page = max(1, (int) $this->get('campaign_page', 1));
        $totalCampaignRows = $analytics->campaignPerformanceCount($search, $status);
        $totalPages = max(1, (int) ceil($totalCampaignRows / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
        }
        $offset = ($page - 1) * $perPage;

        $campaigns = $campaignModel->all();
        $latestSession = $this->latestSession($sendSessionModel, $campaigns);
        $dashboardTotals = $analytics->dashboardTotals();
        $campaignPerformance = $analytics->campaignPerformance($perPage, $search, $status, $offset);

        $this->view('dashboard/index', [
            'title' => 'Panel principal',
            'user' => Auth::user(),
            'activeRecipients' => (int) ($dashboardTotals['active_recipients'] ?? $recipientModel->countActive()),
            'campaignsCount' => (int) ($dashboardTotals['campaigns'] ?? count($campaigns)),
            'latestSession' => $latestSession,
            'dashboardTotals' => $dashboardTotals,
            'campaignPerformance' => $campaignPerformance,
            'campaignFilters' => [
                'search' => $search,
                'status' => $status,
                'per_page' => $perPage,
                'page' => $page,
                'total' => $totalCampaignRows,
                'total_pages' => $totalPages,
                'from' => $totalCampaignRows > 0 ? $offset + 1 : 0,
                'to' => $totalCampaignRows > 0 ? min($offset + $perPage, $totalCampaignRows) : 0,
            ],
        ]);
    }

    private function latestSession(SendSession $sendSessionModel, array $campaigns): ?array
    {
        $latest = null;
        foreach ($campaigns as $campaign) {
            $session = $sendSessionModel->latestByCampaign((int) $campaign['id']);
            if (!$session) {
                continue;
            }
            if ($latest === null || (int) $session['id'] > (int) $latest['id']) {
                $latest = $session;
            }
        }

        return $latest;
    }
}

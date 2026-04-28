<?php

declare(strict_types=1);

final class DashboardController extends Controller
{
    public function index(): void
    {
        $recipientModel = new Recipient();
        $campaignModel = new Campaign();
        $sendSessionModel = new SendSession();
        $analytics = new CampaignAnalytics();

        $campaigns = $campaignModel->all();
        $latestSession = $this->latestSession($sendSessionModel, $campaigns);
        $dashboardTotals = $analytics->dashboardTotals();
        $campaignPerformance = $analytics->campaignPerformance(8);

        $this->view('dashboard/index', [
            'title' => 'Panel principal',
            'user' => Auth::user(),
            'activeRecipients' => (int) ($dashboardTotals['active_recipients'] ?? $recipientModel->countActive()),
            'campaignsCount' => (int) ($dashboardTotals['campaigns'] ?? count($campaigns)),
            'latestSession' => $latestSession,
            'dashboardTotals' => $dashboardTotals,
            'campaignPerformance' => $campaignPerformance,
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

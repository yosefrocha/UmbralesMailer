<?php

declare(strict_types=1);

final class DashboardController extends Controller
{
    public function index(): void
    {
        $recipientModel = new Recipient();
        $campaignModel = new Campaign();
        $sendSessionModel = new SendSession();
        $campaigns = $campaignModel->all();
        $latestSession = !empty($campaigns) ? $sendSessionModel->latestByCampaign((int) $campaigns[0]['id']) : null;

        $this->view('dashboard/index', [
            'title' => 'Panel principal',
            'user' => Auth::user(),
            'activeRecipients' => $recipientModel->countActive(),
            'campaignsCount' => count($campaigns),
            'latestSession' => $latestSession,
        ]);
    }
}

<?php

declare(strict_types=1);

final class App
{
    private Router $router;

    public function __construct()
    {
        $this->router = new Router();
        $this->registerRoutes();
    }

    public function run(): void
    {
        $this->router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $this->currentPath());
    }

    private function currentPath(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return $uri ?: '/';
    }

    private function registerRoutes(): void
    {
        $this->router->get('/', [DashboardController::class, 'index'], [Auth::class, 'requireAuth']);

        $this->router->get('/login', [AuthController::class, 'showLogin'], [Auth::class, 'requireGuest']);
        $this->router->post('/login', [AuthController::class, 'login'], [Auth::class, 'requireGuest']);
        $this->router->post('/logout', [AuthController::class, 'logout'], [Auth::class, 'requireAuth']);

        $this->router->get('/profile/password', [ProfileController::class, 'showPasswordForm'], [Auth::class, 'requireAuth']);
        $this->router->post('/profile/password', [ProfileController::class, 'updatePassword'], [Auth::class, 'requireAuth']);

        $this->router->get('/users', [UsersController::class, 'index'], [Auth::class, 'requireAdmin']);
        $this->router->get('/users/create', [UsersController::class, 'create'], [Auth::class, 'requireAdmin']);
        $this->router->post('/users/store', [UsersController::class, 'store'], [Auth::class, 'requireAdmin']);
        $this->router->get('/users/{id}/edit', [UsersController::class, 'edit'], [Auth::class, 'requireAdmin']);
        $this->router->post('/users/{id}/update', [UsersController::class, 'update'], [Auth::class, 'requireAdmin']);
        $this->router->post('/users/{id}/toggle', [UsersController::class, 'toggle'], [Auth::class, 'requireAdmin']);
        $this->router->post('/users/{id}/temp-password', [UsersController::class, 'generateTempPassword'], [Auth::class, 'requireAdmin']);

        $this->router->get('/recipients', [RecipientsController::class, 'index'], [Auth::class, 'requireAuth']);
        $this->router->get('/recipients/create', [RecipientsController::class, 'create'], [Auth::class, 'requireAdmin']);
        $this->router->post('/recipients/store', [RecipientsController::class, 'store'], [Auth::class, 'requireAdmin']);
        $this->router->get('/recipients/{id}/edit', [RecipientsController::class, 'edit'], [Auth::class, 'requireAdmin']);
        $this->router->post('/recipients/{id}/update', [RecipientsController::class, 'update'], [Auth::class, 'requireAdmin']);
        $this->router->post('/recipients/import', [RecipientsController::class, 'import'], [Auth::class, 'requireAdmin']);
        $this->router->get('/recipients/template', [RecipientsController::class, 'downloadTemplate'], [Auth::class, 'requireAuth']);

        $this->router->get('/campaigns', [CampaignsController::class, 'index'], [Auth::class, 'requireAuth']);
        $this->router->get('/campaigns/create', [CampaignsController::class, 'create'], [Auth::class, 'requireAuth']);
        $this->router->post('/campaigns/store', [CampaignsController::class, 'store'], [Auth::class, 'requireAuth']);
        $this->router->get('/campaigns/{id}', [CampaignsController::class, 'show'], [Auth::class, 'requireAuth']);
        $this->router->get('/campaigns/{id}/edit', [CampaignsController::class, 'edit'], [Auth::class, 'requireAuth']);
        $this->router->post('/campaigns/{id}/update', [CampaignsController::class, 'update'], [Auth::class, 'requireAuth']);
        $this->router->post('/campaigns/{id}/activate', [CampaignsController::class, 'activate'], [Auth::class, 'requireAdmin']);
        $this->router->post('/campaigns/{id}/deactivate', [CampaignsController::class, 'deactivate'], [Auth::class, 'requireAdmin']);
        $this->router->post('/campaigns/{id}/delete', [CampaignsController::class, 'delete'], [Auth::class, 'requireAdmin']);

        $this->router->get('/campaigns/{id}/message', [CampaignsController::class, 'messageForm'], [Auth::class, 'requireAuth']);
        $this->router->post('/campaigns/{id}/message', [CampaignsController::class, 'saveMessage'], [Auth::class, 'requireAuth']);

        $this->router->get('/campaigns/{id}/send', [CampaignsController::class, 'sendSetup'], [Auth::class, 'requireAuth']);
        $this->router->post('/campaigns/{id}/send/start', [CampaignsController::class, 'startSend'], [Auth::class, 'requireAdmin']);

        $this->router->get('/campaigns/{id}/recipients', [CampaignsController::class, 'recipients'], [Auth::class, 'requireAuth']);
        $this->router->post('/campaigns/{id}/recipients/import', [CampaignsController::class, 'importRecipients'], [Auth::class, 'requireAdmin']);
        $this->router->post('/campaigns/{id}/recipients/{recipientId}/remove', [CampaignsController::class, 'removeRecipient'], [Auth::class, 'requireAdmin']);

        $this->router->get('/campaigns/{id}/sequence', [CampaignsController::class, 'redirectSequenceToMessage'], [Auth::class, 'requireAuth']);

        $this->router->get('/sending/{id}', [SendingController::class, 'show'], [Auth::class, 'requireAuth']);
        $this->router->get('/sending/{id}/status', [SendingController::class, 'status'], [Auth::class, 'requireAuth']);
        $this->router->post('/sending/{id}/process', [SendingController::class, 'process'], [Auth::class, 'requireAdmin']);
        $this->router->post('/sending/{id}/pause', [SendingController::class, 'pause'], [Auth::class, 'requireAdmin']);
        $this->router->post('/sending/{id}/resume', [SendingController::class, 'resume'], [Auth::class, 'requireAdmin']);

        $this->router->get('/settings', [SettingsController::class, 'index'], [Auth::class, 'requireAdmin']);
        $this->router->post('/settings/save', [SettingsController::class, 'save'], [Auth::class, 'requireAdmin']);

        $this->router->get('/unsubscribe/{token}', [PublicController::class, 'unsubscribe']);
    }
}
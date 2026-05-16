<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\DashboardRepository;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        if (!$this->authService()->isAuthenticated()) {
            return $this->redirect('/login');
        }

        return Response::html($this->view('dashboard.index', [
            'title' => 'Panel główny',
        ]));
    }

    public function apiDashboard(Request $request): Response
    {
        if ($response = $this->requireAuthJson()) {
            return $response;
        }

        $user = $this->currentUser();
        $stats = $this->dashboardService()->getStats(
            $user['id'],
            $user['role'] === 'admin',
        );

        return $this->json($stats);
    }

    private function dashboardService(): DashboardService
    {
        return new DashboardService(new DashboardRepository($this->db()));
    }
}

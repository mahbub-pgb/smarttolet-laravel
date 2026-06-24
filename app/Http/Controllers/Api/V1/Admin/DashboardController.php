<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private AdminDashboardService $dashboard) {}

    /** GET /admin/dashboard */
    public function index(): JsonResponse
    {
        return $this->ok($this->dashboard->cards(), 'OK');
    }

    /** GET /admin/dashboard/charts?days= */
    public function charts(Request $request): JsonResponse
    {
        $days = (int) $request->integer('days', 30);
        $days = max(7, min(365, $days));

        return $this->ok($this->dashboard->charts($days), 'OK');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\Admin\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    /** GET /admin/reports */
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->reports->paginate(
            $request->only('status'),
            (int) $request->integer('limit', 20),
            (int) $request->integer('page', 1),
        );

        return $this->paginatedResponse($paginator, 'OK');
    }

    /** PATCH /admin/reports/{report} */
    public function resolve(Request $request, Report $report): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in([Report::STATUS_RESOLVED, Report::STATUS_DISMISSED])],
            'takedown' => ['sometimes', 'boolean'],
        ]);

        $report = $this->reports->resolve(
            $request->user(),
            $report,
            $data['status'],
            (bool) ($data['takedown'] ?? false),
        );

        return $this->ok($report, 'Report updated.');
    }
}

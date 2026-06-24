<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentLedgerController extends Controller
{
    /** GET /admin/payments — payment ledger (manage_payments / super_admin). */
    public function index(Request $request): JsonResponse
    {
        $paginator = Payment::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->value()))
            ->when($request->filled('gateway'), fn ($q) => $q->where('gateway', $request->string('gateway')->value()))
            ->with('user:id,name,mobile')
            ->latest()
            ->paginate(
                perPage: (int) $request->integer('limit', 25),
                page: (int) $request->integer('page', 1),
            );

        return $this->paginatedResponse($paginator, 'OK');
    }
}

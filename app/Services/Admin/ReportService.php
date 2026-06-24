<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Exceptions\ApiException;
use App\Models\Listing;
use App\Models\Report;
use App\Models\User;
use App\Services\Listing\ListingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReportService
{
    public function __construct(private ListingService $listings) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Report>
     */
    public function paginate(array $filters, int $perPage, int $page): LengthAwarePaginator
    {
        return Report::query()
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->with(['reporter:id,name,mobile', 'listing:id,title,slug,status'])
            ->latest()
            ->paginate(perPage: $perPage, page: $page);
    }

    /**
     * Resolve or dismiss a report. When resolving with `takedown`, the offending
     * listing is rejected.
     */
    public function resolve(User $staff, Report $report, string $status, bool $takedown = false): Report
    {
        if (! in_array($status, [Report::STATUS_RESOLVED, Report::STATUS_DISMISSED], true)) {
            throw ApiException::badRequest('Invalid report status.', 'invalid_status');
        }

        $report->forceFill([
            'status' => $status,
            'resolver_id' => $staff->id,
            'resolved_at' => now(),
        ])->save();

        if ($status === Report::STATUS_RESOLVED && $takedown) {
            $listing = Listing::find($report->listing_id);
            if ($listing) {
                $this->listings->moderate($listing, 'reject', 'Removed following a report.');
            }
        }

        return $report->refresh()->load(['reporter:id,name,mobile', 'listing:id,title,slug,status']);
    }
}

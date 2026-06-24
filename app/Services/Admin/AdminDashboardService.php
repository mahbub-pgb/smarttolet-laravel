<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Listing;
use App\Models\Payment;
use App\Models\Report;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    /**
     * Headline analytics cards.
     *
     * @return array<string, mixed>
     */
    public function cards(): array
    {
        return [
            'users' => [
                'total' => User::count(),
                'suspended' => User::where('is_suspended', true)->count(),
                'staff' => User::whereIn('role', ['moderator', 'admin', 'super_admin'])->count(),
                'new_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'listings' => [
                'total' => Listing::count(),
                'pending' => Listing::where('status', Listing::STATUS_PENDING)->count(),
                'approved' => Listing::where('status', Listing::STATUS_APPROVED)->count(),
                'rejected' => Listing::where('status', Listing::STATUS_REJECTED)->count(),
                'rented' => Listing::where('status', Listing::STATUS_RENTED)->count(),
            ],
            'reports' => [
                'open' => Report::where('status', Report::STATUS_OPEN)->count(),
                'total' => Report::count(),
            ],
            'revenue' => [
                'completed_payments' => Payment::where('status', Payment::STATUS_COMPLETED)->count(),
                'total_amount' => (float) Payment::where('status', Payment::STATUS_COMPLETED)->sum('amount'),
                'this_month' => (float) Payment::where('status', Payment::STATUS_COMPLETED)
                    ->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
            ],
            'subscriptions' => Subscription::where('status', 'active')
                ->select('plan', DB::raw('count(*) as count'))
                ->groupBy('plan')
                ->pluck('count', 'plan'),
        ];
    }

    /**
     * Growth charts (daily buckets) for the last $days days.
     *
     * @return array<string, mixed>
     */
    public function charts(int $days = 30): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        return [
            'range_days' => $days,
            'users' => $this->dailySeries(User::query(), 'created_at', $from, $days),
            'listings' => $this->dailySeries(Listing::query(), 'created_at', $from, $days),
            'revenue' => $this->dailySeries(
                Payment::query()->where('status', Payment::STATUS_COMPLETED),
                'paid_at',
                $from,
                $days,
                'amount',
            ),
        ];
    }

    /**
     * Build a zero-filled daily series. Uses date-only grouping (portable
     * across MySQL/SQLite).
     *
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $query
     * @return array<int, array{date: string, value: float|int}>
     */
    private function dailySeries($query, string $column, Carbon $from, int $days, ?string $sumColumn = null): array
    {
        $rows = (clone $query)
            ->where($column, '>=', $from)
            ->get([$column, ...($sumColumn ? [$sumColumn] : [])]);

        $buckets = [];
        for ($i = 0; $i < $days; $i++) {
            $buckets[$from->copy()->addDays($i)->toDateString()] = 0;
        }

        foreach ($rows as $row) {
            $value = $row->{$column};
            if ($value === null) {
                continue;
            }
            $date = Carbon::parse($value)->toDateString();
            if (! array_key_exists($date, $buckets)) {
                continue;
            }
            $buckets[$date] += $sumColumn ? (float) $row->{$sumColumn} : 1;
        }

        return array_map(
            fn ($date, $value) => ['date' => $date, 'value' => $value],
            array_keys($buckets),
            array_values($buckets),
        );
    }
}

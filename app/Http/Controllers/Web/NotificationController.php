<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Notification\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $notifications) {}

    /** GET /notifications — the signed-in user's notification feed. */
    public function index(Request $request): View
    {
        $notifications = $this->notifications
            ->paginateFor($request->user(), 20, max(1, $request->integer('page', 1)))
            ->withQueryString();

        return view('notifications.index', compact('notifications'));
    }

    /** POST /notifications/{notification}/read */
    public function read(Request $request, int $notification): RedirectResponse
    {
        $this->notifications->markRead($request->user(), $notification);

        return back();
    }

    /** POST /notifications/read-all */
    public function readAll(Request $request): RedirectResponse
    {
        $this->notifications->markAllRead($request->user());

        return back()->with('status', 'All notifications marked as read.');
    }
}

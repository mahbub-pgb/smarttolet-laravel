<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Services\Media\MediaService;
use App\Services\Page\PageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Server-rendered static-page management for staff with the `manage_pages`
 * permission. Validation is done inline (rather than via API form requests)
 * because the web layer authenticates on the session `web` guard. Mirrors the
 * admin blog controller.
 */
class PageController extends Controller
{
    public function __construct(private PageService $pages, private MediaService $media) {}

    /** GET /admin/pages — list of every page (draft + published). */
    public function index(Request $request): View
    {
        $status = $request->string('status')->value();

        $pages = Page::query()
            ->with('author:id,name')
            ->when(in_array($status, ['draft', 'published'], true), fn ($q) => $q->where('status', $status))
            ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $counts = Page::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.pages.index', compact('pages', 'counts', 'status'));
    }

    /** GET /admin/pages/create */
    public function create(): View
    {
        return view('admin.pages.form', [
            'page' => new Page(['status' => Page::STATUS_DRAFT]),
        ]);
    }

    /** POST /admin/pages */
    public function store(Request $request): RedirectResponse
    {
        $page = $this->pages->create(auth('web')->user(), $this->validateData($request));

        return redirect()->route('admin.pages.index')
            ->with('status', "“{$page->title}” saved.");
    }

    /** GET /admin/pages/{page}/edit */
    public function edit(Page $page): View
    {
        return view('admin.pages.form', compact('page'));
    }

    /** PUT /admin/pages/{page} */
    public function update(Request $request, Page $page): RedirectResponse
    {
        $this->pages->update($page, $this->validateData($request));

        return redirect()->route('admin.pages.index')
            ->with('status', "“{$page->title}” updated.");
    }

    /** DELETE /admin/pages/{page} */
    public function destroy(Page $page): RedirectResponse
    {
        $title = $page->title;
        $this->pages->delete($page);

        return back()->with('status', "“{$title}” deleted.");
    }

    /**
     * POST /admin/pages/upload-image — image drop target for CKEditor's upload
     * adapter. Returns the stored URL in the shape the adapter expects.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'upload' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,gif', 'max:5120'],
        ]);

        $media = $this->media->upload(auth('web')->user(), $request->file('upload'), 'pages');

        return response()->json(['url' => $media->url]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'body' => ['required', 'string'],
            'status' => ['required', Rule::in([Page::STATUS_DRAFT, Page::STATUS_PUBLISHED])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [], [
            'meta_description' => 'meta description',
        ]);

        $data['meta_description'] = $data['meta_description'] ?: null;
        $data['show_in_header'] = $request->boolean('show_in_header');
        $data['show_in_footer'] = $request->boolean('show_in_footer');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}

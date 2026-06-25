<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\UpdateProfileRequest;
use App\Services\Media\ImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private ImageService $images) {}

    /** GET /dashboard/profile — "Profile Settings" tab. */
    public function edit(Request $request): View
    {
        return view('dashboard.profile', ['user' => $request->user()]);
    }

    /** PUT /dashboard/profile — update the signed-in user's profile. */
    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // The raw uploaded file is never a column — replace it with a URL.
        unset($data['photo']);
        if ($request->hasFile('photo')) {
            $stored = $this->images->upload($request->file('photo'), 'avatars');
            if ($stored !== null) {
                $data['photo'] = $stored['url'];
            }
        }

        // Only overwrite the password when a new one was supplied.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->fill($data)->save();

        return redirect()->route('dashboard.profile')->with('status', 'Profile updated.');
    }
}

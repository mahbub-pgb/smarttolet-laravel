<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdvertisementRequest;
use App\Models\Advertisement;
use App\Services\Admin\AdvertisementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    public function __construct(private AdvertisementService $ads) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->ads->paginate(
            (int) $request->integer('limit', 20),
            (int) $request->integer('page', 1),
        );

        return $this->paginatedResponse($paginator, 'OK');
    }

    public function store(AdvertisementRequest $request): JsonResponse
    {
        return $this->created($this->ads->create($request->validated()), 'Advertisement created.');
    }

    public function update(AdvertisementRequest $request, Advertisement $advertisement): JsonResponse
    {
        return $this->ok($this->ads->update($advertisement, $request->validated()), 'Advertisement updated.');
    }

    public function destroy(Advertisement $advertisement): JsonResponse
    {
        $this->ads->delete($advertisement);

        return $this->noContentResponse('Advertisement deleted.');
    }
}

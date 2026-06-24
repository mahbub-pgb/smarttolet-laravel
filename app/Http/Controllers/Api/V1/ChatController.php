<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Services\Chat\ConversationService;
use App\Services\Chat\MessageService;
use App\Events\UserTyping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        private ConversationService $conversations,
        private MessageService $messages,
    ) {}

    /** GET /chat/conversations */
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->conversations->listForUser(
            $request->user(),
            (int) $request->integer('limit', 20),
            (int) $request->integer('page', 1),
        );

        return $this->paginatedResponse($paginator, 'OK', fn ($items) => ConversationResource::collection($items));
    }

    /** POST /chat/conversations — find or create. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer'],
            'listing_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $conversation = $this->conversations->findOrCreate(
            $request->user(),
            (int) $data['user_id'],
            isset($data['listing_id']) ? (int) $data['listing_id'] : null,
        );

        $conversation->load(['participantA:id,name,photo', 'participantB:id,name,photo', 'listing:id,title,slug']);

        return $this->ok(new ConversationResource($conversation), 'OK');
    }

    /** GET /chat/conversations/{id}/messages */
    public function messages(Request $request, int $id): JsonResponse
    {
        $conversation = $this->conversations->authorizeAccess($request->user(), $id);

        // Opening a conversation marks the peer's messages delivered+read.
        $this->messages->markRead($conversation, $request->user());

        $paginator = $this->messages->paginate(
            $conversation,
            (int) $request->integer('limit', 30),
            (int) $request->integer('page', 1),
        );

        return $this->paginatedResponse($paginator, 'OK', fn ($items) => MessageResource::collection($items));
    }

    /** POST /chat/conversations/{id}/messages */
    public function send(Request $request, int $id): JsonResponse
    {
        $conversation = $this->conversations->authorizeAccess($request->user(), $id);

        $data = $request->validate(['body' => ['required', 'string', 'max:4000']]);

        $message = $this->messages->send($conversation, $request->user(), $data['body']);

        return $this->created(new MessageResource($message), 'Message sent.');
    }

    /** POST /chat/conversations/{id}/read */
    public function read(Request $request, int $id): JsonResponse
    {
        $conversation = $this->conversations->authorizeAccess($request->user(), $id);
        $count = $this->messages->markRead($conversation, $request->user());

        return $this->ok(['read' => $count], 'OK');
    }

    /** POST /chat/conversations/{id}/typing */
    public function typing(Request $request, int $id): JsonResponse
    {
        $conversation = $this->conversations->authorizeAccess($request->user(), $id);
        $isTyping = $request->boolean('typing', true);

        broadcast(new UserTyping($conversation->id, $request->user()->id, $isTyping))->toOthers();

        return $this->ok(null, 'OK');
    }
}

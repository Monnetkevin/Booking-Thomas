<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Messages\ListContactMessagesRequest;
use App\Http\Requests\Admin\Messages\UpdateContactMessageRequest;
use App\Http\Resources\Admin\ContactMessageResource;
use App\Models\ContactMessage;
use App\Services\Contact\ContactInboxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/** Messages du formulaire de contact : lecture et suivi (lu, non lu). */
class ContactMessageController extends Controller
{
    public function __construct(private readonly ContactInboxService $inbox) {}

    public function index(ListContactMessagesRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', ContactMessage::class);

        return ContactMessageResource::collection($this->inbox->list($request->unreadOnly()))
            ->additional(['meta' => ['unread' => $this->inbox->unreadCount()]]);
    }

    public function unreadCount(): JsonResponse
    {
        Gate::authorize('viewAny', ContactMessage::class);

        return response()->json(['data' => ['count' => $this->inbox->unreadCount()]]);
    }

    public function update(UpdateContactMessageRequest $request, ContactMessage $message): ContactMessageResource
    {
        Gate::authorize('update', $message);

        return new ContactMessageResource($this->inbox->setRead($message, $request->read()));
    }
}

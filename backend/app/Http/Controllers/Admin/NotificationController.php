<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Notifications\ListNotificationsRequest;
use App\Http\Resources\Admin\NotificationResource;
use App\Models\Notification;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/** Centre de notifications du compte connecté (hôte ou ménage). */
class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(ListNotificationsRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Notification::class);

        return NotificationResource::collection($this->notifications->list($this->user($request), $request->unreadOnly()));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Notification::class);

        return response()->json(['data' => ['count' => $this->notifications->unreadCount($this->user($request))]]);
    }

    public function read(Notification $notification): NotificationResource
    {
        Gate::authorize('update', $notification);

        return new NotificationResource($this->notifications->markRead($notification));
    }

    public function readAll(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Notification::class);

        return response()->json(['data' => ['marked' => $this->notifications->markAllRead($this->user($request))]]);
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}

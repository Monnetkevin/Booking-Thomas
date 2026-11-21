<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Users\StoreUserRequest;
use App\Http\Requests\Admin\Users\UpdateUserRequest;
use App\Http\Resources\Admin\AccountResource;
use App\Models\User;
use App\Services\Users\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/** Comptes du back-office : invitation du ménage, activation, lien de mot de passe. */
class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function index(): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        return AccountResource::collection($this->users->list());
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        Gate::authorize('create', User::class);

        $user = $this->users->invite($request->string('name')->toString(), $request->string('email')->toString(), $request->role());

        return (new AccountResource($user))->response()->setStatusCode(201);
    }

    public function update(UpdateUserRequest $request, User $user): AccountResource
    {
        Gate::authorize('update', $user);

        /** @var User $actor */
        $actor = $request->user();

        return new AccountResource($this->users->update($actor, $user, $request->accountData()));
    }

    public function sendPasswordReset(User $user): Response
    {
        Gate::authorize('update', $user);

        $this->users->sendPasswordReset($user);

        return response()->noContent();
    }
}

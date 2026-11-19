<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Users\UserService;
use Illuminate\Http\Response;

/** Choix du mot de passe depuis le lien reçu par e-mail (route publique, limitée). */
class PasswordController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function update(ResetPasswordRequest $request): Response
    {
        $this->users->resetPassword(
            $request->string('email')->toString(),
            $request->string('token')->toString(),
            $request->string('password')->toString(),
        );

        return response()->noContent();
    }
}

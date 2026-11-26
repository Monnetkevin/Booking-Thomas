<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\ContactRequest;
use App\Services\Contact\ContactService;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function __construct(private readonly ContactService $contact) {}

    public function store(ContactRequest $request): JsonResponse
    {
        /** @var array{name: string, email: string, phone?: string|null, message: string, apartment?: string|null, website?: string|null} $data */
        $data = $request->safe()->only(['name', 'email', 'phone', 'message', 'apartment', 'website']);

        $this->contact->send($data, $request->input('captcha_token'), $request->ip(), app()->getLocale());

        return response()->json(['message' => __('contact.sent')], 201);
    }
}

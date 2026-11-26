<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\SubmitReviewRequest;
use App\Http\Resources\Public\ReviewInvitationResource;
use App\Services\Review\ReviewSubmissionService;
use Illuminate\Http\JsonResponse;

/**
 * Lien personnel reçu après le séjour : lire l'invitation, puis déposer l'avis. Le jeton
 * est la seule preuve (pas de compte voyageur) ; une seule erreur pour un lien inconnu,
 * expiré ou déjà utilisé.
 */
class ReviewInvitationController extends Controller
{
    public function __construct(private readonly ReviewSubmissionService $reviews) {}

    public function show(string $token): ReviewInvitationResource
    {
        return new ReviewInvitationResource($this->reviews->invitation($token));
    }

    public function store(SubmitReviewRequest $request, string $token): JsonResponse
    {
        $this->reviews->submit($token, $request->review());

        return response()->json(['message' => __('reviews.submitted')], 201);
    }
}

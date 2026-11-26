<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Reviews\ListReviewsRequest;
use App\Http\Requests\Admin\Reviews\RejectReviewRequest;
use App\Http\Requests\Admin\Reviews\ReplyReviewRequest;
use App\Http\Resources\Admin\AdminReviewResource;
use App\Models\Review;
use App\Services\Review\ReviewInvitationService;
use App\Services\Review\ReviewModerationService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/** Avis des voyageurs : file de modération, publication, refus, réponse, renvoi de l'invitation. */
class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewModerationService $moderation,
        private readonly ReviewInvitationService $invitations,
    ) {}

    public function index(ListReviewsRequest $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Review::class);

        return AdminReviewResource::collection($this->moderation->list($request->status(), $request->apartmentId()))
            ->additional(['meta' => ['counts' => $this->moderation->counts($request->apartmentId())]]);
    }

    public function publish(Review $review): AdminReviewResource
    {
        Gate::authorize('moderate', $review);

        return new AdminReviewResource($this->moderation->publish($review));
    }

    public function reject(RejectReviewRequest $request, Review $review): AdminReviewResource
    {
        Gate::authorize('moderate', $review);

        return new AdminReviewResource($this->moderation->reject($review, $request->reason()));
    }

    public function reply(ReplyReviewRequest $request, Review $review): AdminReviewResource
    {
        Gate::authorize('moderate', $review);

        return new AdminReviewResource($this->moderation->reply($review, $request->reply()));
    }

    public function resendInvitation(Review $review): AdminReviewResource
    {
        Gate::authorize('moderate', $review);

        return new AdminReviewResource($this->invitations->resend($review));
    }
}

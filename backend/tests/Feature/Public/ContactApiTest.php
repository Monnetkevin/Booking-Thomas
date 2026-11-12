<?php

use App\Mail\NewContactMessage;
use App\Models\Apartment;
use App\Models\ContactMessage;
use App\Repositories\Contracts\SettingRepositoryInterface;
use App\Services\Security\HumanVerifier;
use App\Services\Security\TurnstileVerifier;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    app(SettingRepositoryInterface::class)->put('notifications.admin_emails', ['hote@example.com']);
});

function contactPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Camille Martin',
        'email' => 'camille@example.com',
        'phone' => '06 12 34 56 78',
        'message' => 'Bonjour, le logement est-il libre pour les 24 Heures ?',
    ], $overrides);
}

it('enregistre le message et prévient l’hôte, réponse directe au visiteur', function () {
    $apartment = Apartment::factory()->create(['slug' => 'le-cocon']);

    $this->postJson('/api/contact', contactPayload(['apartment' => 'le-cocon']))
        ->assertCreated()
        ->assertJsonPath('message', 'Merci, votre message a bien été envoyé. Nous vous répondons rapidement.');

    $message = ContactMessage::sole();
    expect($message->apartment_id)->toBe($apartment->id)->and($message->read_at)->toBeNull();
    Mail::assertQueued(NewContactMessage::class, fn (NewContactMessage $mail) => $mail->hasTo('hote@example.com')
        && $mail->hasReplyTo('camille@example.com'));
});

it('ignore en silence un message dont le champ piège est rempli', function () {
    $this->postJson('/api/contact', contactPayload(['website' => 'http://spam.example']))->assertCreated();

    expect(ContactMessage::count())->toBe(0);
    Mail::assertNothingQueued();
});

it('refuse un message si le défi anti-robot échoue', function () {
    $this->app->instance(HumanVerifier::class, new class implements HumanVerifier
    {
        public function verify(?string $token, ?string $ip): bool
        {
            return false;
        }
    });

    $this->postJson('/api/contact', contactPayload())->assertJsonValidationErrors('captcha');
    expect(ContactMessage::count())->toBe(0);
});

it('valide les champs', function () {
    $this->postJson('/api/contact', contactPayload(['email' => 'pas-un-email', 'message' => 'Court', 'apartment' => 'inconnu']))
        ->assertJsonValidationErrors(['email', 'message', 'apartment']);
});

it('limite à 3 messages par minute et par IP', function () {
    foreach (range(1, 3) as $ignored) {
        $this->postJson('/api/contact', contactPayload())->assertCreated();
    }

    $this->postJson('/api/contact', contactPayload())->assertTooManyRequests();
});

describe('Turnstile', function () {
    it('est désactivé sans clé secrète', function () {
        expect((new TurnstileVerifier(app(Factory::class), null))->verify(null, '127.0.0.1'))->toBeTrue();
    });

    it('vérifie le jeton auprès de Cloudflare', function () {
        Http::fake(['challenges.cloudflare.com/*' => Http::sequence()->push(['success' => true])->push(['success' => false])]);
        $verifier = new TurnstileVerifier(app(Factory::class), 'secret');

        expect($verifier->verify('bon-jeton', '1.2.3.4'))->toBeTrue()
            ->and($verifier->verify('mauvais-jeton', '1.2.3.4'))->toBeFalse()
            ->and($verifier->verify(null, '1.2.3.4'))->toBeFalse();
        Http::assertSent(fn ($request) => $request['secret'] === 'secret' && $request['remoteip'] === '1.2.3.4');
    });
});

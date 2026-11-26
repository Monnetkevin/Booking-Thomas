<?php

use App\Mail\NewContactMessage;
use App\Mail\Previews\MailPreviewCatalog;
use App\Mail\Support\MailLinks;
use App\Mail\TransactionalMail;
use App\Models\ContactMessage;
use Illuminate\Contracts\Queue\ShouldQueue;

beforeEach(function () {
    config(['app.name' => 'Nom du site', 'booking.frontend_url' => 'https://exemple.fr']);
});

function contactMail(): NewContactMessage
{
    return new NewContactMessage(new ContactMessage([
        'name' => 'Camille Martin', 'email' => 'camille@example.com', 'message' => 'Est-il libre en juin ?',
    ]));
}

it('donne des liens du site dans la langue de l’e-mail', function () {
    expect(MailLinks::forLocale('fr'))->toBe([
        'home' => 'https://exemple.fr/',
        'contact' => 'https://exemple.fr/contact',
        'legal' => 'https://exemple.fr/mentions-legales',
    ])->and(MailLinks::forLocale('en')['home'])->toBe('https://exemple.fr/en')
        ->and(MailLinks::forLocale('en')['contact'])->toBe('https://exemple.fr/en/contact');
});

it('habille chaque e-mail : en-tête, pied de page, raison de l’envoi et thème du site', function () {
    $mail = contactMail();

    $mail->assertSeeInHtml('Nom du site')
        ->assertSeeInHtml('logo-mark', false)
        ->assertSeeInHtml('https://exemple.fr/mentions-legales', false)
        ->assertSeeInHtml('Message envoyé depuis le formulaire de contact du site.')
        // Thème mis en ligne : couleur du bouton du site dans le HTML.
        ->assertSeeInHtml('#2a2119', false)
        ->assertHasSubject('Nouveau message de Camille Martin')
        ->assertHasReplyTo('camille@example.com', 'Camille Martin');
});

it('fournit une version texte avec les liens utiles', function () {
    contactMail()
        ->assertSeeInText('Est-il libre en juin ?')
        ->assertSeeInText('https://exemple.fr/contact')
        ->assertDontSeeInText('<table');
});

it('envoie en file d’attente, après la validation de la transaction', function () {
    $mail = contactMail();

    expect($mail)->toBeInstanceOf(ShouldQueue::class)
        ->and($mail->afterCommit)->toBeTrue()
        ->and($mail->locale)->toBe('fr');
});

it('rend chaque modèle de l’aperçu en français et en anglais', function () {
    foreach (app(MailPreviewCatalog::class)->all() as $name => $preview) {
        foreach (TransactionalMail::LOCALES as $locale) {
            $mail = ($preview['build'])($locale)->locale($locale);
            expect($mail->render())->toContain('Nom du site')
                ->and($mail->renderText())->not->toBeEmpty();
        }
    }
});

it('n’expose pas l’aperçu des e-mails hors environnement local', function () {
    $this->get('/dev/mails')->assertNotFound();
});

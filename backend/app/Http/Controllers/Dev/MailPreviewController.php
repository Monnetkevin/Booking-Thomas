<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Mail\Previews\MailPreviewCatalog;
use App\Mail\TransactionalMail;
use App\Services\Booking\BookingConfirmationPdf;
use Illuminate\Http\Response;

/**
 * Aperçu des e-mails en local (/dev/mails) : liste des modèles, rendu HTML ou texte en
 * français et en anglais. Route déclarée seulement en environnement local.
 */
class MailPreviewController extends Controller
{
    public function __construct(private readonly MailPreviewCatalog $catalog) {}

    public function index(): Response
    {
        $rows = collect($this->catalog->all())->map(function (array $preview, string $name): string {
            $links = collect(TransactionalMail::LOCALES)->map(fn (string $locale) => sprintf(
                '<a href="/dev/mails/%1$s/%2$s">%3$s</a> · <a href="/dev/mails/%1$s/%2$s?format=text">texte</a>',
                $name, $locale, strtoupper($locale),
            ))->implode(' — ');

            return '<li><strong>'.e($preview['label']).'</strong> : '.$links.'</li>';
        })->implode('');

        $pdf = '<li><strong>Confirmation PDF</strong> : <a href="/dev/pdf/booking/fr">FR</a> — <a href="/dev/pdf/booking/en">EN</a></li>';

        return response('<!doctype html><meta charset="utf-8"><title>Aperçu des e-mails</title>'
            .'<body style="font-family:system-ui;padding:24px"><h1>Aperçu des e-mails</h1><ul>'.$rows.$pdf.'</ul></body>');
    }

    /** Confirmation PDF d'une réservation d'exemple, affichée dans le navigateur. */
    public function pdf(string $locale, BookingConfirmationPdf $pdf): Response
    {
        abort_unless(in_array($locale, TransactionalMail::LOCALES, true), 404);

        return response($pdf->render($this->catalog->sampleBooking($locale)), 200, ['Content-Type' => 'application/pdf']);
    }

    public function show(string $name, string $locale): Response
    {
        $build = $this->catalog->find($name);
        abort_if($build === null || ! in_array($locale, TransactionalMail::LOCALES, true), 404);

        $mail = $build($locale)->locale($locale);

        return request('format') === 'text'
            ? response($mail->renderText(), 200, ['Content-Type' => 'text/plain; charset=utf-8'])
            : response($mail->render());
    }
}

<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Mail\Previews\MailPreviewCatalog;
use App\Mail\TransactionalMail;
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

        return response('<!doctype html><meta charset="utf-8"><title>Aperçu des e-mails</title>'
            .'<body style="font-family:system-ui;padding:24px"><h1>Aperçu des e-mails</h1><ul>'.$rows.'</ul></body>');
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

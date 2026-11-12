<?php

namespace App\Mail\Previews;

use App\Mail\NewContactMessage;
use App\Mail\TransactionalMail;
use App\Models\Apartment;
use App\Models\ContactMessage;
use Closure;

/**
 * Modèles d'e-mails affichés par l'aperçu local (/dev/mails), avec des données d'exemple
 * jamais enregistrées. Un nouvel e-mail s'ajoute ici pour être relu avant envoi.
 */
class MailPreviewCatalog
{
    /**
     * @return array<string, array{label: string, build: Closure(string): TransactionalMail}>
     */
    public function all(): array
    {
        return [
            'contact' => [
                'label' => 'Nouveau message (hôte)',
                'build' => fn (string $locale): TransactionalMail => new NewContactMessage($this->sampleContact()),
            ],
        ];
    }

    /**
     * @return (Closure(string): TransactionalMail)|null
     */
    public function find(string $name): ?Closure
    {
        return $this->all()[$name]['build'] ?? null;
    }

    private function sampleContact(): ContactMessage
    {
        $contact = new ContactMessage([
            'name' => 'Camille Martin',
            'email' => 'camille@example.com',
            'phone' => '06 12 34 56 78',
            'message' => "Bonjour, le logement est-il disponible pour les 24 Heures du Mans ?\nNous serions deux adultes.",
        ]);
        $contact->setRelation('apartment', new Apartment(['name' => ['fr' => 'Le Cocon', 'en' => 'The Cocoon']]));

        return $contact;
    }
}

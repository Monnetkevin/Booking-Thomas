<?php

namespace App\Services\Booking;

/**
 * Remboursement proposé à l'annulation (spec « Annulation et remboursement ») : le palier
 * du barème s'applique à l'hébergement et aux options, ménage et taxe de séjour sont
 * rendus en entier ; jamais plus que ce qui a été encaissé. L'admin peut l'ajuster.
 */
final readonly class RefundProposal
{
    public function __construct(
        /** Jours entre aujourd'hui et l'arrivée (négatif si le séjour a commencé). */
        public int $daysBeforeArrival,
        /** Part remboursée de l'hébergement et des options, selon le barème. */
        public int $percent,
        /** Montant dû selon le barème, en centimes. */
        public int $byPolicy,
        /** Déjà encaissé (paiements réussis moins remboursements), en centimes. */
        public int $paid,
    ) {}

    /** Montant proposé : celui du barème, plafonné à ce qui a été encaissé. */
    public function amount(): int
    {
        return min($this->byPolicy, $this->paid);
    }

    /**
     * @return array{days_before_arrival: int, percent: int, by_policy: int, paid: int, amount: int}
     */
    public function toArray(): array
    {
        return [
            'days_before_arrival' => $this->daysBeforeArrival,
            'percent' => $this->percent,
            'by_policy' => $this->byPolicy,
            'paid' => $this->paid,
            'amount' => $this->amount(),
        ];
    }
}

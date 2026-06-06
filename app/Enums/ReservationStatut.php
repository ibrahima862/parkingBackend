<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case PENDING = 'en_attente';
    case CONFIRMED = 'confirme';
    case CANCELLED = 'annule';
    case COMPLETED = 'termine';
    case EXPIRED = 'expire';

    // Tu peux même ajouter des méthodes pour obtenir des labels lisibles ou des couleurs
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'En attente de paiement',
            self::CONFIRMED => 'Confirmée',
            self::CANCELLED => 'Annulée',
            self::COMPLETED => 'Terminée',
            self::EXPIRED => 'Expirée',
        };
    }
}
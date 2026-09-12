<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';

    public function isConfirmed(): bool
    {
        return $this === self::Confirmed;
    }
}

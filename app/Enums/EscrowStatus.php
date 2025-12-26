<?php

namespace App\Enums;

class EscrowStatus
{
    const CREATED   = 'created';
    const FUNDED    = 'funded';
    const SHIPPING  = 'shipping';
    const DELIVERED = 'delivered';
    const RELEASED  = 'released';
    const DISPUTED  = 'disputed';
    const REFUNDED  = 'refunded';
}

<?php

namespace App\Services;

use App\Enums\EscrowStatus;

class EscrowStateMachine
{
    public static function transitions(): array
    {
        return [
            EscrowStatus::CREATED => [
                EscrowStatus::FUNDED,
            ],

            EscrowStatus::FUNDED => [
                EscrowStatus::SHIPPING,
            ],

            EscrowStatus::SHIPPING => [
                EscrowStatus::DELIVERED,
            ],

            EscrowStatus::DELIVERED => [
                EscrowStatus::RELEASED,
                EscrowStatus::DISPUTED,
            ],

            EscrowStatus::DISPUTED => [
                EscrowStatus::RELEASED,
                EscrowStatus::REFUNDED,
            ],
        ];
    }

    public static function canTransition(string $from, string $to): bool
    {
        return in_array(
            $to,
            self::transitions()[$from] ?? []
        );
    }

    public static function allowedRoles(string $from, string $to): array
    {
        $map = [
            // Buyer
            'buyer' => [
                EscrowStatus::CREATED   => [EscrowStatus::FUNDED],
                EscrowStatus::DELIVERED => [
                    EscrowStatus::RELEASED,
                    EscrowStatus::DISPUTED
                ],
            ],

            // Seller
            'seller' => [
                EscrowStatus::FUNDED   => [EscrowStatus::SHIPPING],
                EscrowStatus::SHIPPING => [EscrowStatus::DELIVERED],
            ],

            // Arbiter
            'arbiter' => [
                EscrowStatus::DISPUTED => [
                    EscrowStatus::RELEASED,
                    EscrowStatus::REFUNDED
                ],
            ],

            // System (scheduler)
            'system' => [
                EscrowStatus::DELIVERED => [EscrowStatus::RELEASED],
            ],
        ];

        return $map;
    }

    public static function canTransitionByRole(
        string $from,
        string $to,
        string $role
    ): bool {
        // 1. Cek status boleh pindah atau nggak
        if (! self::canTransition($from, $to)) {
            return false;
        }

        // 2. Cek role boleh atau nggak
        $rolesMap = self::allowedRoles($from, $to);

        return in_array(
            $to,
            $rolesMap[$role][$from] ?? []
        );
    }
}

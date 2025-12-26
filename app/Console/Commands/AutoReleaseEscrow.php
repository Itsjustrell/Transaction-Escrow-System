<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Escrow;
use App\Services\EscrowStateMachine;
use App\Enums\EscrowStatus;
use Illuminate\Support\Facades\DB;

class AutoReleaseEscrow extends Command
{
    protected $signature = 'escrow:auto-release';
    protected $description = 'Auto release escrow when confirmation deadline is passed';

    public function handle()
    {
        $now = now();

        // 1. Ambil escrow yang eligible
        $escrows = Escrow::where('status', EscrowStatus::DELIVERED)
            ->whereNotNull('confirm_deadline')
            ->where('confirm_deadline', '<', $now)
            ->get();

        foreach ($escrows as $escrow) {

            // 2. Double check via state machine
            if (! EscrowStateMachine::canTransitionByRole(
                $escrow->status,
                EscrowStatus::RELEASED,
                'system'
            )) {
                continue;
            }

            DB::transaction(function () use ($escrow) {

                // Update status
                $escrow->update([
                    'status' => EscrowStatus::RELEASED
                ]);

                // Status log
                $escrow->statusLogs()->create([
                    'from_status' => EscrowStatus::DELIVERED,
                    'to_status'   => EscrowStatus::RELEASED,
                    'changed_by'  => null,
                    'reason'      => 'Auto release by system (deadline passed)',
                ]);

                // Transaksi pelepasan dana
                $escrow->transactions()->create([
                    'type'        => 'release',
                    'amount'      => $escrow->amount,
                    'executed_by' => null,
                    'executed_at' => now(),
                ]);
            });
        }

        return Command::SUCCESS;
    }
}

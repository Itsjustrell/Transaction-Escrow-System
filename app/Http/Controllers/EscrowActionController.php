<?php

namespace App\Http\Controllers;

use App\Models\Escrow;
use App\Services\EscrowStateMachine;
use App\Enums\EscrowStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EscrowActionController extends Controller
{
    public function fund(Request $request, Escrow $escrow)
    {
        $user = auth()->user();

        // 1. Pastikan user adalah buyer di escrow ini
        $isBuyer = $escrow->participants()
            ->where('user_id', $user->id)
            ->where('role', 'buyer')
            ->exists();

        if (! $isBuyer) {
            abort(403, 'You are not the buyer of this escrow.');
        }

        // 2. Validasi state + role via state machine
        $canTransition = EscrowStateMachine::canTransitionByRole(
            $escrow->status,
            EscrowStatus::FUNDED,
            'buyer'
        );

        if (! $canTransition) {
            abort(400, 'Invalid escrow state transition.');
        }

        // 3. Jalankan transisi (pakai transaction biar aman)
        DB::transaction(function () use ($escrow, $user) {

            // update status escrow
            $escrow->update([
                'status' => EscrowStatus::FUNDED
            ]);

            // catat status log
            $escrow->statusLogs()->create([
                'from_status' => EscrowStatus::CREATED,
                'to_status'   => EscrowStatus::FUNDED,
                'changed_by'  => $user->id,
            ]);

            // catat transaksi (simulasi funding)
            $escrow->transactions()->create([
                'type'        => 'funding',
                'amount'      => $escrow->amount,
                'executed_by' => $user->id,
                'executed_at' => now(),
            ]);
        });

        return redirect()->back()
            ->with('success', 'Escrow successfully funded.');
    }

    public function ship(Request $request, Escrow $escrow)
    {
        $user = auth()->user();

        // 1. Pastikan user adalah seller di escrow ini
        $isSeller = $escrow->participants()
            ->where('user_id', $user->id)
            ->where('role', 'seller')
            ->exists();

        if (! $isSeller) {
            abort(403, 'You are not the seller of this escrow.');
        }

        // 2. Validasi transisi via state machine
        $canTransition = EscrowStateMachine::canTransitionByRole(
            $escrow->status,
            EscrowStatus::SHIPPING,
            'seller'
        );

        if (! $canTransition) {
            abort(400, 'Invalid escrow state transition.');
        }

        // 3. Jalankan transisi secara atomic
        DB::transaction(function () use ($escrow, $user) {

            // Update status escrow
            $escrow->update([
                'status' => EscrowStatus::SHIPPING
            ]);

            // Catat status log
            $escrow->statusLogs()->create([
                'from_status' => EscrowStatus::FUNDED,
                'to_status'   => EscrowStatus::SHIPPING,
                'changed_by'  => $user->id,
            ]);
        });

        return redirect()->back()
            ->with('success', 'Escrow marked as shipping.');
    }

    public function deliver(Request $request, Escrow $escrow)
    {
        $user = auth()->user();

        // 1. Pastikan user adalah seller escrow ini
        $isSeller = $escrow->participants()
            ->where('user_id', $user->id)
            ->where('role', 'seller')
            ->exists();

        if (! $isSeller) {
            abort(403, 'You are not the seller of this escrow.');
        }

        // 2. Validasi transisi via state machine
        $canTransition = EscrowStateMachine::canTransitionByRole(
            $escrow->status,
            EscrowStatus::DELIVERED,
            'seller'
        );

        if (! $canTransition) {
            abort(400, 'Invalid escrow state transition.');
        }

        // 3. Jalankan transisi (atomic)
        DB::transaction(function () use ($escrow, $user) {

            $deliveredAt = now();
            $deadline = $deliveredAt
                ->copy()
                ->addHours($escrow->confirmation_window);

            // Update escrow
            $escrow->update([
                'status'           => EscrowStatus::DELIVERED,
                'delivered_at'     => $deliveredAt,
                'confirm_deadline' => $deadline,
            ]);

            // Catat status log
            $escrow->statusLogs()->create([
                'from_status' => EscrowStatus::SHIPPING,
                'to_status'   => EscrowStatus::DELIVERED,
                'changed_by'  => $user->id,
            ]);
        });

        return redirect()->back()
            ->with('success', 'Escrow marked as delivered.');
    }

    public function release(Request $request, Escrow $escrow)
    {
        $user = auth()->user();

        // 1. Pastikan user adalah buyer escrow ini
        $isBuyer = $escrow->participants()
            ->where('user_id', $user->id)
            ->where('role', 'buyer')
            ->exists();

        if (! $isBuyer) {
            abort(403, 'You are not the buyer of this escrow.');
        }

        // 2. Validasi transisi via state machine
        $canTransition = EscrowStateMachine::canTransitionByRole(
            $escrow->status,
            EscrowStatus::RELEASED,
            'buyer'
        );

        if (! $canTransition) {
            abort(400, 'Invalid escrow state transition.');
        }

        // 3. Jalankan transisi secara atomic
        DB::transaction(function () use ($escrow, $user) {

            // Update status escrow
            $escrow->update([
                'status' => EscrowStatus::RELEASED
            ]);

            // Catat status log
            $escrow->statusLogs()->create([
                'from_status' => EscrowStatus::DELIVERED,
                'to_status'   => EscrowStatus::RELEASED,
                'changed_by'  => $user->id,
                'reason'      => 'Buyer confirmed delivery',
            ]);

            // Catat transaksi pelepasan dana (simulasi)
            $escrow->transactions()->create([
                'type'        => 'release',
                'amount'      => $escrow->amount,
                'executed_by' => $user->id,
                'executed_at' => now(),
            ]);
        });

        return redirect()->back()
            ->with('success', 'Escrow released successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Escrow;
use App\Services\EscrowStateMachine;
use App\Enums\EscrowStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreDisputeEvidenceRequest;
use Illuminate\Support\Facades\Storage;

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

    public function dispute(Request $request, Escrow $escrow)
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
            EscrowStatus::DISPUTED,
            'buyer'
        );

        if (! $canTransition) {
            abort(400, 'Escrow cannot be disputed at this state.');
        }

        // 3. Validasi input (alasan dispute)
        $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        // 4. Jalankan transisi secara atomic
        DB::transaction(function () use ($escrow, $user, $request) {

            // Update status escrow
            $escrow->update([
                'status' => EscrowStatus::DISPUTED
            ]);

            // Buat record dispute
            $escrow->dispute()->create([
                'opened_by' => $user->id,
                'reason'    => $request->reason,
                'status'    => 'open',
            ]);

            // Catat status log
            $escrow->statusLogs()->create([
                'from_status' => EscrowStatus::DELIVERED,
                'to_status'   => EscrowStatus::DISPUTED,
                'changed_by'  => $user->id,
                'reason'      => 'Buyer opened dispute',
            ]);
        });

        return redirect()->back()
            ->with('success', 'Dispute has been submitted.');
    }

    public function uploadEvidence(
        StoreDisputeEvidenceRequest $request,
        Escrow $escrow
    ) {
        $user = auth()->user();

        // 1. Pastikan escrow sedang disputed
        if ($escrow->status !== EscrowStatus::DISPUTED) {
            abort(400, 'Escrow is not in disputed state.');
        }

        // 2. Ambil dispute (harus ada)
        $dispute = $escrow->dispute;

        if (! $dispute || $dispute->status !== 'open') {
            abort(400, 'Dispute is not open.');
        }

        // 3. Pastikan user adalah buyer escrow ini
        $isBuyer = $escrow->participants()
            ->where('user_id', $user->id)
            ->where('role', 'buyer')
            ->exists();

        if (! $isBuyer) {
            abort(403, 'Only buyer can upload dispute evidence.');
        }

        // 4. Simpan file
        $path = $request->file('evidence')
            ->store('dispute-evidences', 'public');

        // 5. Simpan record evidence
        $dispute->evidences()->create([
            'uploaded_by' => $user->id,
            'file_path'   => $path,
            'description' => $request->description,
        ]);

        return redirect()->back()
            ->with('success', 'Evidence uploaded successfully.');
    }

    public function resolveDispute(Request $request, Escrow $escrow)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        // 1. Pastikan user adalah arbiter
        if (! $user->hasRole('arbiter')) {
            abort(403, 'Only arbiter can resolve dispute.');
        }

        // 2. Pastikan escrow sedang disputed
        if ($escrow->status !== EscrowStatus::DISPUTED) {
            abort(400, 'Escrow is not in disputed state.');
        }

        // 3. Ambil dispute (harus open)
        $dispute = $escrow->dispute;

        if (! $dispute || $dispute->status !== 'open') {
            abort(400, 'Dispute is not open.');
        }

        // 4. Validasi input keputusan
        $request->validate([
            'resolution' => 'required|in:release,refund',
            'note'       => 'nullable|string|max:255',
        ]);

        // 5. Tentukan target status escrow
        $targetStatus = $request->resolution === 'release'
            ? EscrowStatus::RELEASED
            : EscrowStatus::REFUNDED;

        // 6. Validasi transisi via state machine
        if (! EscrowStateMachine::canTransitionByRole(
            $escrow->status,
            $targetStatus,
            'arbiter'
        )) {
            abort(400, 'Invalid escrow transition.');
        }

        // 7. Jalankan keputusan secara atomic
        DB::transaction(function () use (
            $escrow,
            $dispute,
            $user,
            $request,
            $targetStatus
        ) {

            // Update escrow
            $escrow->update([
                'status' => $targetStatus
            ]);

            // Update dispute
            $dispute->update([
                'status'      => 'resolved',
                'resolved_by' => $user->id,
                'resolution'  => $request->resolution,
                'resolved_at' => now(),
            ]);

            // Status log
            $escrow->statusLogs()->create([
                'from_status' => EscrowStatus::DISPUTED,
                'to_status'   => $targetStatus,
                'changed_by'  => $user->id,
                'reason'      => 'Dispute resolved by arbiter',
            ]);

            // Transaksi (simulasi)
            $escrow->transactions()->create([
                'type'        => $request->resolution,
                'amount'      => $escrow->amount,
                'executed_by' => $user->id,
                'executed_at' => now(),
            ]);
        });

        return redirect()->back()
            ->with('success', 'Dispute has been resolved.');
    }
}

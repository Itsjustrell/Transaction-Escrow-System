<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Escrow;

class EnsureEscrowState
{
    public function handle(Request $request, Closure $next, ...$states)
    {
        $escrow = $request->route('escrow');

        if (! $escrow instanceof Escrow) {
            abort(404, 'Escrow not found.');
        }

        if (! in_array($escrow->status, $states)) {
            abort(400, 'Invalid escrow state for this action.');
        }

        return $next($request);
    }
}


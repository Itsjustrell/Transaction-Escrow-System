<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EscrowDispute extends Model
{
    use HasFactory;
    protected $fillable = [
        'escrow_id', 'opened_by', 'reason', 'status', 'resolved_by', 'resolution', 'resolved_at'
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EscrowStatusLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'escrow_id', 'from_status', 'to_status', 'changed_by', 'reason'
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EscrowParticipant extends Model
{
    use HasFactory;
    protected $fillable = ['escrow_id', 'user_id', 'role'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function escrow()
    {
        return $this->belongsTo(Escrow::class);
    }
}

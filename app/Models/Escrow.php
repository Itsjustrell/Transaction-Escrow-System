<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Escrow extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'description',
        'amount',
        'status',
        'confirmation_window',
        'delivered_at',
        'confirm_deadline',
        'created_by'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants()
    {
        return $this->hasMany(EscrowParticipant::class);
    }

    public function transactions()
    {
        return $this->hasMany(EscrowTransaction::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(EscrowStatusLog::class);
    }

    public function dispute()
    {
        return $this->hasOne(EscrowDispute::class);
    }
}

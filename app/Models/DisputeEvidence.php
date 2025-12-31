<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisputeEvidence extends Model
{
    use HasFactory;
    protected $fillable = [
        'dispute_id',
        'uploaded_by',
        'file_path',
        'description'
    ];

    public function dispute()
    {
        return $this->belongsTo(EscrowDispute::class, 'dispute_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bank_code',
        'account_name',
        'account_number',
        'bank_name',
        'provider',
        'provider_data',
        'is_active',
        'activated_at',
    ];

    protected $casts = [
        'provider_data' => 'object',
        'is_active' => 'boolean',
        'deactivated_at' => 'datetime',
        'activated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

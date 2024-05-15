<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VirtualAccount extends Model
{
    use HasFactory;

    // $table->foreignId('user_id')->constrained()->onDelete('cascade');
    // $table->string('account_number',10)->unique();
    // $table->string('provider', 12);
    // $table->string('account_name');
    // $table->string('account_bank');
    // $table->string('account_bank_code', 5);
    // $table->json('provider_data')->nullable();
    // $table->boolean('is_active')->default(true);
    // $table->timestamp('deactivated_at')->nullable();
    // $table->timestamp('activated_at')->nullable();
    // $table->timestamps();

    protected $fillable = [
        'user_id',
        'account_number',
        'provider',
        'account_name',
        'account_bank',
        'account_bank_code',
        'provider_data',
        'is_active',
        'deactivated_at',
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

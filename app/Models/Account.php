<?php

namespace App\Models;

use App\Observers\AccountObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

#[ObservedBy([AccountObserver::class])]
class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'transaction_limit',
        'currency',
        'email_subscribe',
        'account_type',
        'name',
    ];

    protected $with = ['virtualAccount'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function virtualAccount()
    {
        return $this->hasOne(VirtualAccount::class);
    }

    public function getAccountType()
    {
        return $this->account_type;
    }

    public function histories()
    {
        // order by latest
        return $this->hasMany(WalletHistory::class)->latest();
    }
}

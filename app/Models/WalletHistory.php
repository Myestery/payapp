<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletHistory extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $table = 'histories';

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}

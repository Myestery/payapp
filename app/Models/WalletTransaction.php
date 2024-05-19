<?php

namespace App\Models;

use App\Wallet\WalletResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = [
        'reference',
        'status',
        'total_sent',
        'total_debit',
        'message',
        'currency',
        'payload',
        'idempotency',
        'provider_reference',
        'provider',
    ];

    public function updateData($status, $message, $total_debit)
    {
        $this->message = $message;
        $this->status = $status;
        $this->total_debit = $total_debit;
        $this->save();

        return $this;
    }

    public function getWalletResponse(): WalletResponse
    {
        return new WalletResponse($this->status, $this->message, $this->reference, $this->total_debit);
    }

    public function isFinalized()
    {
        // PENDING
        return ($this->status != 1) ? true : false;
    }
}

<?php

namespace App\Payments;

use App\Models\VirtualAccount;
use Illuminate\Support\Collection;

class VirtualAccountCreationResult
{
    /**
     * VirtualAccountCreationResult constructor.
     */
    public function __construct(
        public int $accountId,
        public string $bankCode,
        public string $accountName,
        public string $accountNumber,
        public string $bankName,
        public string $provider,
        public Collection $providerData,
        public bool $isActive,
        public string $activatedAt
    ) {
    }

    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId,
            'bank_code' => $this->bankCode,
            'account_name' => $this->accountName,
            'account_number' => $this->accountNumber,
            'bank_name' => $this->bankName,
            'provider' => $this->provider,
            'provider_data' => $this->providerData->toArray(),
            'is_active' => $this->isActive,
            'activated_at' => $this->activatedAt,
        ];
    }

    public function save(): VirtualAccount
    {
        return VirtualAccount::create($this->toArray());
    }

    static function fromArray(array $data): VirtualAccountCreationResult
    {
        return new VirtualAccountCreationResult(
            $data['account_id'],
            $data['bank_code'],
            $data['account_name'],
            $data['account_number'],
            $data['bank_name'],
            $data['provider'],
            collect($data['provider_data']),
            $data['is_active'],
            $data['activated_at'],
        );
    }

}

<?php


namespace App\Payments;


class BankAccount
{
    public function __construct(
        public string $accountName,
        public string $accountNumber,
        public string $bankName,
        public string $bankCode,
    ) {
    }

    static function fromArray(array $data): self
    {
        return new self(
            $data['accountName'],
            $data['accountNumber'],
            $data['bankName'],
            $data['bankCode'],
        );
    }
}

<?php

namespace App\Payments;

class VirtualAccountCreationResult
{
    public string $subAccountCode;
    public string $accountName;

    /**
     * SubaccountCreationResult constructor.
     * @param string $subAccountCode
     * @param string $accountName
     */
    public function __construct(string $subAccountCode, string $accountName)
    {
        $this->subAccountCode = $subAccountCode;
        $this->accountName = $accountName;
    }


}

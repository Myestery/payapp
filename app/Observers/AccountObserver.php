<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Account;
use App\Jobs\CreateVirtualAccount;

class AccountObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(Account $account): void
    {
        // create virtual account
        $user = $account->user;
        CreateVirtualAccount::dispatch($user, $account);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(Account $account): void
    {
        //
    }

    /**
     * Handle the Account "deleted" event.
     */
    public function deleted(Account $account): void
    {
        //
    }

    /**
     * Handle the Account "restored" event.
     */
    public function restored(Account $account): void
    {
        //
    }

    /**
     * Handle the Account "force deleted" event.
     */
    public function forceDeleted(Account $account): void
    {
        //
    }
}

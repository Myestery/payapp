<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Actions\Payment\CreateVirtualAccountAction;

class CreateVirtualAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private User $user, private Account $account)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(CreateVirtualAccountAction $action): void
    {
        $result = $action->execute($this->user, $this->account);
        // save result to db
        $result->save();
    }
}

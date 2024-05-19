<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Account;
use App\Models\WalletHistory;

class RunGLEOD extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:gl:eod';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Balances the GL accounts at the end of the day';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $accounts = Account::where('account_type', 'GL')->get();
        $this->info('Balancing GL accounts...');

        foreach ($accounts as $account) {
            $this->info('Balancing account: ' . $account->name);
            $total_debits = WalletHistory::whereDate('created_at', date('Y-m-d', strtotime("-1 days")))
                            ->where('account_id', $account->id)
                            ->where('type', 'DEBIT')
                            ->sum('amount');

            $total_credits = WalletHistory::whereDate('created_at', date('Y-m-d', strtotime("-1 days")))
                            ->where('type', 'CREDIT')
                            ->where('account_id', $account->id)
                            ->sum('amount');

            $account->balance = ($account->balance + $total_credits) - $total_debits;
            $account->save();
        }

        return 0;
    }
}

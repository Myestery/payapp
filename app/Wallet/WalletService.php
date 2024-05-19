<?php

namespace App\Wallet;

use App\Models\Account;
use App\Wallet\WalletConst;
use App\Models\WalletHistory;
use App\Wallet\WalletResponse;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use App\Exceptions\WalletException;
use Illuminate\Support\Facades\Log;



class WalletService
{
    private $reference;
    private $ledgers = [];
    private $ledger_total = 0;
    private $total_amount = 0.0;

    private $wallets_to_lock = [];
    private $debit_accounts = [];

    private $provider_reference = null;
    private $provider = null;

    public function post($reference, $total_amount, array $ledgers, $provider_reference = null, $provider = null): WalletResponse
    {
        $this->reference = $reference;
        $this->total_amount = (float) $total_amount;
        $this->ledgers = $ledgers;

        $this->provider_reference = $provider_reference;
        $this->provider = $provider;

        return $this->process();
    }

    public function generateHash($reference, $amount, $ledgers)
    {
        return hash("sha512", $reference . $this->getTotalAmount($amount) . json_encode($ledgers));
    }

    public function getTotalAmount($amount)
    {
        return number_format((float) $amount, 2, '.', '');
    }


    public function process(): WalletResponse
    {
        try {
            /** @var WalletTransaction */
            $walletTxn = WalletTransaction::where('reference', $this->reference)
                ->first();

            // Check Transaction Exists
            if (!empty($walletTxn)) {
                // Check Idempotency
                if ($walletTxn->idempotency != $this->generateHash($this->reference, $this->total_amount, $this->ledgers)) {
                    return new WalletResponse(WalletConst::FAILED, "Reference already exists with a different payload structure", $this->reference);
                }

                // Check Transaction already completed
                if ($walletTxn->status != WalletConst::PENDING) {
                    return new WalletResponse($walletTxn->status, $walletTxn->message, $this->reference, $walletTxn->total_debit);
                }
            } else {
                $walletTxn = $this->logTransaction();
            }

            // Run Validations
            /** @var WalletTransaction */
            $walletTxn = $this->validateLedgers($walletTxn);

            if ($walletTxn->isFinalized()) {
                return $walletTxn->getWalletResponse();
            }

            // Post Transaction
            $this->postTransaction($walletTxn);
        } catch (\PDOException $e) {
            Log::critical($e->getMessage());

            $walletTxn->updateData(WalletConst::PENDING, substr($e->getMessage(), 0, 250), 0.00);
        } catch (WalletException $e) {

            $walletTxn->updateData(WalletConst::FAILED, $e->getMessage(), 0.00);
        } catch (\Throwable $e) {
            Log::critical($e->getMessage());
            $walletTxn->updateData(WalletConst::PENDING, substr($e->getMessage(), 0, 250), 0.00);
        } finally {
            // Log::info("$this->reference | $this->total_amount | " . json_encode($this->ledgers));
        }

        $this->processCreditHooks($this->ledgers);

        return $walletTxn->getWalletResponse();
    }

    public function logTransaction()
    {
        return WalletTransaction::create([
            'reference' => $this->reference,
            'status' => WalletConst::PENDING,
            'total_sent' => $this->total_amount,
            'total_debit' => 0,
            'message' => 'awaiting processing',
            'payload' => json_encode($this->ledgers),
            'idempotency' => $this->generateHash($this->reference, $this->total_amount, $this->ledgers),
            // additional fields
            "provider_reference" => $this->provider_reference,
            "provider" => $this->provider
        ]);
    }

    public function postTransaction($walletTxn)
    {
        // Initiate transaction
        DB::transaction(function () {

            // Perform row locks
            $lockedAccounts = $this->lockForUpdate();

            // Perform wallet balance validation checks and
            // refresh the previously set ledger wallets
            $this->checkDebitBalances($lockedAccounts);

            //Perform updates
            $this->saveTransactions($lockedAccounts);
        }, 5);

        $walletTxn->updateData(WalletConst::SUCCESSFUL, 'successful', $this->ledger_total);
    }


    private function processCreditHooks($ledgers)
    {
        // ProcessCreditHook::dispatch($this->ledgers);
    }

    public function lockForUpdate()
    {
        return Account::whereIn("id", $this->wallets_to_lock)->lockForUpdate()->get();
    }

    public function saveTransactions($lockedAccounts)
    {
        $lockedAccounts = $lockedAccounts->keyBy('id');

        foreach ($this->ledgers as $ledger) {
            if ($ledger->account->getAccountType() == WalletConst::GL_ACCOUNT) {
                $this->saveGLTransaction($ledger);
            } else {
                $this->saveRegularTransaction($ledger, $lockedAccounts->get($ledger->account->id));
            }
        }

        $this->ledger_total = $this->total_amount;
    }

    public function saveGLTransaction($ledger)
    {
        $this->createRecordLog($ledger, 0, 0);
    }

    public function saveRegularTransaction($ledger, $model)
    {
        // Get the new balance
        $prev_balance = $model->balance + $model->overdraw_limit;

        if ($ledger->action === WalletConst::CREDIT) {
            $model->balance += $ledger->amount;
        } else {
            $model->balance -= $ledger->amount;
        }

        $new_balance = $model->balance + $model->overdraw_limit;

        $model->save();

        $this->createRecordLog($ledger, $prev_balance, $new_balance);
    }

    public function createRecordLog($ledger, $prev_balance, $new_balance)
    {
        return WalletHistory::create([
            'account_id' => $ledger->account->id,
            'reference' => $this->reference,
            'amount' => $ledger->amount,
            'previous_balance' => $prev_balance,
            'current_balance' => $new_balance,
            'narration' => $ledger->narration,
            'type' => $ledger->action,
            'category' => $ledger->category,
            'status' => 'successful',
        ]);
    }

    public function checkDebitBalances($lockedAccounts)
    {
        foreach ($lockedAccounts as $account) {
            if (array_key_exists($account->id, $this->debit_accounts)) {
                if (($account->balance + $account->overdraw_limit) < $this->debit_accounts[$account->id]['amount']) {
                    throw new WalletException("Insufficient balance on $account->account_name - $account->account_no : $account->currency" . $account->balance);
                }
            }
        }
    }

    public function validateLedgers($walletTxn)
    {
        $ledger_total_debits = 0.00;
        $ledger_total_credits = 0.00;

        $early_return_error = "";



        foreach ($this->ledgers as $ledger) {
            if (empty($ledger->account)) {
                $early_return_error = "Account number not found for " . $ledger->account_no;
                break;
            }

            $account_id = $ledger->account->id;

            if ($ledger->action === WalletConst::DEBIT) {
                $ledger_total_debits += $ledger->amount;

                if (array_key_exists($account_id, $this->debit_accounts)) {
                    $this->debit_accounts[$account_id]['amount'] += $ledger->amount;
                } else {
                    $this->debit_accounts[$account_id]['amount'] = $ledger->amount;
                }
            }

            if ($ledger->action === WalletConst::CREDIT) {
                $ledger_total_credits += $ledger->amount;
            }

            if ($ledger->account->account_type != WalletConst::GL_ACCOUNT) {
                array_push($this->wallets_to_lock, $account_id);
            }
        }


        if (!empty($early_return_error)) {
            $walletTxn->updateData(WalletConst::FAILED, $early_return_error, 0.00);
            return $walletTxn;
        }

        if ($ledger_total_debits != $ledger_total_credits) {
            $walletTxn->updateData(WalletConst::FAILED, "Total debits does not match total credits", 0.00);
            return $walletTxn;
        }

        if (
            (string) round($this->total_amount, 2) !=
            (string) round($ledger_total_debits, 2)
        ) {
            $walletTxn->updateData(WalletConst::FAILED, "Ledger total {$ledger_total_debits} does not match the transaction total {$this->total_amount}", 0.00);
            return $walletTxn;
        }

        return $walletTxn;
    }

    public function requery($reference)
    {
    }
}


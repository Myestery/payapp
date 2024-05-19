<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Wallet\Ledger;
use App\Models\Account;
use App\Wallet\WalletConst;
use App\Wallet\WalletService;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WalletServiceTest extends TestCase
{

    public $service;

    public function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->service = new WalletService;
    }


    public function test_wallet_can_not_post_transaction_with_existing_reference()
    {
        $txn = new WalletTransaction;
        $txn->reference = substr(md5(microtime()), rand(0, 26), 5);
        $txn->status = WalletConst::SUCCESSFUL;
        $txn->total_debit = 1000.00;
        $txn->total_sent = 1000.00;
        $txn->message = "successful";
        $txn->payload = json_encode([]);
        $txn->idempotency = "dlkskdkjsjkdjksjkd";
        $txn->save();

        Account::where(['user_id' => 6])->delete();
        Account::where(['user_id' => 7])->delete();

        $accountA = Account::create(['account_name' => 'testAccount1', 'account_type' => 'main', 'balance' => 5000, 'user_id' => 6]);
        $accountB = Account::create(['account_name' => 'testAccount1', 'account_type' => 'main', 'balance' => 0, 'user_id' => 7]);
        $amount = 1000.00;

        $ledgers = [
            new Ledger('DEBIT',  $accountA->id, $amount, 'Transfer from johnpaul', 'disbursement'),
            new Ledger('CREDIT', $accountB->id, $amount, 'Transfer from johnpaul', 'disbursement')
        ];

        $dto = $this->service->post('1234567890', $amount, $ledgers);


        $this->assertEquals(0, $dto->status, $dto->message);
        $this->assertEquals("Reference already exists with a different payload structure", $dto->message, $dto->message);
    }

    public function test_wallet_can_not_post_transaction_already_finalized()
    {
        // Create Account
        Account::where(['user_id' => 6])->delete();
        Account::where(['user_id' => 7])->delete();

        $accountA = Account::create(['account_name' => 'testAccount1', 'account_type' => 'main', 'balance' => 5000, 'user_id' => 6]);
        $accountB = Account::create(['account_name' => 'testAccount1', 'account_type' => 'main', 'balance' => 0, 'user_id' => 7]);
        $amount = 1000.00;
        // Create Ledgers
        $ledgers = [
            new Ledger('DEBIT',  $accountA->id, $amount, 'Transfer from johnpaul', 'disbursement'),
            new Ledger('CREDIT', $accountB->id, $amount, 'Transfer from johnpaul', 'disbursement')
        ];

        // Mock a transaction from above
        $txn = new WalletTransaction;
        $txn->reference = substr(md5(microtime()), rand(0, 26), 5);
        $txn->status = WalletConst::SUCCESSFUL;
        $txn->total_debit = $amount;
        $txn->total_sent = $amount;
        $txn->message = "successful";
        $txn->payload = json_encode($ledgers);
        $txn->idempotency = $this->service->generateHash($txn->reference, $txn->total_debit, $ledgers);
        $txn->save();

        // Post a txn
        $dto = $this->service->post($txn->reference, $amount, $ledgers);

        $this->assertEquals(2, $dto->status, $dto->message);
        $this->assertEquals($txn->reference, $dto->reference, "Came back with a different reference");
    }

    public function test_wallet_can_detect_credit_debit_mismatch()
    {
        // Create Account
        Account::where(['user_id' => 6])->delete();
        Account::where(['user_id' => 7])->delete();

        $accountA = Account::create(['account_name' => 'testAccount1', 'account_type' => 'main', 'balance' => 5000, 'user_id' => 6]);
        $accountB = Account::create(['account_name' => 'testAccount1', 'account_type' => 'main', 'balance' => 0, 'user_id' => 7]);
        $amount = 1000.00;

        // Create Ledgers
        $ledgers = [
            new Ledger('DEBIT',  $accountA->id, $amount, 'Transfer from johnpaul', 'disbursement'),
            new Ledger('CREDIT', $accountB->id, ($amount + 50), 'Transfer from johnpaul', 'disbursement')
        ];

        // Post a txn
        $reference = substr(md5(microtime()), rand(0, 26), 5);
        $dto = $this->service->post($reference, $amount, $ledgers);

        $transaction = WalletTransaction::where('reference', $reference)->first();

        // assert can log
        $this->assertEquals($reference, $transaction->reference);

        // assert credit/debit mismatch
        $this->assertEquals(0, $dto->status);
        $this->assertEquals('Total debits does not match total credits', $dto->message);
    }

    public function test_wallet_can_detect_ledger_total_mismatch()
    {
        // Create Account
        Account::where(['user_id' => 6])->delete();
        Account::where(['user_id' => 7])->delete();

        $accountA = Account::create(['account_name' => 'testAccount1', 'account_type' => 'main', 'balance' => 5000, 'user_id' => 6]);
        $accountB = Account::create(['account_name' => 'testAccount1', 'account_type' => 'main', 'balance' => 0, 'user_id' => 7]);
        $amount = 1000.00;

        // Create Ledgers
        $ledgers = [
            new Ledger('DEBIT',  $accountA->id, $amount, 'Transfer from johnpaul', 'disbursement'),
            new Ledger('CREDIT', $accountB->id, ($amount), 'Transfer from johnpaul', 'disbursement')
        ];

        // Post a txn
        $reference = substr(md5(microtime()), rand(0, 26), 5);
        $dto = $this->service->post($reference, ($amount + 50), $ledgers);

        $transaction = WalletTransaction::where('reference', $reference)->first();

        // assert can log
        $this->assertEquals($reference, $transaction->reference);

        // assert credit/debit mismatch
        $this->assertEquals(0, $dto->status);
        $this->assertMatchesRegularExpression('/Ledger total \d+ does not match the transaction total \d+/', $dto->message);
    }

    public function test_wallet_can_detect_insufficient_balance()
    {
        Account::where(['user_id' => 6])->delete();
        Account::where(['user_id' => 7])->delete();

        $accountA = Account::create(['account_name' => 'testAccount1', 'account_type' => 'main', 'balance' => 500, 'user_id' => 6]);
        $accountB = Account::create(['account_name' => 'testAccount1', 'account_type' => 'main', 'balance' => 0, 'user_id' => 7]);
        $amount = 1000.00;

        // Create Ledgers
        $ledgers = [
            new Ledger('DEBIT',  $accountA->id, $amount, 'Transfer from johnpaul', 'disbursement'),
            new Ledger('CREDIT', $accountB->id, $amount, 'Transfer from johnpaul', 'disbursement')
        ];

        // Post a txn
        $reference = substr(md5(microtime()), rand(0, 26), 20);
        $dto = $this->service->post($reference, $amount, $ledgers);

        $transaction = WalletTransaction::where('reference', $reference)->first();

        // assert can log
        $this->assertEquals($reference, $transaction->reference);

        // assert credit/debit mismatch
        $this->assertEquals(0, $dto->status);
        $this->assertStringStartsWith('Insufficient balance', $dto->message);
    }


    public function test_wallet_can_do_multiple_debits_multiple_credits()
    {
        // Create Account

        Account::where(['user_id' => 6])->delete();
        Account::where(['user_id' => 7])->delete();
        Account::where(['user_id' => 8])->delete();
        Account::where(['user_id' => 9])->delete();

        $accountA = Account::create(['account_name' => 'testAccount1', 'account_type' => 'main', 'balance' => 5000, 'user_id' => 6]);
        $accountB = Account::create(['account_name' => 'testAccountb', 'account_type' => 'main', 'balance' => 4000, 'user_id' => 7]);

        $accountC = Account::create(['account_name' => 'testAccountC', 'account_type' => 'main', 'balance' => 0, 'user_id' => 8]);
        $accountD = Account::create(['account_name' => 'testAccountD', 'account_type' => 'main', 'balance' => 0, 'user_id' => 9]);
        $amount = 6000.00;


        // Create Ledgers
        $ledgers = [
            new Ledger('DEBIT',  $accountA->id, 5000, 'Transfer from johnpaula', 'disbursement'),
            new Ledger('DEBIT', $accountB->id, 1000, 'Transfer from johnpaulb', 'disbursement'),
            new Ledger('CREDIT',  $accountC->id, 4000, 'Transfer from johnpaulc', 'disbursement'),
            new Ledger('CREDIT', $accountD->id, 2000, 'Transfer from johnpauld', 'disbursement')
        ];

        // Post a txn
        $reference = substr(md5(microtime()), rand(0, 26), 5);

        $dto = $this->service->post($reference, $amount, $ledgers);

        $transaction = WalletTransaction::where('reference', $reference)->first();

        // assert can log
        $this->assertEquals($reference, $transaction->reference);

        // assert credit/debit mismatch
        $this->assertEquals('successful', $dto->message);
        $this->assertEquals(2, $dto->status);
    }

    public function test_database_error_returns_pending()
    {
        // Create Account
        Account::where(['user_id' => 6])->delete();
        Account::where(['user_id' => 7])->delete();

        $accountA = Account::create(['account_name' => 'testAccount1', 'account_type' => 'main', 'balance' => 5000, 'user_id' => 6]);
        $accountB = Account::create(['account_name' => 'testAccount1', 'account_type' => 'main', 'balance' => 0, 'user_id' => 7]);
        $amount = 1000.00;

        // Create Ledgers
        $ledgers = [
            new Ledger('DEBIT',  $accountA->id, $amount, 'Transfer from johnpaul', 'disbursement'),
            new Ledger(
                'CREDIT',
                $accountB->id,
                $amount,
                'Transfer from johnpaul',
                'Conversazioni encephalon hopkinsville aaron concavely socialize columnarity karyogamy merrymaking allomorphism izba unshouting superfit whiteslave. Internalized haemophilia damageability remembrancer outbargain domesticative crucifixion incivil estrangedness extraordinariness interplacental groundable envision algy. Breathiest becker subsidence overclog sharra sumoist noritic christianity nondevotional ladd hungary michelozzo unnutritious florianpolis. Unscanned acockbill scurrying copolymerise trichogynial trios eradiation bruxism meacon alcis crossline orchestrina desecrated ourari. Biddable printing cityward extravasated malatesta inviolate hyperbolical bolshy unmannish sensiltised mettled checkmate flipping hexahedron. Enervation colleted klavern tatary countermined regorging unexponible thrombotic homo tardigrade hagridden supplying nonexceptional gamily. Ubermensch furfural forespeak morello uncivilly seismal glassless burthen noel simaruba berosus inspiredly pseudosocial gormand.
                Reassent zagazig challenging overstarch overwore untrailerable epiphenomenalist ponderously slept victorine androconia wran nagari bastard. Broccoli konakri fourflusher weinek acknowledged reengaged ro blockading tup nonvenal colony autotomise ciceronian circularizer. Cheers waxing injun overly nonidiomatical osmiridium coccous longship pedanticalness endolymphatic carpophagous sparkishly unrejoiced timbrelled. Allegorically reincrease overlavish azores scrawniness superromantically wherefrom weed impeded absorb poachiest hoarseness uncuffed unappliquï¿¥ï¾½d. Dystrophy talladega truistical mulhac reimpregnating joycean overcaution onionskin cadorna alethiology reperceive orthopaedic vintage hedgy. Epinephrine equivalency contaminous verdi insusceptible subclavate divertive renewal torquemada dramatized bionomic lowlander formulate devolatilize. Aftermath coprosterol unspouted chider hyperexcursive hastelessness loper poetising motoneuron waviest francoist dominick leiomyomas mridang.
                Sarge diaspora angularness pivotal phloridzin busiest serenader paraffinizing adscititious switcherhit descrier preexperiencing unviolent chippendale. Dishabituating repledging brengun sphincterial uncacophonous elephant jealousies lowing unparticipating damaskeening ngc
                '
            )
        ];

        // Post a txn
        $reference = substr(md5(microtime()), rand(0, 26), 5);
        $dto = $this->service->post($reference, $amount, $ledgers);

        $transaction = WalletTransaction::where('reference', $reference)->first();

        // assert can log
        $this->assertEquals($reference, $transaction->reference);

        // assert credit/debit mismatch
        $this->assertEquals(1, $dto->status);
    }

    public function test_debit_from_GL_did_not_lock_or_impact()
    {
        // Create Account
        Account::where(['user_id' => 7])->delete();
        $accountA = Account::where(["name" => "FEES GL"])->first();
        //create(['account_name'=>'testAccountX', 'account_type'=>'GL', 'balance'=>20000]);
        $accountB = Account::create(['account_name' => 'testAccountJ', 'account_type' => 'main', 'balance' => 0, 'user_id' => 7]);
        $amount = 20000.00;

        // Create Ledgers
        $ledgers = [
            new Ledger('DEBIT',  $accountA->id, $amount, 'Transfer from johnpaul', 'disbursement'),
            new Ledger('CREDIT', $accountB->id, $amount, 'Transfer from johnpaul', 'disbursement')
        ];

        // Post a txn
        $reference = substr(md5(microtime()), rand(0, 26), 5);
        $dto = $this->service->post($reference, $amount, $ledgers);

        $transaction = WalletTransaction::where('reference', $reference)->first();

        // assert can log
        $this->assertEquals($reference, $transaction->reference);

        // assert credit/debit mismatch
        $this->assertEquals(2, $dto->status);
        $this->assertEquals('successful', $dto->message);
    }
}

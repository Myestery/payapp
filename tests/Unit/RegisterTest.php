<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\DB;

class RegisterTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_that_bvn_is_hashed_after_registration(): void
    {
        $user = User::first();
        $dbUser = DB::table('users')->where('id', $user->id)->first();

        $this->assertNotEquals($user->bvn, $dbUser->bvn);
    }
}

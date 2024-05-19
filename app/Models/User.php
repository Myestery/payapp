<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\VirtualAccount;
use App\Observers\UserObserver;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/* list some types for the automatic PHPDoc generator
 * @property-read string $name
 * @property-read string $email
 * @property-read string $password
 * @property-read string $bvn
 * @property-read string $phone
 * @property-read string $remember_token
 */
 class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'bvn',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Interact with the user's address.
     */
    protected function bvn(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => Crypt::decryptString($value),
            set: fn(string $value) => Crypt::encryptString($value),
        );
    }

    /**
     * Get the virtual accounts for the user.
     */
    public function account()
    {
        return $this->hasOne(Account::class);
    }

    public function getNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}

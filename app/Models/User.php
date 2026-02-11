<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'last_login_at',
        'last_login_ip',
        'is_active',
        'failed_login_attempts',
        'locked_until',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
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
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Check if user is a coordinator
     *
     * @return bool
     */
    public function isCoordinator(): bool
    {
        return $this->role === 'coordinator';
    }

    /**
     * Check if account is locked
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        if ($this->locked_until === null) {
            return false;
        }

        // If locked_until is in the future, account is locked
        if ($this->locked_until->isFuture()) {
            return true;
        }

        // If locked_until has passed, unlock the account
        $this->update([
            'locked_until' => null,
            'failed_login_attempts' => 0,
        ]);

        return false;
    }

    /**
     * Increment failed login attempts
     *
     * @return void
     */
    public function incrementFailedAttempts(): void
    {
        $this->increment('failed_login_attempts');
        
        // Lock account after 5 failed attempts for 15 minutes
        if ($this->failed_login_attempts >= 5) {
            $this->update([
                'locked_until' => now()->addMinutes(15),
            ]);
        }
    }

    /**
     * Reset failed login attempts
     *
     * @return void
     */
    public function resetFailedAttempts(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Update last login information
     *
     * @param string $ip
     * @return void
     */
    public function updateLastLogin(string $ip): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }

    /**
     * Get login logs for this user
     */
    public function loginLogs()
    {
        return $this->hasMany(LoginLog::class, 'user_id', 'user_id')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Get logout logs for this user
     */
    public function logoutLogs()
    {
        return $this->hasMany(LogoutLog::class, 'user_id', 'user_id')
                    ->orderBy('created_at', 'desc');
    }
}

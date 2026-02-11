<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'login_logs';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'log_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'email_entered',
        'status',
        'ip_address',
        'user_agent',
        'failure_reason',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the user that owns the login log.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Check if login was successful
     *
     * @return bool
     */
    public function wasSuccessful(): bool
    {
        return $this->status === 'SUCCESS';
    }

    /**
     * Get formatted status with color
     *
     * @return array
     */
    public function getStatusBadge(): array
    {
        return $this->status === 'SUCCESS'
            ? ['text' => 'Success', 'class' => 'success']
            : ['text' => 'Failed', 'class' => 'danger'];
    }
}

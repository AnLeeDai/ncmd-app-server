<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'avatar',
        'role',
    ];

    /**
     * Attributes to append when the model is serialized.
     * This ensures `total_points` is always present in toArray()/JSON outputs.
     *
     * @var array
     */
    protected $appends = ['total_points'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'is_active',
        'role',
        'remember_token',
        'email_verified_at',
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

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public function hiddenRoleAdmin()
    {
        if ($this->isAdmin()) {
            $this->role = null;
        }
    }

    public function adViews()
    {
        return $this->hasMany(AdView::class);
    }

    public function userPoints()
    {
        return $this->hasMany(UserPoint::class);
    }

    public function spinTurns()
    {
        return $this->hasMany(SpinTurn::class);
    }

    public function getTotalPointsAttribute()
    {
        return $this->userPoints()->sum('points');
    }

    public function getTotalSpinTurnsAttribute()
    {
        return $this->spinTurns()->sum('turns');
    }
}

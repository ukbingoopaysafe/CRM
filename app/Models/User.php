<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_RENTAL_AGENT = 'rental_agent';
    public const ROLE_RESALE_AGENT = 'resale_agent';

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
        'phone',
        'is_active',
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
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isRentalAgent(): bool
    {
        return $this->role === self::ROLE_RENTAL_AGENT || $this->isAdmin();
    }

    public function isResaleAgent(): bool
    {
        return $this->role === self::ROLE_RESALE_AGENT || $this->isAdmin();
    }

    public function rentalRequests()
    {
        return $this->hasMany(RentalRequest::class, 'assigned_to');
    }

    public function resaleRequests()
    {
        return $this->hasMany(ResaleRequest::class, 'assigned_to');
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class, 'assigned_to');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Citizen extends Authenticatable
{
    protected $fillable = [
        'tiin',
        'full_name',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relationships
    public function taxReturns(): HasMany
    {
        return $this->hasMany(TaxReturn::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    // Custom methods
    public function getIdentifierName(): string
    {
        return 'tiin';
    }

    public function getAuthIdentifierName(): string
    {
        return 'tiin';
    }

    /**
     * Create a simple token for API authentication
     */
    public function createToken($name)
    {
        $token = Str::random(80);
        // In a real app, you'd store this in a tokens table
        // For now, we'll just return a mock token structure
        return (object) ['plainTextToken' => $token];
    }
}

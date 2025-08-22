<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NbrOfficer extends Authenticatable
{
    protected $fillable = [
        'username',
        'full_name',
        'password',
        'officer_id',
        'department',
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
    public function reviewedTaxReturns(): HasMany
    {
        return $this->hasMany(TaxReturn::class, 'reviewed_by');
    }

    public function getAuthIdentifierName(): string
    {
        return 'username';
    }
}

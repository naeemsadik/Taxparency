<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BppaOfficer extends Authenticatable
{
    protected $fillable = [
        'username',
        'full_name',
        'password',
        'officer_id',
        'department',
        'designation',
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
    public function createdProcurements(): HasMany
    {
        return $this->hasMany(Procurement::class, 'created_by');
    }

    public function approvedVendors(): HasMany
    {
        return $this->hasMany(Vendor::class, 'approved_by');
    }

    public function getAuthIdentifierName(): string
    {
        return 'username';
    }
}

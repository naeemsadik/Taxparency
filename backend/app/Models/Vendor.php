<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vendor extends Authenticatable
{
    protected $fillable = [
        'username',
        'company_name',
        'password',
        'vendor_license_number',
        'contact_person',
        'contact_email',
        'contact_phone',
        'company_address',
        'is_approved',
        'approved_at',
        'approved_by',
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
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    // Relationships
    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(BppaOfficer::class, 'approved_by');
    }

    public function getAuthIdentifierName(): string
    {
        return 'username';
    }
}

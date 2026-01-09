<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'email',
        'phone',
        'plan',
        'status',
        'max_agents',
        'max_concurrent_calls',
        'settings',
        'trial_ends_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
    ];

    /**
     * Get users for this tenant
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get calls for this tenant
     */
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    /**
     * Get campaigns for this tenant
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Get tickets for this tenant
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get subscription for this tenant
     */
    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * Get invoices for this tenant
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Check if tenant is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if tenant is on trial
     */
    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if tenant has reached agent limit
     */
    public function hasReachedAgentLimit(): bool
    {
        return $this->users()->count() >= $this->max_agents;
    }

    /**
     * Get active agents count
     */
    public function getActiveAgentsCountAttribute(): int
    {
        return $this->users()
            ->where('status', 'active')
            ->where('is_online', true)
            ->count();
    }
}

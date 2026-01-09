<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'status',
        'is_online',
        'last_activity_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'is_online' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function booted()
    {
        // Automatically scope all queries to the current tenant
        static::addGlobalScope('tenant', function ($builder) {
            if ($tenantId = auth()->user()?->tenant_id) {
                $builder->where('tenant_id', $tenantId);
            }
        });

        // Set tenant_id when creating a user
        static::creating(function ($user) {
            if (!$user->tenant_id && auth()->check()) {
                $user->tenant_id = auth()->user()->tenant_id;
            }
        });
    }

    /**
     * Get the tenant that owns the user
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get roles for this user
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * Get calls handled by this agent
     */
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class, 'agent_id');
    }

    /**
     * Get tickets assigned to this user
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assigned_to');
    }

    /**
     * Get tickets created by this user
     */
    public function createdTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'created_by');
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('slug', $role)->exists();
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is supervisor
     */
    public function isSupervisor(): bool
    {
        return $this->hasRole('supervisor');
    }

    /**
     * Check if user is agent
     */
    public function isAgent(): bool
    {
        return $this->hasRole('agent');
    }

    /**
     * Update online status
     */
    public function updateOnlineStatus(bool $isOnline): void
    {
        $this->update([
            'is_online' => $isOnline,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Get active calls
     */
    public function getActiveCallsAttribute()
    {
        return $this->calls()
            ->whereIn('status', ['ringing', 'in-progress'])
            ->count();
    }
}

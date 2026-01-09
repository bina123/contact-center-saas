<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Call extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'agent_id',
        'campaign_id',
        'call_id',
        'direction',
        'from_number',
        'to_number',
        'status',
        'started_at',
        'answered_at',
        'ended_at',
        'duration',
        'wait_time',
        'recording_url',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function booted()
    {
        // Automatically scope to tenant
        static::addGlobalScope('tenant', function ($builder) {
            if ($tenantId = auth()->user()?->tenant_id) {
                $builder->where('calls.tenant_id', $tenantId);
            }
        });

        // Set tenant_id when creating
        static::creating(function ($call) {
            if (!$call->tenant_id && auth()->check()) {
                $call->tenant_id = auth()->user()->tenant_id;
            }
        });
    }

    /**
     * Get the tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the agent
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Get the campaign
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Calculate call duration
     */
    public function calculateDuration(): void
    {
        if ($this->answered_at && $this->ended_at) {
            $this->duration = $this->ended_at->diffInSeconds($this->answered_at);
            $this->save();
        }
    }

    /**
     * Calculate wait time
     */
    public function calculateWaitTime(): void
    {
        if ($this->started_at && $this->answered_at) {
            $this->wait_time = $this->answered_at->diffInSeconds($this->started_at);
            $this->save();
        }
    }

    /**
     * Scope for active calls
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['ringing', 'in-progress']);
    }

    /**
     * Scope for completed calls
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for today's calls
     */
    public function scopeToday($query)
    {
        return $query->whereDate('started_at', today());
    }
}

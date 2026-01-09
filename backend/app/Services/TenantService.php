<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantService
{
    /**
     * Create a new tenant with admin user
     */
    public function createTenant(array $data): Tenant
    {
        return DB::transaction(function () use ($data) {
            // Create tenant
            $tenant = Tenant::create([
                'name' => $data['name'],
                'slug' => Str::slug($data['name']),
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'plan' => $data['plan'] ?? 'starter',
                'status' => 'active',
                'max_agents' => $this->getMaxAgentsByPlan($data['plan'] ?? 'starter'),
                'max_concurrent_calls' => $this->getMaxCallsByPlan($data['plan'] ?? 'starter'),
                'trial_ends_at' => now()->addDays(14), // 14-day trial
            ]);

            // Create admin user
            $adminUser = User::withoutGlobalScope('tenant')->create([
                'tenant_id' => $tenant->id,
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'status' => 'active',
            ]);

            // Assign admin role
            $adminRole = \App\Models\Role::where('slug', 'admin')->first();
            if ($adminRole) {
                $adminUser->roles()->attach($adminRole);
            }

            return $tenant;
        });
    }

    /**
     * Update tenant subscription
     */
    public function updateSubscription(Tenant $tenant, string $plan): Tenant
    {
        $tenant->update([
            'plan' => $plan,
            'max_agents' => $this->getMaxAgentsByPlan($plan),
            'max_concurrent_calls' => $this->getMaxCallsByPlan($plan),
        ]);

        return $tenant;
    }

    /**
     * Suspend tenant account
     */
    public function suspendTenant(Tenant $tenant, string $reason = null): void
    {
        $tenant->update([
            'status' => 'suspended',
            'settings' => array_merge($tenant->settings ?? [], [
                'suspension_reason' => $reason,
                'suspended_at' => now()->toIso8601String(),
            ]),
        ]);

        // Deactivate all users
        $tenant->users()->update(['status' => 'inactive']);
    }

    /**
     * Reactivate tenant account
     */
    public function reactivateTenant(Tenant $tenant): void
    {
        $tenant->update(['status' => 'active']);
        
        // Reactivate users
        $tenant->users()->update(['status' => 'active']);
    }

    /**
     * Get tenant statistics
     */
    public function getTenantStatistics(Tenant $tenant): array
    {
        return [
            'total_users' => $tenant->users()->count(),
            'active_agents' => $tenant->users()
                ->where('is_online', true)
                ->whereHas('roles', fn($q) => $q->where('slug', 'agent'))
                ->count(),
            'total_calls_today' => $tenant->calls()->whereDate('started_at', today())->count(),
            'total_tickets_open' => $tenant->tickets()->where('status', 'open')->count(),
            'active_campaigns' => $tenant->campaigns()->where('status', 'active')->count(),
            'usage' => [
                'agents_used' => $tenant->users()->count(),
                'agents_limit' => $tenant->max_agents,
                'calls_today' => $tenant->calls()->whereDate('started_at', today())->count(),
            ],
        ];
    }

    /**
     * Get max agents by plan
     */
    protected function getMaxAgentsByPlan(string $plan): int
    {
        return match($plan) {
            'starter' => 5,
            'pro' => 25,
            'enterprise' => 100,
            default => 5,
        };
    }

    /**
     * Get max concurrent calls by plan
     */
    protected function getMaxCallsByPlan(string $plan): int
    {
        return match($plan) {
            'starter' => 10,
            'pro' => 50,
            'enterprise' => 200,
            default => 10,
        };
    }

    /**
     * Check if tenant can add more agents
     */
    public function canAddAgent(Tenant $tenant): bool
    {
        return !$tenant->hasReachedAgentLimit();
    }

    /**
     * Get plan pricing
     */
    public function getPlanPricing(): array
    {
        return [
            'starter' => [
                'name' => 'Starter',
                'price' => 49,
                'billing_period' => 'monthly',
                'features' => [
                    'Up to 5 agents',
                    '10 concurrent calls',
                    'Basic analytics',
                    'Email support',
                ],
            ],
            'pro' => [
                'name' => 'Pro',
                'price' => 199,
                'billing_period' => 'monthly',
                'features' => [
                    'Up to 25 agents',
                    '50 concurrent calls',
                    'Advanced analytics',
                    'Priority support',
                    'API access',
                    'Custom integrations',
                ],
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'price' => 499,
                'billing_period' => 'monthly',
                'features' => [
                    'Up to 100 agents',
                    '200 concurrent calls',
                    'Custom analytics',
                    '24/7 support',
                    'Unlimited API access',
                    'White-label option',
                    'Dedicated account manager',
                ],
            ],
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Call;
use App\Models\User;
use App\Events\CallStarted;
use App\Events\CallEnded;
use App\Events\CallAnswered;
use Illuminate\Support\Facades\DB;

class CallService
{
    /**
     * Create a new call
     */
    public function createCall(array $data): Call
    {
        $call = DB::transaction(function () use ($data) {
            $call = Call::create([
                'tenant_id' => auth()->user()->tenant_id,
                'call_id' => $data['call_id'],
                'direction' => $data['direction'],
                'from_number' => $data['from_number'],
                'to_number' => $data['to_number'],
                'status' => 'queued',
                'started_at' => now(),
                'campaign_id' => $data['campaign_id'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);

            // Find available agent if not specified
            if (!isset($data['agent_id']) && $data['direction'] === 'inbound') {
                $agent = $this->findAvailableAgent();
                if ($agent) {
                    $call->agent_id = $agent->id;
                    $call->save();
                }
            } else {
                $call->agent_id = $data['agent_id'] ?? null;
                $call->save();
            }

            return $call;
        });

        // Broadcast event
        broadcast(new CallStarted($call))->toOthers();

        return $call;
    }

    /**
     * Answer a call
     */
    public function answerCall(Call $call, User $agent): Call
    {
        $call->update([
            'agent_id' => $agent->id,
            'status' => 'in-progress',
            'answered_at' => now(),
        ]);

        $call->calculateWaitTime();

        broadcast(new CallAnswered($call))->toOthers();

        return $call;
    }

    /**
     * End a call
     */
    public function endCall(Call $call, array $data = []): Call
    {
        $call->update([
            'status' => $data['status'] ?? 'completed',
            'ended_at' => now(),
            'notes' => $data['notes'] ?? null,
            'recording_url' => $data['recording_url'] ?? null,
        ]);

        $call->calculateDuration();

        broadcast(new CallEnded($call))->toOthers();

        return $call;
    }

    /**
     * Find available agent
     */
    protected function findAvailableAgent(): ?User
    {
        return User::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_online', true)
            ->where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->where('slug', 'agent');
            })
            ->whereDoesntHave('calls', function ($query) {
                $query->whereIn('status', ['ringing', 'in-progress']);
            })
            ->first();
    }

    /**
     * Get call statistics for dashboard
     */
    public function getCallStatistics(array $filters = []): array
    {
        $query = Call::query();

        // Apply date filter
        if (isset($filters['start_date'])) {
            $query->where('started_at', '>=', $filters['start_date']);
        }
        if (isset($filters['end_date'])) {
            $query->where('started_at', '<=', $filters['end_date']);
        }

        // Apply agent filter
        if (isset($filters['agent_id'])) {
            $query->where('agent_id', $filters['agent_id']);
        }

        return [
            'total_calls' => $query->count(),
            'answered_calls' => (clone $query)->whereNotNull('answered_at')->count(),
            'missed_calls' => (clone $query)->where('status', 'no-answer')->count(),
            'average_duration' => (clone $query)->whereNotNull('duration')->avg('duration'),
            'average_wait_time' => (clone $query)->whereNotNull('wait_time')->avg('wait_time'),
            'active_calls' => Call::active()->count(),
            'calls_by_direction' => [
                'inbound' => (clone $query)->where('direction', 'inbound')->count(),
                'outbound' => (clone $query)->where('direction', 'outbound')->count(),
            ],
            'calls_by_status' => (clone $query)
                ->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray(),
        ];
    }

    /**
     * Get calls for an agent
     */
    public function getAgentCalls(User $agent, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Call::where('agent_id', $agent->id);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['direction'])) {
            $query->where('direction', $filters['direction']);
        }

        return $query->latest('started_at')
            ->with(['campaign'])
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Queue a call for an agent
     */
    public function queueCall(Call $call): void
    {
        $call->update(['status' => 'queued']);
        
        // Logic to add to call queue (Redis queue)
        // This would integrate with your telephony system
    }
}

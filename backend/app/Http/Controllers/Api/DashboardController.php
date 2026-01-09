<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard overview statistics
     */
    public function overview(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        // Real-time metrics
        $activeAgents = User::where('tenant_id', $tenantId)
            ->where('is_online', true)
            ->whereHas('roles', fn($q) => $q->where('slug', 'agent'))
            ->count();

        $activeCalls = Call::where('tenant_id', $tenantId)
            ->whereIn('status', ['ringing', 'in-progress'])
            ->count();

        $queuedCalls = Call::where('tenant_id', $tenantId)
            ->where('status', 'queued')
            ->count();

        // Today's metrics
        $callsToday = Call::where('tenant_id', $tenantId)
            ->whereDate('started_at', today())
            ->count();

        $answeredCallsToday = Call::where('tenant_id', $tenantId)
            ->whereDate('started_at', today())
            ->whereNotNull('answered_at')
            ->count();

        $missedCallsToday = Call::where('tenant_id', $tenantId)
            ->whereDate('started_at', today())
            ->where('status', 'no-answer')
            ->count();

        $avgCallDurationToday = Call::where('tenant_id', $tenantId)
            ->whereDate('started_at', today())
            ->whereNotNull('duration')
            ->avg('duration');

        $avgWaitTimeToday = Call::where('tenant_id', $tenantId)
            ->whereDate('started_at', today())
            ->whereNotNull('wait_time')
            ->avg('wait_time');

        // Ticket metrics
        $openTickets = Ticket::where('tenant_id', $tenantId)
            ->where('status', 'open')
            ->count();

        $ticketsToday = Ticket::where('tenant_id', $tenantId)
            ->whereDate('created_at', today())
            ->count();

        return response()->json([
            'real_time' => [
                'active_agents' => $activeAgents,
                'active_calls' => $activeCalls,
                'queued_calls' => $queuedCalls,
            ],
            'today' => [
                'total_calls' => $callsToday,
                'answered_calls' => $answeredCallsToday,
                'missed_calls' => $missedCallsToday,
                'answer_rate' => $callsToday > 0 
                    ? round(($answeredCallsToday / $callsToday) * 100, 2) 
                    : 0,
                'avg_call_duration' => round($avgCallDurationToday ?? 0),
                'avg_wait_time' => round($avgWaitTimeToday ?? 0),
                'tickets_created' => $ticketsToday,
            ],
            'tickets' => [
                'open' => $openTickets,
            ],
        ]);
    }

    /**
     * Get call volume chart data
     */
    public function callVolumeChart(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $days = $request->input('days', 7);

        $data = Call::where('tenant_id', $tenantId)
            ->where('started_at', '>=', now()->subDays($days))
            ->select(
                DB::raw('DATE(started_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(CASE WHEN status = "no-answer" THEN 1 ELSE 0 END) as missed')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($data);
    }

    /**
     * Get agent performance
     */
    public function agentPerformance(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $agents = User::where('tenant_id', $tenantId)
            ->whereHas('roles', fn($q) => $q->where('slug', 'agent'))
            ->withCount([
                'calls as total_calls' => fn($q) => $q->whereDate('started_at', today()),
                'calls as completed_calls' => fn($q) => $q
                    ->whereDate('started_at', today())
                    ->where('status', 'completed'),
            ])
            ->with(['calls' => function($q) {
                $q->whereDate('started_at', today())
                    ->whereNotNull('duration')
                    ->select('agent_id', DB::raw('AVG(duration) as avg_duration'))
                    ->groupBy('agent_id');
            }])
            ->get()
            ->map(function($agent) {
                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'status' => $agent->status,
                    'is_online' => $agent->is_online,
                    'total_calls' => $agent->total_calls,
                    'completed_calls' => $agent->completed_calls,
                    'avg_duration' => $agent->calls->first()->avg_duration ?? 0,
                ];
            });

        return response()->json($agents);
    }

    /**
     * Get call status distribution
     */
    public function callStatusDistribution(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $distribution = Call::where('tenant_id', $tenantId)
            ->whereDate('started_at', today())
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(fn($item) => [$item->status => $item->count]);

        return response()->json($distribution);
    }

    /**
     * Get recent activity
     */
    public function recentActivity(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $limit = $request->input('limit', 10);

        $recentCalls = Call::where('tenant_id', $tenantId)
            ->with(['agent'])
            ->latest('started_at')
            ->limit($limit)
            ->get()
            ->map(fn($call) => [
                'type' => 'call',
                'id' => $call->id,
                'description' => "{$call->direction} call {$call->status}",
                'agent' => $call->agent?->name,
                'timestamp' => $call->started_at,
            ]);

        $recentTickets = Ticket::where('tenant_id', $tenantId)
            ->with(['assignedTo'])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($ticket) => [
                'type' => 'ticket',
                'id' => $ticket->id,
                'description' => $ticket->subject,
                'agent' => $ticket->assignedTo?->name,
                'timestamp' => $ticket->created_at,
            ]);

        $activity = $recentCalls->concat($recentTickets)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();

        return response()->json($activity);
    }
}

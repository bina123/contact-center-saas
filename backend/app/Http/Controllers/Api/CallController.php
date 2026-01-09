<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Services\CallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CallController extends Controller
{
    public function __construct(
        protected CallService $callService
    ) {}

    /**
     * Get all calls with pagination
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $status = $request->input('status');
        $direction = $request->input('direction');

        $query = Call::with(['agent', 'campaign']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($direction) {
            $query->where('direction', $direction);
        }

        $calls = $query->latest('started_at')->paginate($perPage);

        return response()->json($calls);
    }

    /**
     * Get call by ID
     */
    public function show(Call $call): JsonResponse
    {
        $call->load(['agent', 'campaign']);
        
        return response()->json($call);
    }

    /**
     * Create a new call (webhook from telephony system)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'call_id' => 'required|string|unique:calls,call_id',
            'direction' => 'required|in:inbound,outbound',
            'from_number' => 'required|string',
            'to_number' => 'required|string',
            'agent_id' => 'nullable|exists:users,id',
            'campaign_id' => 'nullable|exists:campaigns,id',
            'metadata' => 'nullable|array',
        ]);

        $call = $this->callService->createCall($validated);

        return response()->json([
            'message' => 'Call created successfully',
            'call' => $call,
        ], 201);
    }

    /**
     * Answer a call
     */
    public function answer(Request $request, Call $call): JsonResponse
    {
        $agent = $request->user();

        $call = $this->callService->answerCall($call, $agent);

        return response()->json([
            'message' => 'Call answered',
            'call' => $call,
        ]);
    }

    /**
     * End a call
     */
    public function end(Request $request, Call $call): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
            'status' => 'nullable|in:completed,failed,no-answer,busy',
            'recording_url' => 'nullable|url',
        ]);

        $call = $this->callService->endCall($call, $validated);

        return response()->json([
            'message' => 'Call ended',
            'call' => $call,
        ]);
    }

    /**
     * Get call statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $filters = $request->only(['start_date', 'end_date', 'agent_id']);
        
        $statistics = $this->callService->getCallStatistics($filters);

        return response()->json($statistics);
    }

    /**
     * Get active calls
     */
    public function active(): JsonResponse
    {
        $activeCalls = Call::active()
            ->with(['agent'])
            ->get();

        return response()->json($activeCalls);
    }

    /**
     * Get my calls (agent)
     */
    public function myCalls(Request $request): JsonResponse
    {
        $agent = $request->user();
        $filters = $request->only(['status', 'direction', 'per_page']);

        $calls = $this->callService->getAgentCalls($agent, $filters);

        return response()->json($calls);
    }

    /**
     * Update call notes
     */
    public function updateNotes(Request $request, Call $call): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        $call->update(['notes' => $validated['notes']]);

        return response()->json([
            'message' => 'Notes updated successfully',
            'call' => $call,
        ]);
    }
}

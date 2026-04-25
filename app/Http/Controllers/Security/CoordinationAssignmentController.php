<?php

namespace App\Http\Controllers\Security;

use App\Actions\Security\AssignCoordinatorAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Security\StoreCoordinationAssignmentRequest;
use App\Models\Coordination;
use App\Models\CoordinationAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class CoordinationAssignmentController extends Controller
{
    /**
     * Return the full assignment history for a coordination as JSON.
     * Used by the frontend history modal (fetched on demand with fetch()).
     */
    public function index(Coordination $coordination): JsonResponse
    {
        Gate::authorize('viewHistory', $coordination);

        $assignments = $coordination->assignments()
            ->with('user:id,name', 'assignedBy:id,name')
            ->orderByDesc('assigned_at')
            ->get()
            ->map(fn (CoordinationAssignment $a) => [
                'id' => $a->id,
                'user' => ['id' => $a->user->id, 'name' => $a->user->name],
                'assigned_by' => $a->assignedBy
                    ? ['id' => $a->assignedBy->id, 'name' => $a->assignedBy->name]
                    : null,
                'assigned_at' => $a->assigned_at->toDateTimeString(),
                'ended_at' => $a->ended_at?->toDateTimeString(),
            ]);

        return response()->json($assignments);
    }

    /**
     * Assign a coordinator to the given coordination.
     * Closes any existing active assignment first.
     */
    public function store(StoreCoordinationAssignmentRequest $request, Coordination $coordination, AssignCoordinatorAction $action): RedirectResponse
    {
        $action->handle($coordination, $request->validated('user_id'), $request->user()->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Coordinador asignado.']);

        return to_route('security.coordinations.index');
    }
}

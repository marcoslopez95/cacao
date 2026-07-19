<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Resources\Academic\GuardianListResource;
use App\Http\Resources\Academic\GuardianShowResource;
use App\Models\Guardian;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GuardianController extends Controller
{
    /**
     * Display a paginated list of guardians with their student count.
     */
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $perPage = min(100, max(10, (int) $request->input('per_page', 25)));

        $guardians = Guardian::query()
            ->with('user')
            ->withCount('students')
            ->when($search, fn ($q) => $q->whereHas('user', function ($q) use ($search): void {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            }))
            ->orderBy(User::select('name')->whereColumn('users.id', 'guardians.user_id'))
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('admin/Guardians/Index', [
            'guardians' => GuardianListResource::collection($guardians),
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Display the students a single guardian is responsible for.
     */
    public function show(Guardian $guardian): Response
    {
        $guardian->load([
            'user',
            'students' => fn ($q) => $q->withPivot(['kinship_type_id', 'is_primary', 'is_emergency_contact']),
            'students.user',
        ]);

        return Inertia::render('admin/Guardians/Show', [
            'guardian' => (new GuardianShowResource($guardian))->resolve(),
        ]);
    }
}

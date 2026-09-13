<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Resources\Scheduling\ScheduleResource;
use App\Services\Scheduling\StudentScheduleResolver;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(Request $request, StudentScheduleResolver $resolver): Response
    {
        $student = $request->user()->student;
        abort_unless($student !== null, 404);

        return Inertia::render('student/Schedule', [
            'schedules' => ScheduleResource::collection($resolver->resolve($student))->resolve(),
        ]);
    }
}

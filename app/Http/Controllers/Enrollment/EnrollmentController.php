<?php

namespace App\Http\Controllers\Enrollment;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('enrollment/Index');
    }
}

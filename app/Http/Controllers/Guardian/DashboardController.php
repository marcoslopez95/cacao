<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index(): never
    {
        abort(501);
    }
}

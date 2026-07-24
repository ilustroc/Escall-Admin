<?php

namespace App\Http\Controllers;

use App\Queries\Dashboard\DashboardQuery;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(DashboardQuery $dashboard): Response
    {
        return Inertia::render('Dashboard/Index', $dashboard->get());
    }
}

<?php

namespace App\Http\Controllers\Expertis;

use App\Http\Controllers\Controller;
use App\Queries\Expertis\ExpertisDashboardQuery;
use Inertia\Inertia;
use Inertia\Response;

class ExpertisDashboardController extends Controller
{
    public function index(ExpertisDashboardQuery $dashboard): Response
    {
        return Inertia::render('Expertis/Dashboard', [
            'expertis' => $dashboard->get(),
            'legacy' => null,
        ]);
    }
}

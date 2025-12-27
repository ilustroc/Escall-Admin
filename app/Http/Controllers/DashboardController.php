<?php

namespace App\Http\Controllers;

use App\Services\ScheduleStatusService;

class DashboardController extends Controller
{
    public function index(ScheduleStatusService $schedule)
    {
        return view('dashboard', [
            'tasks' => $schedule->get(),
        ]);
    }
}

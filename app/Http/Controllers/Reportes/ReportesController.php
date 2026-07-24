<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ReportesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Reportes/Index');
    }
}

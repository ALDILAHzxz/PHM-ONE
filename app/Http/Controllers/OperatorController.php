<?php

namespace App\Http\Controllers;

use App\Models\ProductionAssignment;
use Illuminate\View\View;

class OperatorController extends Controller
{
    public function tasks(): View
    {
        return view('operator.tasks');
    }

    public function createReport(?ProductionAssignment $assignment = null): View
    {
        // Ownership is re-checked inside the Livewire component and again in
        // the service, so a guessed id cannot reach another operator's work.
        return view('operator.report-create', ['assignment' => $assignment]);
    }

    public function history(): View
    {
        return view('operator.history');
    }
}

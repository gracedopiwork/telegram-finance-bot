<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiagnosticStage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiagnosticStagesController extends Controller
{
    public function index(): View
    {
        $stages = DiagnosticStage::query()->orderBy('sort_order')->get();

        return view('admin.diagnostic_stages.index', compact('stages'));
    }

    public function edit(DiagnosticStage $diagnostic_stage): View
    {
        return view('admin.diagnostic_stages.form', ['stage' => $diagnostic_stage]);
    }

    public function update(Request $request, DiagnosticStage $diagnostic_stage): RedirectResponse
    {
        $data = $request->validate([
            'label' => 'required|string|max:64',
            'emoji' => 'nullable|string|max:8',
            'phase' => 'nullable|string|max:32',
            'diagnosis' => 'nullable|string|max:255',
            'risk_label' => 'nullable|string|max:128',
            'risk_description' => 'nullable|string|max:5000',
            'panel_color' => 'nullable|string|max:16',
            'illustration_url' => 'nullable|string|max:512',
            'score_min' => 'required|integer|min:0|max:99',
            'score_max' => 'required|integer|min:0|max:99|gte:score_min',
        ]);

        $diagnostic_stage->update($data);

        return redirect()->route('admin.diagnostic-stages.index')
            ->with('success', 'Tampilan hasil tahap diperbarui.');
    }
}

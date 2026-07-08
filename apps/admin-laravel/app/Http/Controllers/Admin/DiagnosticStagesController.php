<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiagnosticStage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'illustration_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'score_min' => 'required|integer|min:0|max:99',
            'score_max' => 'required|integer|min:0|max:99|gte:score_min',
        ]);

        if ($request->hasFile('illustration_file')) {
            $storedPath = $request->file('illustration_file')->store('diagnostic-stages', 'public');
            $data['illustration_url'] = Storage::disk('public')->url($storedPath);

            $oldPath = $this->publicStoragePathFromUrl((string) ($diagnostic_stage->illustration_url ?? ''));
            if ($oldPath !== null && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $diagnostic_stage->update($data);

        return redirect()->route('admin.diagnostic-stages.index')
            ->with('success', 'Tampilan hasil tahap diperbarui.');
    }

    private function publicStoragePathFromUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        $path = $parts['path'] ?? $url;
        if (! is_string($path) || $path === '') {
            return null;
        }

        $storagePrefix = '/storage/';
        if (! str_starts_with($path, $storagePrefix)) {
            return null;
        }

        $relative = ltrim(substr($path, strlen($storagePrefix)), '/');

        return $relative !== '' ? $relative : null;
    }
}

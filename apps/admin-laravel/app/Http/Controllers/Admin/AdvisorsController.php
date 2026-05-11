<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CpAdvisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdvisorsController extends Controller
{
    public function index()
    {
        $advisors = CpAdvisor::orderBy('sort')->get();
        return view('admin.advisors.index', compact('advisors'));
    }

    public function create()
    {
        return view('admin.advisors.form', [
            'advisor' => new CpAdvisor(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateAdvisor($request);
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('cp/advisors', 'public');
        }
        CpAdvisor::create($data);
        return redirect()->route('admin.advisors.index')->with('success', 'Penasihat ditambahkan.');
    }

    public function edit(CpAdvisor $advisor)
    {
        return view('admin.advisors.form', compact('advisor'));
    }

    public function update(Request $request, CpAdvisor $advisor)
    {
        $data = $this->validateAdvisor($request);

        if ($request->hasFile('photo')) {
            if ($advisor->photo_path && Storage::disk('public')->exists($advisor->photo_path)) {
                Storage::disk('public')->delete($advisor->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('cp/advisors', 'public');
        }

        $advisor->update($data);
        return redirect()->route('admin.advisors.index')->with('success', 'Penasihat diperbarui.');
    }

    public function destroy(CpAdvisor $advisor)
    {
        if ($advisor->photo_path && Storage::disk('public')->exists($advisor->photo_path)) {
            Storage::disk('public')->delete($advisor->photo_path);
        }
        $advisor->delete();
        return redirect()->route('admin.advisors.index')->with('success', 'Penasihat dihapus.');
    }

    private function validateAdvisor(Request $request): array
    {
        $data = $request->validate([
            'name'        => 'required|string|max:200',
            'role_label'  => 'nullable|string|max:100',
            'badges_text' => 'nullable|string',
            'years_exp'   => 'nullable|string|max:30',
            'spec_short'  => 'nullable|string|max:200',
            'spec_icon'   => 'nullable|string|max:60',
            'spec_long'   => 'nullable|string',
            'tag'         => 'nullable|string|max:30',
            'photo'       => 'nullable|image|max:2048',
            'sort'        => 'nullable|integer',
            'is_active'   => 'sometimes|boolean',
        ]);

        $badges = collect(preg_split("/\r\n|\n|\r|,/", $data['badges_text'] ?? ''))
            ->map(fn ($l) => trim($l))
            ->filter()
            ->values()
            ->toArray();
        unset($data['badges_text'], $data['photo']);
        $data['badges'] = $badges;
        $data['is_active'] = (bool) ($request->boolean('is_active', true));
        return $data;
    }
}

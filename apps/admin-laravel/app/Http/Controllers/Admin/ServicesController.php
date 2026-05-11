<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServicesController extends Controller
{
    public function index()
    {
        $services = CpService::orderBy('sort')->get();
        return view('admin.services.index', compact('services'));
    }

    public function create()
    {
        return view('admin.services.form', [
            'service' => new CpService(['section' => 'main']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateService($request);
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('cp/services', 'public');
        }
        CpService::create($data);
        return redirect()->route('admin.services.index')->with('success', 'Layanan ditambahkan.');
    }

    public function edit(CpService $service)
    {
        return view('admin.services.form', compact('service'));
    }

    public function update(Request $request, CpService $service)
    {
        $data = $this->validateService($request);
        if ($request->hasFile('image')) {
            if ($service->image_path && Storage::disk('public')->exists($service->image_path)) {
                Storage::disk('public')->delete($service->image_path);
            }
            $data['image_path'] = $request->file('image')->store('cp/services', 'public');
        }
        $service->update($data);
        return redirect()->route('admin.services.index')->with('success', 'Layanan diperbarui.');
    }

    public function destroy(CpService $service)
    {
        if ($service->image_path && Storage::disk('public')->exists($service->image_path)) {
            Storage::disk('public')->delete($service->image_path);
        }
        $service->delete();
        return redirect()->route('admin.services.index')->with('success', 'Layanan dihapus.');
    }

    private function validateService(Request $request): array
    {
        $data = $request->validate([
            'section'       => 'nullable|string|max:60',
            'eyebrow'       => 'nullable|string|max:100',
            'title'         => 'required|string|max:200',
            'description'   => 'nullable|string',
            'icon'          => 'nullable|string|max:60',
            'image'         => 'nullable|image|max:2048',
            'features_text' => 'nullable|string',
            'cta_label'     => 'nullable|string|max:100',
            'cta_route'     => 'nullable|string|max:100',
            'sort'          => 'nullable|integer',
            'is_active'     => 'sometimes|boolean',
        ]);

        $features = collect(preg_split("/\r\n|\n|\r/", $data['features_text'] ?? ''))
            ->map(fn ($l) => trim($l))
            ->filter()
            ->values()
            ->toArray();
        unset($data['features_text'], $data['image']);
        $data['features'] = $features;
        $data['is_active'] = (bool) ($request->boolean('is_active', true));
        return $data;
    }
}

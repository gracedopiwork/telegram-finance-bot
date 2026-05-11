<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CpPackage;
use Illuminate\Http\Request;

class PackagesController extends Controller
{
    public function index()
    {
        $packages = CpPackage::orderBy('sort')->get();
        return view('admin.packages.index', compact('packages'));
    }

    public function create()
    {
        return view('admin.packages.form', [
            'package' => new CpPackage(['variant' => 'plain', 'period' => '/paket']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatePackage($request);
        CpPackage::create($data);
        return redirect()->route('admin.packages.index')->with('success', 'Paket berhasil dibuat.');
    }

    public function edit(CpPackage $package)
    {
        return view('admin.packages.form', compact('package'));
    }

    public function update(Request $request, CpPackage $package)
    {
        $data = $this->validatePackage($request, $package->id);
        $package->update($data);
        return redirect()->route('admin.packages.index')->with('success', 'Paket berhasil diperbarui.');
    }

    public function destroy(CpPackage $package)
    {
        $package->delete();
        return redirect()->route('admin.packages.index')->with('success', 'Paket dihapus.');
    }

    private function validatePackage(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code'           => "required|string|max:30|unique:cp_packages,code,{$ignoreId}",
            'name'           => 'required|string|max:200',
            'name_en'        => 'nullable|string|max:200',
            'tier_label'     => 'nullable|string|max:60',
            'price'          => 'required|integer|min:0',
            'period'         => 'nullable|string|max:30',
            'description'    => 'nullable|string',
            'features_text'  => 'nullable|string',
            'variant'        => 'nullable|string|in:plain,featured',
            'is_recommended' => 'sometimes|boolean',
            'is_active'      => 'sometimes|boolean',
            'sort'           => 'nullable|integer',
        ]);

        // Convert features (textarea, one per line) to array
        $features = collect(preg_split("/\r\n|\n|\r/", $data['features_text'] ?? ''))
            ->map(fn ($l) => trim($l))
            ->filter()
            ->values()
            ->toArray();

        unset($data['features_text']);
        $data['features'] = $features;
        $data['is_recommended'] = (bool) ($request->boolean('is_recommended'));
        $data['is_active'] = (bool) ($request->boolean('is_active', true));

        return $data;
    }
}

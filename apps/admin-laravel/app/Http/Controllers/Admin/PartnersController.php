<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CpPartner;
use Illuminate\Http\Request;

class PartnersController extends Controller
{
    public function index()
    {
        $partners = CpPartner::orderBy('sort')->orderBy('id')->get();

        return view('admin.partners.index', compact('partners'));
    }

    public function create()
    {
        return view('admin.partners.form', [
            'partner' => new CpPartner(['is_active' => true, 'icon' => 'handshake', 'sort' => 0]),
        ]);
    }

    public function store(Request $request)
    {
        CpPartner::create($this->validatePartner($request));

        return redirect()->route('admin.partners.index')->with('success', 'Partner ditambahkan.');
    }

    public function edit(CpPartner $partner)
    {
        return view('admin.partners.form', compact('partner'));
    }

    public function update(Request $request, CpPartner $partner)
    {
        $partner->update($this->validatePartner($request));

        return redirect()->route('admin.partners.index')->with('success', 'Partner diperbarui.');
    }

    public function destroy(CpPartner $partner)
    {
        $partner->delete();

        return redirect()->route('admin.partners.index')->with('success', 'Partner dihapus.');
    }

    private function validatePartner(Request $request): array
    {
        $data = $request->validate([
            'title'       => 'required|string|max:120',
            'icon'        => 'nullable|string|max:80',
            'description' => 'nullable|string|max:2000',
            'sort'        => 'nullable|integer|min:0',
            'is_active'   => 'sometimes|boolean',
        ]);

        $data['icon'] = trim((string) ($data['icon'] ?? '')) ?: 'handshake';
        $data['sort'] = (int) ($data['sort'] ?? 0);
        $data['is_active'] = (bool) $request->boolean('is_active', true);

        return $data;
    }
}

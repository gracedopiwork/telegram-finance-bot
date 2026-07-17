<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Group order for display tabs.
     */
    private const GROUPS = [
        'brand'    => ['label' => 'Brand', 'icon' => 'fas fa-certificate'],
        'contact'  => ['label' => 'Kontak', 'icon' => 'fas fa-address-book'],
        'hero'     => ['label' => 'Hero (Home)', 'icon' => 'fas fa-bullhorn'],
        'about'    => ['label' => 'About / Latar Belakang', 'icon' => 'fas fa-history'],
        'stats'    => ['label' => 'Statistik', 'icon' => 'fas fa-chart-bar'],
        'vision'   => ['label' => 'Visi', 'icon' => 'fas fa-flag'],
        'mission'  => ['label' => 'Misi (8 poin)', 'icon' => 'fas fa-list-ol'],
        'values'   => ['label' => 'Core Values (6)', 'icon' => 'fas fa-heart'],
        'bot'      => ['label' => 'Integrasi Bot & Email', 'icon' => 'fab fa-telegram'],
        'affiliate'=> ['label' => 'Referral / Affiliate', 'icon' => 'fas fa-handshake'],
        'reviews'  => ['label' => 'Testimoni Homepage', 'icon' => 'fas fa-star'],
    ];

    public function index(Request $request)
    {
        $activeGroup = $request->query('group', 'brand');
        if (! array_key_exists($activeGroup, self::GROUPS)) {
            $activeGroup = 'brand';
        }

        $settings = Setting::where('group', $activeGroup)->orderBy('sort')->get();

        return view('admin.settings.index', [
            'groups'      => self::GROUPS,
            'activeGroup' => $activeGroup,
            'settings'    => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $group = $request->input('_group', 'brand');

        $payload = $request->input('settings', []);
        if (! is_array($payload)) {
            return back()->with('error', 'Invalid payload.');
        }

        foreach ($payload as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value]);
        }

        // Handle logo upload (special case)
        if ($request->hasFile('logo_file')) {
            $file = $request->file('logo_file');
            $request->validate([
                'logo_file' => 'image|max:2048',
            ]);
            $path = $file->store('cp/brand', 'public');
            Setting::where('key', 'brand.logo')->update(['value' => 'storage/' . $path]);
        }

        if ($request->hasFile('logo_footer_file')) {
            $file = $request->file('logo_footer_file');
            $request->validate([
                'logo_footer_file' => 'image|max:2048',
            ]);
            $path = $file->store('cp/brand', 'public');
            Setting::where('key', 'brand.logo_footer')->update(['value' => 'storage/' . $path]);
        }

        Setting::bust();

        return redirect()
            ->route('admin.settings.index', ['group' => $group])
            ->with('success', 'Settings berhasil disimpan.');
    }
}

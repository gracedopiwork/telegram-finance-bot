<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CpDigitalProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DigitalProductsController extends Controller
{
    public function index()
    {
        $products = CpDigitalProduct::orderBy('sort')->orderBy('name')->get();
        return view('admin.digital_products.index', compact('products'));
    }

    public function create()
    {
        return view('admin.digital_products.form', [
            'product' => new CpDigitalProduct([
                'icon'         => 'auto_awesome',
                'currency'     => 'IDR',
                'period'       => 'per tahun',
                'billing_mode' => 'pivot',
                'cta_label'    => 'Beli Sekarang',
                'is_active'    => true,
                'sort'         => 0,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateProduct($request);
        $data['image_url'] = $this->handleImage($request, null);

        CpDigitalProduct::create($data);

        return redirect()->route('admin.digital-products.index')
                         ->with('success', 'Produk digital berhasil dibuat.');
    }

    public function edit(CpDigitalProduct $digital_product)
    {
        return view('admin.digital_products.form', ['product' => $digital_product]);
    }

    public function update(Request $request, CpDigitalProduct $digital_product)
    {
        $data = $this->validateProduct($request, $digital_product->id);
        $data['image_url'] = $this->handleImage($request, $digital_product->image_url);

        $digital_product->update($data);

        return redirect()->route('admin.digital-products.index')
                         ->with('success', 'Produk digital berhasil diperbarui.');
    }

    public function updateBillingMode(Request $request, CpDigitalProduct $digital_product)
    {
        $data = $request->validate([
            'billing_mode' => 'required|in:pivot,midtrans,wa,url,soon',
        ]);

        $payload = ['billing_mode' => $data['billing_mode']];

        if ($data['billing_mode'] === 'soon') {
            $payload['badge'] = 'Coming Soon';
            $payload['cta_label'] = 'Coming Soon';
        } elseif ($digital_product->billing_mode === 'soon') {
            // Kembali jual: bersihkan label Coming Soon default
            if (strtolower(trim((string) $digital_product->badge)) === 'coming soon') {
                $payload['badge'] = 'Tersedia';
            }
            if (strtolower(trim((string) $digital_product->cta_label)) === 'coming soon') {
                $payload['cta_label'] = 'Beli Sekarang';
            }
        }

        $digital_product->update($payload);

        $label = $data['billing_mode'] === 'soon' ? 'Coming Soon' : strtoupper($data['billing_mode']);

        return redirect()->route('admin.digital-products.index')
            ->with('success', "Mode \"{$digital_product->name}\" diubah ke {$label}.");
    }

    public function destroy(CpDigitalProduct $digital_product)
    {
        if ($digital_product->image_url && Str::startsWith($digital_product->image_url, '/storage/')) {
            $path = Str::after($digital_product->image_url, '/storage/');
            Storage::disk('public')->delete($path);
        }
        $digital_product->delete();
        return redirect()->route('admin.digital-products.index')
                         ->with('success', 'Produk dihapus.');
    }

    /* ---------------------------------------------------------------------- */

    private function validateProduct(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code'             => "required|string|max:50|alpha_dash|unique:cp_digital_products,code,{$ignoreId}",
            'name'             => 'required|string|max:150',
            'tagline'          => 'nullable|string|max:200',
            'description'      => 'nullable|string',
            'icon'             => 'nullable|string|max:60',
            'image'            => 'nullable|image|max:2048',          // upload baru
            'image_url'        => 'nullable|string|max:255',          // URL existing
            'badge'            => 'nullable|string|max:60',
            'is_active'        => 'sometimes|boolean',
            'is_featured'      => 'sometimes|boolean',
            'sort'             => 'nullable|integer|min:0',
            'price'            => 'required|integer|min:0',
            'discount_price'   => 'nullable|integer|min:0|lt:price',
            'currency'         => 'nullable|string|size:3',
            'period'           => 'nullable|string|max:60',
            'features_text'    => 'nullable|string',
            'billing_mode'     => 'required|in:pivot,midtrans,wa,url,soon',
            'cta_label'        => 'nullable|string|max:80',
            'cta_url'          => 'nullable|url|max:255',
            'meta_title'               => 'nullable|string|max:255',
            'meta_description'         => 'nullable|string|max:500',
            'demo_video_enabled'       => 'sometimes|boolean',
            'demo_video_url'           => 'nullable|string|max:500',
            'demo_video_description'   => 'nullable|string',
        ]);

        $features = collect(preg_split("/\r\n|\n|\r/", $data['features_text'] ?? ''))
            ->map(fn ($l) => trim($l))
            ->filter()
            ->values()
            ->toArray();

        unset($data['features_text'], $data['image']);

        $data['features']    = $features;
        $data['currency']    = strtoupper($data['currency'] ?? 'IDR');
        $data['is_active']            = (bool) $request->boolean('is_active', true);
        $data['is_featured']          = (bool) $request->boolean('is_featured');
        $data['demo_video_enabled']   = (bool) $request->boolean('demo_video_enabled');
        $data['sort']                 = (int) ($data['sort'] ?? 0);
        $data['cta_label']            = $data['cta_label'] ?: 'Beli Sekarang';
        $data['demo_video_url']       = filled($data['demo_video_url'] ?? null) ? trim($data['demo_video_url']) : null;

        if (($data['billing_mode'] ?? '') === 'soon') {
            if (! filled($data['badge'] ?? null) || strtolower(trim((string) $data['badge'])) === 'tersedia') {
                $data['badge'] = 'Coming Soon';
            }
            if (! filled($data['cta_label'] ?? null) || in_array(strtolower(trim((string) $data['cta_label'])), ['beli sekarang', 'beli'], true)) {
                $data['cta_label'] = 'Coming Soon';
            }
        }

        // Discount tidak boleh ada kalau price 0
        if (($data['price'] ?? 0) <= 0) {
            $data['discount_price'] = null;
        }

        return $data;
    }

    private function handleImage(Request $request, ?string $current): ?string
    {
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('digital-products', 'public');
            return '/storage/' . $path;
        }
        return $request->input('image_url') ?: $current;
    }
}

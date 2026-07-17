<?php

namespace App\Services;

use App\Models\CpDigitalProduct;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DigitalProductMenuService
{
    /**
     * Item menu mega-dropdown "Produk Digital" — dari produk aktif ber-flag featured.
     *
     * @return list<array<string, mixed>>
     */
    public function featuredMenuItems(): array
    {
        if (! Schema::hasTable('cp_digital_products')) {
            return $this->fallbackItems();
        }

        try {
            $products = CpDigitalProduct::query()
                ->active()
                ->featured()
                ->orderBy('sort')
                ->orderBy('id')
                ->limit(6)
                ->get();

            if ($products->isEmpty()) {
                $products = CpDigitalProduct::query()
                    ->active()
                    ->orderBy('sort')
                    ->orderBy('id')
                    ->limit(3)
                    ->get();
            }

            if ($products->isEmpty()) {
                return $this->fallbackItems();
            }

            return $products->map(fn (CpDigitalProduct $p) => $this->toMenuItem($p))->all();
        } catch (\Throwable) {
            return $this->fallbackItems();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function toMenuItem(CpDigitalProduct $product): array
    {
        $desc = trim((string) ($product->tagline ?: ''));
        if ($desc === '' && $product->description) {
            $desc = Str::limit(strip_tags((string) $product->description), 90);
        }
        $period = trim((string) ($product->period ?? ''));
        if ($period !== '' && ! in_array($period, ['—', 'gratis'], true)) {
            $desc = $desc !== '' ? "{$desc} · {$period}" : $period;
        }

        $isSoon = ($product->billing_mode ?? '') === 'soon'
            || str_contains(strtolower((string) ($product->badge ?? '')), 'soon');

        $badge = $isSoon
            ? 'Coming Soon'
            : (trim((string) ($product->badge ?? '')) ?: 'Tersedia');

        $item = [
            'key' => $product->code,
            'label' => $product->name,
            'desc' => $desc,
            'icon' => $product->icon ?: 'inventory_2',
            'badge' => $badge,
            'route' => null,
            'url' => null,
            'new_tab' => false,
        ];

        // Coming Soon: item tidak bisa diklik sama sekali
        if ($isSoon) {
            return $item;
        }

        return array_merge($item, $this->resolveLink($product));
    }

    /**
     * @return array{route: ?string, url: ?string, new_tab: bool}
     */
    private function resolveLink(CpDigitalProduct $product): array
    {
        return match ($product->billing_mode) {
            'midtrans' => ['route' => null, 'url' => route('checkout.show', $product->code), 'new_tab' => false],
            'url' => [
                'route' => null,
                'url' => $product->cta_url ?: route('company.produk'),
                'new_tab' => true,
            ],
            'wa' => [
                'route' => null,
                'url' => $product->cta_url ?: null,
                'new_tab' => true,
            ],
            default => ['route' => null, 'url' => route('company.produk'), 'new_tab' => false],
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fallbackItems(): array
    {
        return [
            [
                'key' => 'produk',
                'label' => 'YFD First Aid',
                'desc' => 'Catat keuangan via chat — AI auto-parse ke dashboard web',
                'route' => null,
                'icon' => 'send',
                'badge' => 'Coming Soon',
                'url' => null,
                'new_tab' => false,
            ],
        ];
    }
}

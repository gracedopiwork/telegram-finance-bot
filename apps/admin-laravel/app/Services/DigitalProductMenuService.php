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

        $item = [
            'key' => $product->code,
            'label' => $product->name,
            'desc' => $desc,
            'icon' => $product->icon ?: 'inventory_2',
            'badge' => $product->badge,
            'route' => null,
            'url' => null,
            'new_tab' => false,
        ];

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
            default => ['route' => null, 'url' => null, 'new_tab' => false],
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
                'label' => 'YFD Bot Telegram',
                'desc' => 'Catat keuangan via chat — AI auto-parse ke dashboard web',
                'route' => 'company.produk',
                'icon' => 'send',
                'badge' => 'Tersedia',
                'url' => null,
                'new_tab' => false,
            ],
        ];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoryBucketMapping;
use App\Services\CategoryBucketMappingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryBucketMappingsController extends Controller
{
    public function index(): View
    {
        $tableReady = Schema::hasTable('category_bucket_mappings');
        $mappings = $tableReady
            ? CategoryBucketMapping::query()->orderBy('sort_order')->orderBy('id')->get()
            : collect();

        return view('admin.category_bucket_mappings.index', [
            'mappings' => $mappings,
            'buckets' => CategoryBucketMapping::BUCKETS,
            'tableReady' => $tableReady,
        ]);
    }

    public function create(): View
    {
        return view('admin.category_bucket_mappings.form', [
            'mapping' => new CategoryBucketMapping([
                'is_active' => true,
                'transaction_type' => 'expense',
                'sort_order' => 0,
            ]),
            'buckets' => CategoryBucketMapping::BUCKETS,
            'transactionTypes' => CategoryBucketMapping::TRANSACTION_TYPES,
            'natures' => CategoryBucketMapping::NATURES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($error = $this->ensureTableReady()) {
            return $error;
        }

        $mapping = CategoryBucketMapping::query()->create($this->validated($request));
        app(CategoryBucketMappingService::class)->forgetCache();

        return redirect()->route('admin.category-bucket-mappings.index')
            ->with('success', "Pemetaan «{$mapping->category}» ditambahkan.");
    }

    public function edit(CategoryBucketMapping $category_bucket_mapping): View
    {
        return view('admin.category_bucket_mappings.form', [
            'mapping' => $category_bucket_mapping,
            'buckets' => CategoryBucketMapping::BUCKETS,
            'transactionTypes' => CategoryBucketMapping::TRANSACTION_TYPES,
            'natures' => CategoryBucketMapping::NATURES,
        ]);
    }

    public function update(Request $request, CategoryBucketMapping $category_bucket_mapping): RedirectResponse
    {
        if ($error = $this->ensureTableReady()) {
            return $error;
        }

        $category_bucket_mapping->update($this->validated($request));
        app(CategoryBucketMappingService::class)->forgetCache();

        return redirect()->route('admin.category-bucket-mappings.index')
            ->with('success', 'Pemetaan bucket diperbarui.');
    }

    public function destroy(CategoryBucketMapping $category_bucket_mapping): RedirectResponse
    {
        if ($error = $this->ensureTableReady()) {
            return $error;
        }

        $category_bucket_mapping->delete();
        app(CategoryBucketMappingService::class)->forgetCache();

        return redirect()->route('admin.category-bucket-mappings.index')
            ->with('success', 'Pemetaan bucket dihapus.');
    }

    public function syncDefaults(): RedirectResponse
    {
        if ($error = $this->ensureTableReady()) {
            return $error;
        }

        $this->seedDefaults();
        app(CategoryBucketMappingService::class)->forgetCache();

        return redirect()->route('admin.category-bucket-mappings.index')
            ->with('success', 'Data default bucket YFD disinkronkan dari template resmi.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:128'],
            'sub_category' => ['nullable', 'string', 'max:128'],
            'bucket' => ['required', Rule::in(CategoryBucketMapping::BUCKETS)],
            'transaction_type' => ['required', Rule::in(array_keys(CategoryBucketMapping::TRANSACTION_TYPES))],
            'nature' => ['nullable', Rule::in(array_filter(CategoryBucketMapping::NATURES))],
            'match_keywords' => ['nullable', 'string', 'max:5000'],
            'reason' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['nature'] = $data['nature'] !== '' ? ($data['nature'] ?? null) : null;
        $data['sub_category'] = null;

        return $data;
    }

    private function ensureTableReady(): ?RedirectResponse
    {
        if (Schema::hasTable('category_bucket_mappings')) {
            return null;
        }

        return redirect()->route('admin.category-bucket-mappings.index')
            ->with('error', 'Tabel category_bucket_mappings belum ada. Jalankan: php artisan migrate --force');
    }

    private function seedDefaults(): void
    {
        $rows = (array) config('category_bucket_mappings_defaults', []);
        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            CategoryBucketMapping::query()->updateOrCreate(
                [
                    'category' => $row['category'],
                    'sub_category' => $row['sub_category'] ?? null,
                    'transaction_type' => $row['transaction_type'] ?? 'expense',
                ],
                [
                    'bucket' => $row['bucket'],
                    'nature' => $row['nature'] ?? null,
                    'match_keywords' => $row['match_keywords'] ?? null,
                    'reason' => $row['reason'] ?? null,
                    'sort_order' => (int) ($row['sort_order'] ?? $index),
                    'is_active' => true,
                ],
            );
        }
    }
}

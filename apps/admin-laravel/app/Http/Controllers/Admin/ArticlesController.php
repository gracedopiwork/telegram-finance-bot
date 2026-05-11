<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CpArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ArticlesController extends Controller
{
    public function index()
    {
        $articles = CpArticle::orderBy('sort')->orderByDesc('id')->get();
        return view('admin.articles.index', compact('articles'));
    }

    public function create()
    {
        return view('admin.articles.form', [
            'article' => new CpArticle(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateArticle($request);
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('cp/articles', 'public');
        }
        CpArticle::create($data);
        return redirect()->route('admin.articles.index')->with('success', 'Artikel dibuat.');
    }

    public function edit(CpArticle $article)
    {
        return view('admin.articles.form', compact('article'));
    }

    public function update(Request $request, CpArticle $article)
    {
        $data = $this->validateArticle($request, $article->id);
        if ($request->hasFile('image')) {
            if ($article->image_path && Storage::disk('public')->exists($article->image_path)) {
                Storage::disk('public')->delete($article->image_path);
            }
            $data['image_path'] = $request->file('image')->store('cp/articles', 'public');
        }
        $article->update($data);
        return redirect()->route('admin.articles.index')->with('success', 'Artikel diperbarui.');
    }

    public function destroy(CpArticle $article)
    {
        if ($article->image_path && Storage::disk('public')->exists($article->image_path)) {
            Storage::disk('public')->delete($article->image_path);
        }
        $article->delete();
        return redirect()->route('admin.articles.index')->with('success', 'Artikel dihapus.');
    }

    private function validateArticle(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'slug'          => "nullable|string|max:200|unique:cp_articles,slug,{$ignoreId}",
            'title'         => 'required|string|max:300',
            'category'      => 'nullable|string|max:60',
            'read_time'     => 'nullable|string|max:30',
            'views_label'   => 'nullable|string|max:30',
            'description'   => 'nullable|string',
            'content_html'  => 'nullable|string',
            'image'         => 'nullable|image|max:2048',
            'sort'          => 'nullable|integer',
            'is_active'     => 'sometimes|boolean',
        ]);
        unset($data['image']);
        $data['is_active'] = (bool) ($request->boolean('is_active', true));
        return $data;
    }
}

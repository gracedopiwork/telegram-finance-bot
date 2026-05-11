<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CpFaq;
use Illuminate\Http\Request;

class FaqsController extends Controller
{
    public function index()
    {
        $faqs = CpFaq::orderBy('sort')->get();
        return view('admin.faqs.index', compact('faqs'));
    }

    public function create()
    {
        return view('admin.faqs.form', [
            'faq' => new CpFaq(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateFaq($request);
        CpFaq::create($data);
        return redirect()->route('admin.faqs.index')->with('success', 'FAQ ditambahkan.');
    }

    public function edit(CpFaq $faq)
    {
        return view('admin.faqs.form', compact('faq'));
    }

    public function update(Request $request, CpFaq $faq)
    {
        $data = $this->validateFaq($request);
        $faq->update($data);
        return redirect()->route('admin.faqs.index')->with('success', 'FAQ diperbarui.');
    }

    public function destroy(CpFaq $faq)
    {
        $faq->delete();
        return redirect()->route('admin.faqs.index')->with('success', 'FAQ dihapus.');
    }

    private function validateFaq(Request $request): array
    {
        $data = $request->validate([
            'category'  => 'nullable|string|max:60',
            'question'  => 'required|string|max:500',
            'answer'    => 'required|string',
            'sort'      => 'nullable|integer',
            'is_active' => 'sometimes|boolean',
        ]);
        $data['is_active'] = (bool) ($request->boolean('is_active', true));
        return $data;
    }
}

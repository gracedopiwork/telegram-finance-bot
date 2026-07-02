<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FtsaQuestion;
use App\Services\FtsaConfigService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FtsaQuestionsController extends Controller
{
    public function index(FtsaConfigService $ftsaConfig): View
    {
        $questions = FtsaQuestion::query()
            ->orderBy('sort_order')
            ->orderBy('question_num')
            ->get();

        return view('admin.ftsa_questions.index', [
            'questions' => $questions,
            'schemaReady' => $ftsaConfig->usesDatabase(),
            'domainOptions' => $ftsaConfig->domainOptions(),
        ]);
    }

    public function edit(FtsaQuestion $ftsa_question): View
    {
        return view('admin.ftsa_questions.form', [
            'question' => $ftsa_question,
            'domainOptions' => app(FtsaConfigService::class)->domainOptions(),
        ]);
    }

    public function update(Request $request, FtsaQuestion $ftsa_question): RedirectResponse
    {
        $domainKeys = array_column(app(FtsaConfigService::class)->domainOptions(), 'value');
        if ($domainKeys === []) {
            $domainKeys = ['chd', 'rvd', 'ssd', 'esd'];
        }

        $data = $request->validate([
            'domain_key' => ['required', 'string', 'in:'.implode(',', $domainKeys)],
            'text' => ['required', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? $ftsa_question->question_num);

        $ftsa_question->update($data);

        return redirect()->route('admin.ftsa-questions.index')
            ->with('success', "Soal FTSA #{$ftsa_question->question_num} diperbarui.");
    }

    public function sync(FtsaConfigService $ftsaConfig): RedirectResponse
    {
        if (! $ftsaConfig->usesDatabase()) {
            return back()->with('error', 'Tabel ftsa_questions belum ada. Jalankan php artisan migrate --force');
        }

        $count = $ftsaConfig->syncFromConfig();

        return redirect()->route('admin.ftsa-questions.index')
            ->with('success', "Sinkron dari config selesai — {$count} soal FTSA diperbarui.");
    }
}

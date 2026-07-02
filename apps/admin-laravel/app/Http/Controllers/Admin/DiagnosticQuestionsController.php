<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiagnosticQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiagnosticQuestionsController extends Controller
{
    public function index(): View
    {
        $questions = DiagnosticQuestion::query()
            ->withCount('options')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.diagnostic_questions.index', compact('questions'));
    }

    public function create(): View
    {
        return view('admin.diagnostic_questions.form', [
            'question' => new DiagnosticQuestion(['is_active' => true, 'is_scored' => false]),
            'options' => collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateQuestion($request);
        $question = DiagnosticQuestion::query()->create($data);
        $this->syncOptions($question, $request->input('options', []));

        return redirect()->route('admin.diagnostic-questions.index')
            ->with('success', 'Soal diagnostik ditambahkan.');
    }

    public function edit(DiagnosticQuestion $diagnostic_question): View
    {
        $diagnostic_question->load('options');

        return view('admin.diagnostic_questions.form', [
            'question' => $diagnostic_question,
            'options' => $diagnostic_question->options,
        ]);
    }

    public function update(Request $request, DiagnosticQuestion $diagnostic_question): RedirectResponse
    {
        $data = $this->validateQuestion($request, $diagnostic_question->id);
        $diagnostic_question->update($data);
        $this->syncOptions($diagnostic_question, $request->input('options', []));

        return redirect()->route('admin.diagnostic-questions.index')
            ->with('success', 'Soal diagnostik diperbarui.');
    }

    public function destroy(DiagnosticQuestion $diagnostic_question): RedirectResponse
    {
        $diagnostic_question->delete();

        return redirect()->route('admin.diagnostic-questions.index')
            ->with('success', 'Soal diagnostik dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateQuestion(Request $request, ?int $ignoreId = null): array
    {
        $uniqueRule = 'required|string|max:32|alpha_dash|unique:diagnostic_questions,question_key';
        if ($ignoreId) {
            $uniqueRule .= ','.$ignoreId;
        }

        $data = $request->validate([
            'question_key' => $uniqueRule,
            'section' => 'required|string|max:128',
            'text' => 'required|string|max:2000',
            'note' => 'nullable|string|max:5000',
            'is_scored' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'options' => 'required|array|min:1',
            'options.*.option_key' => 'required|string|max:64|alpha_dash',
            'options.*.label' => 'required|string|max:512',
            'options.*.score' => 'nullable|integer',
            'options.*.sort_order' => 'nullable|integer|min:0',
        ]);

        $data['is_scored'] = $request->boolean('is_scored');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    private function syncOptions(DiagnosticQuestion $question, array $options): void
    {
        $question->options()->delete();

        foreach (array_values($options) as $index => $opt) {
            if (! is_array($opt)) {
                continue;
            }
            $question->options()->create([
                'option_key' => $opt['option_key'],
                'label' => $opt['label'],
                'score' => $question->is_scored ? (int) ($opt['score'] ?? 0) : null,
                'sort_order' => (int) ($opt['sort_order'] ?? $index),
            ]);
        }
    }
}

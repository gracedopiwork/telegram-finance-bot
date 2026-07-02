@extends('admin.layouts.page')

@section('page_heading', $question->exists ? 'Edit Soal Diagnostik' : 'Tambah Soal Diagnostik')

@section('page_actions')
<a href="{{ route('admin.diagnostic-questions.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left mr-1"></i> Kembali</a>
@endsection

@section('main')
@php
    $optionRows = old('options');
    if (! is_array($optionRows) || $optionRows === []) {
        $optionRows = $options->map(fn ($o) => [
            'option_key' => $o->option_key,
            'label' => $o->label,
            'score' => $o->score,
            'sort_order' => $o->sort_order,
        ])->values()->all();
    }
    if ($optionRows === []) {
        $optionRows = [['option_key' => '', 'label' => '', 'score' => 0, 'sort_order' => 0]];
    }
@endphp

<form method="POST" action="{{ $question->exists ? route('admin.diagnostic-questions.update', $question) : route('admin.diagnostic-questions.store') }}">
    @csrf
    @if($question->exists) @method('PUT') @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card card-outline card-success mb-3">
                <div class="card-header"><strong>Soal</strong></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Kode soal <span class="text-danger">*</span></label>
                            <input type="text" name="question_key" class="form-control" value="{{ old('question_key', $question->question_key) }}" placeholder="q4" required>
                            <small class="text-muted">Contoh: q1, q4 — dipakai sistem skor.</small>
                        </div>
                        <div class="form-group col-md-8">
                            <label>Seksi <span class="text-danger">*</span></label>
                            <input type="text" name="section" class="form-control" value="{{ old('section', $question->section) }}" placeholder="Profil Dasar" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Teks pertanyaan <span class="text-danger">*</span></label>
                        <textarea name="text" rows="3" class="form-control" required>{{ old('text', $question->text) }}</textarea>
                    </div>
                    <div class="form-group mb-0">
                        <label>Catatan (tampil di form check-up)</label>
                        <textarea name="note" rows="3" class="form-control" placeholder="Penjelasan agar responden tidak salah pilih jawaban...">{{ old('note', $question->note) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card card-outline card-info">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Pilihan jawaban</strong>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-option"><i class="fas fa-plus"></i> Tambah opsi</button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0" id="options-table">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:14%">Kode</th>
                                <th>Label jawaban</th>
                                <th style="width:12%" class="score-col">Skor</th>
                                <th style="width:10%">Urut</th>
                                <th style="width:6%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($optionRows as $i => $opt)
                                <tr class="option-row">
                                    <td><input type="text" name="options[{{ $i }}][option_key]" class="form-control form-control-sm" value="{{ $opt['option_key'] ?? '' }}" required></td>
                                    <td><input type="text" name="options[{{ $i }}][label]" class="form-control form-control-sm" value="{{ $opt['label'] ?? '' }}" required></td>
                                    <td class="score-col"><input type="number" name="options[{{ $i }}][score]" class="form-control form-control-sm" value="{{ $opt['score'] ?? 0 }}"></td>
                                    <td><input type="number" name="options[{{ $i }}][sort_order]" class="form-control form-control-sm" value="{{ $opt['sort_order'] ?? $i }}"></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-option"><i class="fas fa-times"></i></button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-outline card-secondary mb-3">
                <div class="card-body">
                    <div class="form-group">
                        <label>Langkah wizard (1–16) <span class="text-danger">*</span></label>
                        <input type="number" name="wizard_step" class="form-control" min="1" max="16"
                               value="{{ old('wizard_step', $question->wizard_step ?? 1) }}" required>
                        <small class="text-muted">Nomor layar di check-up landing. Langkah 1 = profil (bisa berisi beberapa sub-soal).</small>
                    </div>
                    <div class="form-group">
                        <label>Urutan tampil</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $question->sort_order ?? 0) }}">
                    </div>
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" name="is_scored" value="1" id="is_scored" class="custom-control-input" {{ old('is_scored', $question->is_scored) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="is_scored">Soal ber-skor (masuk perhitungan tahap)</label>
                    </div>
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" name="is_active" value="1" id="is_active" class="custom-control-input" {{ old('is_active', $question->is_active ?? true) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="is_active">Aktif</label>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-success btn-block"><i class="fas fa-save mr-1"></i> Simpan</button>
        </div>
    </div>
</form>
@endsection

@section('admin_js')
<script>
(function () {
    let rowIndex = {{ count($optionRows) }};
    const tbody = document.querySelector('#options-table tbody');
    const scored = document.getElementById('is_scored');

    function toggleScoreCols() {
        document.querySelectorAll('.score-col').forEach(el => {
            el.style.display = scored.checked ? '' : 'none';
        });
    }

    document.getElementById('add-option').addEventListener('click', function () {
        const tr = document.createElement('tr');
        tr.className = 'option-row';
        tr.innerHTML = `
            <td><input type="text" name="options[${rowIndex}][option_key]" class="form-control form-control-sm" required></td>
            <td><input type="text" name="options[${rowIndex}][label]" class="form-control form-control-sm" required></td>
            <td class="score-col"><input type="number" name="options[${rowIndex}][score]" class="form-control form-control-sm" value="0"></td>
            <td><input type="number" name="options[${rowIndex}][sort_order]" class="form-control form-control-sm" value="${rowIndex}"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-option"><i class="fas fa-times"></i></button></td>
        `;
        tbody.appendChild(tr);
        rowIndex++;
        toggleScoreCols();
    });

    tbody.addEventListener('click', function (e) {
        const btn = e.target.closest('.remove-option');
        if (!btn) return;
        const rows = tbody.querySelectorAll('.option-row');
        if (rows.length <= 1) return;
        btn.closest('tr').remove();
    });

    scored.addEventListener('change', toggleScoreCols);
    toggleScoreCols();
})();
</script>
@stop

@extends('admin.layouts.page')

@section('page_heading', 'Hasil Diagnostik')
@section('page_subheading', 'Email responden, tahap keuangan, dan ringkasan jawaban check-up. Export menyertakan isian tiap pertanyaan (bisa dibuka di Excel / Google Sheets).')

@section('page_actions')
<a href="{{ route('admin.diagnostic-results.export', request()->query() + ['format' => 'xlsx', 'layout' => 'wide']) }}" class="btn btn-success btn-sm mr-1">
    <i class="fas fa-file-excel mr-1"></i> Export Excel (lengkap)
</a>
<a href="{{ route('admin.diagnostic-results.export', request()->query() + ['format' => 'csv', 'layout' => 'long']) }}" class="btn btn-outline-success btn-sm mr-1">
    <i class="fas fa-file-csv mr-1"></i> Export CSV (per jawaban)
</a>
<a href="{{ route('admin.diagnostic-questions.index') }}" class="btn btn-outline-secondary btn-sm mr-1">
    <i class="fas fa-clipboard-list mr-1"></i> Soal Diagnostik
</a>
<a href="{{ route('admin.ftsa-results.index') }}" class="btn btn-outline-primary btn-sm mr-1">
    <i class="fas fa-brain mr-1"></i> Hasil FTSA
</a>
<a href="{{ route('admin.diagnostic-stages.index') }}" class="btn btn-outline-info btn-sm">
    <i class="fas fa-palette mr-1"></i> Tahap Hasil
</a>
@endsection

@section('main')
@if(!$schemaReady)
    <div class="alert alert-warning">Tabel baseline belum siap. Jalankan <code>php artisan migrate --force</code>.</div>
@endif

<div class="card card-outline card-success mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.diagnostic-results.index') }}" class="form-inline flex-wrap" style="gap:.5rem;">
            <div class="input-group input-group-sm" style="width:280px;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari email / tahap / Telegram ID…">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-success"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <select name="stage" class="form-control form-control-sm" style="width:160px;" onchange="this.form.submit()">
                <option value="">Semua tahap</option>
                @foreach($stages as $key => $meta)
                    <option value="{{ $key }}" @selected(request('stage') === $key)>{{ $meta['label'] ?? ucfirst($key) }}</option>
                @endforeach
            </select>
            <select name="source" class="form-control form-control-sm" style="width:150px;" onchange="this.form.submit()">
                <option value="">Semua sumber</option>
                <option value="landing" @selected(request('source') === 'landing')>Landing (gratis)</option>
                <option value="portal" @selected(request('source') === 'portal')>Portal / Bot</option>
            </select>
            @if(request()->hasAny(['search', 'stage', 'source']))
                <a href="{{ route('admin.diagnostic-results.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            @endif
        </form>
    </div>
</div>

<div class="card card-outline card-success">
    <div class="card-body">
        <table id="diag-results-table" class="table table-hover table-sm" style="width:100%">
            <thead class="thead-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Email</th>
                    <th>Tahap</th>
                    <th class="text-center">Skor</th>
                    <th>FTSA</th>
                    <th>Sumber</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($results as $row)
                    @php
                        $email = $summaryService->resolvedEmail($row);
                        $ftsaFilled = app(\App\Services\FtsaAnswerSummaryService::class)->scoreSummary($row)['filled'];
                    @endphp
                    <tr>
                        <td class="text-nowrap">{{ $row->formatDate('d M Y H:i') }}</td>
                        <td>
                            @if($email)
                                <a href="mailto:{{ $email }}">{{ $email }}</a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-info">{{ $row->stage_label ?: ucfirst($row->financial_stage) }}</span>
                        </td>
                        <td class="text-center font-weight-bold">{{ $row->financial_stage_score }}/39</td>
                        <td>
                            @if(($row->dominant_archetype ?? '') === 'locked' && $ftsaFilled === 0)
                                <span class="badge badge-secondary">Terkunci</span>
                            @elseif($ftsaFilled > 0)
                                <span class="badge badge-primary">{{ $row->dominant_archetype_label ?: 'FTSA' }}</span>
                                <small class="text-muted d-block">{{ $ftsaFilled }}/32</small>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($row->telegram_user_id)
                                <span class="badge badge-primary">Portal</span>
                                <small class="text-muted d-block">TG {{ $row->telegram_user_id }}</small>
                            @else
                                <span class="badge badge-secondary">Landing</span>
                            @endif
                        </td>
                        <td class="text-right text-nowrap">
                            <a href="{{ route('admin.diagnostic-results.show', $row) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                            @include('admin.partials.delete-form', [
                                'action' => route('admin.diagnostic-results.destroy', $row),
                                'confirm' => 'Hapus hasil diagnostik ini? Data jawaban dan skor akan hilang permanen.',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Belum ada hasil diagnostik tersimpan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">{{ $results->links() }}</div>
    </div>
</div>
@endsection

@section('admin_js')
<script>
$(function() {
    $('#diag-results-table').DataTable({
        language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json' },
        paging: false,
        info: false,
        searching: false,
        order: [[0, 'desc']],
        columnDefs: [{ orderable: false, targets: [-1] }],
    });
});
</script>
@stop

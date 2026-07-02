@extends('admin.layouts.page')

@section('page_heading', 'Hasil FTSA')
@section('page_subheading', 'Jawaban kuesioner FTSA 1–32, skor domain, dan archetype behavioral')

@section('page_actions')
<a href="{{ route('admin.diagnostic-results.index') }}" class="btn btn-outline-secondary btn-sm mr-1">
    <i class="fas fa-poll mr-1"></i> Hasil Diagnostik
</a>
<a href="{{ route('admin.diagnostic-questions.index') }}" class="btn btn-outline-info btn-sm">
    <i class="fas fa-clipboard-list mr-1"></i> Soal Diagnostik
</a>
@endsection

@section('main')
@if(!$schemaReady)
    <div class="alert alert-warning">Tabel baseline belum siap. Jalankan <code>php artisan migrate --force</code>.</div>
@endif

<div class="card card-outline card-primary mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('admin.ftsa-results.index') }}" class="form-inline flex-wrap" style="gap:.5rem;">
            <div class="input-group input-group-sm" style="width:280px;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari email / archetype / Telegram ID…">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                </div>
            </div>
            <select name="archetype" class="form-control form-control-sm" style="width:200px;" onchange="this.form.submit()">
                <option value="">Semua archetype</option>
                @foreach($archetypes as $opt)
                    <option value="{{ $opt['value'] }}" @selected(request('archetype') === $opt['value'])>{{ $opt['label'] }}</option>
                @endforeach
            </select>
            <select name="complete" class="form-control form-control-sm" style="width:170px;" onchange="this.form.submit()">
                <option value="">Semua status FTSA</option>
                <option value="1" @selected(request('complete') === '1')>FTSA lengkap</option>
                <option value="0" @selected(request('complete') === '0')>Belum / terkunci</option>
            </select>
            @if(request()->hasAny(['search', 'archetype', 'complete']))
                <a href="{{ route('admin.ftsa-results.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            @endif
        </form>
    </div>
</div>

<div class="card card-outline card-primary">
    <div class="card-body">
        <table id="ftsa-results-table" class="table table-hover table-sm" style="width:100%">
            <thead class="thead-light">
                <tr>
                    <th>Tanggal</th>
                    <th>Email</th>
                    <th>Archetype</th>
                    <th class="text-center">CHD</th>
                    <th class="text-center">RVD</th>
                    <th class="text-center">SSD</th>
                    <th class="text-center">ESD</th>
                    <th class="text-center">Jawaban</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($results as $row)
                    @php
                        $email = $summaryService->resolvedEmail($row);
                        $ftsaMeta = $ftsaService->scoreSummary($row);
                        $locked = $ftsaService->isFtsaLocked($row);
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
                            @if($locked)
                                <span class="badge badge-secondary">Terkunci</span>
                            @else
                                <span class="badge badge-primary">{{ $row->dominant_archetype_label ?: '—' }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $row->ftsa_chd ?: '—' }}</td>
                        <td class="text-center">{{ $row->ftsa_rvd ?: '—' }}</td>
                        <td class="text-center">{{ $row->ftsa_ssd ?: '—' }}</td>
                        <td class="text-center">{{ $row->ftsa_esd ?: '—' }}</td>
                        <td class="text-center">
                            <span class="badge badge-light">{{ $ftsaMeta['filled'] }}/{{ $ftsaMeta['total'] }}</span>
                        </td>
                        <td class="text-right text-nowrap">
                            <a href="{{ route('admin.ftsa-results.show', $row) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                            @include('admin.partials.delete-form', [
                                'action' => route('admin.ftsa-results.destroy', $row),
                                'confirm' => 'Hapus hasil FTSA ini? Data jawaban dan skor akan hilang permanen.',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">Belum ada hasil FTSA tersimpan.</td>
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
    $('#ftsa-results-table').DataTable({
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

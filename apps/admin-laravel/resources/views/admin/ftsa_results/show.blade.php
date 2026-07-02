@extends('admin.layouts.page')

@section('page_heading', 'Detail Hasil FTSA')
@section('page_subheading', $email ?: 'Tanpa email')

@section('page_actions')
<a href="{{ route('admin.ftsa-results.index') }}" class="btn btn-outline-secondary btn-sm mr-1">
    <i class="fas fa-arrow-left mr-1"></i> Semua Hasil FTSA
</a>
<a href="{{ route('admin.diagnostic-results.show', $baseline) }}" class="btn btn-outline-success btn-sm mr-1">
    <i class="fas fa-stethoscope mr-1"></i> Diagnostik Lengkap
</a>
@include('admin.partials.delete-form', [
    'action' => route('admin.ftsa-results.destroy', $baseline),
    'confirm' => 'Hapus hasil FTSA ini? Data jawaban dan skor akan hilang permanen.',
])
@endsection

@section('main')
@php
    $grouped = collect($ftsaAnswers)->groupBy('domain_code')->sortKeys();
@endphp

<div class="row">
    <div class="col-lg-4">
        <div class="card card-outline card-primary mb-3">
            <div class="card-header"><strong>Identitas</strong></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <th width="120">Email</th>
                        <td>
                            @if($email)
                                <a href="mailto:{{ $email }}">{{ $email }}</a>
                            @else
                                <span class="text-muted">Tidak tercatat</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Tanggal</th>
                        <td>{{ $baseline->formatDate('d M Y H:i') }}</td>
                    </tr>
                    <tr>
                        <th>Sumber</th>
                        <td>
                            @if($baseline->telegram_user_id)
                                Portal — ID <code>{{ $baseline->telegram_user_id }}</code>
                            @else
                                Landing check-up
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Tahap FS</th>
                        <td>{{ $baseline->stage_label ?: ucfirst($baseline->financial_stage) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card card-outline card-primary mb-3">
            <div class="card-header"><strong>Ringkasan FTSA</strong></div>
            <div class="card-body">
                @if($isLocked)
                    <div class="alert alert-warning mb-0">
                        FTSA belum diisi atau masih terkunci (paket premium belum aktif).
                    </div>
                @else
                    <div class="mb-3">
                        <div class="text-muted small">Dominant Archetype</div>
                        <div class="h5 mb-0 text-primary">{{ $ftsaSummary['archetype_label'] ?: '—' }}</div>
                    </div>
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Domain</th>
                                <th class="text-center">Skor</th>
                                <th>Level</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ftsaSummary['domains'] as $domain)
                                <tr>
                                    <td>
                                        <span class="badge badge-info">{{ $domain['code'] }}</span>
                                        <div class="small text-muted">{{ $domain['label'] }}</div>
                                    </td>
                                    <td class="text-center font-weight-bold">{{ $domain['score'] }}</td>
                                    <td>{{ $domain['level'] ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="small text-muted mt-2">
                        {{ $ftsaSummary['filled'] }} dari {{ $ftsaSummary['total'] }} pertanyaan terjawab
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card card-outline card-primary">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Jawaban FTSA 1–32</strong>
                <span class="badge badge-light">{{ count($ftsaAnswers) }} jawaban</span>
            </div>
            <div class="card-body p-0">
                @if($ftsaAnswers === [])
                    <div class="p-4 text-muted text-center">Belum ada jawaban FTSA tersimpan di <code>answers_json.ftsa</code>.</div>
                @else
                    @foreach($grouped as $domainCode => $items)
                        <div class="border-bottom">
                            <div class="px-3 py-2 bg-light font-weight-bold">
                                {{ $domainCode }}
                                <span class="text-muted font-weight-normal">— {{ $items->first()['domain_label'] ?? '' }}</span>
                            </div>
                            <table class="table table-sm mb-0">
                                <tbody>
                                    @foreach($items as $item)
                                        <tr>
                                            <td style="width:6%; vertical-align:top;" class="text-muted font-weight-bold">{{ $item['num'] }}</td>
                                            <td style="width:54%; vertical-align:top;">
                                                <div>{{ $item['question'] }}</div>
                                            </td>
                                            <td style="vertical-align:top;">
                                                <span class="badge badge-warning">{{ $item['score'] }}</span>
                                                {{ $item['score_label'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

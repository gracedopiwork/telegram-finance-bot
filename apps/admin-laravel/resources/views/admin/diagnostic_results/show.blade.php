@extends('admin.layouts.page')

@section('page_heading', 'Detail Hasil Diagnostik')
@section('page_subheading', $email ?: 'Tanpa email')

@section('page_actions')
<a href="{{ route('admin.diagnostic-results.index') }}" class="btn btn-outline-secondary btn-sm mr-1">
    <i class="fas fa-arrow-left mr-1"></i> Semua Hasil
</a>
@include('admin.partials.delete-form', [
    'action' => route('admin.diagnostic-results.destroy', $baseline),
    'confirm' => 'Hapus hasil diagnostik ini? Data jawaban dan skor akan hilang permanen.',
])
@endsection

@section('main')
@php
    $panelColor = $stageDisplay['panel_color'] ?? '#7EC8C8';
    $grouped = collect($summary)->groupBy('step')->sortKeys();
@endphp

<div class="row">
    <div class="col-lg-5">
        <div class="card card-outline card-success mb-3">
            <div class="card-header"><strong>Identitas & hasil</strong></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <th width="140">Email</th>
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
                        <th>Review berikut</th>
                        <td>{{ $baseline->formatNextReview('d M Y') }}</td>
                    </tr>
                    <tr>
                        <th>Sumber</th>
                        <td>
                            @if($baseline->telegram_user_id)
                                Portal / Bot — Telegram ID <code>{{ $baseline->telegram_user_id }}</code>
                            @else
                                Landing check-up (gratis)
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Tahap</th>
                        <td>
                            <span class="badge badge-lg" style="background: {{ $panelColor }}; color: #0c2240;">
                                {{ $stageDisplay['label'] ?? $baseline->stage_label }}
                            </span>
                            <div class="small text-muted mt-1">{{ $stageDisplay['phase'] ?? '' }} · {{ $stageDisplay['diagnosis'] ?? '' }}</div>
                        </td>
                    </tr>
                    <tr>
                        <th>Skor</th>
                        <td class="font-weight-bold">{{ $baseline->financial_stage_score }}/39</td>
                    </tr>
                    @if($baseline->dominant_archetype && $baseline->dominant_archetype !== 'guest' && $baseline->dominant_archetype !== 'locked')
                        <tr>
                            <th>Archetype</th>
                            <td>{{ $baseline->dominant_archetype_label }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>

        @if(!empty($stageDisplay['risk_description']))
            <div class="card card-outline card-info">
                <div class="card-body">
                    <div class="font-weight-bold mb-1">{{ $stageDisplay['risk_label'] ?? 'Risiko keuangan' }}</div>
                    <p class="mb-0 text-muted">{{ $stageDisplay['risk_description'] }}</p>
                </div>
            </div>
        @endif
    </div>

    <div class="col-lg-7">
        <div class="card card-outline card-success">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Ringkasan jawaban</strong>
                <span class="badge badge-light">{{ count($summary) }} jawaban</span>
            </div>
            <div class="card-body p-0">
                @if($summary === [])
                    <div class="p-4 text-muted text-center">Tidak ada jawaban tersimpan di <code>answers_json</code>.</div>
                @else
                    @foreach($grouped as $stepNum => $items)
                        <div class="border-bottom">
                            <div class="px-3 py-2 bg-light font-weight-bold">
                                Langkah {{ $stepNum }}
                                @if($stepNum === 1)
                                    <span class="text-muted font-weight-normal">— Profil</span>
                                @endif
                            </div>
                            <table class="table table-sm mb-0">
                                <tbody>
                                    @foreach($items as $item)
                                        <tr>
                                            <td style="width:42%; vertical-align:top;">
                                                <div class="font-weight-bold">{{ $item['question'] }}</div>
                                                @if($item['note'])
                                                    <div class="text-muted small mt-1">{{ $item['note'] }}</div>
                                                @endif
                                            </td>
                                            <td style="vertical-align:top;">
                                                {{ $item['answer_label'] }}
                                                @if($item['is_scored'] && $item['score'] !== null)
                                                    <span class="badge badge-warning ml-1">skor {{ $item['score'] }}</span>
                                                @endif
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

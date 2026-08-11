@extends('admin.layouts.page')

@section('page_heading', 'Jadwal Konsultasi')
@section('page_subheading', 'Slot available dokter — hold → bayar Midtrans → konfirmasi otomatis')

@section('page_actions')
<a href="{{ route('admin.consultation-slots.create') }}" class="btn btn-success btn-sm"><i class="fas fa-plus mr-1"></i> Tambah Slot</a>
@endsection

@section('main')

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="card card-outline card-secondary mb-3">
    <div class="card-body py-2">
        <form method="GET" class="form-inline flex-wrap">
            <label class="mr-2 mb-1">Status</label>
            <select name="status" class="form-control form-control-sm mr-3 mb-1" onchange="this.form.submit()">
                <option value="all" {{ ($status ?? 'all') === 'all' || ($status ?? '') === '' ? 'selected' : '' }}>Semua</option>
                @foreach(['open','held','confirmed','cancelled'] as $st)
                    <option value="{{ $st }}" {{ ($status ?? '') === $st ? 'selected' : '' }}>{{ $st }}</option>
                @endforeach
            </select>
            <label class="mr-2 mb-1">Dokter</label>
            <select name="advisor_id" class="form-control form-control-sm mr-3 mb-1" onchange="this.form.submit()">
                <option value="">Semua</option>
                @foreach($advisors as $a)
                    <option value="{{ $a->id }}" {{ (string) ($advisorId ?? '') === (string) $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>

<div class="card card-outline card-success">
    <div class="card-body table-responsive p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Jadwal</th>
                    <th>Dokter</th>
                    <th>Status</th>
                    <th>Booking</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($slots as $slot)
                    <tr>
                        <td>
                            <strong>{{ $slot->starts_at->format('d/m/Y') }}</strong>
                            <div class="text-muted small">{{ $slot->labelTimeRange() }} WIB</div>
                        </td>
                        <td>{{ $slot->advisor?->name ?? '—' }}</td>
                        <td>
                            @php
                                $badge = match($slot->status) {
                                    'open' => 'success',
                                    'held' => 'warning',
                                    'confirmed' => 'primary',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge badge-{{ $badge }}">{{ $slot->status }}</span>
                            @if($slot->status === 'held' && $slot->held_until)
                                <div class="small text-muted">hold s/d {{ $slot->held_until->format('H:i') }}</div>
                            @endif
                        </td>
                        <td>
                            @if($slot->booking_code)
                                <code>{{ $slot->booking_code }}</code>
                                <div class="small">{{ $slot->guest_name }}</div>
                                @if($slot->guest_phone)<div class="small text-muted">{{ $slot->guest_phone }}</div>@endif
                                @if($slot->stage_key)
                                    <div class="small text-muted">{{ $slot->stage_key }}@if($slot->amount_due) · Rp {{ number_format($slot->amount_due, 0, ',', '.') }}@endif</div>
                                @endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-right text-nowrap">
                            @if($slot->status === 'held')
                                <form action="{{ route('admin.consultation-slots.confirm', $slot) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-success" title="Konfirmasi bayar"><i class="fas fa-check"></i></button>
                                </form>
                                <form action="{{ route('admin.consultation-slots.release', $slot) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-warning" title="Lepas hold"><i class="fas fa-unlock"></i></button>
                                </form>
                            @endif
                            @if($slot->status === 'confirmed')
                                <form action="{{ route('admin.consultation-slots.overtime', $slot) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Buat invoice overtime Midtrans (+1 jam) untuk booking ini?')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary" title="Invoice overtime"><i class="fas fa-clock"></i></button>
                                </form>
                                <form action="{{ route('admin.consultation-slots.release', $slot) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Lepas konfirmasi dan buka ulang slot?')">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-warning" title="Buka ulang"><i class="fas fa-undo"></i></button>
                                </form>
                            @endif
                            @if(in_array($slot->status, ['open','held'], true))
                                <form action="{{ route('admin.consultation-slots.cancel', $slot) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-secondary" title="Batalkan slot"><i class="fas fa-ban"></i></button>
                                </form>
                            @endif
                            @if($slot->status !== 'confirmed')
                                @include('admin.partials.delete-form', ['action' => route('admin.consultation-slots.destroy', $slot)])
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada slot. Tambahkan jadwal available dulu.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($slots->hasPages())
        <div class="card-footer">{{ $slots->links() }}</div>
    @endif
</div>

@endsection

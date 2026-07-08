@extends('admin.layouts.page')

@section('page_heading', 'Paket Health Check Up (Arsip)')
@section('page_subheading', 'Data lama — tidak lagi dipakai di website publik')

@section('main')

<div class="alert alert-warning">
    <h5 class="alert-heading mb-2"><i class="fas fa-info-circle mr-1"></i> Menu ini sudah tidak dipakai</h5>
    <p class="mb-2">Model bisnis YFD sudah berubah. Paket 3-tier (Basic / Comprehensive / Executive) <strong>tidak lagi</strong> ditampilkan di website.</p>
    <ul class="mb-2 pl-3">
        <li><strong>Screening / Health Check-Up</strong> → gratis di <code>/check-up</code></li>
        <li><strong>Konsultasi</strong> → tarif per sesi berdasarkan tahap finansial (Surviving / Growing / Steady / Comfortable) — dikelola di <code>config/consultation_pricing.php</code></li>
        <li><strong>YFD First Aid (bot)</strong> & <strong>FTSA Premium</strong> → produk digital terpisah (menu Produk Digital)</li>
    </ul>
    <p class="mb-0 small text-muted">Tabel di bawah hanya arsip database. Abaikan atau hapus entri lama. Setelah deploy, jalankan <code>php artisan migrate</code> untuk menonaktifkan paket legacy otomatis.</p>
</div>

<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title">Arsip paket lama (read-only)</h3>
    </div>
    <div class="card-body">
        <table id="packages-table" class="table table-hover table-striped" style="width:100%">
            <thead class="thead-light">
                <tr>
                    <th>Code</th>
                    <th>Nama</th>
                    <th class="text-right">Harga</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Sort</th>
                </tr>
            </thead>
            <tbody>
                @forelse($packages as $p)
                    <tr class="{{ $p->is_active ? '' : 'text-muted' }}">
                        <td><code>{{ $p->code }}</code></td>
                        <td>
                            <strong>{{ $p->name }}</strong>
                            @if($p->tier_label)<br><small class="text-muted">{{ $p->tier_label }}</small>@endif
                        </td>
                        <td class="text-right">
                            <strong>Rp {{ number_format($p->price, 0, ',', '.') }}</strong>
                            <small class="text-muted d-block">{{ $p->period }}</small>
                        </td>
                        <td class="text-center">
                            @if($p->is_recommended)<span class="badge badge-warning">★ Recommended</span>@endif
                            @if($p->is_active)<span class="badge badge-secondary">Legacy aktif</span>@else<span class="badge badge-light border">Nonaktif</span>@endif
                        </td>
                        <td class="text-center">{{ $p->sort }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">Tidak ada data arsip.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@section('admin_js')
<script>
$(function() {
    $('#packages-table').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json" },
        "order": [[0, "asc"]],
        "pageLength": 25
    });
});
</script>
@stop

@extends('admin.layouts.page')

@section('page_heading', 'Order & Pembayaran')
@section('page_subheading', 'Daftar pembelian produk (Midtrans) — bukan isi catatan keuangan user di Google Sheet.')

@section('main')

@php $hideUserSheet = (bool) config('services.google.hide_user_sheet_from_admin', true); @endphp
@if(! $hideUserSheet)
    <div class="alert alert-warning">
        <strong>Mode privasi sheet nonaktif.</strong>
        Link Google Sheet pelanggan (termasuk tab <strong>Transaksi</strong>) bisa dibuka dari admin.
        Set <code>GOOGLE_HIDE_USER_SHEET_FROM_ADMIN=true</code> di <code>.env</code> lalu <code>php artisan config:clear</code>.
    </div>
@else
    <div class="alert alert-info small mb-3">
        <i class="fas fa-lock mr-1"></i>
        Catatan keuangan user (<code>/catat</code>) hanya di sheet milik pelanggan — tidak ditampilkan di panel ini.
        Jika Anda masih melihatnya di <strong>Google Drive</strong>, file mungkin tersimpan di Drive akun OAuth Anda;
        gunakan folder <strong>Shared drive</strong> khusus service account (<code>GOOGLE_DRIVE_COPY_PARENT_ID</code>).
    </div>
@endif

{{-- ===== Stat cards ===== --}}
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>Rp {{ number_format($stats['revenue'], 0, ',', '.') }}</h3>
                <p>Total Pendapatan (lunas)</p>
            </div>
            <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['paid'] }}</h3>
                <p>Lunas</p>
            </div>
            <div class="icon"><i class="fas fa-check-circle"></i></div>
            <a href="{{ route('admin.orders.index', ['status' => 'paid']) }}" class="small-box-footer">Lihat → <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['pending'] }}</h3>
                <p>Menunggu Bayar</p>
            </div>
            <div class="icon"><i class="fas fa-hourglass-half"></i></div>
            <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="small-box-footer">Lihat → <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>{{ $stats['failed'] }}</h3>
                <p>Gagal / Batal</p>
            </div>
            <div class="icon"><i class="fas fa-times-circle"></i></div>
            <a href="{{ route('admin.orders.index', ['status' => 'failed']) }}" class="small-box-footer">Lihat → <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

{{-- ===== Filter ===== --}}
<div class="card card-outline card-success">
    <div class="card-header">
        <form method="GET" action="{{ route('admin.orders.index') }}" class="form-inline flex-wrap" style="gap:.5rem;">
            <div class="input-group input-group-sm" style="width:260px;">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari kode/nama/email/WA…">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-success"><i class="fas fa-search"></i></button>
                </div>
            </div>

            <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                <option value="">Semua status</option>
                @foreach(['pending' => 'Menunggu', 'paid' => 'Lunas', 'failed' => 'Gagal'] as $val => $lbl)
                    <option value="{{ $val }}" @selected(request('status') === $val)>{{ $lbl }}</option>
                @endforeach
            </select>

            <select name="product_id" class="form-control form-control-sm" onchange="this.form.submit()">
                <option value="">Semua produk</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" @selected(request('product_id') == $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>

            @if(request()->hasAny(['search', 'status', 'product_id']))
                <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times mr-1"></i>Reset
                </a>
            @endif

            <span class="ml-auto text-muted small">{{ $orders->total() }} order</span>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Order</th>
                        <th>Pembeli</th>
                        <th>Produk</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Lisensi</th>
                        <th class="text-center">Sheet</th>
                        <th>Tanggal</th>
                        <th class="text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $o)
                    @php [$lbl, $color] = $o->statusBadge(); @endphp
                    <tr>
                        <td>
                            <a href="{{ route('admin.orders.show', $o) }}" class="font-weight-bold text-success">{{ $o->order_code }}</a>
                            <br>
                            <small class="text-muted">
                                <i class="fab fa-cc-stripe"></i> {{ $o->payment_gateway ?? 'midtrans' }}
                                @if($o->payment_reference)
                                    · <code class="small">{{ \Illuminate\Support\Str::limit($o->payment_reference, 14) }}</code>
                                @endif
                            </small>
                        </td>
                        <td>
                            <div>{{ $o->full_name }}</div>
                            <small class="text-muted">
                                <i class="fas fa-envelope mr-1"></i>{{ $o->email }}<br>
                                @if($o->phone)<i class="fab fa-whatsapp text-success mr-1"></i>{{ $o->phone }}@endif
                                @if($o->telegram_username) <i class="fab fa-telegram text-info ml-1 mr-1"></i>{{ $o->telegram_username }}@endif
                            </small>
                        </td>
                        <td>
                            @if($o->digitalProduct)
                                <strong>{{ $o->digitalProduct->name }}</strong>
                                <br><small class="text-muted"><code>{{ $o->digitalProduct->code }}</code></small>
                            @elseif($o->product_name)
                                {{ $o->product_name }}
                            @else
                                <em class="text-muted">{{ $o->plan ?? '—' }}</em>
                            @endif
                        </td>
                        <td class="text-right text-nowrap">
                            <strong>{{ $o->amountLabel() }}</strong>
                            @if($o->discount_amount > 0)
                                <br><small class="text-success">−Rp {{ number_format($o->discount_amount, 0, ',', '.') }}</small>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge badge-{{ $color }}">{{ $lbl }}</span>
                        </td>
                        <td class="text-center">
                            @if($o->license)
                                <code class="text-success small">{{ $o->license->license_key }}</code>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @php
                                $hideUserSheet = (bool) config('services.google.hide_user_sheet_from_admin', true);
                                $listSheetHref = null;
                                if (! $hideUserSheet) {
                                    $listSheetHref = ! empty($o->spreadsheet_url)
                                        ? $o->spreadsheet_url
                                        : (! empty($o->spreadsheet_id)
                                            ? 'https://docs.google.com/spreadsheets/d/' . $o->spreadsheet_id . '/edit'
                                            : null);
                                }
                            @endphp
                            @if($listSheetHref)
                                <a href="{{ $listSheetHref }}" target="_blank" rel="noopener" class="btn btn-xs btn-outline-success" title="Buka Google Sheet">
                                    <i class="fas fa-table"></i>
                                </a>
                            @elseif(! empty($o->spreadsheet_id) && $hideUserSheet)
                                <span class="text-success" title="Sheet terkirim (privasi admin)"><i class="fas fa-check-circle"></i></span>
                            @elseif($o->status === 'paid' && $o->purchase_delivery_sent_at && ! $listSheetHref)
                                <span class="text-danger" title="Pengiriman selesai tanpa spreadsheet — cek log & konfigurasi Google"><i class="fas fa-exclamation-triangle"></i></span>
                            @elseif($o->status === 'paid' && ! $listSheetHref)
                                <span class="text-warning" title="Menunggu job / spreadsheet belum ada"><i class="fas fa-hourglass-half"></i></span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <small>{{ $o->created_at->format('d M Y H:i') }}</small>
                            @if($o->paid_at)
                                <br><small class="text-success">✓ {{ $o->paid_at->format('d M H:i') }}</small>
                            @endif
                        </td>
                        <td class="text-right text-nowrap">
                            <a href="{{ route('admin.orders.show', $o) }}" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($o->payment_url && $o->status === 'pending')
                                <a href="{{ $o->payment_url }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Buka link bayar">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-5">
                        @if(request()->hasAny(['search', 'status', 'product_id']))
                            Tidak ada order yang cocok dengan filter.
                        @else
                            Belum ada transaksi. Pastikan halaman <a href="{{ route('company.produk') }}" target="_blank">/produk</a> sudah aktif.
                        @endif
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($orders->hasPages())
        <div class="card-footer clearfix">
            {{ $orders->links() }}
        </div>
    @endif
</div>

@endsection

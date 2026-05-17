@extends('admin.layouts.page')

@section('page_heading', 'Detail Transaksi')
@section('page_subheading', $order->order_code)

@section('page_actions')
<a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left mr-1"></i> Semua Transaksi
</a>
@endsection

@section('main')

@php
    [$lbl, $color] = $order->statusBadge();
    $hideUserSheetFromAdmin = (bool) config('services.google.hide_user_sheet_from_admin', true);
    $adminSheetHref = null;
    if (! $hideUserSheetFromAdmin) {
        if (! empty($order->spreadsheet_url)) {
            $adminSheetHref = $order->spreadsheet_url;
        } elseif (! empty($order->spreadsheet_id)) {
            $adminSheetHref = 'https://docs.google.com/spreadsheets/d/' . $order->spreadsheet_id . '/edit';
        }
    }
    $adminSheetJobDone = $order->purchase_delivery_sent_at !== null;
    $adminSheetProvisioned = ! empty($order->spreadsheet_id);
@endphp

<div class="row">
    {{-- ============== KIRI: ringkasan & action ============== --}}
    <div class="col-lg-8">

        {{-- Status banner --}}
        <div class="card card-outline card-{{ $color }}">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between" style="gap:1rem;">
                <div>
                    <h3 class="mb-1">{{ $order->amountLabel() }}</h3>
                    <span class="badge badge-{{ $color }} mr-2">{{ $lbl }}</span>
                    <small class="text-muted">
                        Dibuat {{ $order->created_at->format('d M Y H:i') }}
                        @if($order->paid_at) · Dibayar {{ $order->paid_at->format('d M Y H:i') }} @endif
                    </small>
                </div>
                <div class="d-flex flex-wrap" style="gap:.4rem;">
                    @if($order->payment_url && $order->status === 'pending')
                        <a href="{{ $order->payment_url }}" target="_blank" class="btn btn-sm btn-info">
                            <i class="fas fa-external-link-alt mr-1"></i>Buka Link Bayar
                        </a>
                    @endif

                    {{-- Quick actions: ubah status manual --}}
                    @if($order->status !== 'paid')
                        <form action="{{ route('admin.orders.updateStatus', $order) }}" method="POST" class="d-inline js-confirm-form" data-msg="Tandai order ini LUNAS dan generate lisensi?">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="paid">
                            <button type="submit" class="btn btn-sm btn-success"><i class="fas fa-check mr-1"></i>Tandai Lunas</button>
                        </form>
                    @endif
                    @if($order->status !== 'failed')
                        <form action="{{ route('admin.orders.updateStatus', $order) }}" method="POST" class="d-inline js-confirm-form" data-msg="Tandai order ini GAGAL?">
                            @csrf @method('PATCH')
                            <input type="hidden" name="status" value="failed">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-ban mr-1"></i>Tandai Gagal</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Info pembeli --}}
        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-user mr-2"></i>Data Pembeli</h3></div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th width="180">Nama</th><td>{{ $order->full_name }}</td></tr>
                    <tr><th>Email</th><td><a href="mailto:{{ $order->email }}">{{ $order->email }}</a></td></tr>
                    @if($order->phone)
                        <tr><th>WhatsApp</th><td>
                            {{ $order->phone }}
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', preg_replace('/^0/', '62', $order->phone)) }}" target="_blank" class="ml-2 small text-success">
                                <i class="fab fa-whatsapp"></i> Chat
                            </a>
                        </td></tr>
                    @endif
                    @if($order->telegram_username)
                        <tr><th>Telegram</th><td>{{ $order->telegram_username }}</td></tr>
                    @endif
                </table>
            </div>
        </div>

        {{-- Detail produk & harga --}}
        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-box mr-2"></i>Detail Pembelian</h3></div>
            <div class="card-body">
                <table class="table table-sm mb-3">
                    <tr>
                        <th width="180">Produk</th>
                        <td>
                            @if($order->digitalProduct)
                                <a href="{{ route('admin.digital-products.edit', $order->digitalProduct) }}">
                                    <strong>{{ $order->digitalProduct->name }}</strong>
                                </a>
                                <br><code class="small">{{ $order->digitalProduct->code }}</code>
                                <small class="text-muted ml-1">· {{ $order->digitalProduct->period }}</small>
                            @else
                                {{ $order->product_name ?? $order->plan ?? '—' }}
                                <br><small class="text-warning">Produk sudah dihapus / order legacy</small>
                            @endif
                        </td>
                    </tr>
                </table>

                <table class="table table-bordered mb-0">
                    <tr><th width="180" class="bg-light">Harga normal</th>
                        <td class="text-right">
                            @if($order->original_price)
                                Rp {{ number_format($order->original_price, 0, ',', '.') }}
                            @else
                                <em class="text-muted">—</em>
                            @endif
                        </td>
                    </tr>
                    @if($order->discount_amount > 0)
                        <tr><th class="bg-light">Diskon</th>
                            <td class="text-right text-success">− Rp {{ number_format($order->discount_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="bg-success-50"><th class="bg-light">Total Bayar</th>
                        <td class="text-right"><strong style="font-size:1.2rem;">{{ $order->amountLabel() }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Webhook events log --}}
        <div class="card card-outline card-success">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="fas fa-rss mr-2"></i>Log Webhook Midtrans</h3>
                <span class="badge badge-secondary float-right">{{ $order->paymentEvents->count() }} event</span>
            </div>
            <div class="card-body p-0">
                @if($order->paymentEvents->isEmpty())
                    <div class="text-muted text-center py-4 small">
                        Belum ada event webhook. Midtrans akan kirim notifikasi otomatis ke
                        <code>/webhooks/midtrans</code> setelah pembayaran.
                    </div>
                @else
                    <div class="timeline timeline-inverse p-3">
                        @foreach($order->paymentEvents as $ev)
                            <div class="time-label"><span class="bg-success">{{ $ev->created_at->format('d M H:i:s') }}</span></div>
                            <div>
                                @php
                                    $iconMap = [
                                        'settlement' => ['fa-check-circle', 'success'],
                                        'capture'    => ['fa-check-circle', 'success'],
                                        'pending'    => ['fa-hourglass-half', 'warning'],
                                        'deny'       => ['fa-ban', 'danger'],
                                        'cancel'     => ['fa-times-circle', 'danger'],
                                        'expire'     => ['fa-clock', 'secondary'],
                                    ];
                                    [$ic, $c] = $iconMap[$ev->event_type] ?? ['fa-info-circle', 'info'];
                                @endphp
                                <i class="fas {{ $ic }} bg-{{ $c }}"></i>
                                <div class="timeline-item">
                                    <h3 class="timeline-header"><strong>{{ strtoupper($ev->event_type) }}</strong> via {{ $ev->provider }}</h3>
                                    <div class="timeline-body">
                                        <pre class="mb-0" style="font-size:11px; max-height:180px; overflow:auto; background:#f8f9fa; padding:.5rem;">{{ json_encode($ev->payload_json, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div><i class="fas fa-clock bg-gray"></i></div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ============== KANAN: lisensi & meta ============== --}}
    <div class="col-lg-4">

        {{-- Lisensi --}}
        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-key mr-2"></i>Kode Lisensi</h3></div>
            <div class="card-body">
                @if($order->license)
                    <div class="text-center mb-3">
                        <code id="licenseKey" class="d-inline-block p-3 bg-light rounded"
                              style="font-size:1.1rem; user-select:all; word-break:break-all;">{{ $order->license->license_key }}</code>
                        <br>
                        <button type="button" class="btn btn-sm btn-outline-success mt-2"
                                onclick="navigator.clipboard.writeText('{{ $order->license->license_key }}'); this.innerText='Tersalin!';">
                            <i class="fas fa-copy mr-1"></i>Copy
                        </button>
                    </div>
                    <table class="table table-sm mb-0">
                        <tr><th>Plan</th><td>{{ $order->license->plan }}</td></tr>
                        <tr><th>Status</th><td><span class="badge badge-{{ $order->license->status === 'active' ? 'success' : 'secondary' }}">{{ $order->license->status }}</span></td></tr>
                        @if($order->license->expires_at)
                            <tr><th>Expires</th><td>{{ $order->license->expires_at->format('d M Y') }}</td></tr>
                        @endif
                        @if($order->license->assigned_username)
                            <tr><th>Aktivasi oleh</th><td><i class="fab fa-telegram text-info"></i> {{ $order->license->assigned_username }}</td></tr>
                        @endif
                    </table>
                @else
                    <div class="text-muted small text-center py-3">
                        <i class="fas fa-key fa-2x text-muted mb-2"></i><br>
                        Lisensi belum dibuat.
                        @if($order->status !== 'paid')
                            <br>Akan otomatis dibuat ketika order ditandai <strong>Lunas</strong>.
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Google Sheet (hasil DeliverPaidOrderJob) --}}
        <div class="card card-outline @if($adminSheetHref) card-success @elseif($order->status === 'paid' && $adminSheetJobDone && ! $adminSheetHref) card-danger @else card-secondary @endif">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="fas fa-table mr-2"></i>Google Sheet</h3>
            </div>
            <div class="card-body">
                @if($adminSheetProvisioned && $hideUserSheetFromAdmin)
                    <span class="badge badge-success mb-2">Spreadsheet terkirim ke pelanggan</span>
                    <p class="small text-muted mb-0">
                        Mode privasi aktif: link &amp; tab <strong>Transaksi</strong> tidak ditampilkan di admin.
                        Pelanggan mengakses lewat email/checkout; dashboard di-update lewat Sync di beranda admin.
                    </p>
                @elseif($adminSheetHref)
                    <p class="mb-2 small text-muted">Spreadsheet untuk order ini (sama dengan email / halaman sukses checkout):</p>
                    <p class="mb-2">
                        <a href="{{ $adminSheetHref }}" target="_blank" rel="noopener" class="font-weight-bold break-all">
                            <i class="fas fa-external-link-alt mr-1"></i>{{ \Illuminate\Support\Str::limit($adminSheetHref, 64) }}
                        </a>
                    </p>
                    <p class="mb-2">
                        <a href="{{ $adminSheetHref }}" target="_blank" rel="noopener" class="btn btn-sm btn-success">
                            <i class="fab fa-google-drive mr-1"></i>Buka di Google Sheets
                        </a>
                    </p>
                    @if($order->spreadsheet_id)
                        <table class="table table-sm table-bordered mb-0">
                            <tr>
                                <th class="bg-light" width="110">Spreadsheet ID</th>
                                <td><code class="small user-select-all">{{ $order->spreadsheet_id }}</code></td>
                            </tr>
                        </table>
                    @endif
                @elseif($order->status === 'paid' && $adminSheetJobDone && ! $adminSheetHref)
                    <span class="badge badge-danger mb-2">Tidak ada link / ID di database</span>
                    <p class="small text-muted mb-2">
                        Job pengiriman sudah selesai (<code>purchase_delivery_sent_at</code> terisi) tetapi penyalinan template Google gagal atau belum dijalankan ulang setelah perbaikan env.
                        Cek log, lalu salin ulang (konfigurasi sudah OK di <code>google:sheet-setup</code> tidak memperbaiki order lama otomatis).
                    </p>
                    <form method="post" action="{{ route('admin.orders.provisionSheet', $order) }}" class="mb-2">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning">
                            <i class="fas fa-redo mr-1"></i>Salin ulang / terapkan privasi sheet
                        </button>
                    </form>
                    <p class="small text-muted mb-0">
                        Atau di server: <code>php artisan google:sheet-setup --provision={{ $order->order_code }}</code>
                    </p>
                @elseif($order->status === 'paid' && ! $adminSheetHref)
                    <span class="badge badge-warning mb-2">Belum ada spreadsheet</span>
                    <p class="small text-muted mb-0">
                        Tunggu antrian <code>DeliverPaidOrderJob</code> (pastikan <code>php artisan queue:work</code> berjalan). Setelah sukses, link akan muncul di sini.
                    </p>
                @else
                    <p class="small text-muted mb-0">
                        Link Google Sheet akan tersedia setelah order <strong>Lunas</strong> dan proses pengiriman digital selesai.
                    </p>
                @endif
            </div>
        </div>

        {{-- Payment ref --}}
        <div class="card card-outline card-success">
            <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-credit-card mr-2"></i>Pembayaran</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tr><th width="120">Gateway</th><td>{{ $order->payment_gateway ?? 'midtrans' }}</td></tr>
                    <tr><th>Order Code</th><td><code>{{ $order->order_code }}</code></td></tr>
                    @if($order->payment_reference)
                        <tr><th>Trx ID</th><td><code class="small">{{ $order->payment_reference }}</code></td></tr>
                    @endif
                    @if($order->payment_token)
                        <tr><th>Snap Token</th><td><code class="small">{{ \Illuminate\Support\Str::limit($order->payment_token, 24) }}</code></td></tr>
                    @endif
                    <tr><th>Currency</th><td>{{ $order->currency ?? 'IDR' }}</td></tr>
                </table>
            </div>
        </div>

        {{-- Danger zone --}}
        <div class="card card-outline card-danger">
            <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-trash mr-2"></i>Danger Zone</h3></div>
            <div class="card-body">
                <p class="small text-muted">
                    Menghapus order ini juga menghapus <strong>lisensi yang terikat</strong> pada order (jika tidak dipakai order lain).
                    Baris <code>license_activations</code> ikut terhapus. Gunakan hanya untuk data uji / koreksi.
                </p>
                <form action="{{ route('admin.orders.destroy', $order) }}" method="POST" class="js-confirm-form" data-msg="Hapus order {{ $order->order_code }} dan lisensi terkait? Tindakan ini tidak bisa dibatalkan.">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger btn-block"><i class="fas fa-trash mr-1"></i>Hapus Order</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('admin_js')
<script>
$(function () {
    $(document).on('submit', '.js-confirm-form', function (e) {
        e.preventDefault();
        var $f = $(this);
        Swal.fire({
            title: 'Konfirmasi',
            text: $f.data('msg') || 'Lanjutkan?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, lanjut',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#28a745',
        }).then(function (r) {
            if (r.isConfirmed) {
                // Native submit agar tidak memicu ulang handler jQuery di document (trigger('submit') membuat form tidak pernah terkirim).
                $f[0].submit();
            }
        });
    });
});
</script>
@stop

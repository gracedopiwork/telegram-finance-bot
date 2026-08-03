@extends('admin.layouts.page')

@section('page_heading', 'Detail Order')
@section('page_subheading', $order->order_code)

@section('page_actions')
<a href="{{ route('admin.orders.index') }}" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left mr-1"></i> Semua Transaksi
</a>
@endsection

@section('main')

@php
    [$lbl, $color] = $order->statusBadge();
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
                    @if($order->isAdminComplimentary())
                        <span class="badge badge-info mr-2">Admin gratis — bukan bayar</span>
                    @endif
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
                        <form action="{{ route('admin.orders.syncPayment', $order) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning">
                                <i class="fas fa-sync mr-1"></i>Sync Midtrans
                            </button>
                        </form>
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
                        Belum ada event webhook. Midtrans harus dikonfigurasi mengirim notifikasi ke:<br>
                        <code>{{ $midtransNotificationUrl ?? url('/webhooks/midtrans') }}</code><br>
                        <span class="text-warning">Jika sudah bayar tapi status masih Menunggu, klik <strong>Sync Midtrans</strong> di panel kanan.</span>
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

        @php
            $copySummaryLines = [
                'Nama: '.($order->full_name ?: '—'),
                'Email: '.($order->email ?: '—'),
                'Lisensi: '.($order->license?->license_key ?: '—'),
                'Kode Affiliate: '.(!empty($buyerAffiliate) ? $buyerAffiliate->referral_code : '—'),
            ];
            $copySummary = implode("\n", $copySummaryLines);
        @endphp
        <div class="card card-outline card-secondary">
            <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-clipboard mr-2"></i>Keterangan siap copy</h3></div>
            <div class="card-body">
                <textarea id="orderCopySummary" class="form-control font-monospace small" rows="5" readonly
                          style="resize:none; background:#f8f9fa;">{{ $copySummary }}</textarea>
                <button type="button" class="btn btn-sm btn-primary btn-block mt-2" id="btnCopyOrderSummary">
                    <i class="fas fa-copy mr-1"></i>Copy semua
                </button>
            </div>
        </div>

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
                        <tr>
                            <th>Hak akses</th>
                            <td>
                                <strong>{{ $licenseEntitlementLabel ?? $order->license->plan }}</strong>
                                @if($order->digitalProduct)
                                    <br><span class="text-muted small">Produk order ini: {{ $order->digitalProduct->name }}</span>
                                @endif
                                @if($licenseEntitlementLabel && $order->license->plan && $licenseEntitlementLabel !== $order->license->plan && ! str_contains((string) $order->license->plan, '+'))
                                    <br><span class="text-muted small">Kode plan DB: {{ $order->license->plan }} (upgrade pakai lisensi yang sama)</span>
                                @endif
                            </td>
                        </tr>
                        <tr><th>Status</th><td><span class="badge badge-{{ $order->license->status === 'active' ? 'success' : 'secondary' }}">{{ $order->license->status }}</span></td></tr>
                        <tr><th>Expires</th><td>
                            @if($order->license->expires_at)
                                {{ $order->license->expires_at->format('d M Y') }}
                            @else
                                <span class="text-muted">Selamanya</span>
                            @endif
                        </td></tr>
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

        <div class="card card-outline card-warning">
            <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-user-tag mr-2"></i>Referral Pemberi</h3></div>
            <div class="card-body">
                @if(!empty($referrerAffiliate) || filled($order->referral_code))
                    <div class="text-center mb-3">
                        <code class="d-inline-block p-3 bg-light rounded" style="font-size:1.1rem; user-select:all;">
                            {{ $referrerAffiliate->referral_code ?? $order->referral_code }}
                        </code>
                        @if(!empty($referrerAffiliate))
                            <div class="small text-muted mt-2">
                                {{ $referrerAffiliate->name ?: '—' }} · {{ $referrerAffiliate->email }}
                                · <a href="{{ route('admin.affiliates.show', $referrerAffiliate) }}">Lihat affiliate</a>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="small text-muted text-center">Belum ada referral pemberi.</p>
                @endif

                <form method="POST" action="{{ route('admin.orders.referrer', $order) }}">
                    @csrf
                    <label class="small mb-1">Set / ubah kode referral pemberi</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="referral_code"
                               value="{{ old('referral_code', $referrerAffiliate->referral_code ?? $order->referral_code) }}"
                               class="form-control text-uppercase" maxlength="32"
                               placeholder="Contoh: YFD-ISD6QV — kosongkan = hapus">
                        <div class="input-group-append">
                            <button class="btn btn-warning" type="submit">Simpan &amp; kredit komisi</button>
                        </div>
                    </div>
                    <input type="hidden" name="credit_commission" value="1">
                    <small class="text-muted d-block mt-1">
                        Kalau order sudah lunas dan belum punya komisi, komisi standar akan dikredit ke pemberi.
                    </small>
                    @error('referral_code')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </form>
            </div>
        </div>

        @if(!empty($buyerAffiliate))
        <div class="card card-outline card-info">
            <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-handshake mr-2"></i>Kode Affiliate Pembeli</h3></div>
            <div class="card-body text-center">
                <code class="d-inline-block p-3 bg-light rounded" style="font-size:1.1rem; user-select:all;">{{ $buyerAffiliate->referral_code }}</code>
                <br>
                <button type="button" class="btn btn-sm btn-outline-info mt-2"
                        onclick="navigator.clipboard.writeText('{{ $buyerAffiliate->referral_code }}'); this.innerText='Tersalin!';">
                    <i class="fas fa-copy mr-1"></i>Copy kode
                </button>
                <div class="small text-muted mt-2">
                    Saldo: Rp {{ number_format($buyerAffiliate->availableBalance(), 0, ',', '.') }}
                    · <a href="{{ route('admin.affiliates.show', $buyerAffiliate) }}">Lihat di Affiliate</a>
                </div>

                <hr>
                <form method="POST" action="{{ route('admin.orders.affiliate', $order) }}" class="text-left">
                    @csrf
                    <label class="small mb-1">Ubah / set kode affiliate</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="referral_code" value="{{ old('referral_code', $buyerAffiliate->referral_code) }}"
                               class="form-control text-uppercase" maxlength="32"
                               placeholder="Kosongkan = tetap / generate">
                        <div class="input-group-append">
                            <button class="btn btn-info" type="submit">Simpan</button>
                        </div>
                    </div>
                    @error('referral_code')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </form>
            </div>
        </div>
        @endif

        {{-- Pengiriman ringkasan (bot + lisensi) --}}
        @if($order->status === 'paid' && $order->license)
            @php
                $deliveryChannel = config('services.order_delivery.channel', 'wa');
                $deliveryUsesWa = in_array($deliveryChannel, ['wa', 'both'], true);
                $deliveryUsesEmail = in_array($deliveryChannel, ['email', 'both'], true);
                $deliveryTarget = $deliveryUsesWa ? $order->phone : $order->email;
            @endphp
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        @if($deliveryUsesWa)
                            <i class="fab fa-whatsapp mr-2 text-success"></i>Pengiriman ke pelanggan
                        @else
                            <i class="fas fa-envelope mr-2"></i>Pengiriman ke pelanggan
                        @endif
                    </h3>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-2">
                        Channel: <strong>{{ $deliveryChannelLabel ?? $deliveryChannel }}</strong> —
                        tautan bot Telegram, kode lisensi + <code>/activate</code>, dan link dashboard web.
                    </p>
                    @if($order->purchase_delivery_sent_at)
                        <p class="small mb-2">
                            <span class="badge badge-success">Terkirim</span>
                            {{ $order->purchase_delivery_sent_at->format('d M Y H:i') }}
                            → <strong>{{ $deliveryTarget }}</strong>
                        </p>
                    @else
                        <p class="small mb-2"><span class="badge badge-warning">Belum terkirim</span> — pastikan queue jalan atau kirim manual.</p>
                    @endif
                    @if(empty($telegramBotUrl))
                        <p class="small text-danger mb-2">
                            <i class="fas fa-exclamation-triangle"></i>
                            Tautan bot belum di-set (<code>TELEGRAM_BOT_USERNAME</code> atau Site Settings → Integrasi Bot).
                        </p>
                    @endif
                    @if($deliveryUsesWa)
                        <p class="small text-muted mb-2">Fonnte: <code>{{ config('services.fonnte.token') ? 'token terisi' : 'FONNTE_TOKEN kosong' }}</code></p>
                    @endif
                    <p class="small text-muted mb-2">MAIL: <code>{{ config('mail.default') }}</code> dari <code>{{ config('mail.from.address') }}</code></p>
                    <form method="post" action="{{ route('admin.orders.resendDelivery', $order) }}" class="js-confirm-form mb-2" data-msg="Kirim ringkasan ke {{ $deliveryTarget }}?">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-info btn-block" @if($deliveryUsesWa && empty($telegramBotUrl)) disabled @endif>
                            <i class="fas fa-paper-plane mr-1"></i>Kirim / kirim ulang ({{ $deliveryChannelLabel ?? $deliveryChannel }})
                        </button>
                    </form>
                    <form method="post" action="{{ route('admin.orders.resendDeliveryEmail', $order) }}" class="js-confirm-form" data-msg="Kirim ulang email aktivasi ke {{ $order->email }}?">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary btn-block" @disabled(trim((string) $order->email) === '')>
                            <i class="fas fa-envelope mr-1"></i>Kirim ulang email aktivasi
                        </button>
                    </form>
                    <p class="small text-muted mb-0 mt-2">
                        Email aktivasi berisi tautan bot, kode <code>/activate</code>, dan link portal — ke <strong>{{ $order->email ?: '—' }}</strong>.
                        CLI: <code>php artisan order:send-delivery {{ $order->order_code }} --force</code>
                    </p>
                </div>
            </div>
        @endif

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
                    @if($order->admin_note)
                        <tr><th>Keterangan admin</th><td>{{ $order->admin_note }}</td></tr>
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
                <p class="small text-muted mb-3">
                    Menghapus order ini juga menghapus <strong>lisensi yang terikat</strong> (jika tidak dipakai order lain).
                    Jika tidak ada order lain dengan email <strong>{{ $order->email }}</strong>, baseline & transaksi bot untuk email ini ikut dihapus.
                </p>
                @if($order->status === 'paid' && $order->license_id)
                    <form action="{{ route('admin.orders.purgeCustomerData', $order) }}" method="POST" class="js-confirm-form mb-2"
                          data-msg="Hapus baseline & transaksi untuk {{ $order->email }}? Order dan lisensi TIDAK dihapus.">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-warning btn-block">
                            <i class="fas fa-eraser mr-1"></i>Hapus Data User (Baseline + Transaksi)
                        </button>
                    </form>
                @endif
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

    $('#btnCopyOrderSummary').on('click', function () {
        var el = document.getElementById('orderCopySummary');
        var btn = this;
        if (!el) return;
        var text = el.value;
        var done = function () {
            var prev = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-check mr-1"></i>Tersalin!';
            setTimeout(function () { btn.innerHTML = prev; }, 1500);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(done);
        } else {
            el.focus();
            el.select();
            document.execCommand('copy');
            done();
        }
    });
});
</script>
@stop

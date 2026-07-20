{{-- Google Business Profile sync panel — shown on Testimoni Homepage settings --}}
<div class="card card-outline card-primary mb-4">
    <div class="card-header">
        <h3 class="card-title mb-0"><i class="fab fa-google mr-2"></i>Sync Google Business Profile</h3>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Ambil <strong>semua</strong> ulasan resmi lewat API pemilik listing
            (<a href="https://developers.google.com/my-business/content/review-data" target="_blank" rel="noopener">Business Profile Reviews API</a>).
            Project Google Cloud harus sudah <strong>disetujui akses GBP API</strong>, lalu isi
            <code>GOOGLE_BUSINESS_CLIENT_ID</code> &amp; <code>GOOGLE_BUSINESS_CLIENT_SECRET</code> di <code>.env</code>.
            Redirect URI: <code>{{ config('services.google_business.redirect_uri') }}</code>
        </p>

        @if(! ($gbpConfigured ?? false))
            <div class="alert alert-warning mb-0">
                Kredensial OAuth belum di-set. Tambahkan di <code>.env</code> lalu <code>php artisan config:clear</code>.
            </div>
        @else
            @php $conn = $gbpConnection ?? null; @endphp
            @if($conn && $conn->isConnected())
                <dl class="row mb-3 small">
                    <dt class="col-sm-3">Status</dt>
                    <dd class="col-sm-9"><span class="badge badge-success">Terhubung</span></dd>
                    <dt class="col-sm-3">Account</dt>
                    <dd class="col-sm-9">{{ $conn->account_label ?: $conn->account_name ?: '—' }}</dd>
                    <dt class="col-sm-3">Lokasi</dt>
                    <dd class="col-sm-9">{{ $conn->location_title ?: $conn->location_name ?: '— belum dipilih' }}</dd>
                    <dt class="col-sm-3">Rating</dt>
                    <dd class="col-sm-9">
                        {{ $conn->average_rating !== null ? number_format($conn->average_rating, 1) : '—' }}
                        @if($conn->total_review_count !== null)
                            · {{ $conn->total_review_count }} ulasan
                        @endif
                    </dd>
                    <dt class="col-sm-3">Last sync</dt>
                    <dd class="col-sm-9">
                        {{ $conn->last_synced_at ? $conn->last_synced_at->timezone(config('app.timezone'))->format('Y-m-d H:i') : '—' }}
                    </dd>
                </dl>
                @if($conn->last_error)
                    <div class="alert alert-danger small">{{ $conn->last_error }}</div>
                @endif
                <div class="d-flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.google-reviews.sync') }}" class="mr-2 mb-2">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm" @disabled(! $conn->hasLocation())>
                            <i class="fas fa-sync mr-1"></i> Sync sekarang
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.google-reviews.disconnect') }}" class="mb-2"
                          onsubmit="return confirm('Putus koneksi Google Business Profile?')">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="fas fa-unlink mr-1"></i> Putuskan
                        </button>
                    </form>
                </div>
            @else
                <a href="{{ route('admin.google-reviews.connect') }}" class="btn btn-primary">
                    <i class="fab fa-google mr-1"></i> Hubungkan Google Business
                </a>
            @endif
        @endif

        @if(($gbpReviews ?? collect())->isNotEmpty())
            <hr>
            <h6 class="font-weight-bold">Ulasan tersimpan ({{ $gbpReviews->count() }})</h6>
            <div class="table-responsive">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Rating</th>
                            <th>Komentar</th>
                            <th>Tampil</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gbpReviews as $rev)
                            <tr>
                                <td class="text-nowrap">{{ $rev->reviewer_name }}</td>
                                <td>{{ $rev->rating }}★</td>
                                <td class="small">{{ \Illuminate\Support\Str::limit($rev->comment, 120) }}</td>
                                <td>
                                    @if($rev->is_published)
                                        <span class="badge badge-success">Ya</span>
                                    @else
                                        <span class="badge badge-secondary">Tidak</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <form method="POST" action="{{ route('admin.google-reviews.toggle', $rev) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-outline-secondary">
                                            {{ $rev->is_published ? 'Sembunyikan' : 'Tampilkan' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

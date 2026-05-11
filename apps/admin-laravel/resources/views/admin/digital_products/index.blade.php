@extends('admin.layouts.page')

@section('page_heading', 'Produk Digital')
@section('page_subheading', 'Kelola katalog produk digital YFD (Bot, App, dll). Harga diskon otomatis tampil di halaman /produk.')

@section('page_actions')
<a href="{{ route('admin.digital-products.create') }}" class="btn btn-success btn-sm">
    <i class="fas fa-plus mr-1"></i> Tambah Produk
</a>
@endsection

@section('main')

<div class="card card-outline card-success">
    <div class="card-body">
        <table id="digital-products-table" class="table table-hover table-striped" style="width:100%">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Produk</th>
                    <th class="text-right">Harga</th>
                    <th class="text-right">Diskon</th>
                    <th class="text-center">Mode</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Sort</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $idx => $p)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-start">
                                <span class="material-icons text-success mr-2" style="font-size:20px;">{{ $p->icon }}</span>
                                <div>
                                    <strong>{{ $p->name }}</strong>
                                    @if($p->is_featured)
                                        <span class="badge badge-warning ml-1">★ Featured</span>
                                    @endif
                                    @if($p->badge)
                                        <span class="badge badge-info ml-1">{{ $p->badge }}</span>
                                    @endif
                                    <br><small class="text-muted">{{ $p->code }} · {{ $p->period }}</small>
                                    @if($p->tagline)
                                        <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($p->tagline, 80) }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-right text-nowrap">
                            @if($p->price > 0)
                                <strong>{{ $p->priceLabel($p->price) }}</strong>
                            @else
                                <em class="text-muted">—</em>
                            @endif
                        </td>
                        <td class="text-right text-nowrap">
                            @if($p->on_sale)
                                <strong class="text-success">{{ $p->priceLabel($p->discount_price) }}</strong>
                                <br><small class="text-success">−{{ $p->discount_percent }}%</small>
                            @else
                                <em class="text-muted">—</em>
                            @endif
                        </td>
                        <td class="text-center">
                            @php
                                $modeMap = [
                                    'midtrans' => ['Midtrans', 'badge-success', 'fa-credit-card'],
                                    'wa'       => ['WhatsApp', 'badge-success', 'fa-whatsapp'],
                                    'url'      => ['External', 'badge-info',    'fa-external-link-alt'],
                                    'soon'     => ['Coming Soon', 'badge-secondary', 'fa-clock'],
                                ];
                                $m = $modeMap[$p->billing_mode] ?? [$p->billing_mode, 'badge-secondary', 'fa-question'];
                            @endphp
                            <span class="badge {{ $m[1] }}"><i class="fa {{ $m[2] }} mr-1"></i>{{ $m[0] }}</span>
                        </td>
                        <td class="text-center">
                            @if($p->is_active)
                                <span class="badge badge-success">Aktif</span>
                            @else
                                <span class="badge badge-secondary">Off</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $p->sort }}</td>
                        <td class="text-right text-nowrap">
                            <a href="{{ route('admin.digital-products.edit', $p) }}" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-edit"></i>
                            </a>
                            @include('admin.partials.delete-form', [
                                'action'  => route('admin.digital-products.destroy', $p),
                                'confirm' => "Hapus produk \"{$p->name}\"? Order yang sudah dibuat tidak ikut terhapus.",
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">
                        Belum ada produk digital.
                        <a href="{{ route('admin.digital-products.create') }}">Tambah produk pertama →</a>
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@section('admin_js')
<script>
$(function() {
    $('#digital-products-table').DataTable({
        "language": { "url": "//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json" },
        "columnDefs": [{ "orderable": false, "targets": [0, -1] }],
        "order": [[6, "asc"]],
        "pageLength": 25
    });
});
</script>
@stop

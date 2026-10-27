{{-- W3: booking konsultasi diparkir karena belum ada / kedaluwarsa FHCU (session booking.pending). --}}
@php($pendingBooking = session('booking.pending'))
@if(is_array($pendingBooking) && filled($pendingBooking['email'] ?? null))
    <div class="mb-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 text-sm" role="status" data-booking-banner>
        Sebelum booking konsultasi, selesaikan Financial Health Check-Up (gratis) dulu ya.
        @if(($pendingBooking['service_type'] ?? 'standard') !== 'recovery')
            Tarif konsultasi mengikuti tahap hasil FHCU-mu.
        @endif
        Gunakan email yang sama (<strong>{{ $pendingBooking['email'] }}</strong>) — setelah hasil keluar, kamu bisa langsung melanjutkan booking.
    </div>
@endif

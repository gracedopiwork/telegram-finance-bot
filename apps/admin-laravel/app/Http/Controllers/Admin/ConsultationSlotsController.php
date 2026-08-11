<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConsultationSlot;
use App\Models\CpAdvisor;
use App\Services\ConsultationCheckoutService;
use App\Services\ConsultationSlotService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ConsultationSlotsController extends Controller
{
    public function __construct(
        private readonly ConsultationSlotService $slots,
        private readonly ConsultationCheckoutService $checkout,
    ) {}

    public function index(Request $request)
    {
        $this->slots->releaseExpiredHolds();

        $status = $request->query('status');
        $advisorId = $request->query('advisor_id');

        $query = ConsultationSlot::query()
            ->with('advisor')
            ->orderByDesc('starts_at');

        if (is_string($status) && $status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($advisorId) {
            $query->where('advisor_id', (int) $advisorId);
        }

        $slots = $query->paginate(40)->withQueryString();
        $advisors = CpAdvisor::orderBy('sort')->get();

        return view('admin.consultation_slots.index', compact('slots', 'advisors', 'status', 'advisorId'));
    }

    public function create()
    {
        return view('admin.consultation_slots.create', [
            'advisors' => CpAdvisor::active()->orderBy('sort')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'advisor_id' => 'required|exists:cp_advisors,id',
            'date' => 'required|date',
            'times' => 'required|string',
            'duration_minutes' => 'nullable|integer|min:15|max:240',
        ]);

        $advisor = CpAdvisor::findOrFail((int) $data['advisor_id']);
        $times = preg_split("/\r\n|\n|\r|,/", $data['times']) ?: [];
        $duration = (int) ($data['duration_minutes'] ?? 60);

        $created = $this->slots->createSlotsForDate($advisor, $data['date'], $times, $duration);

        return redirect()
            ->route('admin.consultation-slots.index')
            ->with('success', "{$created} slot ditambahkan untuk {$advisor->name} ({$data['date']}).");
    }

    public function confirm(ConsultationSlot $consultation_slot)
    {
        try {
            $this->slots->confirmPayment($consultation_slot);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first() ?? 'Gagal konfirmasi.');
        }

        return back()->with('success', 'Pembayaran dikonfirmasi — jadwal terkunci.');
    }

    public function release(ConsultationSlot $consultation_slot)
    {
        $this->slots->releaseHold($consultation_slot);

        return back()->with('success', 'Hold dilepas — slot kembali available.');
    }

    public function cancel(ConsultationSlot $consultation_slot)
    {
        $this->slots->cancelSlot($consultation_slot);

        return back()->with('success', 'Slot dibatalkan.');
    }

    public function overtimeInvoice(ConsultationSlot $consultation_slot)
    {
        try {
            $result = $this->checkout->createOvertimeInvoice($consultation_slot);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first() ?? 'Gagal buat invoice overtime.');
        }

        return back()->with('success', 'Invoice overtime dibuat. Salin link: '.$result['payment_url']);
    }

    public function destroy(ConsultationSlot $consultation_slot)
    {
        if ($consultation_slot->status === ConsultationSlot::STATUS_CONFIRMED) {
            return back()->with('error', 'Slot yang sudah confirmed tidak bisa dihapus. Batalkan dulu.');
        }

        $consultation_slot->delete();

        return back()->with('success', 'Slot dihapus.');
    }
}

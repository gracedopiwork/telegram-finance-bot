<?php

namespace App\Services;

use App\Models\License;
use App\Models\Order;
use App\Models\PortalAccountPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class PortalPasswordService
{
    public function isReady(): bool
    {
        return Schema::hasTable('portal_account_passwords');
    }

    public function hasPassword(string $email): bool
    {
        if (! $this->isReady()) {
            return false;
        }

        return PortalAccountPassword::query()
            ->where('email', $this->normalizeEmail($email))
            ->whereNotNull('password')
            ->exists();
    }

    public function setPassword(string $email, string $plain): void
    {
        $email = $this->normalizeEmail($email);
        PortalAccountPassword::query()->updateOrCreate(
            ['email' => $email],
            [
                'password' => Hash::make($plain),
                'password_set_at' => now(),
            ],
        );
    }

    public function verify(string $email, string $plain): bool
    {
        if (! $this->isReady() || $plain === '') {
            return false;
        }

        $row = PortalAccountPassword::query()
            ->where('email', $this->normalizeEmail($email))
            ->first();

        if ($row === null || ! filled($row->password)) {
            return false;
        }

        return Hash::check($plain, (string) $row->password);
    }

    /**
     * Resolve lisensi aktif terbaru untuk email (login password).
     */
    public function resolveLicenseForEmail(string $email): ?License
    {
        $email = $this->normalizeEmail($email);
        if ($email === '') {
            return null;
        }

        $order = Order::query()
            ->where('status', 'paid')
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNotNull('license_id')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->first();

        if ($order === null) {
            return null;
        }

        $license = License::query()
            ->whereKey($order->license_id)
            ->where('status', 'active')
            ->first();

        if ($license === null) {
            return null;
        }

        if ($license->expires_at && $license->expires_at->isPast()) {
            return null;
        }

        return $license;
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}

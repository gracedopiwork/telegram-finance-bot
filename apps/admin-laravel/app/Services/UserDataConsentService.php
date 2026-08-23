<?php

namespace App\Services;

use App\Models\UserDataConsent;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class UserDataConsentService
{
    public function currentVersion(): string
    {
        return (string) config('portal_privacy.version', '1.1');
    }

    public function textVersionLabel(): string
    {
        $version = $this->currentVersion();
        $updated = (string) config('portal_privacy.updated_at', '');

        return trim($version.' · '.$updated);
    }

    /**
     * @return list<string>
     */
    public function requiredCheckboxIds(): array
    {
        $ids = [];
        foreach ((array) config('portal_privacy.checkboxes', []) as $row) {
            if (! is_array($row) || empty($row['id'])) {
                continue;
            }
            $ids[] = (string) $row['id'];
        }

        return $ids;
    }

    public function hasAcceptedCurrent(int $telegramUserId): bool
    {
        if ($telegramUserId < 1) {
            return false;
        }

        return UserDataConsent::query()
            ->where('telegram_user_id', $telegramUserId)
            ->where('consent_version', $this->currentVersion())
            ->where('status', UserDataConsent::STATUS_ACCEPTED)
            ->exists();
    }

    public function latestForUser(int $telegramUserId): ?UserDataConsent
    {
        if ($telegramUserId < 1) {
            return null;
        }

        return UserDataConsent::query()
            ->where('telegram_user_id', $telegramUserId)
            ->orderByDesc('consented_at')
            ->first();
    }

    /**
     * @param  list<string>  $checkboxIds
     */
    public function accept(
        int $telegramUserId,
        string $method,
        array $checkboxIds,
    ): UserDataConsent {
        if ($telegramUserId < 1) {
            throw ValidationException::withMessages([
                'telegram_user_id' => 'telegram_user_id tidak valid.',
            ]);
        }

        $method = strtolower(trim($method));
        if (! in_array($method, [UserDataConsent::METHOD_BOT, UserDataConsent::METHOD_WEB], true)) {
            throw ValidationException::withMessages([
                'method' => 'Metode consent harus bot atau web.',
            ]);
        }

        $required = $this->requiredCheckboxIds();
        $given = array_values(array_unique(array_map('strval', $checkboxIds)));
        sort($required);
        $sortedGiven = $given;
        sort($sortedGiven);

        if ($required !== [] && $sortedGiven !== $required) {
            throw ValidationException::withMessages([
                'checkbox_ids' => 'Semua persetujuan wajib harus dicentang.',
            ]);
        }

        $version = $this->currentVersion();

        /** @var UserDataConsent $row */
        $row = UserDataConsent::query()->updateOrCreate(
            [
                'telegram_user_id' => $telegramUserId,
                'consent_version' => $version,
            ],
            [
                'status' => UserDataConsent::STATUS_ACCEPTED,
                'method' => $method,
                'consent_text_version' => $this->textVersionLabel(),
                'checkbox_ids' => $given,
                'consented_at' => Carbon::now(),
                'withdrawn_at' => null,
            ],
        );

        return $row;
    }

    public function statusPayload(int $telegramUserId): array
    {
        $version = $this->currentVersion();
        $accepted = $this->hasAcceptedCurrent($telegramUserId);
        $latest = $this->latestForUser($telegramUserId);

        return [
            'ok' => true,
            'telegram_user_id' => $telegramUserId,
            'consent_version' => $version,
            'consent_text_version' => $this->textVersionLabel(),
            'accepted' => $accepted,
            'title' => (string) config('portal_privacy.title', ''),
            'intro' => (string) config('portal_privacy.intro', ''),
            'sections' => array_values((array) config('portal_privacy.sections', [])),
            'checkboxes' => array_values((array) config('portal_privacy.checkboxes', [])),
            'contact_wa' => (string) config('portal_privacy.contact_wa', '+62 851-1122-8911'),
            'latest' => $latest ? [
                'status' => $latest->status,
                'method' => $latest->method,
                'consent_version' => $latest->consent_version,
                'consented_at' => optional($latest->consented_at)->toIso8601String(),
            ] : null,
        ];
    }
}

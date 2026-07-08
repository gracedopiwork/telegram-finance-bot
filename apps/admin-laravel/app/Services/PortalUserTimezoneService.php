<?php

namespace App\Services;

use App\Models\PortalUserPreference;
use Illuminate\Support\Facades\Cache;

class PortalUserTimezoneService
{
    /**
     * @return array<string, array{name: string, label: string, desc: string}>
     */
    public function options(): array
    {
        return (array) config('portal.indonesia_timezones', []);
    }

    /**
     * @return list<string>
     */
    public function allowedIanaNames(): array
    {
        return array_values(array_map(
            fn (array $row) => (string) $row['name'],
            $this->options()
        ));
    }

    public function defaultIana(): string
    {
        return (string) config('portal.display_timezone', 'Asia/Jakarta');
    }

    public function resolve(int $telegramUserId): string
    {
        if ($telegramUserId <= 0) {
            return $this->defaultIana();
        }

        return Cache::remember(
            "portal.user_tz.{$telegramUserId}",
            3600,
            function () use ($telegramUserId): string {
                $pref = PortalUserPreference::forUser($telegramUserId);

                return $this->isValidIana($pref?->timezone)
                    ? (string) $pref->timezone
                    : $this->defaultIana();
            }
        );
    }

    /**
     * @return array{timezone: string, label: string, source: string}
     */
    public function meta(int $telegramUserId): array
    {
        $pref = $telegramUserId > 0 ? PortalUserPreference::forUser($telegramUserId) : null;
        $timezone = $this->resolve($telegramUserId);

        return [
            'timezone' => $timezone,
            'label' => $this->labelFor($timezone),
            'source' => (string) ($pref?->timezone_source ?? 'default'),
        ];
    }

    public function labelFor(string $iana): string
    {
        foreach ($this->options() as $row) {
            if (($row['name'] ?? '') === $iana) {
                return (string) ($row['label'] ?? 'WIB');
            }
        }

        return 'WIB';
    }

    public function setManual(int $telegramUserId, string $zoneKey): void
    {
        $options = $this->options();
        if (! isset($options[$zoneKey])) {
            return;
        }

        $iana = (string) $options[$zoneKey]['name'];
        $this->persist($telegramUserId, $iana, 'manual');
    }

    public function setFromBrowser(int $telegramUserId, string $browserIana): void
    {
        $pref = PortalUserPreference::forUser($telegramUserId);
        if ($pref !== null && $pref->timezone_source === 'manual') {
            return;
        }

        $iana = $this->normalizeBrowserTimezone($browserIana);
        $this->persist($telegramUserId, $iana, 'auto');
    }

    public function normalizeBrowserTimezone(string $browserIana): string
    {
        $browserIana = trim($browserIana);
        if ($browserIana === '') {
            return $this->defaultIana();
        }

        if ($this->isValidIana($browserIana)) {
            return $browserIana;
        }

        $aliases = (array) config('portal.timezone_aliases', []);
        if (isset($aliases[$browserIana]) && $this->isValidIana($aliases[$browserIana])) {
            return (string) $aliases[$browserIana];
        }

        return $this->defaultIana();
    }

    private function persist(int $telegramUserId, string $iana, string $source): void
    {
        if ($telegramUserId <= 0 || ! $this->isValidIana($iana)) {
            return;
        }

        PortalUserPreference::query()->updateOrCreate(
            ['telegram_user_id' => $telegramUserId],
            ['timezone' => $iana, 'timezone_source' => $source],
        );

        Cache::forget("portal.user_tz.{$telegramUserId}");
    }

    private function isValidIana(?string $iana): bool
    {
        return is_string($iana) && in_array($iana, $this->allowedIanaNames(), true);
    }
}

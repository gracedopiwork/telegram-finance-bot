<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $table = 'site_settings';

    protected $fillable = ['key', 'value', 'type', 'group', 'label', 'sort'];

    /**
     * Get a setting value by key (cached).
     */
    public static function val(string $key, ?string $default = null): ?string
    {
        $all = Cache::rememberForever('site_settings.all', function () {
            return self::query()->pluck('value', 'key')->toArray();
        });

        return $all[$key] ?? $default;
    }

    /**
     * Set/update a setting (clears cache).
     */
    public static function put(string $key, ?string $value, string $type = 'text', string $group = 'general', ?string $label = null): self
    {
        $row = self::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type, 'group' => $group, 'label' => $label]
        );
        Cache::forget('site_settings.all');
        return $row;
    }

    public static function bust(): void
    {
        Cache::forget('site_settings.all');
        // Bust group caches used by LandingController.
        foreach (['brand', 'contact', 'hero', 'about', 'stats', 'vision', 'mission', 'values'] as $g) {
            Cache::forget("settings.group.{$g}");
        }
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::bust());
        static::deleted(fn () => self::bust());
    }
}

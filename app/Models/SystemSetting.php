<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'type', 'label', 'description', 'group'];

    /**
     * Boot the model and register event listeners
     */
    protected static function booted()
    {
        // Clear cache when a setting is saved/updated
        static::saved(function ($setting) {
            Cache::forget("setting:{$setting->key}");
        });

        // Clear cache when a setting is deleted
        static::deleted(function ($setting) {
            Cache::forget("setting:{$setting->key}");
        });
    }

    // Get a setting value by key with optional default
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting:{$key}", 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            if (!$setting) return $default;

            return match($setting->type) {
                'boolean' => (bool) $setting->value,
                'integer' => (int)  $setting->value,
                default   => $setting->value,
            };
        });
    }

    // Set a setting value and clear its cache
    public static function set(string $key, mixed $value): void
    {
        self::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget("setting:{$key}");
    }

    // Clear all settings cache
    public static function clearCache(): void
    {
        self::all()->each(fn($s) => Cache::forget("setting:{$s->key}"));
    }

    // ✅ Renamed from refresh() to refreshCache() to avoid conflict
    public static function refreshCache(string $key): mixed
    {
        Cache::forget("setting:{$key}");
        return self::get($key);
    }

    // ✅ Alternative: Force refresh a specific setting (non-static)
    public function refreshSetting(): mixed
    {
        Cache::forget("setting:{$this->key}");
        return self::get($this->key);
    }
}

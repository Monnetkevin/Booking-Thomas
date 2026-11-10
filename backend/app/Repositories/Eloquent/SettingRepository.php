<?php

namespace App\Repositories\Eloquent;

use App\Models\Setting;
use App\Repositories\Contracts\SettingRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Réglages globaux, lus très souvent (calcul des prix) et modifiés rarement :
 * tous mis en cache ensemble, cache vidé à chaque écriture.
 */
class SettingRepository implements SettingRepositoryInterface
{
    private const CACHE_KEY = 'settings.all';

    public function __construct(private readonly Cache $cache) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function all(): array
    {
        /** @var array<string, mixed> */
        return $this->cache->rememberForever(
            self::CACHE_KEY,
            fn () => Setting::query()->pluck('value', 'key')->all(),
        );
    }

    public function put(string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        $this->cache->forget(self::CACHE_KEY);
    }

    public function putIfMissing(string $key, mixed $value): void
    {
        $setting = Setting::query()->firstOrCreate(['key' => $key], ['value' => $value]);

        if ($setting->wasRecentlyCreated) {
            $this->cache->forget(self::CACHE_KEY);
        }
    }
}

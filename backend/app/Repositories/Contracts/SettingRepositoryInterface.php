<?php

namespace App\Repositories\Contracts;

interface SettingRepositoryInterface
{
    public function get(string $key, mixed $default = null): mixed;

    /**
     * @return array<string, mixed>
     */
    public function all(): array;

    public function put(string $key, mixed $value): void;

    /** Enregistre la valeur seulement si la clé n'existe pas encore. */
    public function putIfMissing(string $key, mixed $value): void;
}

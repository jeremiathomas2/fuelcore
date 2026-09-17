<?php

use App\Models\SystemSetting;
use App\Support\UrlId;

if (! function_exists('url_id')) {
    function url_id(int|string|null $id): ?string
    {
        return UrlId::encode($id);
    }
}

if (! function_exists('options')) {
    function options(string $key, mixed $default = null): mixed
    {
        $value = SystemSetting::get($key, $default);

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }
}

if (! function_exists('currency')) {
    function currency(): string
    {
        return SystemSetting::currency();
    }
}

if (! function_exists('format_money')) {
    function format_money(mixed $value): string
    {
        return SystemSetting::currency() . ' ' . number_format((float) $value, 2);
    }
}

if (! function_exists('format_qty')) {
    function format_qty(mixed $value, int $decimals = 2): string
    {
        return number_format((float) $value, $decimals) . ' L';
    }
}
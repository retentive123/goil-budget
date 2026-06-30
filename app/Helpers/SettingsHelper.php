<?php

if (!function_exists('currency')) {
    function currency(): string
    {
        return \App\Models\SystemSetting::get('currency_symbol', '{{ currency() }}');
    }
}

if (!function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return \App\Models\SystemSetting::get($key, $default);
    }
}

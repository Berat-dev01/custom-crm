<?php

if (! function_exists('admin_trans')) {
    function admin_trans(string $key, array $replace = [], ?string $locale = null): string
    {
        return __($key, $replace, $locale);
    }
}

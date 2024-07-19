<?php

if (!function_exists('array_flatten')) {
    function array_flatten($array) {
        return Illuminate\Support\Arr::flatten($array);
    }
}
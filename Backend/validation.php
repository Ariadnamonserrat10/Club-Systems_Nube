<?php

if (!function_exists('mb_strlen')) {
    function mb_strlen($str, $encoding = null) { return strlen($str); }
}

if (!function_exists('mb_strtoupper')) {
    function mb_strtoupper($str, $encoding = null) { return strtoupper($str); }
}

if (!function_exists('mb_strtolower')) {
    function mb_strtolower($str, $encoding = null) { return strtolower($str); }
}

if (!function_exists('normalize_spaces')) {
    function normalize_spaces($value) {
        $value = trim((string)$value);
        return preg_replace('/\s+/', ' ', $value);
    }
}

if (!function_exists('to_title_case')) {
    function to_title_case($value) {
        $value = normalize_spaces($value);
        if ($value === '') return '';

        if (function_exists('mb_convert_case')) {
            return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
        }

        return ucwords(strtolower($value));
    }
}

if (!function_exists('is_text_only')) {
    function is_text_only($value, $allowEmpty = false) {
        $value = normalize_spaces($value);
        if ($value === '') return $allowEmpty;
        return preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü ]+$/u', $value) === 1;
    }
}

if (!function_exists('starts_with_uppercase_letter')) {
    function starts_with_uppercase_letter($value) {
        $value = normalize_spaces($value);
        if ($value === '') return false;
        return preg_match('/^[A-ZÁÉÍÓÚÑÜ]/u', $value) === 1;
    }
}

if (!function_exists('is_title_case_text')) {
    function is_title_case_text($value, $allowEmpty = false) {
        $value = normalize_spaces($value);
        if ($value === '') return $allowEmpty;
        // Acepta también palabras de una letra (ej. "Y").
        return preg_match('/^([A-ZÁÉÍÓÚÑÜ][a-záéíóúñü]*)( [A-ZÁÉÍÓÚÑÜ][a-záéíóúñü]*)*$/u', $value) === 1;
    }
}

if (!function_exists('is_username_letters_only')) {
    function is_username_letters_only($value, $allowEmpty = false) {
        $value = trim((string)$value);
        if ($value === '') return $allowEmpty;
        return preg_match('/^[A-Za-zÁÉÍÓÚáéíóúÑñÜü]+$/u', $value) === 1;
    }
}

if (!function_exists('is_digits_only')) {
    function is_digits_only($value, $minLen = null, $maxLen = null, $allowEmpty = false) {
        $value = trim((string)$value);
        if ($value === '') return $allowEmpty;
        if (!preg_match('/^\d+$/', $value)) return false;

        $len = strlen($value);
        if ($minLen !== null && $len < (int)$minLen) return false;
        if ($maxLen !== null && $len > (int)$maxLen) return false;

        return true;
    }
}

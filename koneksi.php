<?php
// koneksi.php

// 1. Ambil kredensial dari Environment Variables (Vercel) atau file .env lokal
$env_supabase_url = getenv('SUPABASE_URL');
$env_supabase_key = getenv('SUPABASE_KEY');

if (!$env_supabase_url || !$env_supabase_key) {
    $env_file = __DIR__ . '/.env';
    if (file_exists($env_file)) {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                if ($name === 'SUPABASE_URL') $env_supabase_url = $value;
                if ($name === 'SUPABASE_KEY') $env_supabase_key = $value;
            }
        }
    }
}

// 2. Tetapkan konstanta (Isi nilai fallback jika diperlukan)
define('SUPABASE_URL', $env_supabase_url ?: 'https://nmvseaqdbvbwhdcmmqzv.supabase.co');
define('SUPABASE_KEY', $env_supabase_key ?: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Im5tdnNlYXFkYnZid2hkY21tcXp2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODg5MTA5NDQsImV4cCI6MjEwNDQ4Njk0NH0.BwNjnVXXBweZktecTaTdP7kIiOHUvIxUVLqWg85Kq88');

/**
 * Fungsi request Supabase REST API via cURL
 */
function supabase_request($endpoint, $method = 'GET', $body = null) {
    // Bersihkan prefix /rest/v1/ jika tidak sengaja disertakan pada parameter
    $clean_endpoint = ltrim($endpoint, '/');
    if (strpos($clean_endpoint, 'rest/v1/') === 0) {
        $clean_endpoint = substr($clean_endpoint, strlen('rest/v1/'));
    }

    $url = rtrim(SUPABASE_URL, '/') . '/rest/v1/' . $clean_endpoint;

    $ch = curl_init($url);
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    if ($body !== null) {
        $json_payload = is_array($body) ? json_encode($body) : $body;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    $decoded_data = json_decode($response, true);

    return [
        'status' => $http_code,
        'data'   => is_array($decoded_data) ? $decoded_data : [],
        'error'  => $curl_error
    ];
}
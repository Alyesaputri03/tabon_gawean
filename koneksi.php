<?php
// Konfigurasi Kredensial Supabase Project Tabon Gawean
define('SUPABASE_URL', getenv('SUPABASE_URL') ?: 'https://nmvseaqdbvbwhdcmmqzv.supabase.co');

// Masukkan Secret Key lengkap (sb_secret_...) yang disalin dari Settings > API Supabase
efine('SUPABASE_KEY', getenv('SUPABASE_KEY') ?: 'sb_secret_03haNqPIgM99dbxuJotgwA_r9rqMyVS'); 

/**
 * Helper Client untuk menjalankan request REST API ke PostgREST Supabase
 *
 * @param string $endpoint Path REST API (contoh: '/rest/v1/modul_layanan?select=*')
 * @param string $method HTTP Verb: 'GET', 'POST', 'PATCH', 'DELETE'
 * @param array|null $data Payload dalam bentuk array asosiatif (opsional)
 * @return array Hasil respon berupa array asosiatif ['status' => int, 'data' => mixed, 'error' => string]
 */
function supabase_request($endpoint, $method = 'GET', $data = null) {
    $url = rtrim(SUPABASE_URL, '/') . $endpoint;
    $ch  = curl_init($url);

    // Header otentikasi REST API Supabase
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    // Kirim body payload jika metode POST, PATCH, atau PUT
    if ($data !== null && in_array(strtoupper($method), ['POST', 'PATCH', 'PUT'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $decoded = json_decode($response, true);

    return [
        'status' => $httpCode,
        'data'   => $decoded,
        'error'  => $curlError ?: ($httpCode >= 400 ? ($decoded['message'] ?? 'API Error ' . $httpCode) : '')
    ];
}
?>
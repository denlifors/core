<?php
function corePostJson($path, $payload, &$error = null) {
    $url = rtrim(CORE_API_BASE_URL, '/') . '/' . ltrim($path, '/');
    $json = json_encode($payload);

    if ($json === false) {
        $error = 'Failed to encode JSON payload';
        return null;
    }

    if (!function_exists('curl_init')) {
        $error = 'cURL is not available';
        return null;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($json)
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $response = curl_exec($ch);
    if ($response === false) {
        $error = 'Core request failed: ' . curl_error($ch);
        curl_close($ch);
        return null;
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);
    return [
        'status' => $status,
        'raw' => $response,
        'data' => $data
    ];
}
?>

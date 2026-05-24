<?php
/**
 * web_push.php — Web Push sender (VAPID, no payload encryption)
 * ส่ง empty push เพื่อ wake up Service Worker ซึ่งจะ fetch ข้อความเองจาก API
 * ใช้ PHP openssl เท่านั้น — ไม่ต้องการ composer
 */

// XAMPP on Windows needs OPENSSL_CONF set for key operations
if (PHP_OS_FAMILY === 'Windows' && !getenv('OPENSSL_CONF')) {
    foreach ([
        'C:/xampp/apache/conf/openssl.cnf',
        'C:/xampp/php/extras/ssl/openssl.cnf',
    ] as $path) {
        if (file_exists($path)) { putenv("OPENSSL_CONF={$path}"); break; }
    }
}

function wp_b64u_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function wp_b64u_decode(string $data): string {
    $pad = strlen($data) % 4;
    if ($pad) $data .= str_repeat('=', 4 - $pad);
    return base64_decode(strtr($data, '-_', '+/'));
}

/**
 * Generate VAPID keypair.
 * Returns ['public' => base64url(65-byte uncompressed point), 'private_pem' => PEM string]
 */
function webpush_generate_vapid(): array {
    $key     = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
    $details = openssl_pkey_get_details($key);
    $x = str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT);
    $y = str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);
    openssl_pkey_export($key, $privPem);
    return [
        'public'      => wp_b64u_encode("\x04" . $x . $y),
        'private_pem' => $privPem,
    ];
}

/**
 * Convert DER-encoded ECDSA signature → raw 64-byte R||S
 */
function wp_der_to_rs(string $der): string {
    $offset = 2; // skip 0x30 + total_len
    $offset++; // skip 0x02 (R tag)
    $rLen = ord($der[$offset++]);
    $r    = substr($der, $offset, $rLen);
    $offset += $rLen;
    $offset++; // skip 0x02 (S tag)
    $sLen = ord($der[$offset++]);
    $s    = substr($der, $offset, $sLen);
    // Strip leading 0x00 padding, then left-pad to 32 bytes
    $r = str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT);
    $s = str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);
    return $r . $s;
}

/**
 * Build VAPID JWT (ES256)
 */
function wp_build_jwt(string $audience, string $subject, string $privPem): string {
    $hdr = wp_b64u_encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $pay = wp_b64u_encode(json_encode([
        'aud' => $audience,
        'exp' => time() + 43200,
        'sub' => $subject,
    ]));
    $unsigned = $hdr . '.' . $pay;
    $privKey  = openssl_pkey_get_private($privPem);
    openssl_sign($unsigned, $derSig, $privKey, OPENSSL_ALGO_SHA256);
    return $unsigned . '.' . wp_b64u_encode(wp_der_to_rs($derSig));
}

/**
 * Send Web Push notification (empty payload — SW fetches content on wake-up)
 *
 * @param array  $sub        ['endpoint'=>..., 'p256dh'=>..., 'auth'=>...]
 * @param string $vapidPub   base64url-encoded VAPID public key (87 chars)
 * @param string $vapidPem   VAPID private key PEM
 * @param string $subject    mailto: or https: contact URI
 * @return int   HTTP status code (201=ok, 410=expired subscription)
 */
function webpush_send(array $sub, string $vapidPub, string $vapidPem, string $subject = 'mailto:admin@localhost'): int {
    $endpoint = $sub['endpoint'];
    $parsed   = parse_url($endpoint);
    $audience = $parsed['scheme'] . '://' . $parsed['host'] . (isset($parsed['port']) ? ':' . $parsed['port'] : '');
    $jwt      = wp_build_jwt($audience, $subject, $vapidPem);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => '',
        CURLOPT_HTTPHEADER     => [
            "Authorization: vapid t={$jwt},k={$vapidPub}",
            'Content-Type: application/octet-stream',
            'Content-Length: 0',
            'TTL: 86400',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $code;
}

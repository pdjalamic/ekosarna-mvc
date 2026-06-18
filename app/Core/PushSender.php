<?php
namespace Core;

/**
 * Samostalno slanje Web Push notifikacija — BEZ Composer-a / vendor-a.
 *
 * Koristi isključivo PHP ugrađene ekstenzije:
 *   - openssl  (VAPID ES256 potpis, efemerni EC ključ, ECDH, AES-128-GCM)
 *   - curl     (POST na push endpoint)
 *   - hash_hkdf (RFC 5869 HKDF)
 *
 * Implementira:
 *   - VAPID (RFC 8292) — Authorization: vapid t=<JWT>, k=<public>
 *   - Šifrovanje sadržaja aes128gcm (RFC 8188) + ključ. razmena (RFC 8291)
 *
 * Zahtevi: PHP 7.3+ sa openssl (openssl_pkey_derive) i hash_hkdf.
 * VAPID ključevi su base64url (public = 65B nekompresovana tačka, private = 32B skalar).
 */
class PushSender
{
    /** TTL koliko push servis čuva poruku ako je uređaj offline (28 dana). */
    private const TTL = 2419200;

    /**
     * Pošalji jednu notifikaciju.
     *
     * @return array{success:bool, status:int, reason:string}
     */
    public static function send(string $endpoint, string $p256dh, string $authKey, array $payload): array
    {
        try {
            if (!defined('VAPID_PUBLIC_KEY') || VAPID_PUBLIC_KEY === ''
                || !defined('VAPID_PRIVATE_KEY') || VAPID_PRIVATE_KEY === '') {
                return ['success' => false, 'status' => 0, 'reason' => 'VAPID ključevi nisu podešeni.'];
            }

            $body = self::encrypt(json_encode($payload, JSON_UNESCAPED_UNICODE), $p256dh, $authKey);
            $jwt  = self::vapidJwt($endpoint);
            $k    = rtrim(strtr(VAPID_PUBLIC_KEY, '+/', '-_'), '=');

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/octet-stream',
                    'Content-Encoding: aes128gcm',
                    'TTL: ' . self::TTL,
                    'Urgency: normal',
                    'Authorization: vapid t=' . $jwt . ', k=' . $k,
                ],
            ]);
            $resp   = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $cerr   = curl_error($ch);
            curl_close($ch);

            if ($cerr !== '') {
                return ['success' => false, 'status' => $status, 'reason' => 'cURL: ' . $cerr];
            }
            $ok = ($status >= 200 && $status < 300);
            $reason = $ok ? 'OK' : ('HTTP ' . $status . ' ' . trim((string) $resp));
            return ['success' => $ok, 'status' => $status, 'reason' => mb_substr($reason, 0, 300)];
        } catch (\Throwable $e) {
            return ['success' => false, 'status' => 0, 'reason' => $e->getMessage()];
        }
    }

    /** Da li je pretplata istekla/nevažeća (treba je obrisati iz baze). */
    public static function isExpiredStatus(int $status): bool
    {
        return $status === 404 || $status === 410;
    }

    // ── Šifrovanje sadržaja (aes128gcm) ───────────────────────────────────
    private static function encrypt(string $payload, string $p256dh, string $authKey): string
    {
        $clientPub  = self::b64uDec($p256dh);   // 65B nekompresovana tačka klijenta
        $authSecret = self::b64uDec($authKey);  // 16B auth tajna klijenta

        // Efemerni (server) EC P-256 ključ za ovu poruku
        $ec = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        if ($ec === false) {
            throw new \RuntimeException('Ne mogu da napravim efemerni EC ključ (openssl).');
        }
        $d = openssl_pkey_get_details($ec);
        $serverPub = "\x04"
            . str_pad($d['ec']['x'], 32, "\x00", STR_PAD_LEFT)
            . str_pad($d['ec']['y'], 32, "\x00", STR_PAD_LEFT);

        // ECDH zajednička tajna (server priv + klijent pub)
        $peer   = self::pubKeyFromPoint($clientPub);
        $shared = openssl_pkey_derive($peer, $ec, 32);
        if ($shared === false) {
            throw new \RuntimeException('ECDH (openssl_pkey_derive) nije uspeo.');
        }

        // HKDF po RFC 8291
        $info = "WebPush: info\x00" . $clientPub . $serverPub;
        $ikm  = hash_hkdf('sha256', $shared, 32, $info, $authSecret);

        $salt  = random_bytes(16);
        $cek   = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\x00", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\x00", $salt);

        // Jedan zapis (RFC 8188), poslednji => delimiter 0x02
        $tag    = '';
        $cipher = openssl_encrypt($payload . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
        if ($cipher === false) {
            throw new \RuntimeException('AES-128-GCM šifrovanje nije uspelo.');
        }

        // Zaglavlje: salt(16) | rs(4) | idlen(1) | serverPub(65) | ciphertext | tag
        return $salt . pack('N', 4096) . chr(strlen($serverPub)) . $serverPub . $cipher . $tag;
    }

    // ── VAPID JWT (ES256) ─────────────────────────────────────────────────
    private static function vapidJwt(string $endpoint): string
    {
        $p   = parse_url($endpoint);
        $aud = $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');

        $header = self::b64uEnc(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims = self::b64uEnc(json_encode([
            'aud' => $aud,
            'exp' => time() + 43200, // 12h
            'sub' => VAPID_SUBJECT,
        ]));
        $input = $header . '.' . $claims;

        $key = openssl_pkey_get_private(self::vapidPrivatePem());
        if ($key === false) {
            throw new \RuntimeException('VAPID privatni ključ nije validan (openssl).');
        }
        $derSig = '';
        if (!openssl_sign($input, $derSig, $key, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('VAPID potpis (openssl_sign) nije uspeo.');
        }
        return $input . '.' . self::b64uEnc(self::derToRaw($derSig));
    }

    /** Sklapa EC privatni ključ (PEM) iz sirovog 32B skalara + 65B tačke. */
    private static function vapidPrivatePem(): string
    {
        $priv = self::b64uDec(VAPID_PRIVATE_KEY); // 32B
        $pub  = self::b64uDec(VAPID_PUBLIC_KEY);  // 65B
        $der  = "\x30\x77\x02\x01\x01\x04\x20" . $priv
              . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07"
              . "\xa1\x44\x03\x42\x00" . $pub;
        return "-----BEGIN EC PRIVATE KEY-----\n"
             . chunk_split(base64_encode($der), 64, "\n")
             . "-----END EC PRIVATE KEY-----\n";
    }

    /** Pravi OpenSSL javni ključ iz sirove 65B nekompresovane tačke (SPKI). */
    private static function pubKeyFromPoint(string $point): \OpenSSLAsymmetricKey
    {
        $der = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01"
             . "\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00" . $point;
        $pem = "-----BEGIN PUBLIC KEY-----\n"
             . chunk_split(base64_encode($der), 64, "\n")
             . "-----END PUBLIC KEY-----\n";
        $k = openssl_pkey_get_public($pem);
        if ($k === false) {
            throw new \RuntimeException('Klijentski p256dh ključ nije validan.');
        }
        return $k;
    }

    /** ASN.1 DER ECDSA potpis -> sirovih 64B (R||S), kao što JWT očekuje. */
    private static function derToRaw(string $der): string
    {
        $o = 0;
        if (ord($der[$o++]) !== 0x30) throw new \RuntimeException('Loš ECDSA potpis (SEQ).');
        $len = ord($der[$o++]);
        if ($len & 0x80) $o += ($len & 0x7f); // duga forma dužine (ne treba nam vrednost)

        if (ord($der[$o++]) !== 0x02) throw new \RuntimeException('Loš ECDSA potpis (R).');
        $rlen = ord($der[$o++]); $r = substr($der, $o, $rlen); $o += $rlen;

        if (ord($der[$o++]) !== 0x02) throw new \RuntimeException('Loš ECDSA potpis (S).');
        $slen = ord($der[$o++]); $s = substr($der, $o, $slen);

        $r = str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT);
        $s = str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);
        return $r . $s;
    }

    // ── base64url ─────────────────────────────────────────────────────────
    private static function b64uEnc(string $d): string
    {
        return rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
    }

    private static function b64uDec(string $d): string
    {
        $d   = strtr($d, '-_', '+/');
        $pad = strlen($d) % 4;
        if ($pad) $d .= str_repeat('=', 4 - $pad);
        return base64_decode($d);
    }
}

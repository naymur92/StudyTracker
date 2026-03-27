<?php

namespace App\Services;

/**
 * Custom ID encoder/decoder for the Study Tracker API.
 *
 * Converts integer database IDs to short opaque strings exposed in API
 * responses, preventing sequential ID enumeration and accidental reference
 * leaks.
 *
 * Algorithm
 * ---------
 * 1. XOR the integer with a 32-bit key derived from HASHIDS_SALT.
 * 2. Run 3 rounds of a Feistel cipher (split into two 16-bit halves,
 *    mix-and-XOR each half using salt-derived round keys).
 * 3. Base-62 encode the resulting 32-bit integer.
 * 4. Prepend a 2-hex-character HMAC prefix for tamper detection.
 *
 * Decoding reverses steps 4 → 1.
 *
 * Configuration
 * -------------
 * Set HASHIDS_SALT in .env to any non-empty random string.
 * Do NOT change this value in production — it invalidates all outstanding IDs.
 *
 * No external packages are required.
 */
class IdHasher
{
    /** Base-62 URL-safe alphabet */
    private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /** Cached key material, derived once per process from the configured salt */
    private static ?array $keys = null;

    // ------------------------------------------------------------------ //
    //  Public API
    // ------------------------------------------------------------------ //

    /**
     * Encode a positive integer database ID to an opaque hash string.
     *
     * @throws \InvalidArgumentException for non-positive IDs.
     */
    public static function encode(int $id): string
    {
        if ($id < 1) {
            throw new \InvalidArgumentException("Only positive integers can be encoded (got {$id}).");
        }

        ['xor' => $xor, 'rounds' => $rounds, 'mac' => $macKey] = static::derivedKeys();

        $value   = static::feistelEnc($id ^ $xor, $rounds);
        $encoded = static::baseEncode($value);

        return static::computeMac($encoded, $macKey) . $encoded;
    }

    /**
     * Decode an opaque hash string back to an integer database ID.
     *
     * Returns null for any invalid, tampered, or unrecognised input.
     */
    public static function decode(string $hash): ?int
    {
        if (strlen($hash) < 3) {
            return null;
        }

        ['xor' => $xor, 'rounds' => $rounds, 'mac' => $macKey] = static::derivedKeys();

        $mac     = substr($hash, 0, 2);
        $encoded = substr($hash, 2);

        // Constant-time comparison prevents timing-based enumeration
        if (! hash_equals(static::computeMac($encoded, $macKey), $mac)) {
            return null;
        }

        $value = static::baseDecode($encoded);

        if ($value === null) {
            return null;
        }

        $id = static::feistelDec($value, $rounds) ^ $xor;

        return $id >= 1 ? $id : null;
    }

    // ------------------------------------------------------------------ //
    //  Key derivation  (computed once, then cached for the process lifetime)
    // ------------------------------------------------------------------ //

    private static function derivedKeys(): array
    {
        if (static::$keys !== null) {
            return static::$keys;
        }

        $salt = config('hashids.salt', 'StudyTracker_please_set_HASHIDS_SALT');

        // Domain-separated SHA-256 → 32 bytes of deterministic key material
        $raw = hash('sha256', 'st_id_hasher_v1:' . $salt, true);

        return static::$keys = [
            // 32-bit XOR key: bytes 0–3
            'xor'    => unpack('N', substr($raw, 0, 4))[1],

            // Three 16-bit Feistel round keys: bytes 4–9
            'rounds' => [
                unpack('n', substr($raw, 4, 2))[1],
                unpack('n', substr($raw, 6, 2))[1],
                unpack('n', substr($raw, 8, 2))[1],
            ],

            // 16-byte HMAC key: bytes 10–25
            'mac'    => substr($raw, 10, 16),
        ];
    }

    // ------------------------------------------------------------------ //
    //  3-round Feistel cipher on 32-bit integers
    //
    //  Encryption:
    //    lo1 = (lo0 XOR mix(hi0, r0)) & 0xFFFF
    //    hi1 = (hi0 XOR mix(lo1, r1)) & 0xFFFF
    //    lo2 = (lo1 XOR mix(hi1, r2)) & 0xFFFF
    //    output = (hi1 << 16) | lo2
    //
    //  Decryption reverses the rounds (r2 → r1 → r0) on the cipher output.
    //  The Feistel structure guarantees a bijection regardless of the mix fn.
    // ------------------------------------------------------------------ //

    private static function feistelEnc(int $x, array $rk): int
    {
        $lo = $x & 0xFFFF;
        $hi = ($x >> 16) & 0xFFFF;

        $lo = ($lo ^ self::mix($hi, $rk[0])) & 0xFFFF;
        $hi = ($hi ^ self::mix($lo, $rk[1])) & 0xFFFF;
        $lo = ($lo ^ self::mix($hi, $rk[2])) & 0xFFFF;

        return ($hi << 16) | $lo;
    }

    private static function feistelDec(int $x, array $rk): int
    {
        $lo = $x & 0xFFFF;
        $hi = ($x >> 16) & 0xFFFF;

        // Reverse: apply round keys in reverse order (r2, r1, r0)
        $lo = ($lo ^ self::mix($hi, $rk[2])) & 0xFFFF;
        $hi = ($hi ^ self::mix($lo, $rk[1])) & 0xFFFF;
        $lo = ($lo ^ self::mix($hi, $rk[0])) & 0xFFFF;

        return ($hi << 16) | $lo;
    }

    /**
     * Non-linear 16-bit mix function (Murmur-style multiply-XOR).
     * The round function in a Feistel cipher does NOT need to be invertible.
     */
    private static function mix(int $x, int $key): int
    {
        $h = ($x ^ ($x >> 8)) & 0xFFFF;

        return ($h * 0xB543 + $key) & 0xFFFF;
    }

    // ------------------------------------------------------------------ //
    //  Base-62 encode / decode
    // ------------------------------------------------------------------ //

    private static function baseEncode(int $num): string
    {
        $alpha  = self::ALPHABET;
        $base   = strlen($alpha); // 62
        $result = '';

        do {
            $result = $alpha[$num % $base] . $result;
            $num    = intdiv($num, $base);
        } while ($num > 0);

        return $result;
    }

    private static function baseDecode(string $str): ?int
    {
        $alpha = self::ALPHABET;
        $base  = strlen($alpha);
        $num   = 0;

        for ($i = 0, $len = strlen($str); $i < $len; $i++) {
            $pos = strpos($alpha, $str[$i]);

            if ($pos === false) {
                return null; // Character not in alphabet
            }

            $num = $num * $base + $pos;
        }

        return $num;
    }

    // ------------------------------------------------------------------ //
    //  2-hex-char HMAC prefix for lightweight tamper detection
    // ------------------------------------------------------------------ //

    private static function computeMac(string $data, string $key): string
    {
        return substr(hash_hmac('sha256', $data, $key), 0, 2);
    }
}

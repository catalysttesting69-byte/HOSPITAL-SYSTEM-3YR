<?php
// ============================================================
// includes/encryption.php — AES-256-CBC Encryption Helpers
// ============================================================

require_once __DIR__ . '/../config/db.php';

/**
 * Encrypt a plain-text string using AES-256-CBC.
 * Returns base64-encoded ciphertext (IV prepended).
 */
function encryptData(string $plaintext): string {
    $iv         = openssl_random_pseudo_bytes(16);
    $ciphertext = openssl_encrypt($plaintext, 'AES-256-CBC', ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
    if ($ciphertext === false) {
        throw new RuntimeException('Encryption failed: ' . openssl_error_string());
    }
    // Prepend IV so we can use it during decryption
    return base64_encode($iv . $ciphertext);
}

/**
 * Decrypt an AES-256-CBC encrypted string (IV prepended, base64-encoded).
 */
function decryptData(string $encoded): string {
    $raw        = base64_decode($encoded, true);
    if ($raw === false || strlen($raw) < 17) {
        return '[Decryption Error: invalid data]';
    }
    $iv         = substr($raw, 0, 16);
    $ciphertext = substr($raw, 16);
    $plaintext  = openssl_decrypt($ciphertext, 'AES-256-CBC', ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
    if ($plaintext === false) {
        return '[Decryption Error: ' . openssl_error_string() . ']';
    }
    return $plaintext;
}

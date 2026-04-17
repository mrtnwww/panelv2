<?php

namespace App\Utils;

class Encryptation
{

    const ENV_VAR_KEY = 'ENCRYPT_KEY';

    public static function encrypt ($data)
    {
        $key = 'B796907244651E3710AD18E91126A195';
        if ($key) {
            $cipher = "AES-256-CBC";
            $ivlen = openssl_cipher_iv_length($cipher);
            $iv = openssl_random_pseudo_bytes($ivlen);
            $encrypted = openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv);
            $base64 = base64_encode($iv . $encrypted);

            // Convertir a base64url
            $base64url = strtr($base64, '+/', '-_');
            $base64url = rtrim($base64url, '=');

            return $base64url;
        } else {
            return null;
        }
    }

    public static function decrypt ($data)
    {
        $key = 'B796907244651E3710AD18E91126A195';
        if ($key) {
            // Convertir de base64url a base64 estándar
            $base64 = strtr($data, '-_', '+/');
            $data = str_pad($base64, strlen($base64) % 4, '=', STR_PAD_RIGHT);

            $cipher = "AES-256-CBC";
            $data = base64_decode($data);
            $ivlen = openssl_cipher_iv_length($cipher);
            $iv = substr($data, 0, $ivlen);
            $encrypted = substr($data, $ivlen);
            return openssl_decrypt($encrypted, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        } else {
            return null;
        }
    }

}

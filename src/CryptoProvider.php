<?php

namespace Itrack\CSRF;

class CryptoProvider implements ICryptoProvider {

    public function getRandomHexString($length) {
        return bin2hex(random_bytes($length / 2));
    }

    public function hash($data, $secret) {
        return hash_hmac('sha512', $data, $secret);
    }

}

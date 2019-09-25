<?php

namespace Itrack\CSRF;


class SignatureGenerator {

    protected $validityWindow = '-1 hour',
              $data           = array(),
              $crypto,
              $secret;

    public function __construct($secret, ICryptoProvider $crypto = null) {
        if (!$crypto) {
            $crypto = new CryptoProvider;
        }

        $this->secret = $secret;
        $this->crypto = $crypto;
        $this->setValidityWindow($this->validityWindow);
    }

    public function setValidityWindow($window) {
        switch (true) {
            case is_int($window) :
                $this->validityWindow = $window;
                break;
            case is_string($window) :
                $this->validityWindow = strtotime($window);
                break;
            case $window instanceof \DateTime :
                $this->validityWindow = $window->getTimestamp();
                break;
            default :
                throw new \InvalidArgumentException(sprintf('Invalid argument of type %s', gettype($window)));
        }
    }

    public function addValue($value) {
        $this->data[] = $value;
    }

    public function addKeyValue($key, $value) {
        $this->data[$key] = $value;
    }

    public function setData(array $data) {
        $this->data = $data;
    }

    public function getSignature() {
        $timestamp = time();
        $token     = $this->generateToken();
        $signature = $this->generateSignature($timestamp, $token);
        return base64_encode("$timestamp:$token:$signature");
    }

    public function validateSignature($signatureToken) {
        $decode = base64_decode($signatureToken);
        $args = explode(':', $decode);
        if (count($args) != 3) {
            return false;
        }
        
        list($timestamp, $token, $signature) = $args;

        if ($timestamp < $this->validityWindow) {
            return false;
        }
        return $this->generateSignature($timestamp, $token) === $signature;
    }

    protected function generateSignature($timestamp, $token) {
        if (!is_numeric($timestamp)) {
            throw new \InvalidArgumentException('$timestamp must be an integer');
        }
        $timestamp = (int)$timestamp;

        $array = $object = array();
        foreach ($this->data as $key => $value) {
            if (is_int($key)) {
                $array[] = $value;
            } else {
                $object[$key] = $value;
            }
        }

        sort($array);
        ksort($object);

        $data = json_encode(compact('timestamp', 'token', 'array', 'object'));
        return $this->hexToBase64($this->crypto->hash($data, $this->secret));
    }

    protected function generateToken() {
        return $this->hexToBase64($this->crypto->getRandomHexString(128));
    }

    protected function hexToBase64($hex) {
        return base64_encode(pack('H*', $hex));
    }

}

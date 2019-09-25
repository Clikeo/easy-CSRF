<?php

namespace Itrack\CSRF;


interface ICryptoProvider {

    public function getRandomHexString($length);
    public function hash($data, $secret);

}
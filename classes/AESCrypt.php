<?php
class AESCrypt {

    function __construct($key, $iv){
        $this->key = $key;
        $this->iv = $iv;
    }

    //암호화
    function encrypt($plain_text){
        
        $encryptedMessage = openssl_encrypt($plain_text, "aes-256-cbc", $this->key ,true ,$this->iv);
        
        return base64_encode($encryptedMessage);
    }

    //복호화
    function decrypt($base64_text){

        $decryptedMessage = openssl_decrypt(base64_decode($base64_text), "aes-256-cbc", $this->key, true, $this->iv);
        
        return $decryptedMessage;
    }

}

        
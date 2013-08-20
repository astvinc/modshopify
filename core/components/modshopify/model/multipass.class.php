<?php

class ShopifyMultipass{

 private $encryption_key;
 private $signature_key;

    
public function __construct($multipass_secret){
    ### Use the Multipass secret to derive two cryptographic keys, 
    ### one for encryption, one for signing

    $method = 'sha256';
    $key_material = openssl_digest ( $multipass_secret, $method , TRUE);
    if($key_material){
        $this->encryption_key = substr($key_material,0,16);
        $this->signature_key = substr($key_material,16,16);
    }
    
}

public function generate_token($customer_data_hash){


    ### Serialize the customer data to JSON and encrypt it
    
    $ciphertext = $this->encrypt(json_encode($customer_data_hash));

    if($ciphertext == null)
        return null;
    
    
    ### Create a signature (message authentication code) of the ciphertext
    ### and encode everything using URL-safe Base64 (RFC 4648)
    $signed = $this->sign($ciphertext);
    
    if($signed == null)
        return null;
    
    return urlencode(base64_encode($ciphertext.$signed));
    
}

private function encrypt($textToEncrypt){
    
    $padded_data = $this->pkcs5_pad($textToEncrypt);
    
    $method = 'AES-128-CBC';
    ### Use a random IV
    $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND);

    ### Use IV as first block of ciphertext
    $cipher = openssl_encrypt($padded_data, $method, $this->encryption_key, 1, $iv);
    return $iv.$cipher;    
}

private function sign($textToSign){
     return hash_hmac("sha256",$textToSign , $this->signature_key, TRUE); 
    
}

private function pkcs5_pad ($text, $blocksize){
    $pad = $blocksize - (strlen($text) % $blocksize);
    return $text . str_repeat(chr($pad), $pad);
}



}
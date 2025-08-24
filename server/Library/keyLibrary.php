<?php 

namespace Library;

class keyLibrary {
    private $encMethod = "AES-256-CBC"; //encryption method
    private $enciv = "632kMg8V3dmsd2Vz"; //initation vector for encryption (16 characters for AES-256-CBC)
    private $emailKey = "Z_<vpw/4[bTHS/neh1?CL[74&R=nPZA>?/#hXW`DyM5 ^T:rJ]?N7F-.`fI=+n77"; // key for encrypting email
    private $encKey = "t6w9z\$C&F)J@NcRfTjWnZr4u7x!A%D*G-KaPdSgVkXp2s5v8y/B?E(H+MbQeThWm"; // key for encrypting result data

    function __construct(){
        // no direct create
    }

    // encryption / decryption functions
     function emailEnc($email,$userkey=""){
        //encrypt email
        $encemail = openssl_encrypt($email,$this->encMethod,$this->emailKey.$userkey,0,$this->enciv);
        return $encemail;
    }

     function emailDec($encemail,$userkey=""){
        //decrypt email
        $email = openssl_decrypt($encemail,$this->encMethod,$this->emailKey.$userkey,0,$this->enciv); 
        return $email;
    }

     function resultEnc($data,$userkey=""){
        //encrypt result data
        $encdata = openssl_encrypt($data,$this->encMethod,$this->encKey.$userkey,0,$this->enciv);
        return $encdata;
    }

     function resultDec($encdata,$userkey=""){
        //decrypt result data
        $data = openssl_decrypt($encdata,$this->encMethod,$this->encKey.$userkey,0,$this->enciv);
        return $data;
    }
}
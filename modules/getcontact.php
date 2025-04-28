<?php

if (!function_exists("getcontact_signature")) {
  function getcontact_signature($timestamp, $message, $key)
  {
    return base64_encode(hash_hmac("sha256", "$timestamp-$message", hex2bin($key), true));
  }
}

if (!function_exists("getcontact_hex2str")) {
  function getcontact_hex2str($hex)
  {
    // reference: https://stackoverflow.com/a/57572155/14267929
    $string = "";
    for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
      $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
    }
    return $string;
  }
}

if (!function_exists("getcontact_encrypt")) {
  function getcontact_encrypt($data, $passphrase)
  {
    return base64_encode(openssl_encrypt($data, "aes-256-ecb", hex2bin($passphrase), OPENSSL_RAW_DATA));
  }
}

if (!function_exists("getcontact_decrypt")) {
  function getcontact_decrypt($data, $passphrase)
  {
    return openssl_decrypt(base64_decode($data), "aes-256-ecb", hex2bin($passphrase), OPENSSL_RAW_DATA);
  }
}

if (!function_exists("censor_phone_number")) {
  function censor_phone_number($phone_number)
  {
    if (strlen($phone_number) < 7) {
      return "xxxxx";
    }

    $censored_after_position = 6;
    $count_censored_digit = 5;

    return substr_replace($phone_number, "xxxxx", $censored_after_position, $count_censored_digit);
  }
}

<?php

if (!function_exists("getcontact_signature")) {
  function getcontact_signature($timestamp, $message, $key)
  {
    return base64_encode(hash_hmac("sha256", "$timestamp-$message", hex2bin($key), true));
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

if (!function_exists("getcontact_generate_client_private_key")) {
  function getcontact_generate_client_private_key()
  {
    try {

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'https://tools.naufalist.com/getcontact/api/credentials/private-key');
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

      $response = curl_exec($ch);
      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      if ($http_code === 200) {
        $json = json_decode($response, true);
        if (isset($json['data'])) {
          return (int)$json['data'];
        } else {
          throw new Exception("Invalid response data field");
        }
      } else {
        throw new Exception("Invalid http code: $http_code");
      }
    } catch (Exception $e) {
      throw new Exception("Failed to generate client private key: " . $e->getMessage());
    }
  }
}

if (!function_exists("getcontact_generate_client_public_key")) {
  function getcontact_generate_client_public_key($client_private_key)
  {
    try {

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'https://tools.naufalist.com/getcontact/api/credentials/public-key?privateKey=' . $client_private_key);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

      $response = curl_exec($ch);
      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      if ($http_code === 200) {
        $json = json_decode($response, true);
        if (isset($json['data'])) {
          return (int)$json['data'];
        } else {
          throw new Exception("Invalid response data field");
        }
      } else {
        throw new Exception("Invalid http code: $http_code");
      }
    } catch (Exception $e) {
      throw new Exception("Failed to generate client public key: " . $e->getMessage());
    }
  }
}

if (!function_exists("getcontact_generate_final_key")) {
  function getcontact_generate_final_key($client_private_key, $server_public_key)
  {
    try {

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, 'https://tools.naufalist.com/getcontact/api/credentials/final-key?privateKey=' . $client_private_key . '&publicKey=' . $server_public_key);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, 10);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

      $response = curl_exec($ch);
      $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      if ($http_code === 200) {
        $json = json_decode($response, true);
        if (isset($json['data'])) {
          return $json['data'];
        } else {
          throw new Exception("Invalid response data field");
        }
      } else {
        throw new Exception("Invalid http code: $http_code");
      }
    } catch (Exception $e) {
      throw new Exception("Failed to generate final key: " . $e->getMessage());
    }
  }
}

if (!function_exists("getcontact_call_api_subscription")) {
  function getcontact_call_api_subscription($request)
  {
    $request_body = (object)[
      "token" => $request->token
    ];

    $request_body_data_json = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $request_timestamp = date_create()->format("Uv");
    $request_client_device_id = isset($request->clientDeviceId) && $request->clientDeviceId ? $request->clientDeviceId : GTC_CLIENT_DEVICE_ID;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => GTC_API_BASE_URL . GTC_API_EP_SUBSCRIPTION,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{\"data\": \"" . getcontact_encrypt($request_body_data_json, $request->finalKey) . "\"}",
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-os: " . GTC_ANDROID_OS,
        "x-app-version: " . GTC_APP_VERSION,
        "x-client-device-id: " . $request_client_device_id,
        "x-lang: " . GTC_LANG,
        "x-token: " . $request->token,
        "x-req-timestamp: $request_timestamp",
        "x-country-code: id",
        "x-encrypted: 1",
        "x-req-signature: " . getcontact_signature($request_timestamp, $request_body_data_json, GTC_HMAC_SECRET_KEY),
      ],
      CURLOPT_HEADER => true
    ));

    return curl_execute_request($curl);
  }
}

if (!function_exists("getcontact_call_api_search")) {
  function getcontact_call_api_search($request)
  {
    $request_body = (object)[
      "countryCode" => GTC_COUNTRY_CODE,
      "phoneNumber" => $request->phoneNumber,
      "source" => "search",
      "token" => $request->token
    ];

    $request_body_data_json = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $request_timestamp = date_create()->format("Uv");
    $request_client_device_id = isset($request->clientDeviceId) && $request->clientDeviceId ? $request->clientDeviceId : GTC_CLIENT_DEVICE_ID;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => GTC_API_BASE_URL . GTC_API_EP_SEARCH,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{\"data\": \"" . getcontact_encrypt($request_body_data_json, $request->finalKey) . "\"}",
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-os: " . GTC_ANDROID_OS,
        "x-app-version: " . GTC_APP_VERSION,
        "x-client-device-id: " . $request_client_device_id,
        "x-lang: " . GTC_LANG,
        "x-token: " . $request->token,
        "x-req-timestamp: $request_timestamp",
        "x-country-code: id",
        "x-encrypted: 1",
        "x-req-signature: " . getcontact_signature($request_timestamp, $request_body_data_json, GTC_HMAC_SECRET_KEY),
      ],
      CURLOPT_HEADER => true
    ));

    return curl_execute_request($curl);
  }
}

if (!function_exists("getcontact_call_api_number_detail")) {
  function getcontact_call_api_number_detail($request)
  {
    $request_body = (object)[
      "countryCode" => GTC_COUNTRY_CODE,
      "phoneNumber" => $request->phoneNumber,
      "source" => "profile",
      "token" => $request->token
    ];

    $request_body_data_json = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $request_timestamp = date_create()->format("Uv");
    $request_client_device_id = isset($request->clientDeviceId) && $request->clientDeviceId ? $request->clientDeviceId : GTC_CLIENT_DEVICE_ID;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => GTC_API_BASE_URL . GTC_API_EP_NUMBER_DETAIL,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{\"data\": \"" . getcontact_encrypt($request_body_data_json, $request->finalKey) . "\"}",
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-os: " . GTC_ANDROID_OS,
        "x-app-version: " . GTC_APP_VERSION,
        "x-client-device-id: " . $request_client_device_id,
        "x-lang: " . GTC_LANG,
        "x-token: " . $request->token,
        "x-req-timestamp: $request_timestamp",
        "x-country-code: id",
        "x-encrypted: 1",
        "x-req-signature: " . getcontact_signature($request_timestamp, $request_body_data_json, GTC_HMAC_SECRET_KEY),
      ],
      CURLOPT_HEADER => true
    ));

    return curl_execute_request($curl);
  }
}

if (!function_exists("getcontact_call_api_verify_code")) {
  function getcontact_call_api_verify_code($request)
  {
    $request_body = (object)[
      "validationCode" => $request->validationCode,
      "token" => $request->token,
    ];

    $request_body_data_json = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $request_timestamp = date_create()->format("Uv");
    $request_client_device_id = isset($request->clientDeviceId) && $request->clientDeviceId ? $request->clientDeviceId : GTC_CLIENT_DEVICE_ID;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => GTC_API_BASE_URL . GTC_API_EP_VERIFY_CODE,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{\"data\": \"" . getcontact_encrypt($request_body_data_json, $request->finalKey) . "\"}",
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-os: " . GTC_ANDROID_OS,
        "x-app-version: " . GTC_APP_VERSION,
        "x-client-device-id: " . $request_client_device_id,
        "x-lang: " . GTC_LANG,
        "x-token: " . $request->token,
        "x-req-timestamp: $request_timestamp",
        "x-country-code: id",
        "x-encrypted: 1",
        "x-req-signature: " . getcontact_signature($request_timestamp, $request_body_data_json, GTC_HMAC_SECRET_KEY),
      ],
      CURLOPT_HEADER => true
    ));

    return curl_execute_request($curl);
  }
}

if (!function_exists("getcontact_call_api_refresh_code")) {
  function getcontact_call_api_refresh_code($request)
  {
    $request_body = (object)[
      "token" => $request->token,
    ];

    $request_body_data_json = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $request_timestamp = date_create()->format("Uv");
    $request_client_device_id = isset($request->clientDeviceId) && $request->clientDeviceId ? $request->clientDeviceId : GTC_CLIENT_DEVICE_ID;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => GTC_API_BASE_URL . GTC_API_EP_REFRESH_CODE,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{\"data\": \"" . getcontact_encrypt($request_body_data_json, $request->finalKey) . "\"}",
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-os: " . GTC_ANDROID_OS,
        "x-app-version: " . GTC_APP_VERSION,
        "x-client-device-id: " . $request_client_device_id,
        "x-lang: " . GTC_LANG,
        "x-token: " . $request->token,
        "x-req-timestamp: $request_timestamp",
        "x-country-code: id",
        "x-encrypted: 1",
        "x-req-signature: " . getcontact_signature($request_timestamp, $request_body_data_json, GTC_HMAC_SECRET_KEY),
      ],
      CURLOPT_HEADER => true
    ));

    return curl_execute_request($curl);
  }
}

if (!function_exists("getcontact_call_api_register")) {
  function getcontact_call_api_register($request)
  {
    $request_body = (object)[
      "carrierCountryCode" => GTC_CARRIER_COUNTRY_CODE,
      "carrierName" => GTC_CARRIER_NAME,
      "carrierNetworkCode" => GTC_CARRIER_NETWORK_CODE,
      "countryCode" => GTC_COUNTRY_CODE,
      "deepLink" => null,
      "deviceName" => GTC_DEVICE_NAME,
      "deviceType" => GTC_DEVICE_TYPE,
      "email" => null,
      "notificationToken" => "",
      "oldToken" => null,
      "peerKey" => $request->clientPublicKey,
      "timeZone" => GTC_TIME_ZONE,
      "token" => ""
    ];

    $request_body_data_json = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $request_timestamp = date_create()->format('Uv');
    $request_client_device_id = isset($request->clientDeviceId) && $request->clientDeviceId ? $request->clientDeviceId : GTC_CLIENT_DEVICE_ID;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => GTC_API_BASE_URL . GTC_API_EP_REGISTER,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => $request_body_data_json,
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-os: " . GTC_ANDROID_OS,
        "x-app-version: " . GTC_APP_VERSION,
        "x-client-device-id: " . $request_client_device_id,
        "x-lang: " . GTC_LANG,
        "x-req-timestamp: $request_timestamp",
        "x-country-code: id",
        "x-encrypted: 0",
        "x-req-signature: " . getcontact_signature($request_timestamp, $request_body_data_json, GTC_HMAC_SECRET_KEY),
      ],
      CURLOPT_HEADER => true
    ));

    return curl_execute_request($curl);
  }
}

if (!function_exists("getcontact_call_api_init_basic")) {
  function getcontact_call_api_init_basic($request)
  {
    $request_body = (object)[
      "carrierCountryCode" => GTC_CARRIER_COUNTRY_CODE,
      "carrierName" => GTC_CARRIER_NAME,
      "carrierNetworkCode" => GTC_CARRIER_NETWORK_CODE,
      "countryCode" => GTC_COUNTRY_CODE,
      "deviceName" => GTC_DEVICE_NAME,
      "notificationToken" => "",
      "timeZone" => GTC_TIME_ZONE,
      "token" => $request->token
    ];

    $request_body_data_json = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $request_timestamp = date_create()->format('Uv');
    $request_client_device_id = isset($request->clientDeviceId) && $request->clientDeviceId ? $request->clientDeviceId : GTC_CLIENT_DEVICE_ID;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => GTC_API_BASE_URL . GTC_API_EP_INIT_BASIC,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{\"data\": \"" . getcontact_encrypt($request_body_data_json, $request->finalKey) . "\"}",
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-os: " . GTC_ANDROID_OS,
        "x-app-version: " . GTC_APP_VERSION,
        "x-client-device-id: " . $request_client_device_id,
        "x-lang: " . GTC_LANG,
        "x-token: " . $request->token,
        "x-req-timestamp: $request_timestamp",
        "x-country-code: id",
        "x-encrypted: 1",
        "x-req-signature: " . getcontact_signature($request_timestamp, $request_body_data_json, GTC_HMAC_SECRET_KEY),
      ],
      CURLOPT_HEADER => true
    ));

    return curl_execute_request($curl);
  }
}

if (!function_exists("getcontact_call_api_ad_settings")) {
  function getcontact_call_api_ad_settings($request)
  {
    $request_body = (object)[
      "source" => "init",
      "token" => $request->token
    ];

    $request_body_data_json = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $request_timestamp = date_create()->format('Uv');
    $request_client_device_id = isset($request->clientDeviceId) && $request->clientDeviceId ? $request->clientDeviceId : GTC_CLIENT_DEVICE_ID;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => GTC_API_BASE_URL . GTC_API_EP_AD_SETTINGS,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{\"data\": \"" . getcontact_encrypt($request_body_data_json, $request->finalKey) . "\"}",
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-os: " . GTC_ANDROID_OS,
        "x-app-version: " . GTC_APP_VERSION,
        "x-client-device-id: " . $request_client_device_id,
        "x-lang: " . GTC_LANG,
        "x-token: " . $request->token,
        "x-req-timestamp: $request_timestamp",
        "x-country-code: id",
        "x-encrypted: 1",
        "x-req-signature: " . getcontact_signature($request_timestamp, $request_body_data_json, GTC_HMAC_SECRET_KEY),
      ],
      CURLOPT_HEADER => true
    ));

    return curl_execute_request($curl);
  }
}

if (!function_exists("getcontact_call_api_init_intro")) {
  function getcontact_call_api_init_intro($request)
  {
    $request_body = (object)[
      "carrierCountryCode" => GTC_CARRIER_COUNTRY_CODE,
      "carrierName" => GTC_CARRIER_NAME,
      "carrierNetworkCode" => GTC_CARRIER_NETWORK_CODE,
      "countryCode" => GTC_COUNTRY_CODE,
      "deviceName" => GTC_DEVICE_NAME,
      "hasRouting" => false,
      "notificationToken" => "",
      "timeZone" => GTC_TIME_ZONE,
      "token" => $request->token
    ];

    $request_body_data_json = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $request_timestamp = date_create()->format('Uv');
    $request_client_device_id = isset($request->clientDeviceId) && $request->clientDeviceId ? $request->clientDeviceId : GTC_CLIENT_DEVICE_ID;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => GTC_API_BASE_URL . GTC_API_EP_INIT_INTRO,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{\"data\": \"" . getcontact_encrypt($request_body_data_json, $request->finalKey) . "\"}",
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-os: " . GTC_ANDROID_OS,
        "x-app-version: " . GTC_APP_VERSION,
        "x-client-device-id: " . $request_client_device_id,
        "x-lang: " . GTC_LANG,
        "x-token: " . $request->token,
        "x-req-timestamp: $request_timestamp",
        "x-country-code: id",
        "x-encrypted: 1",
        "x-req-signature: " . getcontact_signature($request_timestamp, $request_body_data_json, GTC_HMAC_SECRET_KEY),
      ],
      CURLOPT_HEADER => true
    ));

    return curl_execute_request($curl);
  }
}

if (!function_exists("getcontact_call_api_email_code_validate_start")) {
  function getcontact_call_api_email_code_validate_start($request)
  {
    $request_body = (object)[
      "email" => $request->email,
      "fullName" => $request->fullname,
      "token" => $request->token
    ];

    $request_body_data_json = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $request_timestamp = date_create()->format('Uv');
    $request_client_device_id = isset($request->clientDeviceId) && $request->clientDeviceId ? $request->clientDeviceId : GTC_CLIENT_DEVICE_ID;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => GTC_API_BASE_URL . GTC_API_EP_EMAIL_CODE_VALIDATE_START,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{\"data\": \"" . getcontact_encrypt($request_body_data_json, $request->finalKey) . "\"}",
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-os: " . GTC_ANDROID_OS,
        "x-app-version: " . GTC_APP_VERSION,
        "x-client-device-id: " . $request_client_device_id,
        "x-lang: " . GTC_LANG,
        "x-token: " . $request->token,
        "x-req-timestamp: $request_timestamp",
        "x-country-code: id",
        "x-encrypted: 1",
        "x-req-signature: " . getcontact_signature($request_timestamp, $request_body_data_json, GTC_HMAC_SECRET_KEY),
      ],
      CURLOPT_HEADER => true
    ));

    return curl_execute_request($curl);
  }
}

if (!function_exists("getcontact_call_api_country")) {
  function getcontact_call_api_country($request)
  {
    $request_body = (object)[
      "countryCode" => strtoupper(GTC_COUNTRY_CODE),
      "token" => $request->token
    ];

    $request_body_data_json = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $request_timestamp = date_create()->format('Uv');
    $request_client_device_id = isset($request->clientDeviceId) && $request->clientDeviceId ? $request->clientDeviceId : GTC_CLIENT_DEVICE_ID;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => GTC_API_BASE_URL . GTC_API_EP_COUNTRY,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{\"data\": \"" . getcontact_encrypt($request_body_data_json, $request->finalKey) . "\"}",
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-os: " . GTC_ANDROID_OS,
        "x-app-version: " . GTC_APP_VERSION,
        "x-client-device-id: " . $request_client_device_id,
        "x-lang: " . GTC_LANG,
        "x-token: " . $request->token,
        "x-req-timestamp: $request_timestamp",
        "x-country-code: id",
        "x-encrypted: 1",
        "x-req-signature: " . getcontact_signature($request_timestamp, $request_body_data_json, GTC_HMAC_SECRET_KEY),
      ],
      CURLOPT_HEADER => true
    ));

    return curl_execute_request($curl);
  }
}

if (!function_exists("getcontact_call_api_validation_start")) {
  function getcontact_call_api_validation_start($request)
  {
    $request_body = (object)[
      "app" => "verifykit",
      "countryCode" => GTC_COUNTRY_CODE,
      "notificationToken" => "",
      "token" => $request->token
    ];

    $request_body_data_json = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $request_timestamp = date_create()->format('Uv');
    $request_client_device_id = isset($request->clientDeviceId) && $request->clientDeviceId ? $request->clientDeviceId : GTC_CLIENT_DEVICE_ID;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => GTC_API_BASE_URL . GTC_API_EP_VALIDATION_START,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{\"data\": \"" . getcontact_encrypt($request_body_data_json, $request->finalKey) . "\"}",
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-os: " . GTC_ANDROID_OS,
        "x-app-version: " . GTC_APP_VERSION,
        "x-client-device-id: " . $request_client_device_id,
        "x-lang: " . GTC_LANG,
        "x-token: " . $request->token,
        "x-req-timestamp: $request_timestamp",
        "x-country-code: id",
        "x-encrypted: 1",
        "x-req-signature: " . getcontact_signature($request_timestamp, $request_body_data_json, GTC_HMAC_SECRET_KEY),
      ],
      CURLOPT_HEADER => true
    ));

    return curl_execute_request($curl);
  }
}

if (!function_exists("getcontact_call_api_verifykit_result")) {
  function getcontact_call_api_verifykit_result($request)
  {
    $request_body = (object)[
      "sessionId" => $request->sessionId,
      "token" => $request->token
    ];

    $request_body_data_json = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $request_timestamp = date_create()->format('Uv');
    $request_client_device_id = isset($request->clientDeviceId) && $request->clientDeviceId ? $request->clientDeviceId : GTC_CLIENT_DEVICE_ID;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => GTC_API_BASE_URL . GTC_API_EP_VERIFYKIT_RESULT,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{\"data\": \"" . getcontact_encrypt($request_body_data_json, $request->finalKey) . "\"}",
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-os: " . GTC_ANDROID_OS,
        "x-app-version: " . GTC_APP_VERSION,
        "x-client-device-id: " . $request_client_device_id,
        "x-lang: " . GTC_LANG,
        "x-token: " . $request->token,
        "x-req-timestamp: $request_timestamp",
        "x-country-code: id",
        "x-encrypted: 1",
        "x-req-signature: " . getcontact_signature($request_timestamp, $request_body_data_json, GTC_HMAC_SECRET_KEY),
      ],
      CURLOPT_HEADER => true
    ));

    return curl_execute_request($curl);
  }
}

if (!function_exists("verifykit_call_api_init")) {
  function verifykit_call_api_init($request)
  {
    $request_body = (object)[
      "isCallPermissionGranted" => false,
      "countryCode" => GTC_COUNTRY_CODE,
      "deviceName" => 'marlin', // GTC_DEVICE_NAME, // marlin?
      "installedApps" => "{\"whatsapp\":1,\"telegram\":0,\"viber\":0}",
      "outsideCountryCode" => GTC_OUTSIDE_COUNTRY_CODE,
      "outsidePhoneNumber" => $request->outsidePhoneNumber,
      "timezone" => GTC_TIME_ZONE,
      "bundleId" => GTC_BUNDLE_ID,
    ];

    $request_body_data_json = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $request_timestamp = date_create()->format('Uv');
    $request_client_device_id = isset($request->clientDeviceId) && $request->clientDeviceId ? $request->clientDeviceId : VFK_CLIENT_DEVICE_ID;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => VFK_API_BASE_URL . VFK_API_EP_INIT,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{\"data\": \"" . getcontact_encrypt($request_body_data_json, $request->finalKey) . "\"}",
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "X-VFK-Client-Device-Id: " . $request_client_device_id,
        "X-VFK-Client-Key: " . VFK_CLIENT_KEY,
        "X-VFK-Sdk-Version: " . VFK_SDK_VERSION,
        "X-VFK-Os: " . VFK_OS,
        "X-VFK-App-Version: " . VFK_APP_VERSION,
        "X-VFK-Encrypted: 1",
        "X-VFK-Lang: " . VFK_LANG,
        "X-VFK-Req-Timestamp: $request_timestamp",
        "X-VFK-Req-Signature: " . getcontact_signature($request_timestamp, $request_body_data_json, VFK_HMAC_SECRET_KEY),
      ],
      CURLOPT_HEADER => true
    ));

    return curl_execute_request($curl);
  }
}

if (!function_exists("verifykit_call_api_country")) {
  function verifykit_call_api_country($request)
  {
    $request_body = (object)[
      "countryCode" => GTC_COUNTRY_CODE,
      "bundleId" => GTC_BUNDLE_ID,
    ];

    $request_body_data_json = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $request_timestamp = date_create()->format('Uv');
    $request_client_device_id = isset($request->clientDeviceId) && $request->clientDeviceId ? $request->clientDeviceId : VFK_CLIENT_DEVICE_ID;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => VFK_API_BASE_URL . VFK_API_EP_COUNTRY,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{\"data\": \"" . getcontact_encrypt($request_body_data_json, $request->finalKey) . "\"}",
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "X-VFK-Client-Device-Id: " . $request_client_device_id,
        "X-VFK-Client-Key: " . VFK_CLIENT_KEY,
        "X-VFK-Sdk-Version: " . VFK_SDK_VERSION,
        "X-VFK-Os: " . VFK_OS,
        "X-VFK-App-Version: " . VFK_APP_VERSION,
        "X-VFK-Encrypted: 1",
        "X-VFK-Lang: " . VFK_LANG,
        "X-VFK-Req-Timestamp: $request_timestamp",
        "X-VFK-Req-Signature: " . getcontact_signature($request_timestamp, $request_body_data_json, VFK_HMAC_SECRET_KEY),
      ],
      CURLOPT_HEADER => true
    ));

    return curl_execute_request($curl);
  }
}

if (!function_exists("verifykit_call_api_start")) {
  function verifykit_call_api_start($request)
  {
    $request_body = (object)[
      "countryCode" => GTC_COUNTRY_CODE,
      "phoneNumber" => $request->phoneNumber,
      "app" => "whatsapp",
      "bundleId" => GTC_BUNDLE_ID,
    ];

    $request_body_data_json = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $request_timestamp = date_create()->format('Uv');
    $request_client_device_id = isset($request->clientDeviceId) && $request->clientDeviceId ? $request->clientDeviceId : VFK_CLIENT_DEVICE_ID;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => VFK_API_BASE_URL . VFK_API_EP_START,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{\"data\": \"" . getcontact_encrypt($request_body_data_json, $request->finalKey) . "\"}",
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "X-VFK-Client-Device-Id: " . $request_client_device_id,
        "X-VFK-Client-Key: " . VFK_CLIENT_KEY,
        "X-VFK-Sdk-Version: " . VFK_SDK_VERSION,
        "X-VFK-Os: " . VFK_OS,
        "X-VFK-App-Version: " . VFK_APP_VERSION,
        "X-VFK-Encrypted: 1",
        "X-VFK-Lang: " . VFK_LANG,
        "X-VFK-Req-Timestamp: $request_timestamp",
        "X-VFK-Req-Signature: " . getcontact_signature($request_timestamp, $request_body_data_json, VFK_HMAC_SECRET_KEY),
      ],
      CURLOPT_HEADER => true
    ));

    return curl_execute_request($curl);
  }
}

if (!function_exists("verifykit_call_api_check")) {
  function verifykit_call_api_check($request)
  {
    $request_body = (object)[
      "reference" => $request->reference,
      "bundleId" => GTC_BUNDLE_ID,
    ];

    $request_body_data_json = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $request_timestamp = date_create()->format('Uv');
    $request_client_device_id = isset($request->clientDeviceId) && $request->clientDeviceId ? $request->clientDeviceId : VFK_CLIENT_DEVICE_ID;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => VFK_API_BASE_URL . VFK_API_EP_CHECK,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{\"data\": \"" . getcontact_encrypt($request_body_data_json, $request->finalKey) . "\"}",
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "X-VFK-Client-Device-Id: " . $request_client_device_id,
        "X-VFK-Client-Key: " . VFK_CLIENT_KEY,
        "X-VFK-Sdk-Version: " . VFK_SDK_VERSION,
        "X-VFK-Os: " . VFK_OS,
        "X-VFK-App-Version: " . VFK_APP_VERSION,
        "X-VFK-Encrypted: 1",
        "X-VFK-Lang: " . VFK_LANG,
        "X-VFK-Req-Timestamp: $request_timestamp",
        "X-VFK-Req-Signature: " . getcontact_signature($request_timestamp, $request_body_data_json, VFK_HMAC_SECRET_KEY),
      ],
      CURLOPT_HEADER => true
    ));

    return curl_execute_request($curl);
  }
}

if (!function_exists("getcontact_call_api_profile_settings")) {
  function getcontact_call_api_profile_settings($request)
  {
    $request_body = (object)[
      "blockCountrySpam" => null,
      "communicationSettings" => null,
      "howDoILook" => null,
      "landing" => null,
      "notificationSettings" => null,
      "privateMode" => $request->privateMode,
      "serviceNumber" => null,
      "showCommunication" => null,
      "showPrivatePopup" => null,
      "telegramUsed" => null,
      "whatsappUsed" => null,
      "whoIsHere" => null,
      "token" => $request->token,
    ];

    $request_body_data_json = json_encode($request_body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $request_timestamp = date_create()->format("Uv");
    $request_client_device_id = isset($request->clientDeviceId) && $request->clientDeviceId ? $request->clientDeviceId : GTC_CLIENT_DEVICE_ID;

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => GTC_API_BASE_URL . GTC_API_EP_PROFILE_SETTINGS,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 20,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{\"data\": \"" . getcontact_encrypt($request_body_data_json, $request->finalKey) . "\"}",
      CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "x-os: " . GTC_ANDROID_OS,
        "x-app-version: " . GTC_APP_VERSION,
        "x-client-device-id: " . $request_client_device_id,
        "x-lang: " . GTC_LANG,
        "x-token: " . $request->token,
        "x-req-timestamp: $request_timestamp",
        "x-country-code: id",
        "x-encrypted: 1",
        "x-req-signature: " . getcontact_signature($request_timestamp, $request_body_data_json, GTC_HMAC_SECRET_KEY),
      ],
      CURLOPT_HEADER => true
    ));

    return curl_execute_request($curl);
  }
}

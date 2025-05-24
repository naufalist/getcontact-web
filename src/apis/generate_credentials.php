<?php
ini_set("display_errors", 0);
ini_set("display_startup_errors", 0);
ini_set("log_errors", 1);
ini_set("error_log", "errors.log");
error_reporting(E_ALL);
date_default_timezone_set("Asia/Jakarta");

header("Content-Type: application/json");

try {

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["message" => "Method not allowed"]);
    exit();
  }

  $phase = $_GET['phase'] ?? null;

  if (!is_numeric($phase) || $phase <= 0) {
    http_response_code(400);
    echo json_encode([
      "message" => "Invalid phase number"
    ]);
    exit();
  }

  switch ($phase) {
    case 1:

      #region Parse request body

      $raw_json = file_get_contents("php://input");

      if (strlen($raw_json) > MAX_JSON_SIZE) {
        http_response_code(413);
        echo json_encode([
          "message" => "Payload too large"
        ]);
        exit();
      }

      $parsed_json = json_decode($raw_json, true);

      if (json_last_error() !== JSON_ERROR_NONE) {
        error_log(json_last_error_msg());
        error_log($raw_json);
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      #endregion

      #region Parse and validate all request input

      $phone_number = trim(htmlspecialchars($parsed_json['phoneNumber'])) ?? null;

      if (!$phone_number) {
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid phone number"
        ]);
        exit();
      }

      if (strpos($phone_number, "0") === 0) {
        $phone_number = "+62" . substr($phone_number, 1);
      } else if (
        strpos($phone_number, "62") === 0
      ) {
        $phone_number = "+" . $phone_number;
      } else if (strpos($phone_number, "-") !== false || strpos($phone_number, " ") !== false) {
        $phone_number = str_replace(["-", " "], "", $phone_number);
      } else {
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid phone number"
        ]);
        exit();
      }

      #endregion

      #region Generate client device id, client private key, client public key

      // generate client device id
      $client_device_id = strtolower(bin2hex(random_bytes(8)));

      // generate client private key
      $client_private_key = getcontact_generate_client_private_key();

      // generate client public key
      $client_public_key = getcontact_generate_client_public_key($client_private_key);

      #endregion

      #region Call v2.8/register

      $api_request = (object)[
        "clientPublicKey" => $client_public_key,
        "clientDeviceId" => $client_device_id,
      ];

      $api_response = getcontact_call_api_register($api_request);

      #endregion

      #region Validate http status code (201)

      if ($api_response->httpCode !== 201) {
        log_api_transaction($api_request, $api_response, "register: call");
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid HTTP status code: " . $api_response->httpCode
        ]);
        exit();
      }

      #endregion

      #region Parse and decode API response

      $api_response_body = json_decode($api_response->body, false);

      if (json_last_error() !== JSON_ERROR_NONE) {
        log_api_transaction($api_request, $api_response, "register: decode body");
        error_log(json_last_error_msg());
        error_log($api_response->body);
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      #endregion

      #region Parse and validate token, server public key

      // parsing token
      $token = get_nested_value($api_response_body, 'result.token');
      if (empty($token)) {
        http_response_code(500);
        echo json_encode([
          "message" => "Invalid token"
        ]);
        exit();
      }

      // parsing server public key
      $server_public_key = get_nested_value($api_response_body, 'result.serverKey');
      // permit 0 => !isset($server_public_key) || $server_public_key === null || $server_public_key === ''
      if (empty($server_public_key)) {
        http_response_code(500);
        echo json_encode([
          "message" => "Invalid server key"
        ]);
        exit();
      }

      #endregion

      #region Generate final key

      // generate final key
      $final_key = getcontact_generate_final_key($client_private_key, $server_public_key);

      if (empty($final_key)) {
        http_response_code(500);
        echo json_encode([
          "message" => "Invalid final key"
        ]);
        exit();
      }

      #endregion

      #region Log client device id, fk and token for debugging

      error_log($client_device_id);
      error_log($final_key);
      error_log($token);

      #endregion

      #region Call v2.8/init-basic

      $api_request = (object)[
        "clientDeviceId" => $client_device_id,
        "finalKey" => $final_key,
        "token" => $token
      ];

      $api_response = getcontact_call_api_init_basic($api_request);

      #endregion

      #region Validate http status code (201)

      if ($api_response->httpCode !== 201) {
        log_api_transaction($api_request, $api_response, "init basic: call");
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid HTTP status code: " . $api_response->httpCode
        ]);
        exit();
      }

      #endregion

      #region Decode API response

      $api_response_body = json_decode($api_response->body, false);

      if (json_last_error() !== JSON_ERROR_NONE) {
        log_api_transaction($api_request, $api_response, "init basic: decode body");
        error_log(json_last_error_msg());
        error_log($api_response->body);
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      // no need to parse response body, nothing important

      #endregion

      #region Call v2.8/ad-settings

      $api_request = (object)[
        "clientDeviceId" => $client_device_id,
        "finalKey" => $final_key,
        "token" => $token
      ];

      $api_response = getcontact_call_api_ad_settings($api_request);

      #endregion

      #region Validate http status code (200)

      if ($api_response->httpCode !== 200) {
        log_api_transaction($api_request, $api_response, "ad settings: call");
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid HTTP status code: " . $api_response->httpCode
        ]);
        exit();
      }

      #endregion

      #region Decode API response

      $api_response_body = json_decode($api_response->body, false);

      if (json_last_error() !== JSON_ERROR_NONE) {
        log_api_transaction($api_request, $api_response, "ad settings: decode body");
        error_log(json_last_error_msg());
        error_log($api_response->body);
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      // no need to parse response body, nothing important

      #endregion

      #region Call v2.8/init-intro

      $api_request = (object)[
        "clientDeviceId" => $client_device_id,
        "finalKey" => $final_key,
        "token" => $token
      ];

      $api_response = getcontact_call_api_init_intro($api_request);

      #endregion

      #region Validate http status code (201)

      if ($api_response->httpCode !== 201) {
        log_api_transaction($api_request, $api_response, "init intro: call");
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid HTTP status code: " . $api_response->httpCode
        ]);
        exit();
      }

      #endregion

      #region Decode API response

      $api_response_body = json_decode($api_response->body, false);

      if (json_last_error() !== JSON_ERROR_NONE) {
        log_api_transaction($api_request, $api_response, "init intro: decode body");
        error_log(json_last_error_msg());
        error_log($api_response->body);
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      // no need to parse response body, nothing important

      #endregion

      #region Generate (random) fullname and email

      // generate random fullname and email
      $fullname = "User" . random_int(1000, 999999);
      $email = "user" . random_int(10000000, 99999999) . "@gmail.com";

      #endregion

      #region Call v2.8/email-code-validate/start

      $api_request = (object)[
        "email" => $email,
        "fullname" => $fullname,
        "clientDeviceId" => $client_device_id,
        "finalKey" => $final_key,
        "token" => $token
      ];

      $api_response = getcontact_call_api_email_code_validate_start($api_request);

      #endregion

      #region Validate http status code (200)

      if ($api_response->httpCode !== 200) {
        log_api_transaction($api_request, $api_response, "email code validate/start: call");
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid HTTP status code: " . $api_response->httpCode
        ]);
        exit();
      }

      #endregion

      #region Decode API response

      $api_response_body = json_decode($api_response->body, false);

      if (json_last_error() !== JSON_ERROR_NONE) {
        log_api_transaction($api_request, $api_response, "email code validate/start: decode body");
        error_log(json_last_error_msg());
        error_log($api_response->body);
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      // no need to parse response body, nothing important

      #endregion

      #region Call v2.8/country

      $api_request = (object)[
        "clientDeviceId" => $client_device_id,
        "finalKey" => $final_key,
        "token" => $token
      ];

      $api_response = getcontact_call_api_country($api_request);

      #endregion

      #region Validate http status code (200)

      if ($api_response->httpCode !== 200) {
        log_api_transaction($api_request, $api_response, "country: call");
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid HTTP status code: " . $api_response->httpCode
        ]);
        exit();
      }

      #endregion

      #region Decode API response (decoded response data field already in decrypted)

      $api_response_body = json_decode($api_response->body, false);

      if (json_last_error() !== JSON_ERROR_NONE) {
        log_api_transaction($api_request, $api_response, "country: decode body");
        error_log(json_last_error_msg());
        error_log($api_response->body);
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      // no need to parse response body, nothing important

      #endregion

      #region Call v2.8/validation-start

      $api_request = (object)[
        "clientDeviceId" => $client_device_id,
        "finalKey" => $final_key,
        "token" => $token
      ];

      $api_response = getcontact_call_api_validation_start($api_request);

      #endregion

      #region Validate http status code (200)

      if ($api_response->httpCode !== 200) {
        log_api_transaction($api_request, $api_response, "validation start: call");
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid HTTP status code: " . $api_response->httpCode
        ]);
        exit();
      }

      #endregion

      #region Decode API response

      $api_response_body = json_decode($api_response->body, false);

      if (json_last_error() !== JSON_ERROR_NONE) {
        log_api_transaction($api_request, $api_response, "validation start: decode body");
        error_log(json_last_error_msg());
        error_log($api_response->body);
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      // no need to parse response body, nothing important

      #endregion

      #region Generate outside phone number

      $outside_phone_number = $phone_number;
      if (strpos($phone_number, '+62') === 0) {
        $outside_phone_number = substr($phone_number, 3); // cut first 3 char (+62)
      }

      #endregion

      #region Call v2.0/init (VFK)

      $api_request = (object)[
        "outsidePhoneNumber" => $outside_phone_number,
        "clientDeviceId" => $client_device_id,
        "finalKey" => VFK_FINAL_KEY,
      ];

      $api_response = verifykit_call_api_init($api_request);

      #endregion

      #region Validate http status code (200)

      if ($api_response->httpCode !== 200) {
        log_api_transaction($api_request, $api_response, "vfk init: call");
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid HTTP status code: " . $api_response->httpCode
        ]);
        exit();
      }

      #endregion

      #region Decode API response

      $api_response_body = json_decode($api_response->body, false);

      if (json_last_error() !== JSON_ERROR_NONE) {
        log_api_transaction($api_request, $api_response, "vfk init: decode body");
        error_log(json_last_error_msg());
        error_log($api_response->body);
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      // no need to parse response body, nothing important

      #endregion

      #region Call v2.0/country (VFK)

      $api_request = (object)[
        "clientDeviceId" => $client_device_id,
        "finalKey" => VFK_FINAL_KEY,
      ];

      $api_response = verifykit_call_api_country($api_request);

      #endregion

      #region Validate http status code (200)

      if ($api_response->httpCode !== 200) {
        log_api_transaction($api_request, $api_response, "vfk country: call");
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid HTTP status code: " . $api_response->httpCode
        ]);
        exit();
      }

      #endregion

      #region Decode API response (decoded response data field already in decrypted)

      $api_response_body = json_decode($api_response->body, false);

      if (json_last_error() !== JSON_ERROR_NONE) {
        log_api_transaction($api_request, $api_response, "vfk country: decode body");
        error_log(json_last_error_msg());
        error_log($api_response->body);
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      // no need to parse response body, nothing important

      #endregion

      #region Return response

      http_response_code(200);
      echo json_encode([
        "message" => "Data processed successfully",
        "data" => encrypt_data([
          "phoneNumber" => $phone_number,
          "clientDeviceId" => $client_device_id,
          "token" => $token,
          "finalKey" => $final_key,
        ])
      ]);
      exit();

      #endregion

      break;

    case 2:

      #region Parse request body

      $raw_json = file_get_contents("php://input");

      if (strlen($raw_json) > MAX_JSON_SIZE) {
        http_response_code(413);
        echo json_encode([
          "message" => "Payload too large"
        ]);
        exit();
      }

      $parsed_json = json_decode($raw_json, true);

      if (json_last_error() !== JSON_ERROR_NONE) {
        error_log(json_last_error_msg());
        error_log($raw_json);
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      #endregion

      #region Parse and validate all request input

      $parsed_json = $parsed_json['data'] ?? null;

      $parsed_json = decrypt_data($parsed_json);

      if (empty($parsed_json)) {
        http_response_code(400);
        echo json_encode([
          "message" => "The form data is invalid. Please fill out and submit the form again from the start. (1)"
        ]);
        exit();
      }

      $phone_number = trim(htmlspecialchars($parsed_json['phoneNumber'])) ?? null;

      if (!$phone_number) {
        http_response_code(400);
        echo json_encode([
          // "message" => "Invalid phone number"
          "message" => "The form data is invalid. Please fill out and submit the form again from the start. (2)"
        ]);
        exit();
      }

      $client_device_id = trim(htmlspecialchars($parsed_json['clientDeviceId'])) ?? null;

      if (!$client_device_id) {
        http_response_code(400);
        echo json_encode([
          // "message" => "Invalid client device id"
          "message" => "The form data is invalid. Please fill out and submit the form again from the start. (3)"
        ]);
        exit();
      }

      $final_key = trim(htmlspecialchars($parsed_json['finalKey'])) ?? null;

      if (!$final_key) {
        http_response_code(400);
        echo json_encode([
          // "message" => "Invalid final key"
          "message" => "The form data is invalid. Please fill out and submit the form again from the start. (4)"
        ]);
        exit();
      }

      $token = trim(htmlspecialchars($parsed_json['token'])) ?? null;

      if (!$token) {
        http_response_code(400);
        echo json_encode([
          // "message" => "Invalid token"
          "message" => "The form data is invalid. Please fill out and submit the form again from the start. (5)"
        ]);
        exit();
      }

      #endregion

      #region Call v2.0/start (VFK)

      $api_request = (object)[
        "phoneNumber" => $phone_number,
        "clientDeviceId" => $client_device_id,
        "finalKey" => VFK_FINAL_KEY,
      ];

      $api_response = verifykit_call_api_start($api_request);

      #endregion

      #region Validate http status code (200)

      if ($api_response->httpCode !== 200) {
        log_api_transaction($api_request, $api_response, "vfk start: call");
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid HTTP status code: " . $api_response->httpCode
        ]);
        exit();
      }

      #endregion

      #region Decode API response

      $api_response_body = json_decode($api_response->body, false);

      if (json_last_error() !== JSON_ERROR_NONE) {
        log_api_transaction($api_request, $api_response, "vfk start: decode body");
        error_log(json_last_error_msg());
        error_log($api_response->body);
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      #endregion

      #region Decrypt API response

      $decrypted_data_raw = getcontact_decrypt($api_response_body->data, VFK_FINAL_KEY);
      $decrypted_body = json_decode($decrypted_data_raw, false);

      if (json_last_error() !== JSON_ERROR_NONE) {
        log_api_transaction($api_request, $api_response, "vfk start: decode body data");
        error_log(json_last_error_msg());
        error_log($decrypted_data_raw);
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      if ($decrypted_body == null) {
        log_api_transaction($api_request, $api_response, "vfk start: body null");
        http_response_code(500);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      #endregion

      #region Parse deeplink (this link contain verification code)

      $deeplink = get_nested_value($decrypted_body, 'result.deeplink');
      if (empty($deeplink)) {
        http_response_code(500);
        echo json_encode([
          "message" => "Invalid deeplink"
        ]);
        exit();
      }

      #endregion

      #region Parse and validate verification code from deeplink url

      // parse verification code
      preg_match_all('/\*(.*?)\*/', urldecode($deeplink), $matches);

      if (empty($matches[1])) {
        http_response_code(404);
        echo json_encode([
          "message" => "Verification code not found in deeplink"
        ]);
        exit();
      }

      $verification_code = null;

      foreach ($matches[1] as $candidate) {
        if (preg_match('/^[A-Za-z0-9]+(?:-[A-Za-z0-9]+)+$/', $candidate)) {
          // example format: eipvB-seNwn-CpnN0-B2nkU
          $verification_code = $candidate;
          break;
        }
      }

      if (!$verification_code) {
        http_response_code(404);
        echo json_encode([
          "message" => "Verification code not found in deeplink"
        ]);
        exit();
      }

      #endregion

      #region Parse and validate reference (id)

      // parse reference
      $reference = get_nested_value($decrypted_body, 'result.reference');
      if (empty($reference)) {
        http_response_code(500);
        echo json_encode([
          "message" => "Invalid reference"
        ]);
        exit();
      }

      #endregion

      #region Call v2.8/validation-start

      $api_request = (object)[
        "clientDeviceId" => $client_device_id,
        "finalKey" => $final_key,
        "token" => $token
      ];

      $api_response = getcontact_call_api_validation_start($api_request);

      #endregion

      #region Validate http status code (200)

      if ($api_response->httpCode !== 200) {
        log_api_transaction($api_request, $api_response, "validation start: call");
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid HTTP status code: " . $api_response->httpCode
        ]);
        exit();
      }

      #endregion

      #region Decode API response

      $api_response_body = json_decode($api_response->body, false);

      if (json_last_error() !== JSON_ERROR_NONE) {
        log_api_transaction($api_request, $api_response, "validation start: decode body");
        error_log(json_last_error_msg());
        error_log($api_response->body);
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      // no need to parse response body, nothing important

      #endregion

      #region Return response

      http_response_code(200);
      echo json_encode([
        "message" => "Data processed successfully",
        "data" => encrypt_data([
          "phoneNumber" => $phone_number,
          "clientDeviceId" => $client_device_id,
          "token" => $token,
          "finalKey" => $final_key,
          "deeplink" => $deeplink,
          "reference" => $reference,
        ]),
        "verificationCode" => $verification_code, // for public view
      ]);
      exit();

      #endregion

      break;

    case 3:

      #region Parse request body

      $raw_json = file_get_contents("php://input");

      if (strlen($raw_json) > MAX_JSON_SIZE) {
        http_response_code(413);
        echo json_encode([
          "message" => "Payload too large"
        ]);
        exit();
      }

      $parsed_json = json_decode($raw_json, true);

      if (json_last_error() !== JSON_ERROR_NONE) {
        error_log(json_last_error_msg());
        error_log($raw_json);
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      #endregion

      #region Parse and validate all request input

      $parsed_json = $parsed_json['data'] ?? null;

      $parsed_json = decrypt_data($parsed_json);

      if (empty($parsed_json)) {
        http_response_code(400);
        echo json_encode([
          "message" => "The form data is invalid. Please fill out and submit the form again from the start. (1)"
        ]);
        exit();
      }

      $phone_number = trim(htmlspecialchars($parsed_json['phoneNumber'])) ?? null;

      if (!$phone_number) {
        http_response_code(400);
        echo json_encode([
          // "message" => "Invalid phone number"
          "message" => "The form data is invalid. Please fill out and submit the form again from the start. (1)"
        ]);
        exit();
      }

      $client_device_id = trim(htmlspecialchars($parsed_json['clientDeviceId'])) ?? null;

      if (!$client_device_id) {
        http_response_code(400);
        echo json_encode([
          // "message" => "Invalid client device id"
          "message" => "The form data is invalid. Please fill out and submit the form again from the start. (2)"
        ]);
        exit();
      }

      $reference = trim(htmlspecialchars($parsed_json['reference'])) ?? null;

      if (!$reference) {
        http_response_code(400);
        echo json_encode([
          // "message" => "Invalid reference"
          "message" => "The form data is invalid. Please fill out and submit the form again from the start. (3)"
        ]);
        exit();
      }

      $final_key = trim(htmlspecialchars($parsed_json['finalKey'])) ?? null;

      if (!$final_key) {
        http_response_code(400);
        echo json_encode([
          // "message" => "Invalid final key"
          "message" => "The form data is invalid. Please fill out and submit the form again from the start. (4)"
        ]);
        exit();
      }

      $token = trim(htmlspecialchars($parsed_json['token'])) ?? null;

      if (!$token) {
        http_response_code(400);
        echo json_encode([
          // "message" => "Invalid token"
          "message" => "The form data is invalid. Please fill out and submit the form again from the start. (5)"
        ]);
        exit();
      }

      #endregion

      #region Call v2.0/check (VFK)

      $api_request = (object)[
        "reference" => $reference,
        "clientDeviceId" => $client_device_id,
        "finalKey" => VFK_FINAL_KEY,
      ];

      $api_response = verifykit_call_api_check($api_request);

      #endregion

      #region Validate http status code (200)

      if ($api_response->httpCode !== 200) {
        log_api_transaction($api_request, $api_response, "vfk check: call");
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid HTTP status code: " . $api_response->httpCode
        ]);
        exit();
      }

      #endregion

      #region Decode API response

      $api_response_body = json_decode($api_response->body, false);

      if (json_last_error() !== JSON_ERROR_NONE) {
        log_api_transaction($api_request, $api_response, "vfk check: decode body");
        error_log(json_last_error_msg());
        error_log($api_response->body);
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      #endregion

      #region Decrypt API response

      $decrypted_data_raw = getcontact_decrypt($api_response_body->data, VFK_FINAL_KEY);
      $decrypted_body = json_decode($decrypted_data_raw, false);

      if (json_last_error() !== JSON_ERROR_NONE) {
        log_api_transaction($api_request, $api_response, "vfk check: decode body data");
        error_log(json_last_error_msg());
        error_log($decrypted_data_raw);
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      if ($decrypted_body == null) {
        log_api_transaction($api_request, $api_response, "vfk check: body null");
        http_response_code(500);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      #endregion

      #region Parse and validate session (id)

      // parse sessionId (use to confirm to gtc api that i have been verified, ex: "mnu9Ymt9VLZeeb5")
      $session_id = get_nested_value($decrypted_body, 'result.sessionId');
      if (empty($session_id)) {
        http_response_code(500);
        echo json_encode([
          "message" => "Invalid session id"
        ]);
        exit();
      }

      #endregion

      #region Call v2.8/verifykit-result

      $api_request = (object)[
        "sessionId" => $session_id,
        "clientDeviceId" => $client_device_id,
        "finalKey" => $final_key,
        "token" => $token,
      ];

      $api_response = getcontact_call_api_verifykit_result($api_request);

      #endregion

      #region Validate http status code (200)

      if ($api_response->httpCode !== 200) {
        log_api_transaction($api_request, $api_response, "gtc verifykit result: call");
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid HTTP status code: " . $api_response->httpCode
        ]);
        exit();
      }

      #endregion

      #region Decode API response

      $api_response_body = json_decode($api_response->body, false);

      if (json_last_error() !== JSON_ERROR_NONE) {
        log_api_transaction($api_request, $api_response, "gtc verifykit result: decode body");
        error_log(json_last_error_msg());
        error_log($api_response->body);
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      #endregion

      #region Decrypt API response

      $decrypted_data_raw = getcontact_decrypt($api_response_body->data, $final_key);
      $decrypted_body = json_decode($decrypted_data_raw, false);

      if (json_last_error() !== JSON_ERROR_NONE) {
        log_api_transaction($api_request, $api_response, "gtc verifykit result: decode body data");
        error_log(json_last_error_msg());
        error_log($decrypted_data_raw);
        http_response_code(400);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      if ($decrypted_body == null) {
        log_api_transaction($api_request, $api_response, "gtc verifykit result: body null");
        http_response_code(500);
        echo json_encode([
          "message" => "Invalid JSON response. Please check error log."
        ]);
        exit();
      }

      #endregion

      #region Parse and validate validation date

      // parse validationDate (ex: "2025-04-27 11:44:45" ---> server time (UTC))
      $validation_date = get_nested_value($decrypted_body, 'result.validationDate');
      if (empty($validation_date)) {
        http_response_code(500);
        echo json_encode([
          "message" => "Invalid validation date"
        ]);
        exit();
      }

      #endregion

      #region Return response

      error_log(json_encode([
        "phoneNumber" => $phone_number,
        "clientDeviceId" => $client_device_id,
        "token" => $token,
        "finalKey" => $final_key,
        "validationDate" => $validation_date,
      ], JSON_PRETTY_PRINT));

      http_response_code(200);
      echo json_encode([
        "message" => "Data processed successfully",
        "data" => [
          "clientDeviceId" => $client_device_id,
          "token" => $token,
          "finalKey" => $final_key,
          "validationDate" => $validation_date,
        ]
      ]);
      exit();

      #endregion

      break;

    default:
      http_response_code(400);
      echo json_encode([
        "message" => "Invalid phase number",
        "data" => $result
      ]);
      exit();
      break;
  }
} catch (\Exception $e) {
  error_log("Error GetContact API Generate Credentials: " . $ex->getMessage());
  http_response_code(500);
  echo json_encode([
    "message" => "Internal server error"
  ]);
  exit();
}

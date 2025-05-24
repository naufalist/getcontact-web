<?php

if (!function_exists("d")) {
  function d($variable)
  {
    echo "<pre>";
    print_r($variable);
    echo "</pre>";
  }
}

if (!function_exists("dd")) {
  function dd($variable)
  {
    var_dump($variable);
    die();
  }
}

if (!function_exists("base_url")) {
  function base_url($suffix = "")
  {
    $protocol = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http";
    $domain = $_SERVER["HTTP_HOST"];
    $base_url = $protocol . "://" . $domain . URL_PREFIX . "/"  . ltrim($suffix, "/");
    return $base_url;
  }
}

if (!function_exists("page_file_exists")) {
  function page_file_exists(string $filePath)
  {
    if (!file_exists($filePath)) {
      header("HTTP/1.0 404 Not Found");
      exit();
    }
  }
}

if (!function_exists("is_logged_in")) {
  function is_logged_in()
  {
    $max_inactive = SESSION_MAX_INACTIVE ?? 3600;

    if (!isset($_SESSION["admin_id"])) {
      return false;
    }

    if (!isset($_SESSION["last_activity"])) {
      return false;
    }

    if (time() - $_SESSION["last_activity"] > $max_inactive) {
      session_unset();
      session_destroy();
      return false;
    }

    // auto slide expiry time
    $_SESSION["last_activity"] = time();

    return true;
  }
}

if (!function_exists("require_login")) {
  function require_login()
  {
    if (!is_logged_in()) {
      header("Location: " . URL_PREFIX . "/dashboard");
      exit();
    }
  }
}

if (!function_exists("csrf_token_generate")) {
  function csrf_token_generate()
  {
    if (empty($_SESSION["csrf_token"])) {
      $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
      $_SESSION["csrf_token_expire"] = time() + CSRF_EXPIRY_DURATION;
    }

    return $_SESSION["csrf_token"];
  }
}

if (!function_exists("csrf_token_validate")) {
  function csrf_token_validate(string $csrfToken)
  {
    if (!isset($_SESSION["csrf_token"])) {
      return false;
    }

    if (!isset($_SESSION["csrf_token_expire"])) {
      return false;
    }

    if (!is_string($csrfToken)) {
      return false;
    }

    $is_csrf_token_valid = hash_equals($_SESSION["csrf_token"], $csrfToken);

    if (!$is_csrf_token_valid) {
      return false;
    }

    // auto renew
    if ($_SESSION["csrf_token_expire"] < time()) {
      unset($_SESSION["csrf_token"]);
      unset($_SESSION["csrf_token_expire"]);
      csrf_token_generate();
    }

    return true;
  }
}

if (!function_exists("curl_execute_request")) {
  function curl_execute_request($curl)
  {
    try {
      $response = curl_exec($curl);
      $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
      $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

      $response_header = null;
      $response_body = null;

      if ($response !== false) {
        $response_header = substr($response, 0, $header_size);
        $response_body = substr($response, $header_size);
      }

      if ($response === false || curl_errno($curl)) {
        $error_message = curl_error($curl);
        error_log("cURL Error: $error_message");
        error_log("Status Code: $http_code");
        error_log("Header: " . ($response_header ?? "[null]"));
        error_log("Body: " . ($response_body ?? "[null]"));
        throw new Exception("cURL failed: $error_message");
      }

      return (object)[
        "httpCode" => $http_code,
        "header" => $response_header,
        "body" => $response_body
      ];
    } finally {
      curl_close($curl);
    }
  }
}

if (!function_exists("log_api_transaction")) {
  function log_api_transaction($request, $response, $label = "API")
  {
    error_log("==== [REQUEST ($label)] ====");
    error_log(json_encode($request, JSON_PRETTY_PRINT));

    error_log("==== [RESPONSE ($label)] ====");
    error_log(json_encode($response, JSON_PRETTY_PRINT));

    error_log("==========================");
  }
}

if (!function_exists("has_nested_property")) {
  function has_nested_property($data, $path)
  {
    $keys = explode(".", $path);

    foreach ($keys as $key) {
      if (is_object($data)) {
        if (!isset($data->$key)) {
          return false;
        }
        $data = $data->$key;
      } elseif (is_array($data)) {
        if (!array_key_exists($key, $data)) {
          return false;
        }
        $data = $data[$key];
      } else {
        return false;
      }
    }

    return true;
  }
}

if (!function_exists("get_nested_value")) {

  /**
   * Example of usage:
   * 
   * $token = get_nested_value($data, "result.token");
   * echo "Token: " . ($token ?? "-") . PHP_EOL;
   * 
   * $providers = get_nested_value($data, "result.preEmailValidate.providerList");
   * $first_provider = is_array($providers) && isset($providers[0]) ? $providers[0] : null;
   * echo "First Provider: " . ($first_provider ?? "-") . PHP_EOL;
   */
  function get_nested_value($data, $path, $default = null)
  {
    $keys = explode(".", $path);

    foreach ($keys as $key) {
      if (is_object($data)) {
        if (!isset($data->$key)) {
          return $default;
        }
        $data = $data->$key;
      } elseif (is_array($data)) {
        if (!array_key_exists($key, $data)) {
          return $default;
        }
        $data = $data[$key];
      } else {
        return $default;
      }
    }

    return $data;
  }
}

if (!function_exists("encrypt_data")) {
  function encrypt_data($input)
  {
    if (!is_string($input) && !is_int($input) && !is_array($input)) {
      return false;
    }

    $data = json_encode($input);
    if ($data === false) {
      return false;
    }

    $key = hash("sha256", FORM_SECRET_KEY, true);

    $iv = openssl_random_pseudo_bytes(16); // random IV, 16 byte, for AES-256-CBC

    $encrypted = openssl_encrypt($data, "AES-256-CBC", $key, OPENSSL_RAW_DATA, $iv);
    if ($encrypted === false) {
      return false;
    }

    $hmac = hash_hmac("sha256", $encrypted, $key, true); // hmac for integrity

    return base64_encode($iv . $hmac . $encrypted);
  }
}

if (!function_exists("decrypt_data")) {
  function decrypt_data($encryptedData)
  {
    $key = hash("sha256", FORM_SECRET_KEY, true);

    $decoded = base64_decode($encryptedData, true);
    if ($decoded === false || strlen($decoded) < 48) {
      return false; // 16 (IV) + 32 (HMAC)
    }

    // separate
    $iv = substr($decoded, 0, 16);
    $hmac = substr($decoded, 16, 32);
    $ciphertext = substr($decoded, 48);

    $calculated_hmac = hash_hmac("sha256", $ciphertext, $key, true);
    if (!hash_equals($hmac, $calculated_hmac)) {
      return false;
    }

    $decrypted = openssl_decrypt($ciphertext, "AES-256-CBC", $key, OPENSSL_RAW_DATA, $iv);
    if ($decrypted === false) return false;

    return json_decode($decrypted, true); // true: decode to array/primitive
  }
}

if (!function_exists("censor_phone_number")) {
  function censor_phone_number($phoneNumber)
  {
    if (strlen($phoneNumber) < 7) {
      return "xxxxx";
    }

    $censored_after_position = 6;
    $count_censored_digit = 5;

    return substr_replace($phoneNumber, "xxxxx", $censored_after_position, $count_censored_digit);
  }
}

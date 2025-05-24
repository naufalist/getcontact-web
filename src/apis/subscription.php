<?php
ini_set("display_errors", 0);
ini_set("display_startup_errors", 0);
ini_set("log_errors", 1);
ini_set("error_log", "errors.log");
error_reporting(E_ALL);
date_default_timezone_set("Asia/Jakarta");

header("Content-Type: application/json");

try {

  #region Validate request method

  if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
      "message" => "Method not allowed"
    ]);
    exit();
  }

  #endregion

  #region Test

  // test
  // $result = [
  //   "info" => [
  //     "search" => [
  //       "limit" => 999,
  //       "remainingCount" => 999,
  //     ],
  //     "numberDetail" => [
  //       "limit" => 999,
  //       "remainingCount" => 999,
  //     ],
  //     "receiptEndDate" => ""
  //   ]
  // ];

  // http_response_code(200);
  // echo json_encode([
  //   "message" => "Data received successfully",
  //   "data" => $result
  // ]);
  // exit();
  // test

  #endregion

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

  $id = $parsed_json["id"] ?? null;
  $decrypted_id = decrypt_data($id);

  if (!isset($decrypted_id) || !is_int($decrypted_id) || $decrypted_id <= 0) {
    error_log("Decryption failed for ID: $id");
    http_response_code(400);
    echo json_encode([
      "message" => "Invalid or malformed credential ID"
    ]);
    exit();
  }

  $id = $decrypted_id;

  #endregion

  #region Fetch and validate credentials from database/config

  if (USE_DATABASE) {

    $pdo_statement = $pdo->prepare("
      SELECT final_key AS finalKey, token AS token, client_device_id AS clientDeviceId
      FROM credentials
      WHERE deleted_at IS NULL AND id = ?
      ORDER BY id ASC
    ");

    $pdo_statement->execute([$id]);

    $credential = $pdo_statement->fetch(PDO::FETCH_ASSOC);
  } else {
    $credentials = json_decode(GTC_CREDENTIALS, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      error_log(json_last_error());
      http_response_code(400);
      echo json_encode([
        "message" => "Invalid credentials"
      ]);
      exit();
    }

    $credential = array_filter($credentials, function ($credential) use ($id) {
      return $credential["id"] === $id;
    });

    $credential = reset($credential);
  }

  if (!$credential || !isset($credential["finalKey"], $credential["token"])) {
    http_response_code(400);
    echo json_encode([
      "message" => "Credential not found or incomplete"
    ]);
    exit();
  }

  $final_key = $credential["finalKey"] ?? null;
  $token = $credential["token"] ?? null;

  if (empty($final_key) || empty($token)) {
    http_response_code(400);
    echo json_encode([
      "message" => "Invalid final key or token"
    ]);
    exit();
  }

  #endregion

  #region Call GetContact API Subscription

  $api_request = (object)[
    "clientDeviceId" => $credential["clientDeviceId"],
    "finalKey" => $final_key,
    "token" => $token
  ];

  $api_response = getcontact_call_api_subscription($api_request);

  #endregion

  #region Validate http status code

  if ($api_response->httpCode !== 200) {
    log_api_transaction($api_request, $api_response, "subscription: call");
    http_response_code(400);
    echo json_encode([
      "message" => "Invalid HTTP status code: " . $api_response->httpCode
    ]);
    exit();
  }

  #endregion

  #region Parse, decrypt, and decode API response

  $api_response_body = json_decode($api_response->body, false);

  if (json_last_error() !== JSON_ERROR_NONE) {
    log_api_transaction($api_request, $api_response, "subscription: decode body");
    error_log(json_last_error_msg());
    error_log($api_response->body);
    http_response_code(400);
    echo json_encode([
      "message" => "Invalid JSON response. Please check error log."
    ]);
    exit();
  }

  $decrypted_data_raw = getcontact_decrypt($api_response_body->data, $final_key);
  $decrypted_body = json_decode($decrypted_data_raw, false);

  if (json_last_error() !== JSON_ERROR_NONE) {
    log_api_transaction($api_request, $api_response, "subscription: decode body data");
    error_log(json_last_error_msg());
    error_log($decrypted_data_raw);
    http_response_code(400);
    echo json_encode([
      "message" => "Invalid JSON response. Please check error log."
    ]);
    exit();
  }

  if ($decrypted_body == null) {
    log_api_transaction($api_request, $api_response, "subscription: body null");
    http_response_code(500);
    echo json_encode([
      "message" => "Could not get subscription info (invalid response body)"
    ]);
    exit();
  }

  #endregion

  #region Validate and return result

  $search_limit_path = "result.subscriptionInfo.usage.search.limit";
  $search_remaining_count_path = "result.subscriptionInfo.usage.search.remainingCount";
  $number_detail_limit_path = "result.subscriptionInfo.usage.numberDetail.limit";
  $number_detail_remaining_count_path = "result.subscriptionInfo.usage.numberDetail.remainingCount";
  // $receipt_end_date_path = "result.subscriptionInfo.receiptEndDate";
  $renew_date_path = "result.subscriptionInfo.renewDate";

  $required_keys = [
    $search_limit_path,
    $search_remaining_count_path,
    $number_detail_limit_path,
    $number_detail_remaining_count_path,
    // $receipt_end_date_path,
    $renew_date_path,
  ];

  foreach ($required_keys as $path) {
    if (!has_nested_property($decrypted_body, $path)) {
      log_api_transaction($api_request, $api_response, "subscription: missing $path");
      http_response_code(500);
      echo json_encode([
        "message" => "Unexpected API response structure"
      ]);
      exit();
    }
  }

  $result = [
    "info" => [
      "search" => [
        "limit" => 0,
        "remainingCount" => 0,
      ],
      "numberDetail" => [
        "limit" => 0,
        "remainingCount" => 0,
      ],
      "receiptEndDate" => ""
    ]
  ];

  $result["info"]["search"]["limit"] = get_nested_value($decrypted_body, $search_limit_path);
  $result["info"]["search"]["remainingCount"] = get_nested_value($decrypted_body, $search_remaining_count_path);
  $result["info"]["numberDetail"]["limit"] = get_nested_value($decrypted_body, $number_detail_limit_path);
  $result["info"]["numberDetail"]["remainingCount"] = get_nested_value($decrypted_body, $number_detail_remaining_count_path);
  // $result["info"]["receiptEndDate"] = get_nested_value($decrypted_body, $receipt_end_date_path);
  $result["info"]["receiptEndDate"] = get_nested_value($decrypted_body, $renew_date_path);

  http_response_code(200);
  echo json_encode([
    "message" => "Data received successfully",
    "data" => $result
  ]);
  exit();

  #endregion

} catch (\Exception $e) {
  error_log("Error GetContact API Subscription: " . $e->getMessage());
  http_response_code(500);
  echo json_encode([
    "message" => "Internal server error"
  ]);
  exit();
}

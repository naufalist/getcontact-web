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

  if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode([
      "message" => "Method not allowed"
    ]);
    exit();
  }

  #endregion

  #region Fetch credentials

  if (USE_DATABASE) {
    $pdo_statement = $pdo->query(
      "
      SELECT 
        id AS id,
        description AS description
        -- CONCAT(SUBSTRING(final_key, 1, CEIL(CHAR_LENGTH(final_key) / 2)), REPEAT('*', FLOOR(CHAR_LENGTH(final_key) / 2))) AS finalKey,
        -- CONCAT(SUBSTRING(token, 1, CEIL(CHAR_LENGTH(token) / 2)), REPEAT('*', FLOOR(CHAR_LENGTH(token) / 2))) AS token
      FROM credentials
      WHERE deleted_at IS NULL
      ORDER BY id ASC"
    );

    $credentials = $pdo_statement->fetchAll(PDO::FETCH_ASSOC);

    if (empty($credentials)) {
      http_response_code(404);
      echo json_encode([
        "message" => "Credential(s) not found"
      ]);
      exit();
    }

    foreach ($credentials as &$credential) {
      $credential["id"] = encrypt_data($credential["id"]);
    }

    unset($credential);
  } else {

    if (!defined("GTC_CREDENTIALS")) {
      http_response_code(404);
      echo json_encode([
        "message" => "Credential(s) not found"
      ]);
      exit();
    }

    $credentials = json_decode(GTC_CREDENTIALS, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      error_log(json_last_error_msg());
      error_log(GTC_CREDENTIALS);
      http_response_code(400);
      echo json_encode([
        "message" => "Invalid JSON response. Please check error log."
      ]);
      exit();
    }

    if (empty($credentials)) {
      http_response_code(404);
      echo json_encode([
        "message" => "Credential(s) not found"
      ]);
      exit();
    }

    foreach ($credentials as &$credential) {
      $credential["id"] = encrypt_data($credential["id"]);
    }

    unset($credential);
  }

  #endregion

  #region Return credentials

  http_response_code(200);
  echo json_encode([
    "message" => "Credential(s) retrieved successfully",
    "data" => $credentials
  ]);
  exit();

  #endregion

} catch (\Exception $e) {
  error_log("Error Get Credentials: " . $e->getMessage());
  http_response_code(500);
  echo json_encode([
    "message" => "Internal server error"
  ]);
  exit();
}

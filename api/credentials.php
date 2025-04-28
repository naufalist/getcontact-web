<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'errors.log');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Jakarta');

header("Content-Type: application/json");

require_once __DIR__ . "/../modules/getcontact.php";

try {

  if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
      "message" => "Method not allowed"
    ]);
    exit();
  }

  require_once "../config.php";

  if (!defined('GTC_CREDENTIALS')) {
    http_response_code(404);
    echo json_encode([
      "message" => "Credential(s) not found"
    ]);
    exit;
  }

  $credentials = json_decode(GTC_CREDENTIALS, true);

  if (empty($credentials)) {
    http_response_code(404);
    echo json_encode([
      "message" => "Credential(s) not found"
    ]);
    exit;
  }

  http_response_code(200);
  echo json_encode([
    "message" => "Credential(s) retrieved successfully",
    "data" => $credentials
  ]);
  exit;
} catch (\Exception $e) {
  error_log("Error Get Credentials: " . $e->getMessage());
  http_response_code(500);
  echo json_encode([
    "message" => "Internal server error"
  ]);
  exit;
}

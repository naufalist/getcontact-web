<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'errors.log');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Jakarta');

header("Content-Type: application/json");

require_once '../getcontact.php';

try {

  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
      "message" => "Method not allowed"
    ]);
    exit();
  }

  $request_body = json_decode(file_get_contents("php://input"), true);

  if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
      "message" => "Invalid JSON: " . json_last_error_msg()
    ]);
    exit;
  }

  require_once "../config.php";

  $final_key = $request_body['finalKey'] ?? null;
  $token = $request_body['token'] ?? null;

  if (!$final_key || !$token) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid final key or token"]);
    exit;
  }

  $credentials = json_decode(GTC_CREDENTIALS, true);

  foreach ($credentials as $credential) {
    if ($credential['finalKey'] === $final_key && $credential['token'] === $token) {

      #region Checking subscription info

      $timestamp = date_create()->format('Uv');

      $key = GTC_HMAC_SECRET_KEY;

      $data = json_encode([
        "token" => $token
      ]);

      $curl = curl_init();

      curl_setopt_array($curl, array(
        CURLOPT_URL => GTC_API_BASE_URL . GTC_API_EP_SUBSCRIPTION,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "{\"data\": \"" . getcontact_encrypt($data, $final_key) . "\"}",
        CURLOPT_HTTPHEADER => array(
          "X-Os: android 9",
          "X-Mobile-Service: GMS",
          "X-App-Version: 5.6.2",
          "X-Client-Device-Id: 063579f5e0654a4e",
          "X-Lang: en_US",
          "X-Token: $token",
          "X-Req-Timestamp: $timestamp",
          "X-Encrypted: 1",
          "X-Network-Country: us",
          "X-Country-Code: us",
          "X-Req-Signature: " . getcontact_signature($timestamp, $data, $key),
          "Content-Type: application/json"
        ),
      ));

      #endregion

      #region Execute

      $curl_response = curl_exec($curl);

      if (curl_errno($curl)) {
        throw new Exception("cURL Error: " . curl_error($curl));
      }

      $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

      if ($http_status !== 200) {
        http_response_code($http_status);
        echo json_encode([
          "message" => "Could not get subscription info (HTTP $http_status)"
        ]);
        exit;
      }

      curl_close($curl);

      $decoded_response = json_decode($curl_response, false);

      $response = isset($decoded_response->data) ? json_decode(getcontact_decrypt($decoded_response->data, $final_key), true) : null;

      #endregion

      #region Handle response

      if ($response == null) {
        http_response_code(500);
        echo json_encode([
          "message" => "Could not get subscription info (invalid response)"
        ]);
        exit;
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

      $result["info"]["search"]["limit"] = $response["result"]["subscriptionInfo"]["usage"]["search"]["limit"] ?? "-";
      $result["info"]["search"]["remainingCount"] = $response["result"]["subscriptionInfo"]["usage"]["search"]["remainingCount"] ?? "-";
      $result["info"]["numberDetail"]["limit"] = $response["result"]["subscriptionInfo"]["usage"]["numberDetail"]["limit"] ?? "-";
      $result["info"]["numberDetail"]["remainingCount"] = $response["result"]["subscriptionInfo"]["usage"]["numberDetail"]["remainingCount"] ?? "-";
      $result["info"]["receiptEndDate"] = $response["result"]["subscriptionInfo"]["receiptEndDate"] ?? "-";

      #endregion

      http_response_code(200);
      echo json_encode([
        "message" => "Data received successfully",
        "data" => $result
      ]);
      exit;
    }
  }

  http_response_code(400);
  echo json_encode([
    "message" => "Invalid final key or token"
  ]);
  exit;
} catch (\Exception $e) {
  error_log("Error GetContact API Subscription: " . $ex->getMessage());
  http_response_code(500);
  echo json_encode([
    "message" => "Internal server error"
  ]);
  exit;
}

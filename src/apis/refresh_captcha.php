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

  // // test
  // sleep(2);
  // $result = [
  //   "captcha" => [
  //     "image" => base64_encode("\/9j\/4AAQSkZJRgABAQEAYABgAAD\/\/gA7Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBxdWFsaXR5ID0gOTAK\/9sAQwADAgIDAgIDAwMDBAMDBAUIBQUEBAUKBwcGCAwKDAwLCgsLDQ4SEA0OEQ4LCxAWEBETFBUVFQwPFxgWFBgSFBUU\/9sAQwEDBAQFBAUJBQUJFA0LDRQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQU\/8AAEQgAKAB4AwERAAIRAQMRAf\/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC\/\/EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29\/j5+v\/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC\/\/EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29\/j5+v\/aAAwDAQACEQMRAD8A\/T2u0+aCgAoAKACgAoAKACgAoAKAOT+JHxX8I\/CHRE1fxhrtroVhJIIUkuCSXcgnaqqCzHAJ4HauSvi6OGt7WVm+mrf3K7+ZjUrQoq83\/XyJPhh8RtI+LfgPSPF2gmc6TqkbSQfaY\/LkAV2QhlycHKnvVYbERxNJVYppO+\/k7efYKVRVoKcf66HUV0mwUAFABQAUAFAHgX7Ufxb8YeENS8EeCfh8bODxd4uvWt4r++j8yOzhQAvJswQTz3BwAePT5rNcbWpVYYag7Sl6dXZJN6LXdnl4zETpyjSp\/FIwtX8Z\/HL9n3SpPEHjMaV8UvCFuQ+pT6Jbmz1HT4ON8wixtmRRkkDBxySACRg6uY5elOv78PVfnZP77rzW5DnisMuepaUevdfl\/W9lqfRXh7X9P8VaFp+s6VdJe6ZqECXNtcR\/dkjdQysPqCK+mo1oYimqsNmepGSnFSjsybVNVstD0641DUryDT7C2Qyz3V1KscUSDkszMQAB6mqqVYUY81SSS8xykormk7I+cPGH\/BRX4J+EPEsejjXbvXMOyT3+j2hntICD3kyPMB7GIOPevKnmtGLtGLkvJW\/No454yEJWSb9Lfq1+Bztp\/wAFMvh3q6wf2T4Q8darLKrFYrXS4nOU5lTiY5ZExIcZAUjJFcs86px0UPvaXz6\/Lv5GTx9JNJp\/h\/mQxftHfG\/4+3K2fwq+Gc\/g3w1cHypPF3i6LDpBLwlxbwlkVnTDsVUzrnYDjPOc8ficVG2Gh7rurr13vsvO1322BVq1b+FH3e9\/x6fhc7P4bfsO+CfD2r3Xibx27\/FPxpfqRfap4liSeB2OAClu25VwqqoyWIAwCBxXRhcrUPfxDvLte6\/JN\/Pfe3bWlhIwfNJ8z8\/6\/Nnvs\/iLSbO4trabU7KCe5laCCKS4RWlkX7yICcsw7gcivU+tYaD5PaRXS11v2Op1IXSclrp8ybSNYsPEGm2+o6XfW2pafcrvhu7SVZYpV9VdSQR7g10QnGpHmg7ruioyjNc0XdFurKCgAoAKACgD5r\/AGvNH1DwvrXw6+LNhYXWq2vgrUzJq9tZrvmGnygLNKi5G4oBnGe+TgAkfH5thnTrU8Xq0rX12s7rXpfVX72PJx0FGdPE2+F6+n9fmj0e\/wDjN8O\/Fnwr1TxBb+K9LvvDk9hL5kpuVXKNGcoyNhlYjjawB7YrrxOLwtTC1OSrrZq19dVtZ\/5fM6amJozoyfMrWf8AVtz8\/wC3+PHxAX9nr4ffB\/wZo2v6d4l16YHTNcgmNsk1oZ3KxxScEEHCscgBQcnmvlKNSo6XsJN25m7d3tZLte\/W1zwaVap7BUJb3fXV67Lyv12Jf+EQ8W698JPiRP8AFvxjqniO4+GEy6dD4Pjumitrl3YeXLdSIFkmiZiCG3B9qnDLwKHGCi5RVnBpba6t3t2s\/wDLsaScvZz5npC2m+7\/AK\/z2L+u\/CXS\/wBntvhRrl3428Pay9pexWmqaTbaZawyWUN7bsrGWVB5kkYVzgzk5BBHXFZVqShTjJTUnNXaT1Wz187eS8kc1SCoqMuZNy3Xbrd99PTyLf7POn\/C+x+CmjXem\/Gi6+GHxHllvpJ\/s2vGOylnindYDeQOTFsaLysA7d4BxuKtj0qSpRoqftHGrZ2Vn5O10rre29rnXRjCNGP7xxm1\/wAHt+v36o9E8WftDeOvj\/8ACX4TeGvBE+reHPHXjFpje6pZRNZwoLeGTzCksm0mMvsdmiLFQpUbnwh6Z5hXxKp4em3zpu\/S9nZap9rt9L+jNZ4mpXUKUH7zvfpe3n+On\/AO08DfFC71r9kzUPiX4u1jUNcvtG0ya3vfDV00cNrHf2xMQEhSNZmdyEZhJIy5kJCjChdLrE4B1a0nPkdrPRa2SbtZvRp773NYT9phXUm3K2lnpr0v17Pfr9258JvidY69rmi+GfHXwWg+H954v03\/AIl00y213BqqRxCWSGTau6MhcsI5RnAOcHg7YSrh3KnSq4eKU1o7b9r3V9fV7q+9y6M4NxhOklzLfv63X53PplVCjAAAznivr4xUVaKsevsLTGFABQAUAFAARkUmk1ZgeQeJ\/wBkT4OeLdb\/ALY1L4faS+oclntka3WQk5JdI2VXJPdgTXkVcrwsk5KLXXRtfJK9jjlg6Endx\/FpfcmfN3xbuPiF8TP2lvC+nfCTRf8AhGIPAGjv5N1runeXa2z3IMZ\/cgM+3YqqmIycgttEf7yvm4w+t4pQw0eTkSsmrONtbtNPdvz7nkVZc9WPsotci7arztZ73XTz6E1l+y18TdS+L10vjrVpvFWi+PNHmtvFOoafDHaWkE0KD7Kdi7XbaVixhYySDnK7gW8txEq8fbK\/M3dq+nXXRLezWny7wsPiJ1PeXxbv+rbWVk0tfmdUv7B\/ie\/8Gaf4Q1r416pf+GNK+fTNPtdGitfs8obdHIzrIWlKtyNx69MV6iyOH2p6eXn63\/BX8zrnl9SUI01Usl5P\/wCS\/ruH7PP7HOj2fwxl0j4kfD7RNY1+z1e7jGoXh2zXVuZNyTeYik4OeFz0HUdBhl+XQqRl7eDTT81sltZ2a8zOhhZqL5qave27Wy8k7+RB4x8L+PvEn7TB0r4S\/wDCP+E7TwJ4bi0zztSgMkFsbtvMxDGqkb9kadRjGc\/erBUnVx\/ssM+XkVumlr33ve7kyZRrSruFBKLgkv60e9+3TUyNT\/ZS+KUXhf4s+BF1\/wD4SDTfF1xZ6uNYmWCzhku5LkPe7oVVmU4QMApC4Kj1FYTwePhKpRhBPms21otNVu0t915ehFTCYpOcYu97Pok3e\/r9z7Ht3xO8A6z4g\/aA+Dt5ax3zeHtDGp3d1cxsDHBL9nWOEE43Zbe3ByCAfevUxVCax1CEI+6vw6+itZHbVw83XpqLfKr+dv6st9z2I2t9FCiQ3yyMPvSXUAdm\/wC+Cg\/Svp9btnbyVoq0Z39Vf8nFfgPbTUndJLhnlcKVKh2WJs9cx52n8c0uXSz1H7FS1qO7+dvuvb77liGGO3hSKJFiijUKiIMKoHAAA6CrN4xUUoxVkh9IoKACgAoAKACgAoAKACgAxSsk7gFMAoAKACgAoA\/\/2Q=="),
  //   ],
  // ];

  // http_response_code(200);
  // echo json_encode([
  //   "message" => "Your code has been refreshed successfully.",
  //   "data" => $result
  // ]);
  // exit();
  // // test

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
  $decrypted_id = (int)$decrypted_id;

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

  #region Call GetContact API Refresh Code

  $api_request = (object)[
    "clientDeviceId" => $credential["clientDeviceId"],
    "finalKey" => $final_key,
    "token" => $token
  ];

  $api_response = getcontact_call_api_refresh_code($api_request);

  #endregion

  #region Parse, decrypt, and decode API response

  $api_response_body = json_decode($api_response->body, false);

  if (json_last_error() !== JSON_ERROR_NONE) {
    log_api_transaction($api_request, $api_response, "refresh code: decode body");
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
    log_api_transaction($api_request, $api_response, "refresh code: decode body data");
    error_log(json_last_error_msg());
    error_log($decrypted_data_raw);
    http_response_code(400);
    echo json_encode([
      "message" => "Invalid JSON response. Please check error log."
    ]);
    exit();
  }

  if ($decrypted_body == null) {
    log_api_transaction($api_request, $api_response, "refresh code: body null");
    http_response_code(500);
    echo json_encode([
      "message" => "Could not refresh captcha (invalid response body)"
    ]);
    exit();
  }

  #endregion

  #region Validate http status code

  $meta_http_status_code_path = "meta.httpStatusCode";

  if (!has_nested_property($decrypted_body, $meta_http_status_code_path)) {
    log_api_transaction($api_request, $api_response, "$source_type: missing $meta_http_status_code_path");
    http_response_code(500);
    echo json_encode([
      "message" => "Unexpected API response structure"
    ]);
    exit();
  }

  $http_code = get_nested_value($decrypted_body, $meta_http_status_code_path);
  $api_response_http_code = $api_response->httpCode ?? null;

  /**
   * 200 -> success
   * 4xx -> failed
   */

  if (
    !in_array($http_code, [200]) ||
    !in_array($api_response_http_code, [200])
  ) {
    log_api_transaction(
      $api_request,
      $api_response,
      "$source_type: invalid http status"
    );

    $error_message_path = "meta.errorMessage";
    $error_message = "An error occurred. Please contact administrator.";

    if (has_nested_property($decrypted_body, $error_message_path)) {
      $error_message = get_nested_value($decrypted_body, $error_message_path);
      error_log(json_encode($decrypted_body, JSON_PRETTY_PRINT));
    } else {
      error_log("$source_type: missing $error_message_path");
    }

    // if (!has_nested_property($decrypted_body, $error_message_path)) {
    //   error_log("$source_type: missing $error_message_path");
    // }

    http_response_code(500);
    echo json_encode([
      "message" => "Invalid response ($http_code: $error_message)"
    ]);
    exit();
  }

  #endregion

  #region Validate and return result

  $image_path = "result.image";

  if (!has_nested_property($decrypted_body, $image_path)) {
    log_api_transaction($api_request, $api_response, "verify code: missing $image_path");
    http_response_code(500);
    echo json_encode([
      "message" => "Unexpected API response structure"
    ]);
    exit();
  }

  $result = [
    "captcha" => [
      "image" => "",
    ],
  ];

  $result["captcha"]["image"] = get_nested_value($decrypted_body, $image_path);

  http_response_code(200);
  echo json_encode([
    "message" => "Your code has been refreshed successfully.",
    "data" => $result
  ]);
  exit();

  #endregion

} catch (\Exception $e) {
  error_log("Error GetContact API Refresh Captcha: " . $e->getMessage());
  http_response_code(500);
  echo json_encode([
    "message" => "Internal server error"
  ]);
  exit();
}

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
  //   "captcha" => [
  //     "image" => base64_encode("\/9j\/4AAQSkZJRgABAQEAYABgAAD\/\/gA+Q1JFQVRPUjogZ2QtanBlZyB2MS4wICh1c2luZyBJSkcgSlBFRyB2ODApLCBkZWZhdWx0IHF1YWxpdHkK\/9sAQwAIBgYHBgUIBwcHCQkICgwUDQwLCwwZEhMPFB0aHx4dGhwcICQuJyAiLCMcHCg3KSwwMTQ0NB8nOT04MjwuMzQy\/9sAQwEJCQkMCwwYDQ0YMiEcITIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIy\/8AAEQgAKAB4AwEiAAIRAQMRAf\/EAB8AAAEFAQEBAQEBAAAAAAAAAAABAgMEBQYHCAkKC\/\/EALUQAAIBAwMCBAMFBQQEAAABfQECAwAEEQUSITFBBhNRYQcicRQygZGhCCNCscEVUtHwJDNicoIJChYXGBkaJSYnKCkqNDU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6g4SFhoeIiYqSk5SVlpeYmZqio6Slpqeoqaqys7S1tre4ubrCw8TFxsfIycrS09TV1tfY2drh4uPk5ebn6Onq8fLz9PX29\/j5+v\/EAB8BAAMBAQEBAQEBAQEAAAAAAAABAgMEBQYHCAkKC\/\/EALURAAIBAgQEAwQHBQQEAAECdwABAgMRBAUhMQYSQVEHYXETIjKBCBRCkaGxwQkjM1LwFWJy0QoWJDThJfEXGBkaJicoKSo1Njc4OTpDREVGR0hJSlNUVVZXWFlaY2RlZmdoaWpzdHV2d3h5eoKDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uLj5OXm5+jp6vLz9PX29\/j5+v\/aAAwDAQACEQMRAD8A9K1y4httHv7vTNN\/s51kunk1KGGOQqY8PI4ET\/MztEBiQqCUxJ\/Cj8\/4u1W7s77+zf7Pub0atDsmtIhI8pUGXzgrDZK8cbhWRSVV1mkAO0rsi1fU5dIvWttSjv5NMSCCK3ksSfIV4lYyxkxoUCo8LM0ipHOhB2LtA2+fXUOreJ9dtbbTri71BLqO8Nv5tlGgYszu+1m2K6FycSEKynkIpCrXbTp9XsYykd\/e+MooPHFvA1jc6vPaInl6lp2nQyzzI6CVIweeSm8l0KghpAIxw65+jeJF1bUf7Gl0h9RvIlkWC11Rnikuo42lOy4Z5Csjg7SuUYK4kAVBhxStrie08deK7jT9IcabHarFqm77PcTQxCL96cNIVeRmQk5dhncXV24DPiLd61d2EMet21kk9rdRlYHug12Y3hjBxsRA0ZfeGZQfnOFwE5pQV1FdhX6noHii1fxBrkFrbmzeVI5Vs5UVvMjIZRIyz4ZYXDJtyInKFAN8bSoKj0XSZ7jTNOv9HvkutPt4L23ghe4aFhHJLGBC0w80kR+W4EiEZ2R7TjJPPJd6lqPiltO1ufTJdNnjm1Kxlvybm2jjy28YZE80Dkq2V2oAVYoSr8Nq9hqC22qX39n6Vb6bZGTTUSKzgB8wBMglmLlwAW3q0hDK2DtJNTGm2uW43LqdHL440HSvE1ncaPeXd9FbFFkmMcdtmEqoYRhWSNuEjXa8O4heHG1Cs+leLfCN3pjRSXI0r+zEBgQxu0csRjKhY0VgysJmWVhuZtyK3mMEDI\/S9TsIPDOkNrGuQaHb2Qh\/s5IdOxcyqcNI8sTrJuVigZZEOwvhiNyhV57UYI\/EPxCnhjuPEFk93alL9tR0xZZmIUYXyIlGRtEZBxkHDcYBrRRi9H0\/rsTdnYXnj7wxruhXL6nJcXWnLA8arqFn5ji6KYTiOMIGIV2BEynLuMKApXJsPEsNxc380kly2j2kAeK6s7OSeCW4kiiSW2kEqFmjkky2GZNzHczFirLzXxD8MW\/hPV7S\/wBO1q+nuLrF2ouo3S5jzyJC+BliwYnIVlOMjnNa+r3EGkeAv7EsbPVksr0JNLNdxp9ilIt2fMEpKNlnWNwpIOVxszujYUIWXL1C7vqbVn471hrPSRbaN4mnnnngurvdC8iXUSqPPkhwQygyOuEU+XhV4AdlPTeEdciNpaWb6Ld6fYXJt5IZVmjjWNyDjcsbjakkkR2sqIkhkxs5Jfzq+1LXPCOgS6XeafYWVzCsSGfbP9t3MjeXIlxGAmFBkRRu+6jpyM52bKePWNKhg17xfdxu9\/HfE3E8EDNC6lo5rZpE\/dsj4VlRyF2SgZ3AGJ01bRaFJ6nc\/wBv3VpdWNxrGpWcdrqMjXdoyghLOBQoxK6v5bqyuo3FsCWRdokGNuPY2kVnbfuLq\/hvpVif7HaQAXFuvmI7OxKNtYW8duHgUCMYVAv7yOpz4nie0u9b03WrCS20qAXV3C6ASXMpXygkrxsI\/MJhZVYblO9SEIWNmfdQ3XhaCDT7b\/Q7K3jgWEJcGQTLFOu3CRqJBLK0gSXEbLiRTuJXY+SVtCh+s366V4M0N7LVbD7Lazxst7BcmG1eOEk+TkSu7FkUpgCbJU5UHBUq9fxW2q3q6Z4ss9KePd5MFxLbMqzM6kNHE7n5JCfKcFSfvMi7jEz0Uk6dvfTC0vsmXpsVxq93HZGF9OuAz\/2pNaWrxMty7W87x+YqoVDLHKm5XYFREzMzMmaep6VdXXjKx1JdWmS0SKWJpPOV5bOW4lZFicI5Zh5pePKkBQuAysm+m3vh2ebTv7Y1WzsLNrhjFeM9\/PavHENsfkz3DMfNVhHGpYKQT90HzPNTh5r66s9Lt7GM2dlZfYkvW8m8vY1gYskm2BZLgRyTIskU231OOSOdYRvs\/wCupLdty\/dWGqWfjrUxFot60d7NbhLKDU4beSQoUcByGlZpAVV3dWDgMXYqHrcn0Hxd4v1fSINfVNPsNOki8zTY7pmluNuzfKrFsuBvQF95K5OCz5DZGpeGvGU3iCH+y9V1iyN5H9+6vpsyIsjAOqrukSIebF8knzoZTnIV3GpbaNaPY3+n+JPEWsXF3BJGr2OsXUkcUxUeYw\/dSuCmySJmYFjEUDt8p2mm9E09RJGN46srSWB9fsNV\/ty8s1WK\/vkgEcUu5VRkLxhY2BDgAKxkUEglhgxUvC+oabf2i6XqmoTZlRrJW0wgvKJv3shlScfMdyhQYV3kpt5zFu1774ZeGrbVoZxfTrospkheeUtFHbvCVhkDylSA7OXdSQqkxlADvBW3feGbbTJYWutY1vQvDTzoLa2F20QDeb8rKrlgg+eOUl2EgMUp8sfwUpx5VG4rO9znPCvjzU9P8P6noOkaabuMLLcfa4gttNDAqjfI20Eb9oOGJJDEfe4UwaK9rPqj6h4rvdQh\/sK3gTT7nTFjuIY\/KI2I0iK6E5ZOvGSQSOBXQaj4XuNV006vfX8N\/aWPmR+RdS3dy8LImW84iUGPIyz8Iw8qICMM5iqFdF0jR5tQt530FdVjxKLLU7WOBkAQKIkczyRBvLcMC6uGZgWLMjbHzQ1tuwsxNKs9SW6i8S+KZJ4rfViyLJcSxMk9u8EjeSZPtEfl71LYGwKrhPuj5ax08PXun30+gQWdvdw7lvLS+dXuYgJYsjYBE3mk7cZRPmCOxX5FaGw6S6ToF7Bf+BknVGmhi1Ce2NsYbXCAEu0CK0hCnZI3z7z93na13UPCieH47S41DRtE+y3KyzRQQGaS4UC1zMWjmaNmjARmClg8buuGJ+Vi9nr\/AF+IWKun3994ht5rnQ9ItEhh08C7hcwi2gmiyRMN+PJ+WWQgLsG8M2WAet\/TrTR9F0i1g8Q2cdtbRTRmB44TazTyxvcrDI53pLFI4+YGTMaiMncpY7INM8Ox3ugW103h+20pWgt5zczWsVypMgkVGi6lhtEY8phI7s6DCuTOx\/wjJ1DxnA9p4d0SJSwlinMbw2t0ECq8RRg6rIQJ90Plq8ZTdubaSZk07rb+u40mP0DUL+TR2vL271K108x38xv7VYRPdl8Ts0bMWWL5VIMUe0h1Jy22Qxa97pus6HpWsx6ZoSWgup\/PXU7u+c\/ZfNVEMpHmSsJlEs2+UFV2x7ssOKz7vwRodvNf2sOizSRNZTXdmLe2c3AYoMIxcv5asi7V81C3mCYrsIUGAa3r\/hnwvp2nXeiw3NvLc2rWl\/DCJWnAceQrJFIoeUJArArI4wsat97dUuzd4j23N7+wdD0G9jin8R2F1qMTQ+fZXCxBrgFWRk8lBuYeVNIIo1GRuVSXUIqlZ2mtN4i0uKfR4ftGkaHcwG3WOWR1DRsJSqLIrSvgMsZbCsqx5jDCRoaKzkpfP7ilYnjSG3j1ptbkmgSC5WS1VLuOW5E1mryvcok+WG9RGAGMjbGUE+XtIgv5PDmn+NLbzG1KBpY1u4pUhkW9lLutr5TPNunfILkKnllVGVDNgqUVpBcxLdjqdZ1+00a3PltDfajodswuvLeRrmIGNPnEJbdLEd6M26QAbQSxZeMdtWHiBGFl4V1vWJpp5JXOrwJHDZrny2iAYqkgVo0byc8kbiwdcqUVnypQ5uo73djB8MWusaj4gslt76z1ImygkXW3uftUls0cjSMq5EUm1t+x4+WVZk3NjCm7L4U1LU9UjTUpdmpWm1pfslpGZbhCsgH7xg+9CkbwjzpFDAqZFBXExRTnNqTSBK6JDp2irp13d6FoKa1cPOJZpRK0yXwi5mkSK4Ztx35j3rvKifKs7bkojgsvCVrcWaTaU15b6fFqFmdStJVgt4kuZGDsrZkSTNy6IgLNmMEnLbaKKpXcuVi6XOlfTUn17UZLKeG8tr+QWTJBtMdhLGss26QRgOHWbLbvMVt0\/YqpOI2kPaa5dWtk01lcad515ONHsGiaS0dmeKKLI8p5S0UQb5AXChNx2PkorKLd36FNE1umqaYv9mwyabcJZ2zi1GmXbTXUJjlkUSiOfzNiBS8TBFlcH5RuwFWS4m0nVNOsGuNDtkTV2n8nUF0USwxXEvliOYl8HBZlTc6DzSob5VxkoqlrqIx9fS98P+HGL2um6joMkbNfLOH8\/f5rGSKSUGQHEsrKmH3q7AlmCSM2ibaLVfEdk84cpet\/Z9lqFlci7truzgdpmim3cguo2MdzFmjfouQ5RTv7vN11DrYnltdJfTtZuzZPaGzgfyLy7tg95PbLmW42QzxrgETvEXO4sWy5J5JRRW1GCle5E5WP\/9k="),
  //   ],
  // ];

  // sleep(2);
  // http_response_code(200);
  // echo json_encode([
  //   "message" => "Captcha has been successfully verified.",
  //   "data" => null
  // ]);

  // sleep(2);
  // http_response_code(400);
  // echo json_encode([
  //   "message" => "Your code is invalid, please try again.",
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

  $captcha_code = $parsed_json["captchaCode"] ?? null;

  if (!$captcha_code) {
    error_log("Invalid captcha code: $captcha_code");
    http_response_code(400);
    echo json_encode([
      "message" => "Invalid captcha code"
    ]);
    exit();
  }

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

  #region Call GetContact API Verify Code

  $api_request = (object)[
    "clientDeviceId" => $credential["clientDeviceId"],
    "validationCode" => $captcha_code,
    "finalKey" => $final_key,
    "token" => $token
  ];

  $api_response = getcontact_call_api_verify_code($api_request);

  #endregion

  #region Parse, decrypt, and decode API response

  $api_response_body = json_decode($api_response->body, false);

  if (json_last_error() !== JSON_ERROR_NONE) {
    log_api_transaction($api_request, $api_response, "verify code: decode body");
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
    log_api_transaction($api_request, $api_response, "verify code: decode body data");
    error_log(json_last_error_msg());
    error_log($decrypted_data_raw);
    http_response_code(400);
    echo json_encode([
      "message" => "Invalid JSON response. Please check error log."
    ]);
    exit();
  }

  if ($decrypted_body == null) {
    log_api_transaction($api_request, $api_response, "verify code: body null");
    http_response_code(500);
    echo json_encode([
      "message" => "Could not verify captcha (invalid response body)"
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
   * 403 -> failed but return a new image
   * 
   * so, we have to permit those http status codes
   */

  if (
    !in_array($http_code, [200, 403]) ||
    !in_array($api_response_http_code, [200, 403]) // permit 200 & 403
  ) {
    log_api_transaction($api_request, $api_response, "$source_type: invalid http status");

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

  if ($api_response_http_code !== 200) {
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

    http_response_code(403);
    echo json_encode([
      "message" => "Failed to verify captcha. Please try again with new captcha.",
      "data" => $result
    ]);
    exit();
  } else {
    http_response_code(200);
    echo json_encode([
      "message" => "Captcha has been successfully verified."
    ]);
    exit();
  }

  #endregion

} catch (\Exception $e) {
  error_log("Error GetContact API Verify Captcha: " . $e->getMessage());
  http_response_code(500);
  echo json_encode([
    "message" => "Internal server error"
  ]);
  exit();
}

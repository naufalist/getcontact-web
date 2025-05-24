<?php
ini_set("display_errors", 0);
ini_set("display_startup_errors", 0);
ini_set("log_errors", 1);
ini_set("error_log", "errors.log");
error_reporting(E_ALL);
date_default_timezone_set("Asia/Jakarta");

// Link to dependency
require_once __DIR__ . "/src/configurations/database.php";
require_once __DIR__ . "/src/configurations/getcontact.php";
require_once __DIR__ . "/src/configurations/site.php";
require_once __DIR__ . "/src/libraries/database.php";
require_once __DIR__ . "/src/libraries/getcontact.php";
require_once __DIR__ . "/src/libraries/helper.php";

// Initialize vars
$endpoint = "";
$query_params = [];

// Get request uri
$request_uri = $_SERVER["REQUEST_URI"];

// Remove url prefix from request uri
if (substr($request_uri, 0, strlen(URL_PREFIX)) == URL_PREFIX) {
  $request_uri = substr($request_uri, strlen(URL_PREFIX));
}

// Parse query params
if (strpos($request_uri, "?") !== false) {
  $request_uri_parts = explode("?", $request_uri);
  $endpoint = $request_uri_parts[0];
  if (count($request_uri_parts) > 1) {
    parse_str($request_uri_parts[1], $query_params);
  } else {
    $query_params = [];
  }
} else {
  $endpoint = $request_uri;
  $query_params = [];
}

// d($query_params);
// dd($endpoint);

// Page and API routing
$routes = array(
  "/" => "src/pages/index.php",
  "/api/getcontact/credentials" => "src/apis/credentials.php",
  "/api/getcontact/credentials/generate" => "src/apis/generate_credentials.php",
  "/api/getcontact/subscription" => "src/apis/subscription.php",
  "/api/getcontact/captcha/verify" => "src/apis/verify_captcha.php",
  "/api/getcontact/captcha/refresh" => "src/apis/refresh_captcha.php",
  "/dashboard" => "src/pages/dashboard/login.php",
  "/dashboard/credentials/manage" => "src/pages/dashboard/manage_credentials.php",
  "/dashboard/credentials/generate" => "src/pages/dashboard/generate_credentials.php",
  "/dashboard/captcha/verify" => "src/pages/dashboard/verify_captcha.php",
  "/dashboard/logout" => "src/pages/dashboard/logout.php",
);

$normalized_endpoint = strtolower($endpoint);

if (array_key_exists($normalized_endpoint, array_change_key_case($routes))) {
  $file = $routes[$normalized_endpoint] ?? $routes[array_keys(array_change_key_case($routes, CASE_LOWER))[$normalized_endpoint]];
  page_file_exists($file);
  include($file);
  exit();
}

if (strpos($normalized_endpoint, "/dashboard") === 0) {
  header("Location: " . URL_PREFIX . "/dashboard");
  exit();
}

header("HTTP/1.0 404 Not Found");
exit();

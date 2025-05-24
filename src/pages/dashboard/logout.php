<?php
ini_set("display_errors", 0);
ini_set("display_startup_errors", 0);
ini_set("log_errors", 1);
ini_set("error_log", "errors.log");
error_reporting(E_ALL);
date_default_timezone_set("Asia/Jakarta");

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

session_destroy();
header("Location: " . URL_PREFIX . "/dashboard");
exit();

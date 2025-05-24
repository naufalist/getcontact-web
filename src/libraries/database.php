<?php
ini_set("display_errors", 0);
ini_set("display_startup_errors", 0);
ini_set("log_errors", 1);
ini_set("error_log", "errors.log");
error_reporting(E_ALL);
date_default_timezone_set("Asia/Jakarta");

try {
  $dsn = "mysql:host=" . DATABASE_HOST . ";dbname=" . DATABASE_NAME . ";charset=utf8mb4";

  $options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ];

  $pdo = new PDO($dsn, DATABASE_USER, DATABASE_PASS, $options);
} catch (\PDOException $e) {
  error_log($e->getMessage());
  die("Database connection failed.");
}

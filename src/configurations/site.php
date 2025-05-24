<?php

/**
 * Database Mode
 * 
 * Set to true to retrieve credentials from the database.
 *
 * When true, credentials will be fetched from the SQL database.
 * When false, credentials will be loaded from the getcontact.php configuration file.
 */
define("USE_DATABASE", true);

/**
 * URL Prefix
 * 
 * If no subdirectory is used, set this value to an empty string.
 */
define("URL_PREFIX", "/getcontact-web");
// define("URL_PREFIX", "");

/**
 * CSRF Duration
 * 
 * Expire time (duration) for CSRF token in second.
 * 
 * i.e.:
 * 300 -> 5 minute
 * 3600 -> 1 hour
 */
define("CSRF_EXPIRY_DURATION", 60);

/**
 * Maximum session (time) for inactive user
 * 
 * Automatically log out the user after a specified number
 * of seconds of inactivity (sliding expiration is enabled).
 */
define("SESSION_MAX_INACTIVE", 3600);

/**
 * Form Encryption/Decryption Secret Key
 * 
 * To ensure data security during API calls.
 */
define("FORM_SECRET_KEY", "Y29udGFjdC5uYXVmYWxpc3RAZ21haWwuY29t"); // Change this value to your own configuration

/**
 * Max json size (request body in form submission)
 * 
 * i.e.:
 * 10240 KB => 10 KB
 * 32768 KB => 32 KB
 * 65536 KB => 64 KB
 */
define("MAX_JSON_SIZE", 65536);

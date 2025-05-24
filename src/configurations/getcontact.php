<?php

/**
 * GetContact Credentials
 * 
 * Id -> Represent identifier like auto increment in sql table
 * Description -> Short description about your getcontact account
 * Final Key -> Key for aes-256-ecb encryption/decryption
 * Token -> getcontact token (retrieve from /register api)
 * Client Device Id -> Android id (random hex 16 digits)
 */
define("GTC_CREDENTIALS", json_encode([
  [
    "id" => 1,
    "description" => "Account #1",
    "finalKey" => "CHANGE_WITH_YOUR_FINAL_KEY_1",
    "token" => "CHANGE_WITH_YOUR_TOKEN_1",
    "clientDeviceId" => "",
  ],
  [
    "id" => 2,
    "description" => "Account #2",
    "finalKey" => "CHANGE_WITH_YOUR_FINAL_KEY_2",
    "token" => "CHANGE_WITH_YOUR_TOKEN_2",
    "clientDeviceId" => "",
  ],
  [
    "id" => 3,
    "description" => "Account #3",
    "finalKey" => "CHANGE_WITH_YOUR_FINAL_KEY_3",
    "token" => "CHANGE_WITH_YOUR_TOKEN_3",
    "clientDeviceId" => "",
  ],
]));

/**
 * GetContact API Base URL
 */
define("GTC_API_BASE_URL", "https://pbssrv-centralevents.com");

/**
 * GetContact API Endpoint URLs
 */
define("GTC_API_EP_SUBSCRIPTION", "/v2.8/subscription"); // subscription
define("GTC_API_EP_SEARCH", "/v2.8/search"); // view profile
define("GTC_API_EP_NUMBER_DETAIL", "/v2.8/number-detail"); // view tag
define("GTC_API_EP_VERIFY_CODE", "/v2.8/verify-code"); // verify captcha code
define("GTC_API_EP_REFRESH_CODE", "/v2.8/refresh-code"); // change captcha code
define("GTC_API_EP_PROFILE_SETTINGS", "/v2.8/profile/settings"); // update profile settings
define("GTC_API_EP_REGISTER", "/v2.8/register");
define("GTC_API_EP_INIT_BASIC", "/v2.8/init-basic");
define("GTC_API_EP_AD_SETTINGS", "/v2.8/ad-settings");
define("GTC_API_EP_INIT_INTRO", "/v2.8/init-intro");
define("GTC_API_EP_EMAIL_CODE_VALIDATE_START", "/v2.8/email-code-validate/start");
define("GTC_API_EP_COUNTRY", "/v2.8/country");
define("GTC_API_EP_VALIDATION_START", "/v2.8/validation-start");
define("GTC_API_EP_VERIFYKIT_RESULT", "/v2.8/verifykit-result");

/**
 * GetContact HMAC Secret Key
 * 
 * Note:
 * HMAC Secret Key for signing request signature, other mobile app version may have different hmac secret key
 * GTC_HMAC_SECRET_KEY "793167..." -> from mobile app version 5.6.2
 * GTC_HMAC_SECRET_KEY "314267..." -> from mobile app version 7.2.2+ (worked in 8.4.0)
 */
// define("GTC_HMAC_SECRET_KEY", "793167597c4a25263656206b5469243e5f416c69385d2f7843716d4d4d5031242a29493846774a2c2a725f59554d2034683f40372b40233c3e2b772d65335657");
define("GTC_HMAC_SECRET_KEY", "31426764382a642f3a6665497235466f3d236d5d785b722b4c657457442a495b494524324866782a2364292478587a78662d7a7b7578593f71703e2b7e365762");

/**
 * GetContact Header Values
 */
define("GTC_COUNTRY_CODE", "id");
define("GTC_ANDROID_OS", "android 9"); // i.e.: "android 5.1", "android 9"
define("GTC_APP_VERSION", "8.4.0"); // i.e.: "5.6.2", "8.4.0"
define("GTC_CLIENT_DEVICE_ID", "174680a6f1765b5f"); // 16 digit random hex value
define("GTC_LANG", "en_US"); // i.e.: "in_ID", "en_US"
define("GTC_BUNDLE_ID", "app.source.getcontact");
define("GTC_CARRIER_COUNTRY_CODE", "510");
define("GTC_CARRIER_NAME", "Indosat Ooredoo");
define("GTC_CARRIER_NETWORK_CODE", "01");
define("GTC_DEVICE_NAME", "SM-G977N");
define("GTC_DEVICE_TYPE", "Android");
define("GTC_TIME_ZONE", "Asia/Bangkok");
define("GTC_OUTSIDE_COUNTRY_CODE", "ID");

define("VFK_HMAC_SECRET_KEY", "3452235d713252604a35562d325f765238695738485863672a705e6841544d3c7e6e45463028266f372b544e596f3829236b392825262e534a7e774f37653932");

/**
 * VerifyKit API Base URL
 */
define("VFK_API_BASE_URL", "https://api.verifykit.com");

/**
 * VerifyKit API Endpoint URLs
 */
define("VFK_API_EP_INIT", "/v2.0/init");
define("VFK_API_EP_COUNTRY", "/v2.0/country");
define("VFK_API_EP_START", "/v2.0/start");
define("VFK_API_EP_CHECK", "/v2.0/check");

/**
 * VerifyKit Header Values
 */
define("VFK_CLIENT_DEVICE_ID", "174680a6f1765b5f");
define("VFK_CLIENT_KEY", "bhvbd7ced119dc6ad6a0b35bd3cf836555d6f71930d9e5a405f32105c790d");
define("VFK_FINAL_KEY", "bd48d8c25293cfb537619cc93ae3d6e372eb2ddfffff4ab0eb000777144c7bfa");
define("VFK_SDK_VERSION", "0.11.4");
define("VFK_OS", "android 9.0");
define("VFK_APP_VERSION", "8.4.0");
define("VFK_LANG", "in_ID");

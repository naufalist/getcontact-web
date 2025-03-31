<?php

// secret key for signing request signature (mobile app version: 5.6.2)
// other version may have different secret key
define("GTC_HMAC_SECRET_KEY", "793167597c4a25263656206b5469243e5f416c69385d2f7843716d4d4d5031242a29493846774a2c2a725f59554d2034683f40372b40233c3e2b772d65335657");

// gtc api base url
define("GTC_API_BASE_URL", "https://pbssrv-centralevents.com");

// gtc api endpoint for subscription
define("GTC_API_EP_SUBSCRIPTION", "/v2.8/subscription");

// gtc api endpoint for view profile
define("GTC_API_EP_NUMBER_DETAIL", "/v2.8/number-detail");

// gtc api endpoint for view tag
define("GTC_API_EP_SEARCH", "/v2.8/search");

/**
 * Credential list
 * 
 * Account -> Short description about your GTC account
 * Final Key -> Key for aes-256-ecb encryption/decryption
 * Token -> GTC Token
 */
define("GTC_CREDENTIALS", json_encode([
  [
    "account" => "Account #1",
    "finalKey" => "CHANGE_WITH_YOUR_FINAL_KEY_1",
    "token" => "CHANGE_WITH_YOUR_TOKEN_1",
  ],
  [
    "account" => "Account #2",
    "finalKey" => "CHANGE_WITH_YOUR_FINAL_KEY_2",
    "token" => "CHANGE_WITH_YOUR_TOKEN_2",
  ],
  [
    "account" => "Account #3",
    "finalKey" => "CHANGE_WITH_YOUR_FINAL_KEY_3",
    "token" => "CHANGE_WITH_YOUR_TOKEN_3",
  ],
]));

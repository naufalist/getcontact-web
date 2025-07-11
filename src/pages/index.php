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

try {
  switch ($_SERVER["REQUEST_METHOD"]) {
    case "POST":

      #region Validate CSRF token

      $csrf_token = $_POST["csrf_token"] ?? "";

      if (empty($csrf_token) || !isset($csrf_token) || !csrf_token_validate($csrf_token)) {
        $_SESSION["error"] = "Invalid CSRF token. Please try again.";
        header("Location: " . URL_PREFIX . "/");
        exit();
      }

      #endregion

      #region Initialize errors, old_values array

      $errors = [];
      $old_values = [];
      $old_values["phone_number"] = trim(htmlspecialchars($_POST["phone_number"]));

      #endregion

      #region Parse form data

      $phone_number = trim(htmlspecialchars($_POST["phone_number"]));
      $credential_id = trim(htmlspecialchars($_POST["credential"]));
      $source_type = trim(htmlspecialchars(strtolower($_POST["source_type"])));

      #endregion

      #region Validate credential id

      $decrypted_id = decrypt_data($credential_id);
      $decrypted_id = (int)$decrypted_id;

      if (!isset($decrypted_id) || !is_int($decrypted_id) || $decrypted_id <= 0) {
        error_log("Decryption failed for ID: $credential_id");
        $alert["type"] = "warning";
        $alert["message"] = "Invalid or malformed credential ID";
        $errors["credential"] = "Credential is invalid";
        break;
      }

      $credential_id = $decrypted_id;

      #endregion

      #region Get credential by credential id

      if (USE_DATABASE) {

        $pdo_statement = $pdo->prepare("SELECT final_key AS finalKey, token AS token, client_device_id AS clientDeviceId FROM credentials WHERE id = ? AND deleted_at IS NULL");

        $pdo_statement->execute([$credential_id]);

        $credential = $pdo_statement->fetch(PDO::FETCH_ASSOC);
      } else {

        $credentials = json_decode(GTC_CREDENTIALS, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
          error_log(json_last_error_msg());
          error_log(GTC_CREDENTIALS);
          $alert["type"] = "warning";
          $alert["message"] = "Form data is invalid";
          $errors["credential"] = "Credential is invalid";
          break;
        }

        $credential = array_filter($credentials, function ($credential) use ($credential_id) {
          return $credential["id"] === $credential_id;
        });

        $credential = reset($credential);
      }

      if (!$credential || !isset($credential["finalKey"], $credential["token"])) {
        $alert["type"] = "warning";
        $alert["message"] = "Credential not found or incomplete";
        $errors["credential"] = "Credential is invalid";
        break;
      }

      $final_key = $credential["finalKey"] ?? null;
      $token = $credential["token"] ?? null;

      if (empty($final_key) || empty($token)) {
        $alert["type"] = "warning";
        $alert["message"] = "Credential not found or incomplete";
        $errors["credential"] = "Credential is invalid";
        break;
      }

      #endregion

      #region Validate phone number

      if (strpos($phone_number, "0") === 0) {
        $phone_number = "+62" . substr($phone_number, 1);
      } else if (strpos($phone_number, "62") === 0) {
        $phone_number = "+" . $phone_number;
      } else if (strpos($phone_number, "-") !== false || strpos($phone_number, " ") !== false) {
        $phone_number = str_replace(["-", " "], "", $phone_number);
      } else {
        $alert["type"] = "warning";
        $alert["message"] = "Form data is invalid";
        $errors["phone_number"] = "Phone number is invalid";
        break;
      }

      #endregion

      #region Validate source type

      /**
       * source = search --> endpoint = search
       * source = profile --> endpoint = number-detail
       */
      if (!isset($source_type) || empty($source_type || is_null($source_type)) || !in_array($source_type, ["search", "profile"])) {
        $alert["type"] = "warning";
        $alert["message"] = "Form data is invalid";
        $errors["source_type"] = "Source type is invalid";
        break;
      }

      #endregion

      #region Call GetContact API Search/Number Detail

      $api_request = (object)[
        "clientDeviceId" => $credential["clientDeviceId"],
        "finalKey" => $final_key,
        "token" => $token,
        "phoneNumber" => $phone_number,
      ];

      if ($source_type === "search") {
        $api_response = getcontact_call_api_search($api_request);
      } else { // else -> profile
        $api_response = getcontact_call_api_number_detail($api_request);
      }

      $api_response_body = json_decode($api_response->body, false);

      if (json_last_error() !== JSON_ERROR_NONE) {
        log_api_transaction($api_request, $api_response, "$source_type: decode body");
        error_log(json_last_error_msg());
        error_log($api_response->body);
        $alert["type"] = "warning";
        $alert["message"] = "Invalid JSON response. Please check error log.";
        break;
      }

      $decrypted_data_raw = getcontact_decrypt($api_response_body->data, $final_key);

      if (!$decrypted_data_raw) {
        log_api_transaction($api_request, $api_response, "$source_type: failed to decrypt");
        error_log($api_response_body->data);
        $alert["type"] = "warning";
        $alert["message"] = "Failed to decrypt response. Please check error log.";
        break;
      }

      $decrypted_body = json_decode($decrypted_data_raw, false);

      if (json_last_error() !== JSON_ERROR_NONE) {
        log_api_transaction($api_request, $api_response, "$source_type: decode body data");
        error_log(json_last_error_msg());
        error_log($decrypted_data_raw);
        $alert["type"] = "warning";
        $alert["message"] = "Invalid JSON response. Please check error log.";
        break;
      }

      if ($decrypted_body == null) {
        log_api_transaction($api_request, $api_response, "$source_type: body null");
        $alert["type"] = "danger";
        $alert["message"] = "Could not get information (invalid response body)";
        break;
      }

      #endregion

      #region Validate http status code

      $meta_http_status_code_path = "meta.httpStatusCode";

      if (!has_nested_property($decrypted_body, $meta_http_status_code_path)) {
        log_api_transaction($api_request, $api_response, "$source_type: missing $meta_http_status_code_path");
        $alert["type"] = "danger";
        $alert["message"] = "Unexpected API response structure";
        break;
      }

      $http_code = get_nested_value($decrypted_body, $meta_http_status_code_path);
      $api_response_http_code = $api_response->httpCode ?? null;

      if ($http_code !== 200 || $api_response_http_code !== 200) {
        log_api_transaction($api_request, $api_response, "$source_type: invalid http status");

        $error_message_path = "meta.errorMessage";
        $error_message = "An error occurred. Please contact administrator.";

        // if (has_nested_property($decrypted_body, $error_message_path)) {
        //   // $error_message = get_nested_value($decrypted_body, $error_message_path);
        //   error_log(json_encode($decrypted_body, JSON_PRETTY_PRINT));
        // } else {
        //   error_log("$source_type: missing $error_message_path");
        // }

        if (!has_nested_property($decrypted_body, $error_message_path)) {
          error_log("$source_type: missing $error_message_path");
        }

        $alert = [
          "type" => "danger",
          "message" => "Invalid response ($http_code: $error_message)"
        ];

        break;
      }

      #endregion

      #region Complete response

      $alert["type"] = "success";
      $alert["message"] = "Success";
      $result = $decrypted_body;

      $censored_phone_number = censor_phone_number($phone_number);
      $censored_phone_number = preg_replace("/[^0-9x]/", "", $censored_phone_number);

      $old_values = null;

      #endregion

      break;
  }
} catch (\Exception $ex) {
  error_log($ex->getMessage());
  $alert["type"] = "danger";
  $alert["message"] = "Internal server error";
}

$csrf_token = csrf_token_generate();
?>
<!doctype html>
<html lang="en" data-bs-theme="dark">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Primary Meta Tags -->
  <title>GetContact PHP Web App</title>
  <meta name="title" content="GetContact PHP Web App">
  <meta name="description" content="A simple PHP web app to view GetContact profiles or tags directly via the API without using the mobile app. Supports searching, screenshot download, and multi-credential config.">

  <!-- Open Graph / Facebook -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://github.com/naufalist/getcontact-web">
  <meta property="og:title" content="GetContact PHP Web App">
  <meta property="og:description" content="A lightweight tool to search and view phone number tags or profiles using GetContact API, written in PHP.">
  <meta property="og:image" content="https://raw.githubusercontent.com/naufalist/getcontact-web/main/assets/images/getcontact.webp">

  <!-- Twitter -->
  <meta property="twitter:card" content="summary_large_image">
  <meta property="twitter:url" content="https://github.com/naufalist/getcontact-web">
  <meta property="twitter:title" content="GetContact PHP Web App">
  <meta property="twitter:description" content="A lightweight PHP app to view phone number tags or profiles via GetContact API without needing the mobile app.">
  <meta property="twitter:image" content="https://raw.githubusercontent.com/naufalist/getcontact-web/main/assets/images/getcontact.webp">

  <meta name="author" content="@naufalist">

  <!-- Bootstrap -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.2/dist/cosmo/bootstrap.min.css">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@500&display=swap" rel="stylesheet">

  <!-- SweetAlert2 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

  <!-- Notyf CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">

  <!-- Custom Css -->
  <link rel="stylesheet" href="<?php echo base_url("/public/css/custom.css") ?>">
</head>

<body class="user-select-none">

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg fixed-top bg-body-tertiary">
    <div class="container">
      <a href="#" class="navbar-brand nav-link" tabindex="-1">GetContact</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link active" href="javascript:void(0);" tabindex="-1"><i class="fa fa-search"></i> Search</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="https://tools.naufalist.com/getcontact/credentials/generate" target="_blank" tabindex="-1"><i class="fa fa-key"></i> Generate Credentials</a>
          </li>
        </ul>
        <ul class="navbar-nav ms-md-auto">
          <li class="nav-item dropdown" data-bs-theme="dark">
            <a class="nav-link dropdown-toggle align-items-center" href="#" id="theme-menu" aria-expanded="false" data-bs-toggle="dropdown" data-bs-display="static" aria-label="Toggle theme" tabindex="-1">
              <i class="fa fa-adjust"></i>
              <span class="d-lg-none ms-2">Toggle theme</span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
              <li>
                <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light"
                  aria-pressed="false">
                  <i class="fas fa-sun"></i><span class="ms-2">Light</span>
                </button>
              </li>
              <li>
                <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark"
                  aria-pressed="true">
                  <i class="fas fa-moon"></i><span class="ms-2">Dark</span>
                </button>
              </li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="container">

    <!-- Result Section -->
    <div class="row justify-content-center">
      <div class="col-12 col-md-8">

        <?php if (!empty($_SESSION["error"])): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION["error"];
            unset($_SESSION["error"]); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <?php if (!isset($result) && isset($alert)): ?>
          <div class="alert alert-<?php echo $alert["type"] ?> alert-dismissible fade show" role="alert">
            <?php echo $alert["message"] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php endif; ?>

        <?php if (isset($result)): ?>
          <div class="accordion mb-3" id="accordionExample">
            <div class="accordion-item">
              <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                  Result
                </button>
              </h2>
              <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                <div class="accordion-body">

                  <?php if (has_nested_property($result, "result.profile")): ?>

                    <div class="text-center">
                      <img src="<?php echo get_nested_value($result, "result.profile.profileImage") ?? "" ?>" class="rounded" alt="Profile Image">
                    </div>
                    <dl class="row">
                      <dt class="col-sm-3">Display Name</dt>
                      <dd class="col-sm-9"><?php echo get_nested_value($result, "result.profile.displayName") ?? "-" ?></dd>

                      <dt class="col-sm-3">Name</dt>
                      <dd class="col-sm-9"><?php echo get_nested_value($result, "result.profile.name") ?? "-" ?></dd>

                      <dt class="col-sm-3">Surname</dt>
                      <dd class="col-sm-9"><?php echo get_nested_value($result, "result.profile.surname") ?? "-" ?></dd>

                      <dt class="col-sm-3">Phone Number</dt>
                      <dd class="col-sm-9"><?php echo get_nested_value($result, "result.profile.phoneNumber") ?? "-" ?></dd>

                      <dt class="col-sm-3">Display Number</dt>
                      <dd class="col-sm-9"><?php echo get_nested_value($result, "result.profile.displayNumber") ?? "-" ?></dd>

                      <dt class="col-sm-3">Tag Count</dt>
                      <dd class="col-sm-9"><?php echo get_nested_value($result, "result.profile.tagCount") ?? "-" ?></dd>

                      <dt class="col-sm-3">Email</dt>
                      <dd class="col-sm-9"><?php echo get_nested_value($result, "result.profile.email") ?? "-" ?></dd>

                    </dl>

                  <?php elseif (has_nested_property($result, "result.tags")): ?>

                    <div class="d-grid gap-2 mb-3">
                      <button onclick="downloadResultTagsToImage(this)" type="button" class="btn btn-outline-success" data-censored-phone-number="<?php echo isset($censored_phone_number) ? trim(htmlspecialchars($censored_phone_number)) : "" ?>"><i class="far fa-download"></i> Download Result (.png)</button>
                    </div>

                    <table id="result-tags" class="table table-sm">
                      <thead>
                        <tr>
                          <th scope="col">#</th>
                          <th scope="col">Tag Name</th>
                          <th scope="col" class="text-center">Tag Count</th>
                        </tr>
                      </thead>
                      <tbody>

                        <?php $tags = get_nested_value($result, "result.tags"); ?>

                        <?php if (is_array($tags) && count($tags) > 0) : ?>
                          <?php foreach ($tags as $tag_key => $tag_value) : ?>
                            <?php $tag_key += 1; ?>
                            <tr>
                              <th scope="row"><?php echo $tag_key ?></th>
                              <td><?php echo $tag_value->tag ?></td>
                              <td class="text-center">
                                <h6 class="fw-bold"><?php echo $tag_value->count ?></h6>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        <?php else : ?>
                          <tr>
                            <td colspan="3" class="text-center">Empty / Not Found</td>
                          </tr>
                        <?php endif; ?>

                      </tbody>
                    </table>

                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Form Section -->
    <div class="row justify-content-center">
      <div class="col-12 col-md-8">
        <div class="card border-0">
          <div class="card-body">
            <form id="form" action="<?php echo base_url("/") ?>" method="POST" onsubmit="return submitForm()">

              <!-- CSRF Token -->
              <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>" autocomplete="off">

              <!-- Phone Number -->
              <div class="row mb-3">
                <label for="phone-number" class="col-sm-3 col-form-label">Phone Number &#127470;&#127465; <small class="text-danger">*</small></label>
                <div class="col-sm-9">
                  <input type="text" class="form-control<?php echo (isset($errors["phone_number"]) && !empty($errors["phone_number"])) ? " is-invalid" : ""; ?>" id="phone-number" name="phone_number" placeholder="62xxx / 0xxx" value="<?php echo isset($old_values["phone_number"]) ? $old_values["phone_number"] : ""; ?>" autocomplete="off">
                  <div class="invalid-feedback">
                    <?php echo isset($errors["phone_number"]) && !empty($errors["phone_number"]) ? $errors["phone_number"] : ""; ?>
                  </div>
                  <div id="phoneNumberHelp" class="form-text">Only numbers are allowed</div>
                </div>
              </div>

              <!-- Credential Select -->
              <div class="row">
                <label for="credential" class="col-sm-3 col-form-label">Credential <small class="text-danger">*</small></label>
                <div class="col-sm-9">
                  <select id="credential" name="credential" class="form-select" aria-label="credential">
                    <option value="" selected disabled>-- Choose GetContact Credential --</option>
                  </select>
                  <div class="invalid-feedback"></div>
                </div>
              </div>

              <!-- How To Get Final Key & Token Modal Button -->
              <div class="row mb-3">
                <div class="col-sm-9 offset-sm-3">
                  <a href="#" class="form-text text-primary text-decoration-underline" data-bs-toggle="modal" data-bs-target="#how-to-get-finalkey-and-token-modal" tabindex="-1">How to get GetContact credential?</a>
                  <span id="display-expired-at" class="badge bg-soft-primary ms-1 mt-2" data-bs-theme="light">Expired at: -</span>
                </div>
              </div>

              <!-- Source Type -->
              <fieldset class="row mb-3">
                <legend class="col-form-label col-sm-3 pt-0">Source Type <small class="text-danger">*</small></legend>
                <div class="col-sm-9">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="source_type" id="source-type-search" value="search" <?php echo isset($old_values["source_type"]) && $old_values["source_type"] == "search" ? "checked" : "" ?> disabled>
                    <label class="form-check-label" for="source-type-search">Search (View Profile Picture) <span id="display-view-profile-limit" class="badge bg-soft-primary" data-bs-theme="light">Remaining: -/-</span>
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="source_type" id="source-type-profile" value="profile" <?php echo isset($old_values["source_type"]) && $old_values["source_type"] == "profile" ? "checked" : "" ?> disabled>
                    <label class="form-check-label" for="source-type-profile">Profile (View Tags) <span id="display-view-tags-limit" class="badge bg-soft-primary" data-bs-theme="light">Remaining: -/-</span></label>
                    <div class="invalid-feedback"></div>
                  </div>
                </div>
              </fieldset>

              <!-- Submit Button -->
              <div class="row mt-4">
                <div class="col-sm-9 offset-sm-3">
                  <button id="submit-btn" type="button" onclick="submitForm()" class="btn btn-primary btn-md py-2 w-100" disabled>Submit</button>
                </div>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>

  </div>

  <!-- Modal -->
  <div class="modal modal-lg fade" id="how-to-get-finalkey-and-token-modal" tabindex="-1" aria-labelledby="how-to-get-finalkey-and-token-modal" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5">How to get GetContact credential?</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="accordion" id="accordionExample">
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                  Manual (Manual login)
                </button>
              </h2>
              <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#accordionExample">
                <div class="accordion-body text-break">
                  <p>Requirements:</p>
                  <ul>
                    <li>
                      Android with ROOT enabled (real device or emulator).
                    </li>
                  </ul>
                  <p>Step:</p>
                  <ol>
                    <li>Open file manager, go to this directory:<br>
                      <code>/data/data/app.source.getcontact/shared_prefs/GetContactSettingsPref.xml</code>
                    </li>
                    <li>Find <code>FINAL_KEY</code> and <code>TOKEN</code></li>
                    <li>Done.</li>
                  </ol>
                  <img
                    src="data:image/png;base64,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"
                    class="img-fluid" alt="FINAL KEY AND TOKEN">
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                  Automatic (Auto login)
                </button>
              </h2>
              <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                <div class="accordion-body text-break">
                  This feature is still experimental. I apologize for any issues. <a href="https://tools.naufalist.com/getcontact/credentials/generate" target="_blank">https://tools.naufalist.com/getcontact/credentials/generate</a>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <footer class="footer bg-body-tertiary" data-bs-theme="dark">
    <div class="container">
      <span class="text-muted">Made with <span style="color: #e25555;">&#10084;&#65039;</span> <a href="https://tools.naufalist.com/" class="text-decoration-none" target="_blank" title="Naufalist" tabindex="-1">naufalist</a></span>
    </div>
  </footer>

  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

  <!-- Bootstrap Bundle (includes Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

  <!-- HTML2Canvas -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

  <!-- Notyf JS -->
  <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>

  <!-- Custom JS -->
  <script src="<?php echo base_url("/public/js/custom.js") ?>"></script>

  <script>
    // function to download gtc result as image
    function downloadResultTagsToImage(button) {

      const captureElement = document.getElementById("result-tags");
      const censoredPhoneNumber = button.getAttribute("data-censored-phone-number");

      Swal.fire({
        title: "Confirmation",
        text: "Are you sure you want to download the result as an image?",
        icon: "question",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes",
        cancelButtonText: "Cancel"
      }).then((result) => {
        if (result.isConfirmed) {
          html2canvas(captureElement).then(function(canvas) {

            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, "0"); // month starts from 0
            const day = String(now.getDate()).padStart(2, "0");
            const hours = String(now.getHours()).padStart(2, "0");
            const minutes = String(now.getMinutes()).padStart(2, "0");
            const seconds = String(now.getSeconds()).padStart(2, "0");
            const currentLocalDateTime = `${year}${month}${day}_${hours}${minutes}${seconds}`;

            const imageData = canvas.toDataURL("image/png");

            const link = document.createElement("a");
            link.href = imageData;
            if (censoredPhoneNumber) {
              link.download = `gtc_result_${censoredPhoneNumber}_${currentLocalDateTime}.png`;
            } else {
              link.download = `gtc_result_${currentLocalDateTime}.png`;
            }

            link.click();
          });
        }
      });

    };

    function setValidationState($element, isValid, message = "") {
      $element.removeClass("is-invalid is-valid");
      if (isValid) {
        $element.addClass("is-valid");
        $element.next("div.invalid-feedback").text("");
      } else {
        $element.addClass("is-invalid");
        $element.next("div.invalid-feedback").text(message);
      }
    }

    function validatePhoneNumber() {
      const $phoneInput = $("#phone-number");
      const phoneNumber = $phoneInput.val();
      const digitsOnly = str => /^[0-9]+$/.test(str);

      if (!phoneNumber) return setValidationState($phoneInput, false, "Phone number is invalid"), false;
      if (!digitsOnly(phoneNumber)) return setValidationState($phoneInput, false, "Only digits are allowed in phone number!"), false;
      if (!(phoneNumber.startsWith("0") || phoneNumber.startsWith("62")))
        return setValidationState($phoneInput, false, "Phone number prefix is invalid"), false;

      setValidationState($phoneInput, true);
      return true;
    }

    function validateCredential() {
      const $credentialSelect = $("#credential");
      const credential = $credentialSelect.val();

      const isValid = !!credential;
      setValidationState($credentialSelect, isValid, "Credential is invalid");
      return isValid;
    }

    function validateSourceType() {
      const $sourceInputs = $("input[name='source_type']");
      const isChecked = $sourceInputs.is(":checked");
      const $feedback = $sourceInputs.nextAll("div.invalid-feedback").first();

      $sourceInputs.removeClass("is-invalid is-valid");
      if (!isChecked) {
        $sourceInputs.addClass("is-invalid");
        $feedback.text("Source type is not checked");
        return false;
      }

      $sourceInputs.addClass("is-valid");
      $feedback.text("");
      return true;
    }

    function submitForm() {
      const isPhoneNumberValid = validatePhoneNumber();
      const isCredentialValid = validateCredential();
      const isSourceTypeValid = validateSourceType();

      if (!isPhoneNumberValid || !isCredentialValid || !isSourceTypeValid) {
        console.log("false");
        new Notyf(notyfOption).error("Please check the form");
        return false;
      }

      Swal.fire({
        title: "Are you sure?",
        text: "You are about to submit the form.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Yes"
      }).then((result) => {
        if (result.isConfirmed) {
          document.getElementById("form").submit();
          return false;
        } else {
          // Reset form state
          ["#phone-number", "#credential", "input[name='source_type']"].forEach(selector => {
            const $el = $(selector);
            $el.removeClass("is-invalid is-valid");
            $el.next("div.invalid-feedback").text("");
          });
        }
      });
    }

    $(document).ready(function() {
      // Phone number cleanup
      $("#phone-number").on("input paste", function(event) {
        const value = (event.type === "paste") ?
          (event.originalEvent.clipboardData || window.clipboardData).getData("text") :
          $(this).val();

        $(this).val(value.replace(/\D/g, ""));
        $("#phone-number").removeClass("is-invalid is-valid").next("div.invalid-feedback").text("");
        if (event.type === "paste") event.preventDefault();
      });

      // Credential select change
      $("#credential").on("change", function() {
        const $sourceInputs = $("input[name='source_type']");
        $("#credential").add($sourceInputs).removeClass("is-invalid is-valid").next("div.invalid-feedback").text("");
        $sourceInputs.prop("checked", false).prop("disabled", true);
        $("#display-expired-at").text("Expired at: -");
        $("#display-view-profile-limit, #display-view-tags-limit").text("Remaining: -/-");

        const id = $(this).val();
        Swal.fire({
          title: "Please wait...",
          text: "Getting subscription data...",
          allowOutsideClick: false,
          didOpen: () => Swal.showLoading()
        });

        $.ajax({
          url: `<?= base_url("api/getcontact/subscription") ?>`,
          type: "POST",
          contentType: "application/json",
          data: JSON.stringify({
            id
          }),
          dataType: "json",
          success: function(response) {
            Swal.close();

            const info = response?.data?.info;
            if (!info) {
              console.error("Missing: response.data.info");
              Swal.fire({
                icon: "error",
                title: "Error",
                text: "Missing: response.data.info"
              });
            }

            const sr = info?.search?.remainingCount ?? (() => {
              if (!info?.search) {
                console.error("Missing: info.search");
              } else {
                console.error("Missing: info.search.remainingCount");
              }
              return "-";
            })();

            const sl = info?.search?.limit ?? (() => {
              if (!info?.search) console.error("Missing: info.search");
              else console.error("Missing: info.search.limit");
              return "-";
            })();

            const nr = info?.numberDetail?.remainingCount ?? (() => {
              if (!info?.numberDetail) console.error("Missing: info.numberDetail");
              else console.error("Missing: info.numberDetail.remainingCount");
              return "-";
            })();

            const nl = info?.numberDetail?.limit ?? (() => {
              if (!info?.numberDetail) console.error("Missing: info.numberDetail");
              else console.error("Missing: info.numberDetail.limit");
              return "-";
            })();

            const expiry = info?.receiptEndDate ?? (() => {
              console.error("Missing: info.receiptEndDate");
              return "-";
            })();

            $("#display-view-profile-limit").text(`Remaining: ${sr}/${sl}`);
            $("#display-view-tags-limit").text(`Remaining: ${nr}/${nl}`);
            $("#display-expired-at").text(`Expired at: ${expiry}`);

            const enableSearch = Number.isInteger(info.search.remainingCount) && info.search.remainingCount > 0;
            const enableProfile = Number.isInteger(info.numberDetail.remainingCount) && info.numberDetail.remainingCount > 0;

            $("#source-type-search").prop("disabled", !enableSearch);
            $("#source-type-profile").prop("disabled", !enableProfile);
            $("#submit-btn").prop("disabled", !(enableSearch || enableProfile));
          },
          error: function(xhr, status, error) {
            console.error("Error fetching subscription:", error);

            let icon = "error";
            let title = "Error";
            let message = "Failed to get subscription data";

            let json = null;
            try {
              json = JSON.parse(xhr.responseText);
            } catch (e) {
              // nothing to process
            }

            if (json && json.message) {
              message = json.message;
            }

            switch (xhr.status) {
              case 400:
                icon = "warning";
                title = "Warning";
                if (!json || !json.message) message = "The request was invalid.";
                break;
              case 404:
                icon = "warning";
                title = "Warning";
                if (!json || !json.message) message = "The requested resource was not found.";
                break;
              case 500:
                icon = "error";
                title = "Error";
                if (!json || !json.message) message = "An internal server error occurred.";
                break;
              default:
                if (!json || !json.message) {
                  message = `Unexpected error (${xhr.status}): ${error}`;
                }
                break;
            }

            Swal.fire({
              icon: icon,
              title: title,
              text: message
            });

            $("#submit-btn").prop("disabled", true);
          }
        });
      });

      // Reset source_type validation state on change
      $("input[name='source_type']").change(function() {
        if ($(this).is(":checked")) {
          $(this).removeClass("is-invalid is-valid").next("div.invalid-feedback").text("");
        }
      });

      // Fetch credentials
      $.ajax({
        url: `<?= base_url("api/getcontact/credentials") ?>`,
        type: "GET",
        dataType: "json",
        success: function(response) {
          const $select = $("#credential");
          response.data.forEach(item => {
            $select.append(`<option value="${item.id}">${item.description}</option>`);
          });
        },
        error: function(xhr, status, error) {
          console.error("Error fetching credentials:", error);

          let icon = "error";
          let title = "Error";
          let message = "Failed to get credentials";

          let json = null;
          try {
            json = JSON.parse(xhr.responseText);
          } catch (e) {
            // nothing to process
          }

          if (json && json.message) {
            message = json.message;
          }

          switch (xhr.status) {
            case 400:
              icon = "warning";
              title = "Warning";
              if (!json || !json.message) message = "The request was invalid.";
              break;
            case 404:
              icon = "warning";
              title = "Warning";
              if (!json || !json.message) message = "The requested resource was not found.";
              break;
            case 500:
              icon = "error";
              title = "Error";
              if (!json || !json.message) message = "An internal server error occurred.";
              break;
            default:
              if (!json || !json.message) {
                message = `Unexpected error (${xhr.status}): ${error}`;
              }
              break;
          }

          Swal.fire({
            icon: icon,
            title: title,
            text: message
          });
        }
      });
    });
  </script>
</body>

</html>
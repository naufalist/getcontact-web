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

require_login();

$csrf_token = csrf_token_generate();
?>
<!doctype html>
<html lang="en" data-bs-theme="dark">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Primary Meta Tags -->
  <title>GetContact PHP Web App | Admin Dashboard</title>
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

  <!-- Datatable -->
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet" />

  <!-- Custom Css -->
  <link rel="stylesheet" href="<?php echo base_url("/public/css/custom.css") ?>">
</head>

<body class="user-select-none">

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg fixed-top bg-body-tertiary">
    <div class="container">
      <a href="javascript:void(0);" class="navbar-brand nav-link" tabindex="-1">GetContact</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link" href="<?php echo base_url("dashboard/credentials/manage") ?>" tabindex="-1"><i class="fa fa-key"></i> Manage Credentials</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="javascript:void(0);" tabindex="-1"><i class="fa fa-check-circle"></i> Verify Captcha</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?php echo base_url("dashboard/credentials/generate") ?>" tabindex="-1"><i class="fa fa-key"></i> Generate Credentials</a>
          </li>
        </ul>
        <ul class="navbar-nav ms-md-auto">
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-circle mb-1 me-1" viewBox="0 0 16 16">
                <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"></path>
                <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"></path>
              </svg>
              <label>Administrator</label>
            </a>
            <div class="dropdown-menu dropdown-menu-end">
              <a class="dropdown-item text-warning" href="<?php echo base_url("dashboard/logout") ?>">
                <label>Logout</label>
              </a>
            </div>
          </li>
          <li class="nav-item dropdown" data-bs-theme="dark">
            <a class="nav-link dropdown-toggle align-items-center" href="#" id="theme-menu" aria-expanded="false"
              data-bs-toggle="dropdown" data-bs-display="static" aria-label="Toggle theme">
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
  <div class="container py-3">

    <!-- Form Section -->
    <div class="row justify-content-center">
      <div class="col-12 col-md-8">

        <div class="alert alert-info alert-dismissible fade show" role="alert">
          Please select a credential and click the <strong>generate or refresh captcha button</strong> to display the captcha code.
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        <div class="card border-0">
          <div class="card-body">
            <form id="form" onsubmit="return submitForm()">

              <!-- Credential Select -->
              <div class="row mb-3">
                <label for="credential" class="col-sm-3 col-form-label">Credential <small class="text-danger">*</small></label>
                <div class="col-sm-9">
                  <select id="credential" name="credential" class="form-select" aria-label="credential" required>
                    <option value="" selected disabled>-- Choose GetContact Credential --</option>
                  </select>
                  <div class="invalid-feedback"></div>
                </div>
              </div>

              <!-- Captcha Code -->
              <div class="row mb-3">
                <label for="captcha-code" class="col-sm-3 col-form-label">Code <small class="text-danger">*</small></label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" id="captcha-code" name="captcha-code" placeholder="Fill with captcha code" value="" autocomplete="off" required>
                  <div class="invalid-feedback">
                  </div>
                </div>
              </div>

              <!-- Submit Button -->
              <div class="row mt-4">
                <div class="col-sm-9 offset-sm-3">
                  <button id="submit-btn" type="button" onclick="submitForm()" class="btn btn-primary btn-md py-2 w-100">Verify</button>
                </div>
              </div>

            </form>
          </div>
        </div>
      </div>
    </div>

    <hr />

    <!-- Captcha Section -->
    <div class="row justify-content-center">
      <div class="col-12 col-md-8">

        <div class="card border-0">
          <div class="card-body">

            <!-- Refresh Captcha -->
            <div class="row mb-3">
              <div class="col-sm-9 offset-sm-3">
                <button id="refresh-captcha-btn" type="button" onclick="generateOrRefreshCode()" class="btn btn-outline-info btn-md py-2 w-100">Click here to generate / refresh captcha</button>
              </div>
            </div>

            <!-- Captcha Image -->
            <div class="row">
              <div class="col offset-md-3">
                <img id="captcha-image" style="display: none;" width="50%" src="" alt="Captcha">
              </div>
            </div>

          </div>
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

  <!-- Datatable -->
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

  <!-- HTML2Canvas -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

  <!-- Notyf JS -->
  <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>

  <!-- Custom JS -->
  <script src="<?php echo base_url("/public/js/custom.js") ?>"></script>

  <script>
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

    function validateCredential() {
      const $credentialSelect = $("#credential");
      const credential = $credentialSelect.val();

      const isValid = !!credential;
      setValidationState($credentialSelect, isValid, "Credential is invalid");
      return isValid;
    }

    function validateCaptchaCode() {
      const $captchaCodeInput = $("#captcha-code");
      const captchaCode = $captchaCodeInput.val();

      const isValid = !!captchaCode;
      setValidationState($captchaCodeInput, isValid, "Captcha code is invalid"), false;
      return isValid;
    }

    function submitForm() {
      const isCredentialValid = validateCredential();
      const isCaptchaCodeValid = validateCaptchaCode();

      if (!isCredentialValid || !isCaptchaCodeValid) {
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

          Swal.fire({
            title: "Please wait...",
            text: "Verifying captcha code...",
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
          });

          const formData = {
            id: $("#credential").val(),
            captchaCode: $("#captcha-code").val()
          };

          console.log(formData);

          $.ajax({
            url: `<?= base_url("api/getcontact/captcha/verify") ?>`,
            type: "POST",
            contentType: "application/json",
            data: JSON.stringify(formData),
            dataType: "json",
            success: function(response) {
              Swal.close();

              console.log(response);

              const message = response?.message;
              if (!message) {
                console.error("Missing: response.message");
                Swal.fire({
                  icon: "error",
                  title: "Error",
                  text: "Missing: response.message"
                });
              }

              Swal.fire({
                icon: "success",
                title: "Verified",
                text: message
              });

              $("#credential").val("");
              $("#captcha-code").val("");
              ["#credential", "#captcha-code"].forEach(selector => {
                const $el = $(selector);
                $el.removeClass("is-invalid is-valid");
                $el.next("div.invalid-feedback").text("");
              });
              $("#captcha-image").attr("src", "");
              $("#captcha-image").hide();

            },
            error: function(xhr, status, error) {
              console.error("Error verify captcha:", error);

              let icon = "error";
              let title = "Error";
              let message = "Failed to verify captcha";

              let json = null;
              try {
                json = JSON.parse(xhr.responseText);
              } catch (e) {
                // nothing to process
              }

              const image = json?.data?.captcha?.image;
              if (!image) {
                console.error("Missing: response.data.captcha.image");
                Swal.fire({
                  icon: "error",
                  title: "Error",
                  text: "Missing: response.data.captcha.image"
                });
              } else {
                handleCaptchaImage(image);
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

        } else {
          ["#credential", "#captcha-code"].forEach(selector => {
            const $el = $(selector);
            $el.removeClass("is-invalid is-valid");
            $el.next("div.invalid-feedback").text("");
          });
        }

        return false;
      });
    }

    function generateOrRefreshCode() {
      const $credentialSelect = $("#credential");
      const credential = $credentialSelect.val();
      const isCredentialValid = !!credential;

      if (!isCredentialValid) {
        console.log("false");
        new Notyf(notyfOption).error("Please choose credential");
        return false;
      }

      $("#refresh-captcha-btn").prop("disabled", true);

      Swal.fire({
        title: "Please wait...",
        text: "Generating captcha code...",
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
      });

      const formData = {
        id: credential
      }

      console.log(formData);

      $.ajax({
        url: `<?= base_url("api/getcontact/captcha/refresh") ?>`,
        type: "POST",
        contentType: "application/json",
        data: JSON.stringify(formData),
        dataType: "json",
        success: function(response) {
          Swal.close();

          $("#refresh-captcha-btn").prop("disabled", false);

          console.log(response);

          const image = response?.data?.captcha?.image;
          if (!image) {
            console.error("Missing: response.data.captcha.image");
            Swal.fire({
              icon: "error",
              title: "Error",
              text: "Missing: response.data.captcha.image"
            });
          } else {
            handleCaptchaImage(image);
          }
        },
        error: function(xhr, status, error) {

          $("#refresh-captcha-btn").prop("disabled", true);

          console.error("Error fetching captcha code:", error);

          let icon = "error";
          let title = "Error";
          let message = "Failed to get captcha code";

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

      $("#refresh-captcha-btn").prop("disabled", false);

      return false;
    }

    function handleCaptchaImage(image) {
      $("#captcha-code").val("");
      ["#credential", "#captcha-code"].forEach(selector => {
        const $el = $(selector);
        $el.removeClass("is-invalid is-valid");
        $el.next("div.invalid-feedback").text("");
      });
      const decodedImage = (atob(image)).replace(/\\\//g, "/"); // convert \/ to /
      console.log(decodedImage);
      $("#captcha-image").attr("src", `data:image/png;base64,${decodedImage}`);
      $("#captcha-image").show();
    }

    $(document).ready(function() {

      // Credential select change
      $("#credential").on("change", function() {
        if ($(this).val()) {
          $("#credential").removeClass("is-invalid is-valid").next("div.invalid-feedback").text("");
        }
      });

      // Captcha code on keyup
      $("#captcha-code").on("keyup", function() {
        $("#captcha-code").removeClass("is-invalid is-valid").next("div.invalid-feedback").text("");
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
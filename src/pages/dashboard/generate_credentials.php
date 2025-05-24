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

  <style>
    .qrcode-box {
      min-width: 150px;
      min-height: 150px;
      background: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      position: relative;
      overflow: hidden;
      cursor: pointer;
    }

    .qr-code-container img {
      width: 100%;
      height: auto;
      padding: 8px;
      background-color: #ffffff;
    }

    .qrcode-overlay {
      position: absolute;
      text-align: center;
      background: rgba(255, 255, 255, 0.98);
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      transition: opacity 0.3s ease;
      pointer-events: none;
    }

    .qrcode-box:not(.blur) .qrcode-overlay {
      opacity: 0;
    }

    .qrcode-box.active .qrcode-overlay {
      opacity: 0;
    }

    .link-text {
      word-break: break-word;
      font-size: 0.95rem;
      color: #9fafff;
    }

    @media (max-width: 768px) {
      .qrcode-box {
        margin: 0 auto;
      }
    }
  </style>
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
            <a class="nav-link" href="<?php echo base_url("dashboard/credentials/manage") ?>" tabindex="-1"><i class="fa fa-key"></i> Manage Credentials</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?php echo base_url("dashboard/captcha/verify") ?>" tabindex="-1"><i class="fa fa-check-circle"></i> Verify Captcha</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="javascript:void(0);" tabindex="-1"><i class="fa fa-key"></i> Generate Credentials</a>
          </li>
        </ul>
        <ul class="navbar-nav ms-md-auto">
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false" tabindex="-1">
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
  <div class="container py-3">

    <div class="row justify-content-center">
      <div class="col-12 col-md-9">
        <div class="card border-0">
          <div class="card-body">

            <input type="hidden" id="temp-form-data" value="" autocomplete="off">

            <form id="phone-number-form" onsubmit="submitPhoneNumber(); return false;" style=" display: block;">

              <!-- Phone Number -->
              <div class="row mb-3">
                <label for="phone-number" class="col-sm-3 col-form-label">Phone Number &#127470;&#127465; <small class="text-danger">*</small></label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" id="phone-number" name="phone_number" placeholder="62xxx / 0xxx" value="" autocomplete="off">
                  <div class="invalid-feedback"></div>
                  <div id="phoneNumberHelp" class="form-text">Only numbers are allowed</div>
                </div>
              </div>

              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-md py-2">Submit phone number</button>
              </div>

            </form>

            <div class="row" id="verification-section" style="display: none;">
              <div class="col">
                <div class="card border-0">
                  <div class="card-body p-0">

                    <div class="alert alert-warning fade show" role="alert">
                      Please <strong>click or scan one of the links below</strong> to verify your number, according to the WhatsApp platform you are using:
                    </div>

                    <ul class="list-group">

                      <li class="list-group-item py-3">
                        <div class="d-flex flex-md-row flex-column align-items-md-center">

                          <div id="wa-desktop-mobile-link-qrcode" class="qrcode-box blur me-md-4">
                            <div class="qr-code-container"></div>
                            <div class="qrcode-overlay text-black fw-bold d-flex flex-column align-items-center justify-content-center">
                              <i class="fa fa-eye-slash h2 mb-0"></i>
                              <label class="mt-1">QR Code</label>
                            </div>
                          </div>

                          <div class="flex-grow-1 d-flex flex-column justify-content-center">
                            <div class="fw-semibold my-2">WhatsApp Desktop & Mobile</div>
                            <a id="wa-desktop-mobile-link" href="javascript:void(0);" class="text-decoration-none text-break mb-2 d-block link-text" target="_blank" rel="noopener noreferrer" tabindex="-1"></a>
                            <button type="button" onclick="copyToClipboard('#verification-section #wa-desktop-mobile-link', 'Verification link for WhatsApp Desktop & Mobile has been successfully copied to the clipboard')" class="btn btn-sm btn-info w-100 py-2" tabindex="-1">
                              <i class="far fa-copy me-1"></i> Copy link (for WhatsApp Desktop & Mobile)
                            </button>
                          </div>

                        </div>
                      </li>

                      <li class="list-group-item py-3">
                        <div class="d-flex flex-md-row flex-column align-items-md-center">

                          <div id="wa-web-link-qrcode" class="qrcode-box blur me-md-4">
                            <div class="qr-code-container"></div>
                            <div class="qrcode-overlay text-black fw-bold d-flex flex-column align-items-center justify-content-center">
                              <i class="fa fa-eye-slash text-black h2 mb-0"></i>
                              <label class="mt-1">QR Code</label>
                            </div>
                          </div>

                          <div class="flex-grow-1 d-flex flex-column justify-content-center">
                            <div class="fw-semibold my-2">WhatsApp Web</div>
                            <a id="wa-web-link" href="javascript:void(0);" class="text-decoration-none text-break mb-2 d-block link-text" target="_blank" rel="noopener noreferrer" tabindex="-1"></a>
                            <button type="button" onclick="copyToClipboard('#verification-section #wa-web-link', 'Verification link for WhatsApp Web has been successfully copied to the clipboard')" class="btn btn-sm btn-info w-100 py-2" tabindex="-1">
                              <i class="far fa-copy me-1"></i> Copy link (for WhatsApp Web)
                            </button>
                          </div>

                        </div>
                      </li>

                    </ul>

                    <div class="alert alert-info fade show mt-3" role="alert">
                      After <strong>sending the message to GetContact</strong> and <strong>seeing two checkmarks</strong>, click the confirmation button below &#128071;&#127995;
                      <!-- <p class="text-center mt-1 mb-0 h2">&#128071;&#127995;&#128071;&#127995;&#128071;&#127995;</p> -->
                    </div>
                    <!-- <label class="fw-bold"></label> -->

                    <!-- Submit Button -->
                    <div class="d-grid gap-2">
                      <button id="confirm-verification-button" type="button" onclick="confirmVerification()" class="btn btn-primary btn-md py-2">Confirm Verification</button>
                    </div>

                  </div>
                </div>
              </div>
            </div>

            <div class="row" id="result-section" style="display: none;">
              <div class="col">
                <div class="card border-0">
                  <div class="card-body p-0">

                    <div class="alert alert-info fade show" role="alert">
                      Below are the <strong>final key</strong> and <strong>token</strong> linked to your verified WhatsApp number. <strong>Please make sure to copy and keep them somewhere safe</strong>. Thankyou.
                    </div>
                    <ul class="list-group">
                      <li class="list-group-item d-flex flex-column">
                        <div class="table-responsive">
                          <table class="table table-hover mb-0">
                            <tbody>
                              <tr>
                                <th class="align-middle" scope="row" style="width: 20%;">Client Device Id</th>
                                <td>
                                  <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center">
                                    <label class="text-break mb-2 mb-md-0 me-md-2" id="client-device-id">
                                      d5a8baa29eca7c2c
                                    </label>
                                    <button type="button" onclick="copyToClipboard('#result-section #client-device-id', 'Client Device Id has been successfully copied to the clipboard')" class="btn btn-sm btn-info">
                                      <i class="far fa-copy"></i> Copy
                                    </button>
                                  </div>
                                </td>
                              </tr>
                              <!-- <tr>
                                <th class="align-middle" scope="row">Email</th>
                                <td>
                                  <div>
                                    <label>
                                      user890170489212@gmail.com
                                    </label>
                                  </div>
                                </td>
                              </tr>
                              <tr>
                                <th class="align-middle" scope="row">Phone</th>
                                <td>+6281234567890</td>
                              </tr> -->
                              <tr>
                                <th scope="row">Final Key</th>
                                <td>
                                  <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center">
                                    <label class="text-break mb-2 mb-md-0 me-md-2" id="final-key">
                                      3a2adf118bb013b99f492c8419592cd7940cdb344320e02aa74a1b877094886a
                                    </label>
                                    <button type="button" onclick="copyToClipboard('#result-section #final-key', 'Final key has been successfully copied to the clipboard')" class="btn btn-sm btn-info">
                                      <i class="far fa-copy"></i> Copy
                                    </button>
                                  </div>
                                </td>
                              </tr>
                              <tr>
                                <th scope="row">Token</th>
                                <td>
                                  <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center">
                                    <label class="text-break mb-2 mb-md-0 me-md-2" id="token">
                                      bxuIUB07327befeadca081fdc2d97d39dd06734a623dc37a172572371f
                                    </label>
                                    <button type="button" onclick="copyToClipboard('#result-section #token', 'Token has been successfully copied to the clipboard')" class="btn btn-sm btn-info">
                                      <i class="far fa-copy"></i> Copy
                                    </button>
                                  </div>
                                </td>
                              </tr>
                              <tr>
                                <th scope="row">Validation Date</th>
                                <td id="validation-date">2025-05-29 11:40:45</td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </li>
                      <li class="list-group-item d-flex flex-column d-none">
                        <div class="fw-semibold mb-2">Final Key</div>
                        <label id="final-key" class="text-break mb-2" style="user-select: text;">
                          3a2adf118bb013b99f492c8419592cd7940cdb344320e02aa74a1b877094886a
                        </label>
                        <button type="button" onclick="copyToClipboard('#result-section #final-key')" class="btn btn-sm btn-info w-100 py-2">
                          <i class="far fa-copy me-1"></i> Copy Final Key
                        </button>
                      </li>
                      <li class="list-group-item d-flex flex-column d-none">
                        <div class="fw-semibold mb-2">Token</div>
                        <label id="token" class="text-break mb-2" style="user-select: text;">
                          bxuIUB07327befeadca081fdc2d97d39dd06734a623dc37a172572371f
                        </label>
                        <button type="button" onclick="copyToClipboard('#result-section #token')" class="btn btn-sm btn-info w-100 py-2">
                          <i class="far fa-copy me-1"></i> Copy Token
                        </button>
                      </li>
                    </ul>

                  </div>

                </div>
              </div>
            </div>

            <hr />

            <div class="text-center my-4" style="display: block;">
              <div class="d-grid gap-2">
                <button type="button" class="btn btn-outline-warning" onclick="confirmReload()" tabindex="-1">
                  Click this button to reload the page and refill the form
                </button>
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

  <!-- Qrcode.js -->
  <script src="<?php echo base_url("/public/js/qrcode.min.js") ?>"></script>

  <!-- Custom JS -->
  <script src="<?php echo base_url("/public/js/custom.js") ?>"></script>

  <script>
    function copyToClipboard(elementId, successMessage = "Text copied to clipboard") {
      var element = document.querySelector(elementId);

      if (navigator.clipboard && /^https?:$/.test(window.location.protocol)) {
        // Gunakan navigator.clipboard jika tersedia dan protokol adalah HTTP atau HTTPS
        navigator.clipboard.writeText(element.textContent.trim())
          .then(() => {
            console.log(`Text copied to clipboard (https): ${element.textContent}`);
            new Notyf(notyfOption).success(successMessage);
          })
          .catch((error) => {
            console.error('Gagal menyalin teks: ', error);
            new Notyf(notyfOption).error("Failed to copy text");
          });
      } else {
        // Fallback jika navigator.clipboard tidak tersedia atau protokol bukan HTTP/HTTPS
        var tempTextArea = document.createElement("textarea");
        tempTextArea.value = element.textContent.trim();
        document.body.appendChild(tempTextArea);
        tempTextArea.select();
        document.execCommand("copy");
        document.body.removeChild(tempTextArea);

        console.log(`Text copied to clipboard (http): ${element.textContent.trim()}`);
        new Notyf(notyfOption).success(successMessage);
      }
    }

    function confirmOpenLink(event, url) {
      event.preventDefault();

      Swal.fire({
        title: 'Are you sure?',
        text: "You are about to open an external WhatsApp link.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes',
        cancelButtonText: 'Cancel',
        focusCancel: true
      }).then((result) => {
        if (result.isConfirmed) {
          window.open(url, '_blank');
        }
      });
    }

    function confirmReload() {
      Swal.fire({
        title: 'Are you sure?',
        text: "Reloading the page will clear the form!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'Cancel',
        focusCancel: true
      }).then((result) => {
        if (result.isConfirmed) {
          location.reload();
        }
      });
    }

    async function getVerificationCode() {
      const formData = {
        data: $("#temp-form-data").val(),
      };

      console.log(formData);

      let timerInterval;
      let timeLeft = 25; // durasi countdown dalam detik
      let ajaxCompleted = false;
      let ajaxFailed = false;
      let ajaxRequest = null; // untuk menyimpan request ajax

      const swalTimer = Swal.fire({
        title: 'Retrieving verification code...',
        html: `Please wait for <b>${timeLeft}</b> seconds`,
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
          const timerElement = Swal.getHtmlContainer().querySelector('b');
          timerInterval = setInterval(() => {
            timeLeft--;
            timerElement.textContent = timeLeft;
            if (timeLeft <= 0) {
              clearInterval(timerInterval);
              Swal.close(); // tutup swal countdown
            }
          }, 1000);
        },
        willClose: async () => {
          clearInterval(timerInterval);
          if (!ajaxCompleted && !ajaxFailed) {
            if (ajaxRequest) {
              ajaxRequest.abort();
            }
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Connection timeout. Please try again later. 1',
              confirmButtonText: 'OK'
            });

          }
        }
      });

      try {
        ajaxRequest = $.ajax({
          url: `<?= base_url("api/getcontact/credentials/generate?phase=2") ?>`,
          type: "POST",
          contentType: "application/json",
          timeout: 30000,
          data: JSON.stringify(formData),
          dataType: "json",
        });

        const response = await ajaxRequest; // tunggu ajax

        if (ajaxFailed) return false; // kalau sudah di-abort, hentikan di sini

        ajaxCompleted = true; // ajax sukses

        Swal.close();

        console.log(response);

        if (!response.data) {
          throw new Error("Invalid data field");
        }

        if (!response.verificationCode) {
          throw new Error("Invalid verification code");
        }

        $("#temp-form-data").val(response.data);

        const deeplinkWhatsappDesktopMobile = `https://api.whatsapp.com/send/?phone=%2B447990653714&type=phone_number&app_absent=0&text=${response.verificationCode}`;
        new QRCode(document.querySelector("#wa-desktop-mobile-link-qrcode .qr-code-container"), {
          text: deeplinkWhatsappDesktopMobile,
          width: 128,
          height: 128,
          colorDark: "#000000",
          colorLight: "#ffffff",
          correctLevel: QRCode.CorrectLevel.H
        });
        $("#wa-desktop-mobile-link").attr("href", deeplinkWhatsappDesktopMobile).text(deeplinkWhatsappDesktopMobile);
        $("#wa-desktop-mobile-link-qrcode").on("click", function() {
          $(this).toggleClass("active blur");
        });

        const deeplinkWhatsappWeb = `https://web.whatsapp.com/send?phone=447990653714&text=${response.verificationCode}`;
        new QRCode(document.querySelector("#wa-web-link-qrcode .qr-code-container"), {
          text: deeplinkWhatsappWeb,
          width: 128,
          height: 128,
          colorDark: "#000000",
          colorLight: "#ffffff",
          correctLevel: QRCode.CorrectLevel.H
        });
        $("#wa-web-link").attr("href", deeplinkWhatsappWeb).text(deeplinkWhatsappWeb);
        $("#wa-web-link-qrcode").on("click", function() {
          $(this).toggleClass("active blur");
        });

        $('#phone-number-form').fadeOut(100, function() {
          $(this).remove();
          $('#verification-section').fadeIn(100);
        });

        return true;
      } catch (xhr) {
        ajaxFailed = true; // set gagal supaya Swal tidak override

        console.error(xhr.responseText);
        console.error(xhr.status);
        console.error(xhr.statusText);

        Swal.close();

        let message = "An error occurred"; // default

        if (xhr.statusText === 'abort') {
          message = "Request was cancelled due to timeout.";
        } else {
          try {
            const response = JSON.parse(xhr.responseText);
            message = response.message || message;
          } catch (e) {
            console.warn('Response is not valid JSON:', e);
          }
        }

        await Swal.fire({
          icon: 'error',
          title: 'Error',
          html: `<h4>Failed to retrieve verification code</h4><small><i>${xhr.status} - ${message}</i></small>`,
          confirmButtonText: 'OK'
        });

        $("#phone-number-form button[type='submit']").prop("disabled", false);

        return false;
      }
    }

    async function submitPhoneNumber() {
      let isPhoneNumberValid = true;
      const $phoneInput = $("#phone-number");
      const phoneNumber = $phoneInput.val();
      const $phoneFeedback = $phoneInput.next("div.invalid-feedback");

      $phoneInput.removeClass("is-invalid is-valid");

      if (!phoneNumber) {
        $phoneInput.addClass("is-invalid");
        $phoneFeedback.text("Phone number is invalid");
        isPhoneNumberValid = false;
      } else {
        const digitsOnly = str => /^[0-9]+$/.test(str);

        if (!digitsOnly(phoneNumber)) {
          $phoneInput.addClass("is-invalid");
          $phoneFeedback.text("Only digits are allowed in phone number!");
          isPhoneNumberValid = false;
        } else if (!(phoneNumber.startsWith("0") || phoneNumber.startsWith("62"))) {
          $phoneInput.addClass("is-invalid");
          $phoneFeedback.text("Phone number prefix is invalid");
          isPhoneNumberValid = false;
        }
      }

      if (isPhoneNumberValid) {
        $phoneInput.addClass("is-valid");
        $phoneFeedback.text("");
      } else {
        $phoneInput.removeClass("is-valid");
      }

      // Final Decision
      if (!isPhoneNumberValid) {
        new Notyf(notyfOption).error("Please check the form");
        return false;
      }

      if ((await Swal.fire({
          title: 'Are you sure?',
          text: 'You are about to submit the form.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Yes',
        })).isConfirmed) {
        console.log('Form submitted!');

        let timerInterval;
        let timeLeft = 55; // durasi countdown dalam detik
        let ajaxCompleted = false;
        let ajaxFailed = false;
        let ajaxRequest = null; // untuk menyimpan request ajax

        const swalTimer = Swal.fire({
          title: 'Processing phone number...',
          html: `Please wait for <b>${timeLeft}</b> seconds`,
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
            const timerElement = Swal.getHtmlContainer().querySelector('b');
            timerInterval = setInterval(() => {
              timeLeft--;
              timerElement.textContent = timeLeft;
              if (timeLeft <= 0) {
                clearInterval(timerInterval);
                Swal.close(); // tutup swal countdown
              }
            }, 1000);
          },
          willClose: async () => {
            clearInterval(timerInterval);
            if (!ajaxCompleted && !ajaxFailed) {
              // kalau AJAX belum selesai saat timeout, cancel
              if (ajaxRequest) {
                ajaxRequest.abort();
              }
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Connection timeout. Please try again later.',
                confirmButtonText: 'OK'
              });
            }
          }
        });

        console.log("lanjut");

        $("#phone-number-form button[type='submit']").prop("disabled", true);

        const formData = {
          phoneNumber: $phoneInput.val(),
        };

        console.log(formData);

        try {
          ajaxRequest = $.ajax({
            url: `<?= base_url("api/getcontact/credentials/generate?phase=1") ?>`,
            type: "POST",
            contentType: "application/json",
            timeout: 60000,
            data: JSON.stringify(formData),
            dataType: "json"
          });

          const response = await ajaxRequest; // await di sini

          ajaxCompleted = true;
          if (!ajaxFailed) {
            Swal.close();
          }

          console.log(response);

          if (!response.data) {
            await Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Invalid data field',
            });
            $("#phone-number-form button[type='submit']").prop("disabled", false);
            return;
          }

          $("#temp-form-data").val(response.data);
          console.log("Phone number submitted!");

          if (!(await getVerificationCode())) {
            console.log("selesai checking getvercode gagal");
            $("#phone-number-form button[type='submit']").prop("disabled", false);
            return;
          }

          console.log("selesai checking getvercode berhasil");

        } catch (xhr) {
          ajaxFailed = true;
          Swal.close();

          console.error(xhr.responseText);

          let message = "An error occurred"; // default

          if (xhr.statusText === 'abort') {
            message = "Request was cancelled due to timeout.";
          } else {
            try {
              const response = JSON.parse(xhr.responseText);
              message = response.message || message;
            } catch (e) {
              console.warn('Response is not valid JSON:', e);
            }
          }

          await Swal.fire({
            icon: 'error',
            title: 'Error',
            html: `<h4>Failed to submit phone number</h4><small><i>${xhr.status} - ${message}</i></small>`,
            confirmButtonText: 'OK'
          });

          $("#phone-number-form button[type='submit']").prop("disabled", false);
        }

      } else {
        console.log('Submission cancelled.');
      }

      return;
    }

    async function confirmVerification() {
      console.log("Confirm verification clicked!");

      if ((await Swal.fire({
          title: 'Verification Check',
          text: 'Have you sent the message to GetContact and seen two checkmarks?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes',
        })).isConfirmed) {
        console.log('Form submitted!');

        let timerInterval;
        let timeLeft = 15; // durasi countdown dalam detik
        let ajaxCompleted = false;
        let ajaxFailed = false;
        let ajaxRequest = null; // untuk menyimpan request ajax

        const swalTimer = Swal.fire({
          title: 'Confirming verification...',
          html: `Please wait for <b>${timeLeft}</b> seconds`,
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
            const timerElement = Swal.getHtmlContainer().querySelector('b');
            timerInterval = setInterval(() => {
              timeLeft--;
              timerElement.textContent = timeLeft;
              if (timeLeft <= 0) {
                clearInterval(timerInterval);
                Swal.close(); // tutup swal countdown
              }
            }, 1000);
          },
          willClose: async () => {
            clearInterval(timerInterval);
            if (!ajaxCompleted && !ajaxFailed) {
              // kalau AJAX belum selesai saat timeout, cancel
              if (ajaxRequest) {
                ajaxRequest.abort();
              }
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Connection timeout. Please try again later.',
                confirmButtonText: 'OK'
              });
            }
          }
        });

        $("#verification-section #confirm-verification-button").prop("disabled", true);

        const formData = {
          data: $("#temp-form-data").val(),
        };

        console.log(formData);

        ajaxRequest = $.ajax({
          url: `<?= base_url("api/getcontact/credentials/generate?phase=3") ?>`,
          type: "POST",
          contentType: "application/json",
          timeout: 20000,
          data: JSON.stringify(formData),
          dataType: "json",
          success: function(response) {
            ajaxCompleted = true;

            if (!ajaxFailed) {
              Swal.close();
            }

            console.log(response);

            if (!response.data) {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Invalid data field',
              });
              $("#verification-section #confirm-verification-button").prop("disabled", false);
              return;
            }

            if (!response.data.finalKey) {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Invalid final key field',
              });
              $("#verification-section #confirm-verification-button").prop("disabled", false);
              return;
            }

            if (!response.data.token) {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Invalid token field',
              });
              $("#verification-section #confirm-verification-button").prop("disabled", false);
              return;
            }

            if (!response.data.validationDate) {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Invalid validation date field',
              });
              $("#verification-section #confirm-verification-button").prop("disabled", false);
              return;
            }

            $("#temp-form-data").val(null);

            Swal.close();

            console.log("Phone number verified!");
            console.log("Client Device ID: " + response.data.clientDeviceId);
            console.log("Final Key: " + response.data.finalKey);
            console.log("Token: " + response.data.token);
            console.log("Validation Date: " + response.data.validationDate);

            $('#verification-section').fadeOut(100, function() {
              $(this).remove();
              $("#result-section #client-device-id").text(response.data.clientDeviceId);
              $("#result-section #final-key").text(response.data.finalKey);
              $("#result-section #token").text(response.data.token);
              $("#result-section #validation-date").text(response.data.validationDate);
              $('#result-section').fadeIn(100);
            });
          },
          error: function(xhr, status, error) {

            ajaxFailed = true;
            console.error(xhr.responseText);
            console.error(status);
            console.error("Error fetching data: " + error);
            Swal.close();

            let message = "An error occurred"; // default

            if (xhr.statusText === 'abort') {
              message = "Request was cancelled due to timeout.";
            } else {
              try {
                const response = JSON.parse(xhr.responseText);
                message = response.message || message;
              } catch (e) {
                console.warn('Response is not valid JSON:', e);
              }
            }

            Swal.fire({
              icon: 'error',
              title: 'Error',
              html: `<h4>Verification check failed</h4><small><i>${xhr.status} - ${message}</i></small>`,
              confirmButtonText: 'OK'
            });

            $("#verification-section #confirm-verification-button").prop("disabled", false);
          },
        });

      } else {
        console.log('Submission cancelled.');
      }

      return;
    }
  </script>
</body>

</html>
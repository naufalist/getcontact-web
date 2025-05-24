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

if (is_logged_in()) {
  header("Location: " . URL_PREFIX . "/dashboard/credentials/manage");
  exit();
}

switch ($_SERVER["REQUEST_METHOD"]) {
  case "POST":

    #region Validate CSRF token

    $csrf_token = $_POST["csrf_token"] ?? "";

    if (empty($csrf_token) || !isset($csrf_token) || !csrf_token_validate($csrf_token)) {
      $_SESSION["error"] = "Invalid CSRF token. Please try again.";
      header("Location: " . URL_PREFIX . "/dashboard");
      exit();
    }

    #endregion

    #region Validate username

    $username = $_POST["username"] ?? "";

    if (empty($username) || !preg_match("/^[a-zA-Z0-9_]{3,50}$/", $username)) {
      $error = "Invalid username";
      break;
    }

    #endregion

    #region Validate password

    $password = $_POST["password"] ?? "";

    if (empty($password)) {
      $error = "Invalid password";
      break;
    }

    #endregion

    #region Login process

    $pdo_statement = $pdo->prepare("SELECT id, username, password FROM administrators WHERE username = :username");
    $pdo_statement->execute(["username" => $username]);
    $admin = $pdo_statement->fetch();

    if (!$admin || !password_verify($password, $admin["password"])) {
      $error = "Username or password is invalid";
      break;
    }

    $_SESSION["admin_id"] = $admin["id"];
    $_SESSION["last_activity"] = time();
    header("Location: " . URL_PREFIX . "/dashboard/credentials/manage");
    exit();

    #endregion

    break;
}

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
  <div class="container py-3">

    <!-- Form Section -->
    <div class="row justify-content-center">
      <div class="col-12 col-md-9 col-lg-8 col-xl-7">

        <div class="card border-0">
          <div class="card-header d-none">
            Login
          </div>
          <div class="card-body">
            <form action="<?= base_url("/dashboard") ?>" method="POST">

              <!-- CSRF Token -->
              <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>" autocomplete="off">

              <!-- Username -->
              <div class="mb-3">
                <label for="username" class="form-label">Username <small class="text-danger">*</small></label>
                <input type="text" class="form-control" id="username" name="username" autocomplete="off" placeholder="Fill with your username" tabindex="1" required>
                <div class="invalid-feedback">
                  Username is required.
                </div>
              </div>

              <!-- Password -->
              <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center">
                  <label for="password" class="form-label">
                    Password <small class="text-danger">*</small>
                  </label>
                  <label class="form-label d-none">
                    <a id="forgot-password-btn" href="#" class="text-decoration-none">Forgot password?</a>
                  </label>
                </div>
                <input type="password" class="form-control" id="password" name="password" autocomplete="off" placeholder="Fill with your password" tabindex="2" required>
                <div class="invalid-feedback">
                  Password is required.
                </div>
              </div>

              <!-- Submit Button -->
              <div class="mb-3 d-grid">
                <button type="submit" id="login-button" class="btn btn-outline-primary mt-2 py-2" tabindex="3">Login</button>
              </div>

              <!-- Error Alert -->
              <?php if (!empty($_SESSION["error"])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <?= $_SESSION["error"];
                  unset($_SESSION["error"]); ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>

            </form>
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

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

  <!-- HTML2Canvas -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

  <!-- Notyf JS -->
  <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>

  <!-- Custom JS -->
  <script src="<?php echo base_url("/public/js/custom.js") ?>"></script>

  <?php if (!empty($error)): ?>
    <script>
      new Notyf(notyfOption).error("<?= $error ?>");
    </script>
  <?php endif; ?>
</body>

</html>
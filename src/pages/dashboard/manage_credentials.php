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

// CREATE or UPDATE
switch ($_SERVER["REQUEST_METHOD"]) {
  case "POST":

    $csrf_token = $_POST["csrf_token"] ?? "";

    if (empty($csrf_token) || !isset($csrf_token) || !csrf_token_validate($csrf_token)) {
      $_SESSION["error"] = "Invalid CSRF token. Please try again.";
      header("Location: " . URL_PREFIX . "/dashboard/credentials/manage");
      exit();
    }

    $id = $_POST["id"] ?? null;
    $description = $_POST["description"];
    $final_key = $_POST["final_key"];
    $token = $_POST["token"];
    $client_device_id = !empty($_POST["client_device_id"]) ? $_POST["client_device_id"] : null; // optional
    $user = "admin"; // Replace with session-based user if needed
    $now = date("Y-m-d H:i:s");

    if ($id) {

      // UPDATE: check if updated_at is still the same
      $pdo_statement = $pdo->prepare("SELECT updated_at FROM credentials WHERE id = ? AND deleted_at IS NULL");
      $pdo_statement->execute([$id]);
      $current = $pdo_statement->fetch(PDO::FETCH_ASSOC);

      if (!$current || $current["updated_at"] !== $_POST["updated_at"]) {
        // data has changed
        $_SESSION["error"] = "The data has been updated elsewhere. Please reload the page.";
        header("Location: " . URL_PREFIX . "/dashboard/credentials/manage");
        exit();
      }

      $pdo_statement = $pdo->prepare("UPDATE credentials SET description = ?, final_key = ?, token = ?, client_device_id = ?, updated_by = ?, updated_at = ? WHERE id = ?");

      $pdo_statement->bindValue(1, $description);
      $pdo_statement->bindValue(2, $final_key);
      $pdo_statement->bindValue(3, $token);
      $pdo_statement->bindValue(4, $client_device_id, is_null($client_device_id) ? PDO::PARAM_NULL : PDO::PARAM_STR);
      $pdo_statement->bindValue(5, $user);
      $pdo_statement->bindValue(6, $now);
      $pdo_statement->bindValue(7, $id);

      $pdo_statement->execute();
    } else {

      // CREATE
      $pdo_statement = $pdo->prepare("INSERT INTO credentials (description, final_key, token, client_device_id, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?)");

      $pdo_statement->bindValue(1, $description);
      $pdo_statement->bindValue(2, $final_key);
      $pdo_statement->bindValue(3, $token);
      $pdo_statement->bindValue(4, $client_device_id, is_null($client_device_id) ? PDO::PARAM_NULL : PDO::PARAM_STR);
      $pdo_statement->bindValue(5, $user);
      $pdo_statement->bindValue(6, $now);

      $pdo_statement->execute();
    }

    header("Location: " . URL_PREFIX . "/dashboard/credentials/manage");
    exit();

    break;
}

// DELETE (Soft Delete)
if (isset($_GET["delete"])) {
  $id = $_GET["delete"];
  $updated_at = $_GET["updated_at"];
  $user = "admin";
  $now = date("Y-m-d H:i:s");

  $pdo_statement = $pdo->prepare("SELECT updated_at FROM credentials WHERE id=? AND deleted_at IS NULL");
  $pdo_statement->execute([$id]);
  $current = $pdo_statement->fetch(PDO::FETCH_ASSOC);

  if (!$current || $current["updated_at"] !== $updated_at) {
    $_SESSION["error"] = "The data has been updated or deleted elsewhere.";
    header("Location: " . URL_PREFIX . "/dashboard/credentials/manage");
    exit();
  }

  $pdo_statement = $pdo->prepare("UPDATE credentials SET deleted_by=?, deleted_at=?, updated_at=? WHERE id=?");
  $pdo_statement->execute([$user, $now, $now, $id]);

  header("Location: " . URL_PREFIX . "/dashboard/credentials/manage");
  exit();
}

// Fetch Data
$pdo_statement = $pdo->query("SELECT * FROM credentials WHERE deleted_at IS NULL ORDER BY id DESC");
$data = $pdo_statement->fetchAll(PDO::FETCH_ASSOC);

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
      <a href="#" class="navbar-brand nav-link" tabindex="-1">GetContact</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a class="nav-link active" href="javascript:void(0);" tabindex="-1"><i class="fa fa-key"></i> Manage Credentials</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?php echo base_url("dashboard/captcha/verify") ?>" tabindex="-1"><i class="fa fa-check-circle"></i> Verify Captcha</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?php echo base_url("dashboard/credentials/generate") ?>" tabindex="-1"><i class="fa fa-key"></i> Generate Credentials</a>
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

    <!-- Table -->
    <div class="row justify-content-center">
      <div class="col-12">

        <?php if (!empty($_SESSION["error"])): ?>
          <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <?= $_SESSION["error"];
            unset($_SESSION["error"]); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <div class="d-grid gap-2 mb-3">
          <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#form-modal" onclick="openForm()">
            + Add Credential
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-hover" id="data-table">
            <thead>
              <tr>
                <th width="5%">ID</th>
                <th width="20%">Description</th>
                <th>Credential Data</th>
                <th width="15%">Header Data</th>
                <th width="20%">Created At/Updated At</th>
                <th width="10%">Action</th>
              </tr>
            </thead>
            <tbody style="user-select: text;">
              <?php foreach ($data as $row): ?>
                <tr class="text-break">
                  <td><?= htmlspecialchars($row["id"] ?? "-") ?></td>
                  <td><?= htmlspecialchars($row["description"] ?? "-") ?></td>
                  <td>
                    <dl class="row">
                      <dt class="col-12 text-muted small mb-1">Final Key</dt>
                      <dd class="col-12 mb-3"><?= htmlspecialchars($row["final_key"] ?? "-") ?></dd>
                      <dt class="col-12 text-muted small">Token</dt>
                      <dd class="col-12"><?= htmlspecialchars($row["token"] ?? "-") ?></dd>
                    </dl>
                  </td>
                  <td>
                    <dl class="row">
                      <dt class="col-12 text-muted small">Client Device ID</dt>
                      <dd class="col-12"><?= htmlspecialchars($row["client_device_id"] ?? "-") ?></dd>
                    </dl>
                  </td>
                  <td>
                    <?= htmlspecialchars($row["created_at"] ?? "-") ?><br /><small class="text-muted">admin</small><br /><br />
                    <?= htmlspecialchars($row["updated_at"] ?? "-") ?><br /><small class="text-muted">admin</small>
                  </td>
                  <td>
                    <button class="btn btn-warning btn-sm" onclick='openForm(<?= json_encode($row) ?>)'>Edit</button>
                    <a href="?delete=<?= $row["id"] ?>&updated_at=<?= urlencode($row["updated_at"]) ?>" onclick="return confirm('Yakin ingin hapus data ini?')" class="btn btn-danger btn-sm">Delete</a>
                  </td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>

      </div>
    </div>

  </div>

  <!-- Modal Form -->
  <div class="modal fade" id="form-modal" tabindex="-1">
    <div class="modal-dialog">
      <form method="post" class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Credential Form</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>" autocomplete="off">
          <input type="hidden" name="id" id="form-id" />
          <input type="hidden" name="updated_at" id="form-updated-at" />
          <div class="mb-3">
            <label>Description <small class="text-danger">*</small></label>
            <input type="text" name="description" id="form-description" class="form-control" autocomplete="off" required />
            <div id="form-description-help" class="form-text">Fill with any text that describe your GetContact account</div>
          </div>
          <div class="mb-3">
            <label>Final Key <small class="text-danger">*</small></label>
            <textarea name="final_key" id="form-final_key" class="form-control" autocomplete="off" required></textarea>
          </div>
          <div class="mb-3">
            <label>Token <small class="text-danger">*</small></label>
            <textarea name="token" id="form-token" class="form-control" autocomplete="off" required></textarea>
          </div>
          <div class="mb-3">
            <label>Client Device ID</label><span class="badge bg-soft-primary ms-1 mb-1" data-bs-theme="light">Header: x-client-device-id</span>
            <input type="text" name="client_device_id" id="form-client_device_id" class="form-control" autocomplete="off" />
            <div id="device-id-help" class="form-text">Leave it blank to use the default constant. If you're creating a credential using the auto-generator, it's recommended to use the generated client device ID instead.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Save</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
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
    $(document).ready(() => $("#data-table").DataTable());

    function openForm(data = null) {
      const formModalEl = document.getElementById("form-modal");
      const modal = bootstrap.Modal.getOrCreateInstance(formModalEl); // make sure only have one instance

      // Reset form
      formModalEl.querySelector("form").reset();
      $("#form-id").val("");
      $("#form-updated-at").val("");

      if (data) {
        $("#form-id").val(data.id);
        $("#form-description").val(data.description);
        $("#form-final_key").val(data.final_key);
        $("#form-token").val(data.token);
        $("#form-client_device_id").val(data.client_device_id);
        $("#form-updated-at").val(data.updated_at);
      }

      modal.show();

      formModalEl.addEventListener("hidden.bs.modal", () => {
        document.body.classList.remove("modal-open");
        const backdrops = document.querySelectorAll(".modal-backdrop");
        backdrops.forEach(el => el.remove());
      }, {
        once: true
      });
    }
  </script>
</body>

</html>
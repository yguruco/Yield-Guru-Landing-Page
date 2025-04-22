<?php
/**********************************************
 *  HANDLE AJAX SUBMISSIONS
 **********************************************/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database credentials (adjust as needed)
    $host   = 'localhost';
    $dbUser = 'YieldGuru';
    $dbPass = 'Kcj034ralio#';
    $dbName = 'YieldGuru';

    // Attempt DB connection
    try {
        $dsn = "mysql:host=$host;dbname=$dbName;charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Database connection failed: ' . $e->getMessage()
        ]);
        exit;
    }

    /**
     * 1) CHECK IF EMAIL EXISTS & PASSWORD EMPTY (Step 1)
     */
    if (isset($_POST['checkEmailAjax']) && $_POST['checkEmailAjax'] === '1') {
        $email = $_POST['email'] ?? '';

        // Check if email exists in tbl_accounts and if Password is still empty
        try {
            $stmt = $pdo->prepare("SELECT Password FROM tbl_accounts WHERE Email = :email");
            $stmt->execute([':email' => $email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                // Email not found at all
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Email not found.'
                ]);
                exit;
            }

            // Email found, check if password is already set
            if (!empty($row['Password'])) {
                // Password is NOT empty => user already set a password
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'A password is already set for this email.'
                ]);
                exit;
            }

            // If we get here: Email found, password is empty
            echo json_encode([
                'status'  => 'success',
                'message' => 'Email found. Password is empty.'
            ]);
            exit;

        } catch (PDOException $e) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'DB Error: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * 2) SET PASSWORD (Step 2) — storing in plain text
     */
    if (isset($_POST['setPasswordAjax']) && $_POST['setPasswordAjax'] === '1') {
        $email    = $_POST['email']    ?? '';
        $password = $_POST['password'] ?? '';

        // Store the password as plain text
        $plainPassword = $password;

        try {
            // Update the password in tbl_accounts (no hashing, just plain text)
            $stmt = $pdo->prepare("UPDATE tbl_accounts
                                   SET Password = :password
                                   WHERE Email = :email
                                     AND Password = ''");
            $stmt->execute([
                ':password' => $plainPassword,
                ':email'    => $email
            ]);

            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'status'  => 'success',
                    'message' => 'Password set successfully.'
                ]);
                exit;
            } else {
                // Either email not found, or password wasn’t empty
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Failed to set password. Email not found or already set.'
                ]);
                exit;
            }

        } catch (PDOException $e) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to update password: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    exit; // End of POST logic
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title>Set Your Password - YieldGuru</title>

  <!-- Favicon -->
  <link rel="icon" href="favicon.png" type="image/png" />

  <!-- Bootstrap CSS -->
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
  />

  <!-- Google Fonts (if needed) -->
  <link
    href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
  />

  <style>
    /**************************************************
     *           BASE STYLES & CUSTOM VARS
     **************************************************/
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Jost', sans-serif;
    }
    :root {
      --primary: #4F1964;
      --primary-dark: #3a1249;
      --secondary: #F78930;
      --text-dark: #1F2937;
      --text-light: #6B7280;
    }

    /* Body with bottom padding to avoid overlap with fixed footer */
    body {
      line-height: 1.6;
      background: #f8f9fa;
      padding-bottom: 80px; /* Enough space so content isn't hidden under footer */
    }

    /**************************************************
     *                 HEADER
     **************************************************/
    .header {
      background: var(--primary);
      color: #fff;
      padding: 1rem;
      text-align: center;
    }
    .header h1 {
      margin: 0;
      font-size: 1.8rem;
    }

    /**************************************************
     *            MAIN CONTAINER / STEPS
     **************************************************/
    .container {
      max-width: 600px;
      margin: 2rem auto;
      background: #fff;
      border-radius: 8px;
      padding: 2rem;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .step-container {
      margin-top: 1.5rem;
    }
    .step {
      display: none;
    }
    .step.active {
      display: block;
    }

    .btn-primary {
      background: var(--primary);
      border: none;
      transition: background 0.3s;
    }
    .btn-primary:hover {
      background: var(--primary-dark);
    }

    .alert {
      margin-top: 1rem;
    }
    .form-group label {
      font-weight: 500;
      color: var(--text-dark);
    }

    /**************************************************
     *          FIXED FOOTER AT BOTTOM
     **************************************************/
    .footer {
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      background: var(--primary);
      color: #fff;
      text-align: center;
      padding: 1rem;
      z-index: 999;
    }
  </style>
</head>
<body>

  <!-- HEADER -->
  <div class="header">
    <h1>Set Your Password</h1>
  </div>

  <!-- MAIN CONTAINER -->
  <div class="container">
    <div class="step-container">
      <!-- STEP 1: Enter Email -->
      <div id="step1" class="step active">
        <h3>Step 1: Enter Your Email</h3>
        <p>Please enter the email address you used to sign up.</p>
        <div class="form-group">
          <label for="step1Email">Email</label>
          <input
            type="email"
            class="form-control"
            id="step1Email"
            name="step1Email"
            required
          />
        </div>
        <button
          id="btnCheckEmail"
          class="btn btn-primary mt-3"
        >
          Next
        </button>
        <div id="step1Alert"></div>
      </div>

      <!-- STEP 2: Set Password -->
      <div id="step2" class="step">
        <h3>Step 2: Set Your Password</h3>
        <p>Enter and confirm your new password.</p>
        <div class="form-group">
          <label for="setPassword">New Password</label>
          <input
            type="password"
            class="form-control"
            id="setPassword"
            name="setPassword"
            required
          />
        </div>
        <div class="form-group">
          <label for="confirmPassword">Confirm Password</label>
          <input
            type="password"
            class="form-control"
            id="confirmPassword"
            name="confirmPassword"
            required
          />
        </div>
        <button
          id="btnSetPassword"
          class="btn btn-primary mt-3"
        >
          Set Password
        </button>
        <div id="step2Alert"></div>
      </div>
    </div>
  </div>

  <!-- FIXED FOOTER AT BOTTOM -->
  <div class="footer">
    <p>&copy; 2025 YieldGuru. All rights reserved.</p>
  </div>

  <!-- JavaScript (jQuery, Bootstrap JS) -->
  <script
    src="https://code.jquery.com/jquery-3.5.1.min.js"
    integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0="
    crossorigin="anonymous"
  ></script>
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"
  ></script>

  <script>
    // Elements
    const step1         = document.getElementById('step1');
    const step2         = document.getElementById('step2');
    const step1Alert    = document.getElementById('step1Alert');
    const step2Alert    = document.getElementById('step2Alert');
    const step1Email    = document.getElementById('step1Email');
    const setPasswordEl = document.getElementById('setPassword');
    const confirmPassEl = document.getElementById('confirmPassword');

    // Store the email so we can use it in step2
    let globalEmail = '';

    /**********************************************
     * STEP 1: CHECK EMAIL
     **********************************************/
    document.getElementById('btnCheckEmail').addEventListener('click', function() {
      const emailValue = step1Email.value.trim();
      if (!emailValue) {
        step1Alert.innerHTML = `<div class="alert alert-warning">Please enter your email.</div>`;
        return;
      }
      // Clear any prior alerts
      step1Alert.innerHTML = '';

      const formData = new FormData();
      formData.append('checkEmailAjax', '1');
      formData.append('email', emailValue);

      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          // Email found, password is empty => proceed to step2
          globalEmail = emailValue; // store so we can use it in step2
          step1.classList.remove('active');
          step2.classList.add('active');
        } else {
          // Some error
          step1Alert.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }
      })
      .catch(err => {
        console.error(err);
        step1Alert.innerHTML = `<div class="alert alert-danger">An error occurred.</div>`;
      });
    });

    /**********************************************
     * STEP 2: SET PASSWORD (plain text)
     **********************************************/
    document.getElementById('btnSetPassword').addEventListener('click', function() {
      const passwordVal = setPasswordEl.value.trim();
      const confirmVal  = confirmPassEl.value.trim();

      // Client-side check
      if (!passwordVal || !confirmVal) {
        step2Alert.innerHTML = `<div class="alert alert-warning">Please fill all fields.</div>`;
        return;
      }
      if (passwordVal !== confirmVal) {
        step2Alert.innerHTML = `<div class="alert alert-warning">Passwords do not match.</div>`;
        return;
      }
      step2Alert.innerHTML = '';

      // AJAX to set password as plain text
      const formData = new FormData();
      formData.append('setPasswordAjax', '1');
      formData.append('email', globalEmail);
      formData.append('password', passwordVal);

      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          step2Alert.innerHTML = `
            <div class="alert alert-success">
              Password set successfully! Redirecting...
            </div>
          `;
          // Disable fields
          setPasswordEl.disabled = true;
          confirmPassEl.disabled = true;
          document.getElementById('btnSetPassword').disabled = true;

          // Redirect to yieldguru.network after 2 seconds
          setTimeout(() => {
            window.location.href = "https://yieldguru.network/";
          }, 2000);

        } else {
          step2Alert.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
        }
      })
      .catch(err => {
        console.error(err);
        step2Alert.innerHTML = `<div class="alert alert-danger">An error occurred.</div>`;
      });
    });
  </script>

</body>
</html>

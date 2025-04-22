<?php
session_start(); // <-- Start session for login/logout

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Requires PHPMailer in the same directory:
//   index.php
//   PHPMailer/src/Exception.php
//   PHPMailer/src/PHPMailer.php
//   PHPMailer/src/SMTP.php
// require __DIR__ . '/PHPMailer/src/Exception.php';
// require __DIR__ . '/PHPMailer/src/PHPMailer.php';
// require __DIR__ . '/PHPMailer/src/SMTP.php';

/**********************************************
 *  LOGOUT HANDLER
 **********************************************/
if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    // Clear session and refresh
    session_destroy();
    // Reload page (remove ?logout=1 from the URL)
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

/**********************************************
 *  CHECK IF USER IS LOGGED IN
 **********************************************/
$loggedIn = false;
$userName = '';
if (!empty($_SESSION['userLogged']) && !empty($_SESSION['userName'])) {
    $loggedIn = true;
    $userName = $_SESSION['userName'];
}

/**********************************************
 *  HANDLE AJAX SUBMISSIONS
 **********************************************/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database credentials
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

    /*
     * Helper function to send an HTML email via PHPMailer
     */
    function sendHtmlEmail($toEmail, $subject, $bodyHTML, $fromEmail='hello@yieldguru.network', $fromName='YieldGuru') {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'mail.yieldguru.network';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'hello@yieldguru.network';
            $mail->Password   = 'Kcj034ralio#';
            $mail->SMTPSecure = 'ssl';
            $mail->Port       = 465;

            $mail->CharSet  = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isHTML(true);

            // Sender info
            $mail->setFrom($fromEmail, $fromName);
            // Recipient
            $mail->addAddress($toEmail);

            $mail->Subject = $subject;
            $mail->Body    = $bodyHTML;

            $mail->send();
        } catch (Exception $e) {
            // For simplicity, ignoring errors in this example
        }
    }

    /*
     * 1) HERO FORM SUBMISSION (Email + InvestmentType)
     *    - Insert into tbl_accounts (for lead capture)
     *    - Send welcome email with link to set password
     *    - Also send an alert email to eliud@yieldguru.co
     */
    if (isset($_POST['heroAjax']) && $_POST['heroAjax'] === '1') {
        $heroEmail  = $_POST['heroEmail']  ?? '';
        $investment = $_POST['heroOption'] ?? '';

        // Convert short option to full name
        $investmentName = '';
        if ($investment === 'EV') {
            $investmentName = 'Electric Vehicles';
        } elseif ($investment === 'Charging') {
            $investmentName = 'Charging Stations';
        } else {
            $investmentName = $investment; // fallback if unknown
        }

        // 1a) Check if email already exists in tbl_accounts
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_accounts WHERE Email = :email");
            $stmt->execute([':email' => $heroEmail]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'This email is already registered.'
                ]);
                exit;
            }

            // 1b) Insert into tbl_accounts
            $sql = "INSERT INTO tbl_accounts 
                      (Name, Email, PhoneNumber, Country, Password, Telegram, InvestmentType)
                    VALUES
                      (:Name, :Email, :PhoneNumber, :Country, :Password, :Telegram, :InvestmentType)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':Name'           => '',
                ':Email'          => $heroEmail,
                ':PhoneNumber'    => '',
                ':Country'        => '',
                ':Password'       => '',   // blank because user hasn't set password yet
                ':Telegram'       => 'no',
                ':InvestmentType' => $investmentName
            ]);

            // 1c) Send welcome email
            $subject  = "Welcome to YieldGuru!";
            $bodyHTML = "
                <html>
                <head>
                  <style>
                    .email-container {
                      font-family: Arial, sans-serif;
                      padding: 20px;
                      max-width: 600px;
                      margin: auto;
                      background: #f8f9fa;
                      border-radius: 6px;
                    }
                    .email-header {
                      text-align: center;
                      margin-bottom: 20px;
                    }
                    .email-body {
                      background: #ffffff;
                      padding: 20px;
                      border-radius: 6px;
                    }
                    .title {
                      color: #4F1964;
                    }
                    .btn-link {
                      display: inline-block;
                      padding: 10px 20px;
                      margin-top: 20px;
                      background: #4F1964;
                      color: #fff;
                      text-decoration: none;
                      border-radius: 4px;
                    }
                  </style>
                </head>
                <body>
                  <div class='email-container'>
                    <div class='email-header'>
                      <img src='https://www.yieldguru.network/logo.png' alt='YieldGuru Logo' width='120' />
                    </div>
                    <div class='email-body'>
                      <h2 class='title'>Hello!</h2>
                      <p>Thank you for creating an account with <strong>YieldGuru</strong>. We're thrilled to have you on board.</p>
                      <p>You selected investment type: <strong>{$investmentName}</strong>.</p>
                      <p>Next steps:</p>
                      <ul>
                        <li>Click below to set your password.</li>
                        <li>Stay tuned for new electric bus or charging station investment opportunities.</li>
                        <li>Contact us if you have any questions.</li>
                      </ul>
                      <p>We appreciate you joining us in revolutionizing e-mobility investments!</p>
                      <p>
                        <a
                          class='btn-link'
                          href='https://yieldguru.network/set-password'
                          target='_blank'
                          style='color:#fff;'
                        >
                          Set Your Password
                        </a>
                      </p>
                    </div>
                  </div>
                </body>
                </html>
            ";
            sendHtmlEmail($heroEmail, $subject, $bodyHTML);

            // 1d) Alert admin
            $adminSubject = "New Account Created";
            $adminBody    = "A new account was just created for email: {$heroEmail}, investment type: {$investmentName}";
            sendHtmlEmail("eliud@yieldguru.co", $adminSubject, nl2br($adminBody));

            // 1e) Return success
            echo json_encode([
                'status'  => 'success',
                'message' => 'Account created successfully. Please check your email.'
            ]);
            exit;
        } catch (PDOException $e) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to insert data: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /*
     * 2) INVESTOR FORM SUBMISSION
     *    - Insert into tbl_investments
     *    - Send a welcome email
     */
    if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
        $full_name       = $_POST['name']            ?? '';
        $email           = $_POST['email']           ?? '';
        $phone           = $_POST['phone']           ?? '';
        $country         = $_POST['country']         ?? '';
        $password        = $_POST['password']        ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';
        $telegram        = $_POST['telegram']        ?? 'no';

        try {
            $sql = "INSERT INTO tbl_investments
                       (full_name, email, phone, country, password, confirm_password, telegram)
                    VALUES
                       (:full_name, :email, :phone, :country, :password, :confirm_password, :telegram)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':full_name'        => $full_name,
                ':email'            => $email,
                ':phone'            => $phone,
                ':country'          => $country,
                ':password'         => $password,         // not hashed for simplicity
                ':confirm_password' => $confirmPassword,  // stored for demonstration
                ':telegram'         => $telegram
            ]);

            // Send email to the investor
            $subject  = "Welcome to YieldGuru, $full_name!";
            $bodyHTML = "
                <html>
                <head>
                  <style>
                    .email-container {
                      font-family: Arial, sans-serif;
                      padding: 20px;
                      max-width: 600px;
                      margin: auto;
                      background: #f8f9fa;
                      border-radius: 6px;
                    }
                    .email-header {
                      text-align: center;
                      margin-bottom: 20px;
                    }
                    .email-body {
                      background: #ffffff;
                      padding: 20px;
                      border-radius: 6px;
                    }
                    .title {
                      color: #4F1964;
                    }
                    .btn-link {
                      display: inline-block;
                      padding: 10px 20px;
                      margin-top: 20px;
                      background: #4F1964;
                      color: #fff;
                      text-decoration: none;
                      border-radius: 4px;
                    }
                  </style>
                </head>
                <body>
                  <div class='email-container'>
                    <div class='email-header'>
                      <img src='https://www.yieldguru.network/logo.png' alt='YieldGuru Logo' width='120' />
                    </div>
                    <div class='email-body'>
                      <h2 class='title'>Hello, $full_name!</h2>
                      <p>Thank you for creating an account with <strong>YieldGuru</strong>. We're thrilled to have you on board.</p>
                      <p>Here are your next steps:</p>
                      <ul>
                        <li>Log in to your dashboard (once available) to see your fractional investments.</li>
                        <li>Stay tuned for new electric bus investment opportunities.</li>
                        <li>Contact us if you have any questions.</li>
                      </ul>
                      <p>We appreciate you joining us in revolutionizing e-mobility investments!</p>
                      <p>
                        <a class='btn-link' href='https://www.yieldguru.network' target='_blank' style='color:#fff;'>Visit Our Website</a>
                      </p>
                    </div>
                  </div>
                </body>
                </html>
            ";
            sendHtmlEmail($email, $subject, $bodyHTML);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Account created successfully.'
            ]);
            exit;
        } catch (PDOException $e) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to insert data: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    /*
     * 3) CONTACT FORM SUBMISSION
     */
    elseif (isset($_POST['contactAjax']) && $_POST['contactAjax'] === '1') {
        $contactName  = $_POST['contactName']  ?? '';
        $contactEmail = $_POST['contactEmail'] ?? '';
        $message      = $_POST['message']      ?? '';

        try {
            $sql = "INSERT INTO tbl_contact (contact_name, contact_email, message)
                    VALUES (:contact_name, :contact_email, :message)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':contact_name'  => $contactName,
                ':contact_email' => $contactEmail,
                ':message'       => $message
            ]);

            // Send email to the contact
            $subject  = "Thank you for contacting YieldGuru, $contactName!";
            $bodyHTML = "
                <html>
                <head>
                  <style>
                    .email-container {
                      font-family: Arial, sans-serif;
                      padding: 20px;
                      max-width: 600px;
                      margin: auto;
                      background: #f8f9fa;
                      border-radius: 6px;
                    }
                    .email-header {
                      text-align: center;
                      margin-bottom: 20px;
                    }
                    .email-body {
                      background: #ffffff;
                      padding: 20px;
                      border-radius: 6px;
                    }
                    .title {
                      color: #4F1964;
                    }
                    .btn-link {
                      display: inline-block;
                      padding: 10px 20px;
                      margin-top: 20px;
                      background: #4F1964;
                      color: #fff;
                      text-decoration: none;
                      border-radius: 4px;
                    }
                  </style>
                </head>
                <body>
                  <div class='email-container'>
                    <div class='email-header'>
                      <img src='https://www.yieldguru.network/logo.png' alt='YieldGuru Logo' width='120' />
                    </div>
                    <div class='email-body'>
                      <h2 class='title'>Hello, $contactName!</h2>
                      <p>Thank you for contacting <strong>YieldGuru</strong>. Here is what you told us:</p>
                      <blockquote style='border-left: 4px solid #ccc; padding-left: 10px; color: #666;'>
                        \"$message\"
                      </blockquote>
                      <p>We'll review your message and get back to you shortly. Meanwhile, feel free to visit our website or connect on social media.</p>
                      <p>
                        <a class='btn-link' href='https://www.yieldguru.network' target='_blank'>Visit Our Website</a>
                      </p>
                    </div>
                  </div>
                </body>
                </html>
            ";
            sendHtmlEmail($contactEmail, $subject, $bodyHTML);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Thank you for contacting us.'
            ]);
            exit;
        } catch (PDOException $e) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to insert contact: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    /*
     * 4) LOGIN AJAX SUBMISSION
     *    - Now checks tbl_accounts for email + plain-text password
     */
    elseif (isset($_POST['loginAjax']) && $_POST['loginAjax'] === '1') {
        $loginEmail    = $_POST['loginEmail']    ?? '';
        $loginPassword = $_POST['loginPassword'] ?? '';

        try {
            // Attempt to find matching user in tbl_accounts
            $stmt = $pdo->prepare("SELECT Id, Name, Email, Password
                                   FROM tbl_accounts
                                   WHERE Email = :email
                                     AND Password = :pass
                                   LIMIT 1");
            $stmt->execute([
                ':email' => $loginEmail,
                ':pass'  => $loginPassword // plain-text password (for demonstration)
            ]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                // Found user => set session
                $_SESSION['userLogged'] = true;
                // If Name is empty, fallback to Email
                $_SESSION['userName']   = $row['Name'] ? $row['Name'] : $row['Email'];

                echo json_encode([
                    'status'  => 'success',
                    'message' => 'Login successful.'
                ]);
                exit;
            } else {
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Invalid email or password.'
                ]);
                exit;
            }
        } catch (PDOException $e) {
            echo json_encode([
                'status'  => 'error',
                'message' => 'Login check failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <!-- Ensure responsive scaling on mobile -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>$BUS Community - Join the Movement</title>
  <link rel="icon" href="../favicon.png" type="image/png" />

  <!-- Bootstrap CSS -->
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
  />
  <!-- Google Fonts -->
  <link
    href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
  />
   <script>var neexa_xgmx_cc_wpq_ms = "9e02f591-7749-438c-a94e-77944fab6c9d";</script>
  <script src="https://chat-widget.neexa.ai/main.js?nonce=1737363838074.0928"></script>

  <!-- =========================
       PAGE STYLES
       ========================= -->
  <style>
    /* Global Styles */
    html, body {
      max-width: 100%;
      overflow-x: hidden;
    }
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
    body {
      line-height: 1.6;
    }
    a {
      text-decoration: none;
    }

    /* Button Styles (same as before) */
    .btn {
      background: var(--primary);
      color: white;
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 1rem;
      transition: all 0.3s;
      font-weight: 500;
    }
    .btn:hover {
      background: var(--primary-dark);
      transform: translateY(-1px);
    }

    /* Top Bar */
    .top-bar {
      background: var(--primary);
      color: white;
      padding: 0.5rem 0;
    }
    .top-bar-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1rem;
    }
    .social-icons, .contact-links {
      display: flex;
      gap: 1rem;
    }
    .top-bar a {
      color: white;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    /* Header & Navigation */
    .header {
      position: sticky;
      top: 0;
      width: 100%;
      background: white;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      z-index: 1000;
    }
    .nav {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.5rem 1rem;
      position: relative;
    }
    .logo img {
      height: 70px;
    }
    .nav-links {
      display: flex;
      align-items: center;
    }
    .nav-links a {
      margin-left: 2rem;
      color: var(--text-dark);
      transition: color 0.3s;
    }
    .nav-links a:hover {
      color: var(--primary);
    }
    .hamburger {
      display: none;
      flex-direction: column;
      justify-content: space-around;
      width: 24px;
      height: 24px;
      cursor: pointer;
    }
    .hamburger span {
      width: 100%;
      height: 3px;
      background: var(--primary);
      border-radius: 5px;
    }
    /* Fullscreen mobile nav */
    .nav-links.show {
      display: flex;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: #fff;
      flex-direction: column;
      align-items: flex-start;
      padding: 2rem;
      z-index: 2000;
      overflow-y: auto;
    }
    .nav-links.show a {
      margin: 1rem 0;
      font-size: 1.2rem;
    }
    .close-menu {
      position: absolute;
      top: 1rem;
      right: 1rem;
      font-size: 2rem;
      background: transparent;
      border: none;
      cursor: pointer;
      color: #000;
      z-index: 9999;
    }

    /* Hero Section */
    .hero {
      position: relative;
      height: 100vh;
      color: white;
      text-align: center;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    /* The background image layer using the provided image */
    .hero-bg {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-image: url('https://www.bev.co.ke/images/ankai-bus/image1.jpg');
      background-position: center;
      background-size: cover;
      background-repeat: no-repeat;
      z-index: 0;
    }
    .hero-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1;
    }
    .hero-content {
      position: relative;
      z-index: 2;
      max-width: 800px;
      padding: 0 1rem;
    }
    .hero h1 {
      font-size: 3rem;
      margin-bottom: 1.5rem;
      line-height: 1.2;
      font-weight: 600;
    }
    .hero p {
      font-size: 1.25rem;
      max-width: 700px;
      margin: 0 auto 2rem;
      opacity: 0.9;
    }

    /* Footer */
    .footer {
      background: var(--primary);
      color: white;
      text-align: center;
      padding: 2rem;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .top-bar {
        display: none;
      }
      .hamburger {
        display: flex;
      }
      .nav-links {
        display: none;
      }
      .hero h1 {
        font-size: 2rem;
      }
      .hero p {
        font-size: 1rem;
      }
    }
  </style>
</head>
<body>
  <!-- ============ TOP BAR ============ -->
  <div class="top-bar">
    <div class="top-bar-content">
      <div class="social-icons">
        <a href="mailto:eliud@yieldguru.co">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
               viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="20" height="16" x="2" y="4" rx="2"/>
            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
          </svg>
          eliud@yieldguru.co
        </a>
      </div>
      <div class="contact-links">
        <a href="https://wa.me/254705810850" target="_blank">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
               viewBox="0 0 448 512" fill="currentColor">
            <path d="M380.9 97.1C339.6 55.7 284.2 32 224.1 32 100.3
                     32 0 132.3 0 256c0 45.5 11.7 89.3 34 128L0
                     480l99.5-32.9c38.2 20.9 81.2 31.9 124.6
                     31.9h.1c123.7 0 224-100.3 224-224 .1-60.1-23.4-115.5-66.3-158.9zM224.1
                     438.6c-37.2 0-73.7-10-105.5-29l-7.5-4.5-62.6
                     20.7 20.8-61.1-4.9-7.7c-21.8-34.1-33.2-73.5-33.2-113.8
                     0-115.1 93.7-208.6 209-208.6 55.7 0 108.1
                     21.7 147.5 61 39.4 39.3 61.2 91.7
                     61.2 147.3-.1 115.3-93.6 208.7-209
                     208.7zm115.2-151.1c-6.3-3.2-37.3-18.4-43.1-20.5-5.8-2.1-10.1-3.2-14.5
                     3.2-4.4 6.3-16.6 20.5-20.4 24.7-3.7 4.2-7.4
                     4.7-13.7 1.6-37.1-18.6-61.4-33.2-85.7-74.2-6.5-11.1
                     6.5-10.3 18.6-34.3 2-4.2 1-7.9-.5-11.1-1.6-3.2-14.5-34.9-19.9-47.7-5.3-12.8-10.7-11-14.5-11.2-3.7-.2-7.9-.2-12.1-.2s-11 1.6-16.8
                     7.9c-5.8 6.3-22.1 21.6-22.1 52.7 0 31
                     22.6 61 25.7 65.2 3.2 4.2 44.6 67.9 108
                     95.2 63.4 27.3 63.4 18.2 74.8 17.1
                     11.4-1.1 36.6-15 41.7-29.6 5.2-14.7
                     5.2-27.3 3.7-29.5-1.3-2.1-5.8-3.7-12.1-6.9z"/>
          </svg>
          +254 705 810850
        </a>
      </div>
    </div>
  </div>

  <!-- ============ HEADER ============ -->
  <header class="header">
    <nav class="nav">
      <div class="logo">
        <a href="https://yieldguru.network/">
          <img src="../logo.png" alt="YieldGuru Logo" />
        </a>
      </div>

      <!-- HAMBURGER ICON (shown on mobile) -->
      <div class="hamburger" onclick="toggleNav()">
        <span></span>
        <span></span>
        <span></span>
      </div>

     <!-- NAV LINKS -->
      <div class="nav-links">
        <!-- CLOSE BUTTON INSIDE THE FULLSCREEN MENU -->
        <!--<button class="close-menu" onclick="toggleNav()">✕</button>-->

        <a href="https://www.yieldguru.network">Welcome</a>
        <a href="#features">Features</a>
        <a href="#signup">Invest</a>
        <a href="#contact">Contact</a>
        <a href="/buy-community-token">Buy Community Token</a>

        <?php if (!$loggedIn): ?>
          <!-- If NOT logged in -->
          <a
            href="#"
            class="btn"
            style="margin-left:1rem; display:flex; align-items:center; gap:0.5rem; color:#fff;"
            data-toggle="modal"
            data-target="#loginModal"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="16"
              height="16"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
            >
              <path d="M10 17l5-5-5-5"/>
              <path d="M3 12h12"/>
              <path d="M19 2h-2a2 2 0 0 0-2 2v2"/>
              <path d="M19 22h-2a2 2 0 0 1-2-2v-2"/>
            </svg>
            Login
          </a>
        <?php else: ?>
          <!-- If logged in, show name, Dashboard, Logout -->
          <span style="margin-left:1rem; font-weight:600; color:var(--text-dark);">
            Hello, <?php echo htmlspecialchars($userName); ?>
          </span>
          <a href="#dashboard" style="margin-left:1rem;">Dashboard</a>
          <a
            href="?logout=1"
            class="btn"
            style="margin-left:1rem; display:flex; align-items:center; gap:0.5rem; color:#fff;"
          >
            Logout
          </a>
        <?php endif; ?>
      </div>
    </nav>
  </header>

  <!-- ============ HERO SECTION ============ -->
  <section class="hero">
    <!-- Background image layer using the provided image -->
    <div class="hero-bg"></div>
    <!-- Semi-transparent overlay -->
    <div class="hero-overlay"></div>
    <div class="hero-content">
      <h1>Join $BUS Community</h1>
      <p>
        This is our community meme coin, and holders of $BUS will receive an initial token airdrop of YieldGuru when we launch.
        We are building our own YieldGuru OG community of degens who buy into the vision.
      </p>
      <a href="#" class="btn" target="_blank">
        Join $BUS Community
      </a>
    </div>
  </section>

  <!-- ============ FOOTER ============ -->
  <footer class="footer">
    <p>
      &copy; 2025 YieldGuru. All rights reserved |
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
           viewBox="0 0 24 24" fill="none" stroke="currentColor"
           stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect width="20" height="16" x="2" y="4" rx="2"/>
        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
      </svg>
      eliud@yieldguru.co |
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
           viewBox="0 0 448 512" fill="currentColor">
        <path d="M380.9 97.1C339.6 55.7 284.2 32 224.1 32 100.3
                 32 0 132.3 0 256c0 45.5 11.7 89.3 34 128L0
                 480l99.5-32.9c38.2 20.9 81.2 31.9 124.6
                 31.9h.1c123.7 0 224-100.3 224-224 .1-60.1-23.4-115.5-66.3-158.9zM224.1
                 438.6c-37.2 0-73.7-10-105.5-29l-7.5-4.5-62.6
                 20.7 20.8-61.1-4.9-7.7c-21.8-34.1-33.2-73.5-33.2-113.8
                 0-115.1 93.7-208.6 209-208.6 55.7 0 108.1
                 21.7 147.5 61 39.4 39.3 61.2 91.7
                 61.2 147.3-.1 115.3-93.6 208.7-209
                 208.7zm115.2-151.1c-6.3-3.2-37.3-18.4-43.1-20.5-5.8-2.1-10.1-3.2-14.5
                 3.2-4.4 6.3-16.6 20.5-20.4 24.7-3.7 4.2-7.4
                 4.7-13.7 1.6-37.1-18.6-61.4-33.2-85.7-74.2-6.5-11.1
                 6.5-10.3 18.6-34.3 2-4.2 1-7.9-.5-11.1-1.6-3.2-14.5-34.9-19.9-47.7-5.3-12.8-10.7-11-14.5-11.2-3.7-.2-7.9-.2-12.1-.2s-11 1.6-16.8
                 7.9c-5.8 6.3-22.1 21.6-22.1 52.7 0 31
                 22.6 61 25.7 65.2 3.2 4.2 44.6 67.9 108
                 95.2 63.4 27.3 63.4 18.2 74.8 17.1
                 11.4-1.1 36.6-15 41.7-29.6 5.2-14.7
                 5.2-27.3 3.7-29.5-1.3-2.1-5.8-3.7-12.1-6.9z"/>
      </svg>
      +254 705 810850
    </p>
  </footer>
  
  <!-- ============ CONTACT SUCCESS MODAL ============ -->
  <div
    class="modal fade"
    id="contactSuccessModal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="contactSuccessModalLabel"
    aria-hidden="true"
  >
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="contactSuccessModalLabel">
            Message Sent
          </h5>
          <button
            type="button"
            class="close"
            data-dismiss="modal"
            aria-label="Close"
          >
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <center><img src="happy.svg" style="width:50%;"/></center>
          <h4 id="contactModalName" style="text-align:center;"></h4>
          <p style="text-align:center;">
            Thank you for contacting us! We’ll respond shortly.
          </p>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-primary"
            data-dismiss="modal"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ============ LOGIN MODAL ============ -->
  <div
    class="modal fade"
    id="loginModal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="loginModalLabel"
    aria-hidden="true"
  >
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="loginModalLabel">Login</h5>
          <button
            type="button"
            class="close"
            data-dismiss="modal"
            aria-label="Close"
          >
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body text-center">
          <h5>Select a method to log in:</h5>
          <div class="d-flex flex-column align-items-center mt-3" style="width:100%;">
            <!-- Email+password form so we can do loginAjax -->
            <form id="loginForm" style="width:100%; margin-top:1rem;">
              <div class="form-group text-left">
                <label for="loginEmail">Email</label>
                <input
                  type="email"
                  class="form-control"
                  id="loginEmail"
                  name="loginEmail"
                  required
                />
              </div>
              <div class="form-group text-left">
                <label for="loginPassword">Password</label>
                <input
                  type="password"
                  class="form-control"
                  id="loginPassword"
                  name="loginPassword"
                  required
                />
              </div>
              <input type="hidden" name="loginAjax" value="1" />
              <button type="submit" class="btn btn-primary w-100">Log In</button>
            </form>

            <div id="loginAlert" style="margin-top:1rem; width:100%;"></div>

            <!-- Social placeholders (Google, FB, WhatsApp) -->
            <button
              class="btn-google"
              onclick="alert('Login with Google')"
            >
              <svg
                class="btn-social-icon"
                xmlns="http://www.w3.org/2000/svg"
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path
                  d="M21.8 10.1h-9.6v3.7h5.5c-.3 1.6-1.8 4.4-5.5 4.4
                     -3.3 0-6-2.7-6-6s2.7-6
                     6-6c1.9 0 3.2.8 4 1.5l2.7-2.6C16.5 3.5
                     14.4 2.5 12.2 2.5
                     6.8 2.5 2.5 6.8 2.5 12
                     s4.3 9.5 9.7 9.5
                     9.5-4.3 9.5-9.5
                     c0-.6-.1-1.2-.2-1.9z"
                />
              </svg>
              Login with Google
            </button>

            <button
              class="btn-facebook"
              onclick="alert('Login with Facebook')"
            >
              <svg
                class="btn-social-icon"
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path
                  d="M18 2h-3a4 4 0 0 0-4 4v3H8v4h3v9h4v-9h3l1-4h-4V6
                     a1 1 0 0 1 1-1h3z"
                />
              </svg>
              Login with Facebook
            </button>

            <button
              class="btn-whatsapp"
              onclick="alert('Login with WhatsApp')"
            >
              <svg
                class="btn-social-icon"
                xmlns="http://www.w3.org/2000/svg"
                width="16"
                height="16"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <path
                  d="M5 3h14a2 2 0 0 1 2 2v14
                   a2 2 0 0 1-2 2H5
                   a2 2 0 0 1-2-2V5
                   a2 2 0 0 1 2-2z"
                />
                <path
                  d="M8 15c1.7 1.4 3.3 2.3 4.5 2.4v-2.4l1.3.1
                   c.1 0 .2 0 .2-.1l.9-1
                   c.1-.1.1-.2 0-.4
                   l-1.5-1.5a.4.4 0 0 0-.5 0
                   l-.9.9
                   a.4.4 0 0 1-.5 0
                   l-1.4-1.4
                   a.4.4 0 0 1 0-.5
                   l.9-.9
                   a.4.4 0 0 0 0-.5
                   l-1.4-1.5
                   a.3.3 0 0 0-.4 0
                   l-1 1
                   a.3.3 0 0 0-.1.2
                   l.1 1.3v2.4z"
                />
              </svg>
              Login with WhatsApp
            </button>
          </div>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            data-dismiss="modal"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ============ JAVASCRIPT ============ -->
  <!-- Optional: jQuery and Bootstrap Bundle for any extra interactivity -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"
          integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0="
          crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
  
  <script>
    // Toggle mobile navigation
    function toggleNav() {
      const navLinks = document.querySelector('.nav-links');
      navLinks.classList.toggle('show');
    }
  </script>
</body>
</html>

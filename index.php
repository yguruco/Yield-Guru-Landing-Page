<?php
session_start(); // <-- Start session for login/logout

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Requires PHPMailer in the same directory:
//   index.php
//   PHPMailer/src/Exception.php
//   PHPMailer/src/PHPMailer.php
//   PHPMailer/src/SMTP.php
require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

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
  <meta charset="UTF-8">
  <!-- Ensure responsive scaling on mobile -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>YieldGuru - Fractional E-Mobility Investments</title>

  <link rel="icon" href="favicon.png" type="image/png">
  <!--  Bootstrap CSS  -->
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
  />
  <!-- Google Fonts & Swiper CSS -->
  <link
    href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600;700&display=swap"
    rel="stylesheet"
  >
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"
  />
  <script>var neexa_xgmx_cc_wpq_ms = "9e02f591-7749-438c-a94e-77944fab6c9d";</script>
  <script src="https://chat-widget.neexa.ai/main.js?nonce=1737363838074.0928"></script>

  <!-- =========================
       ALL PAGE STYLES
       ========================= -->
  <style>
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

  .btn2 {
    background: var(--secondary);
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
    color: #fff !important;
  }

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
  .social-icons {
    display: flex;
    gap: 1rem;
  }
  .contact-links {
    display: flex;
    gap: 2rem;
  }
  .top-bar a {
    color: white;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  /* Make sure icons in the top bar are clearly visible */
  .top-bar svg {
    stroke: white !important;
  }
  @media (max-width: 768px) {
    .top-bar {
      display: none;
    }
  }

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

  /* Fullscreen overlay for mobile nav when open */
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
    overflow-y: auto; /* allow scrolling if needed */
  }
  /* Each link style in the fullscreen overlay */
  .nav-links.show a {
    margin: 1rem 0;
    font-size: 1.2rem;
  }
  /* Close (X) button for overlay */
  .close-menu {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 2rem;
    background: transparent;
    border: none;
    cursor: pointer;
    color: #000; /* ensure it's visible */
    z-index: 9999;
  }

  .hero {
    position: relative;
    height: 120vh;
    color: white;
    text-align: center;
    overflow: hidden;
  }
  .hero-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 2;
    width: 100%;
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
  .swiper {
    width: 100%;
    height: 100%;
  }
  .swiper-slide {
    background-size: cover;
    background-position: center;
  }
  .slide-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
  }
  .swiper-button-next,
  .swiper-rtl .swiper-button-prev {
    right: var(--swiper-navigation-sides-offset, 10px);
    left: auto;
    color: var(--secondary) !important;
  }
  .swiper-button-prev,
  .swiper-rtl .swiper-button-next {
    left: var(--swiper-navigation-sides-offset, 10px);
    right: auto;
    color: var(--secondary) !important;
  }
  .swiper-pagination-bullet-active {
    background: var(--secondary) !important;
  }

  .how-it-works {
    background: #1F1F1F;
    color: #fff;
    padding: 4rem 1rem;
    text-align: center;
  }
  .how-it-works .section-title {
    margin-bottom: 3rem;
  }

  /* We'll group each Step + Arrow in a container for easier mobile layout */
  .how-steps-container {
    max-width: 1200px;
    margin: 0 auto;
  }
  .step-arrow-block {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    margin-bottom: 2rem;
    /* Default (mobile) direction is column to make arrow point down */
    flex-direction: column;
  }
  .how-step {
    flex: 1 1 auto;
    text-align: center;
  }
  .how-step .step-circle {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    margin: 0 auto 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.2rem;
    color: #fff;
  }
  .how-step h5 {
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #fff;
  }
  .how-step p {
    font-size: 0.95rem;
    color: #ccc;
    max-width: 220px;
    margin: 0 auto;
    line-height: 1.4;
  }
  .arrow-icon {
    flex: 0 0 auto;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .arrow-icon svg {
    width: 24px;
    height: 24px;
    stroke: #fff;
    /* On mobile, arrow points down (rotate 90deg) */
    transform: rotate(90deg);
  }

  @media (min-width: 992px) {
    /* On desktop, we want 2 columns and arrow side-by-side (horizontal) */
    .how-steps-container {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 2rem; /* space between columns */
    }
    .step-arrow-block {
      flex-direction: row;
      justify-content: flex-start;
    }
    .arrow-icon svg {
      transform: none !important; /* arrow horizontally */
    }
  }

  .features {
    padding: 6rem 2rem;
    background: white;
  }
  .section-title {
    text-align: center;
    margin-bottom: 3rem;
  }
  .section-title h2 {
    font-size: 2.5rem;
    color: var(--text-dark);
    margin-bottom: 1rem;
  }
  .section-title p {
    color: var(--text-light);
    max-width: 600px;
    margin: 0 auto;
  }
  .features-grid {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
    padding: 2rem 0;
  }
  .feature-card {
    padding: 2rem;
    border-radius: 10px;
    background: #f8fafc;
    text-align: center;
    transition: transform 0.3s;
  }
  .feature-card:hover {
    transform: translateY(-5px);
  }
  .feature-icon {
    margin-bottom: 1rem;
    display: flex;
    justify-content: center;
  }
  .feature-card h5 {
    margin-top: 20px;
    font-size: 1.2rem;
    color: var(--text-dark);
  }

  .team-section {
    background: #1F1F1F;
    color: #fff;
    padding: 4rem 2rem;
    text-align: center;
  }
  .team-section .section-title {
    margin-bottom: 3rem;
  }
  .team-grid {
    max-width: 1200px;
    margin: 10px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
    justify-items: center;
  }
  .team-card {
    background: #2A2A2A;
    border-radius: 16px;
    padding: 4rem;
  }
  .team-photo {
    width: 160px;
    height: 160px;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto 1rem;
  }
  .team-name {
    font-size: 1.15rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
  }
  .team-role {
    font-size: 0.95rem;
    color: #ccc;
    margin-bottom: 1rem;
  }
  .team-social {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
  }
  .team-social a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #444;
    color: #fff;
    transition: background 0.3s;
  }
  .team-social a:hover {
    background: var(--primary-dark);
    color: #fff;
  }

  /* Make the Team section vertical on mobile */
  @media (max-width: 768px) {
    .team-grid {
      grid-template-columns: 1fr !important; /* stack vertically */
    }
  }

  .signup-section {
    padding: 6rem 2rem;
    background: #f8fafc;
  }
  .signup-container {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;
  }
  .signup-image {
    width: 100%;
    height: 100%;
    min-height: 400px;
    background-size: cover;
    background-position: center;
    border-radius: 10px;
  }
  .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
  }
  .form-group {
    margin-bottom: 1.5rem;
  }
  .form-group.full-width {
    grid-column: 1 / -1;
  }
  .form-group label {
    display: block;
    margin-bottom: 0.5rem;
    color: var(--text-dark);
    font-weight: 500;
  }
  .form-group input,
  .form-group select,
  .form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-family: 'Jost', sans-serif;
    transition: border-color 0.3s;
  }
  .form-group input:focus,
  .form-group select:focus,
  .form-group textarea:focus {
    outline: none;
    border-color: var(--primary);
  }
  .radio-group {
    display: flex;
    gap: 2rem;
    margin-top: 0.5rem;
  }
  .radio-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  .alert {
    margin-top: -1rem;
    margin-bottom: 1rem;
  }

  .social-links {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
  }
  .social-links-container {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-top: 2rem;
    flex-wrap: wrap;
  }
  .social-links a {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #fff;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s;
    padding: 0.5rem 1rem;
    border-radius: 5px;
    background: var(--primary);
  }
  .social-links a:hover {
    color: var(--primary);
    background: #f1f5f9;
  }
  /* Ensure all SVG in social links are visible */
  .social-links svg {
    stroke: currentColor !important;
  }

  .footer {
    background: var(--primary);
    color: white;
    text-align: center;
    padding: 2rem;
  }

  .btn-google {
    background-color: #DB4437;
    color: #fff;
    width: 100%;
    max-width: 320px;
    margin: 0.5rem 0;
    border: none;
    border-radius: 5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 500;
    padding: 0.6rem;
  }
  .btn-facebook {
    background-color: #4267B2;
    color: #fff;
    width: 100%;
    max-width: 320px;
    margin: 0.5rem 0;
    border: none;
    border-radius: 5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 500;
    padding: 0.6rem;
  }
  .btn-whatsapp {
    background-color: #25D366;
    color: #fff;
    width: 100%;
    max-width: 320px;
    margin: 0.5rem 0;
    border: none;
    border-radius: 5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 500;
    padding: 0.6rem;
  }
  .btn-social-icon {
    margin-right: 8px;
  }

  .hero-cta-inline {
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 2rem auto;
    max-width: 600px;
    width: 100%;
    padding: 0 1rem;
  }

  .cta-form {
    display: flex;
    flex-wrap: wrap;
    width: 100%;
    background: #fff;
    padding: 1.5rem;
    border-radius: 10px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }
  .cta-form .form-group {
    flex: 1 1 30%;
    min-width: 200px;
    margin: 0.5rem;
  }
  .cta-form .form-control {
    padding: 0.75rem 1rem;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 1rem;
    transition: border-color 0.3s;
  }
  .cta-form .form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 5px rgba(79, 25, 100, 0.5);
  }
  .btn-create-account {
    width: 100%;
    padding: 0.75rem;
    background: var(--secondary);
    color: #fff;
    border: none;
    border-radius: 5px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s, transform 0.3s;
  }
  .btn-create-account:hover {
    background: #d87b25;
    transform: translateY(-2px);
  }

  @media (min-width: 768px) {
    .close-menu {
      display: none;
    }
  }
  @media (max-width: 768px) {
    .hamburger {
      display: flex;
    }
    .nav-links {
      display: none;
    }
    .nav-links.show {
      display: flex;
    }
    .features-grid {
      grid-template-columns: 2fr;
    }
    .form-grid {
      grid-template-columns: 1fr;
    }
    .hero h1 {
      font-size: 2rem;
    }
    .hero p {
      font-size: 1rem;
    }
  }

  @media (max-width: 992px) {
    .nav-links a {
      margin-left: 1rem;
    }
    .hero h1 {
      font-size: 2.2rem;
    }
    .hero p {
      font-size: 1.1rem;
    }
    .signup-container {
      grid-template-columns: 1fr;
    }
    .signup-image {
      min-height: 300px;
      margin-bottom: 2rem;
    }
  }

  @media (max-width: 576px) {
    .hero h1 {
      font-size: 1.8rem;
      margin-bottom: 1rem;
    }
    .hero p {
      font-size: 0.95rem;
    }
    .hero {
      height: 90vh;
    }
    .signup-section {
      padding: 3rem 1rem;
    }
    .signup-container {
      grid-template-columns: 1fr;
      gap: 2rem;
    }
    .social-links {
      padding: 2rem 1rem;
    }
    .features-grid {
      display: flex;
      flex-wrap: nowrap;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      gap: 1rem;
      padding: 2rem 1rem;
    }
    .feature-card {
      min-width: 250px;
      max-width: 280px;
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
        <!-- WhatsApp icon/number -->
        <a href="https://wa.me/254705810850" target="_blank">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="16"
            height="16"
            viewBox="0 0 448 512"
            fill="currentColor"
          >
            <path
              d="M380.9 97.1C339.6 55.7 284.2 32 224.1 32 100.3
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
               5.2-27.3 3.7-29.5-1.3-2.1-5.8-3.7-12.1-6.9z"
            />
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
          <img src="logo.png" alt="YieldGuru Logo">
        </a>
      </div>

      <!-- HAMBURGER ICON (shown <=768px) -->
      <div class="hamburger" onclick="toggleNav()">
        <span></span>
        <span></span>
        <span></span>
      </div>

      <!-- NAV LINKS -->
      <div class="nav-links">
        <!-- CLOSE BUTTON INSIDE THE FULLSCREEN MENU -->
        <button class="close-menu" onclick="toggleNav()">✕</button>

        <a href="https://www.yieldguru.network">Welcome</a>
        <a href="#features">Features</a>
        <a href="#signup">Invest</a>
        <a href="#contact">Contact</a>
        <!--<a href="/buy-community-token">Buy Community Token</a>-->

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

  <!-- ============ HERO SECTION (Swiper) ============ -->
  <section class="hero">
    <div class="swiper">
      <div class="swiper-wrapper">
        <div
          class="swiper-slide"
          style="
            background-image: url('https://www.bev.co.ke/images/ankai-bus/image1.jpg');
            background-position:center;
          "
        >
          <div class="slide-overlay"></div>
        </div>
        <div
          class="swiper-slide"
          style="
            background-image: url('https://www.bev.co.ke/images/rhinggo-motorcycle/image1.jpg');
            background-position:center center;
          "
        >
          <div class="slide-overlay"></div>
        </div>
        <div
          class="swiper-slide"
          style="
            background-image: url('https://www.bev.co.ke/images/rhinggo-tuktuk/image1.jpg');
            background-position:center center;
          "
        >
          <div class="slide-overlay"></div>
        </div>
        <div
          class="swiper-slide"
          style="
            background-image: url('charge.jpeg');
            background-position:center center;
          "
        >
          <div class="slide-overlay"></div>
        </div>
      </div>
      <div class="swiper-pagination"></div>
      <div class="swiper-button-next"></div>
      <div class="swiper-button-prev"></div>
    </div>
    <div class="hero-content">
      <h1>
        Invest in E-Mobility with <span style="color:#F78930;">Simplicity</span>
      </h1>
      <p>
        Yield Guru Investments is pioneering a crowd investing platform into
        E-mobility assets with quarterly yields. Get fractional ownership in
        public transport E-Buses, taxi EVs, and our network of charging stations.
      </p>
      <!-- Enhanced and Responsive CTA Section -->
      <div class="hero-cta-inline">
        <form id="heroCtaForm" class="cta-form">
          <div class="form-group">
            <input
              type="email"
              id="heroEmail"
              name="heroEmail"
              class="form-control"
              placeholder="Enter your email"
              required
            />
          </div>
          <div class="form-group">
            <select
              id="heroOption"
              name="heroOption"
              class="form-control"
              required
            >
              <option value="" disabled selected>Select Investment</option>
              <option value="EV">Invest in EV</option>
              <option value="Charging">Invest in Charging Stations</option>
            </select>
          </div>
          <div class="form-group">
            <button
              type="submit"
              class="btn btn-create-account"
            >
              Create Account
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>

  <!-- ============ FEATURES SECTION ============ -->
  <section id="features" class="features">
    <div class="section-title">
      <h2>Why Choose YieldGuru</h2>
      <p>
        Discover the benefits of investing in the future of transportation
      </p>
    </div>
    <div class="features-grid">
      <!-- 1) Fractional Ownership -->
      <div class="feature-card">
        <div class="feature-icon">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="60"
            height="60"
            viewBox="0 0 24 24"
            fill="none"
            stroke="var(--primary)"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path
              d="M21.21 15.89A10 10 0 1 1 12 2v10h10a10 10 0 0 1-.79 3.89z"
            />
          </svg>
        </div>
        <h5>Fractional Ownership</h5>
        <p>
          Own a piece of e-mobility assets through tokenization. Start with any
          investment size that suits you.
        </p>
      </div>
      <!-- 2) Blockchain Security -->
      <div class="feature-card">
        <div class="feature-icon">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="60"
            height="60"
            viewBox="0 0 24 24"
            fill="none"
            stroke="var(--primary)"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
          </svg>
        </div>
        <h5>Blockchain Security</h5>
        <p>
          Your investments are secured by smart contracts on the Public
          blockchain, ensuring transparency and trust.
        </p>
      </div>
      <!-- 3) Real-time Tracking -->
      <div class="feature-card">
        <div class="feature-icon">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="60"
            height="60"
            viewBox="0 0 24 24"
            fill="none"
            stroke="var(--primary)"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
          </svg>
        </div>
        <h5>Real-time Tracking</h5>
        <p>
          Monitor your investment portfolio and returns in real-time through our
          comprehensive dashboard.
        </p>
      </div>
      <!-- 4) Above Market Rate Yield -->
      <div class="feature-card">
        <div class="feature-icon">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            width="60"
            height="60"
            viewBox="0 0 24 24"
            fill="none"
            stroke="var(--primary)"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <line x1="19" y1="5" x2="5" y2="19"></line>
            <circle cx="6.5" cy="6.5" r="2.5"></circle>
            <circle cx="17.5" cy="17.5" r="2.5"></circle>
          </svg>
        </div>
        <h5>Above Market Rate Yield</h5>
        <p>
          We ensure our assets’ returns are inflation-proof and more competitive
          than many other financial investments.
        </p>
      </div>
    </div>
  </section>

  <!-- ============ HOW IT WORKS SECTION ============ -->
  <section class="how-it-works">
    <div class="section-title">
      <h2 style="color: #fff;">How It Works</h2>
      <p style="color: #fff;">
        A simple user experience, backed by secure and transparent technology.
      </p>
    </div>

    <div class="how-steps-container">
      <!-- STEP 1 -->
      <div class="step-arrow-block">
        <div class="how-step">
          <div
            class="step-circle"
            style="background-color: #F78930;"
          >
            01
          </div>
          <h5>ADD LIQUIDITY</h5>
          <p>
            Stake your favorite digital assets and choose which assets to invest
            in.
          </p>
        </div>
        <div class="arrow-icon">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            viewBox="0 0 24 24"
          >
            <line x1="5" y1="12" x2="19" y2="12"/>
            <polyline points="12 5 19 12 12 19"/>
          </svg>
        </div>
      </div>

      <!-- STEP 2 -->
      <div class="step-arrow-block">
        <div class="how-step">
          <div
            class="step-circle"
            style="background-color: #4F1964;"
          >
            02
          </div>
          <h5>MINT STABLES</h5>
          <p>
            Yield Guru locks added liquidity and mints local currency stable
            coins to Invest in Assets.
          </p>
        </div>
        <div class="arrow-icon">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            viewBox="0 0 24 24"
          >
            <line x1="5" y1="12" x2="19" y2="12"/>
            <polyline points="12 5 19 12 12 19"/>
          </svg>
        </div>
      </div>

      <!-- STEP 3 -->
      <div class="step-arrow-block">
        <div class="how-step">
          <div
            class="step-circle"
            style="background-color: #F78930;"
          >
            03
          </div>
          <h5>FUND ASSETS</h5>
          <p>
            Minted stable coins are invested directly into chosen asset pool.
          </p>
        </div>
        <div class="arrow-icon">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            viewBox="0 0 24 24"
          >
            <line x1="5" y1="12" x2="19" y2="12"/>
            <polyline points="12 5 19 12 12 19"/>
          </svg>
        </div>
      </div>

      <!-- STEP 4 -->
      <div class="step-arrow-block">
        <div class="how-step">
          <div
            class="step-circle"
            style="background-color: #F78930;"
          >
            04
          </div>
          <h5>GENERATE REAL YIELD</h5>
          <p>
            Our team uses cutting-edge technology, backed by experienced 
            professionals, to analyze each asset before releasing it to ensure
            they meet benchmarks of target returns.
          </p>
        </div>
        <div class="arrow-icon">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            viewBox="0 0 24 24"
          >
            <line x1="5" y1="12" x2="19" y2="12"/>
            <polyline points="12 5 19 12 12 19"/>
          </svg>
        </div>
      </div>

      <!-- STEP 5 -->
      <div class="step-arrow-block">
        <div class="how-step">
          <div
            class="step-circle"
            style="background-color: #4F1964;"
          >
            05
          </div>
          <h5>EARN</h5>
          <p>
            Track your passive cashflow with a target annualized returns of 20%
            p.a. minimum ROI.
          </p>
        </div>
        
      </div>

    </div>
  </section>

  <!-- ============ TEAM SECTION ============ -->
  <section class="team-section">
    <div class="section-title">
      <h2 style="color:#fff;">Leadership Team</h2>
      <p style="color:#fff;">
        At Yield Guru, you're encouraged to be your best self, in a work
        environment that values growth, inclusion, and progressive thinking.
      </p>
    </div>
    <div class="team-grid">
      <!-- 1) Eliud -->
      <div class="team-card">
        <img
          class="team-photo"
          src="eliud.jpeg"
          alt="Eliud (CEO & Co-Founder)"
        />
        <p class="team-name">Eliud</p>
        <p class="team-role">Founder, CEO</p>
        <div class="team-social">
          <a
            href="https://www.linkedin.com/in/eliudmungai/"
            target="_blank"
            aria-label="LinkedIn"
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
              <path
                d="M16 8a6 6 0 0 1 6 6v7h-4v-7
                   a2 2 0 0 0-2-2
                   2 2 0 0 0-2 2v7h-4v-7
                   a6 6 0 0 1 6-6z"
              />
              <rect width="4" height="12" x="2" y="9" />
              <circle cx="4" cy="4" r="2" />
            </svg>
          </a>
          <a
            href="https://x.com/elmoonguy"
            target="_blank"
            aria-label="X"
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
              <path
                d="M22 4s-.7 2.1-2 3.4
                   c1.6 10-9.4 17.3-18 11.6
                   2.2.1 4.4-.6 6-2
                   C3 15.5.5 9.6 3 5
                   c2.2 2.6 5.6 4.1 9 4
                   -.9-4.2 4-6.6 7-3.8
                   1.1 0 3-1.2 3-1.2z"
              />
            </svg>
          </a>
        </div>
      </div>
      <!-- 2) Ndeto -->
      <div class="team-card">
        <img
          class="team-photo"
          src="ndeto.jpeg"
          alt="Ndeto (CTO)"
        />
        <p class="team-name">Martin</p>
        <p class="team-role">CTO</p>
        <div class="team-social">
          <a
            href="https://www.linkedin.com/in/mndeto?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=android_app"
            target="_blank"
            aria-label="LinkedIn"
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
              <path
                d="M16 8a6 6 0 0 1 6 6v7h-4v-7
                   a2 2 0 0 0-2-2
                   2 2 0 0 0-2 2v7h-4v-7
                   a6 6 0 0 1 6-6z"
              />
              <rect width="4" height="12" x="2" y="9" />
              <circle cx="4" cy="4" r="2" />
            </svg>
          </a>
          <a
            href=" https://x.com/0xNdeto"
            target="_blank"
            aria-label="X"
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
              <path
                d="M22 4s-.7 2.1-2 3.4
                   c1.6 10-9.4 17.3-18 11.6
                   2.2.1 4.4-.6 6-2
                   C3 15.5.5 9.6 3 5
                   c2.2 2.6 5.6 4.1 9 4
                   -.9-4.2 4-6.6 7-3.8
                   1.1 0 3-1.2 3-1.2z"
              />
            </svg>
          </a>
        </div>
      </div>
      <!-- 3) Mutuku -->
      <div class="team-card">
        <img
          class="team-photo"
          src="mutuku.jpeg"
          alt="Mutuku (Leadership Team)"
        />
        <p class="team-name">Joshua</p>
        <p class="team-role">CFO</p>
        <div class="team-social">
          <a
            href="https://www.linkedin.com/in/joshua-mutuku?utm_source=share&utm_campaign=share_via&utm_content=profile&utm_medium=android_app"
            target="_blank"
            aria-label="LinkedIn"
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
              <path
                d="M16 8a6 6 0 0 1 6 6v7h-4v-7
                   a2 2 0 0 0-2-2
                   2 2 0 0 0-2 2v7h-4v-7
                   a6 6 0 0 1 6-6z"
              />
              <rect width="4" height="12" x="2" y="9" />
              <circle cx="4" cy="4" r="2" />
            </svg>
          </a>
          <a
            href="https://x.com/yield_guru"
            target="_blank"
            aria-label="X"
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
              <path
                d="M22 4s-.7 2.1-2 3.4
                   c1.6 10-9.4 17.3-18 11.6
                   2.2.1 4.4-.6 6-2
                   C3 15.5.5 9.6 3 5
                   c2.2 2.6 5.6 4.1 9 4
                   -.9-4.2 4-6.6 7-3.8
                   1.1 0 3-1.2 3-1.2z"
              />
            </svg>
          </a>
        </div>
      </div>
      <!-- 4) Dennis -->
      <div class="team-card">
        <img
          class="team-photo"
          src="two.jpeg"
          alt="Dennis (E-Mobility Expert)"
        />
        <p class="team-name">Dennis</p>
        <p class="team-role">E-Mobility Expert</p>
        <div class="team-social">
          <a
            href="https://www.linkedin.com/in/dennis-wakaba-a408304"
            target="_blank"
            aria-label="LinkedIn"
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
              <path
                d="M16 8a6 6 0 0 1 6 6v7h-4v-7
                   a2 2 0 0 0-2-2
                   2 2 0 0 0-2 2v7h-4v-7
                   a6 6 0 0 1 6-6z"
              />
              <rect width="4" height="12" x="2" y="9" />
              <circle cx="4" cy="4" r="2" />
            </svg>
          </a>
          <a
            href="https://x.com/plannerwakaba"
            target="_blank"
            aria-label="X"
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
              <path
                d="M22 4s-.7 2.1-2 3.4
                   c1.6 10-9.4 17.3-18 11.6
                   2.2.1 4.4-.6 6-2
                   C3 15.5.5 9.6 3 5
                   c2.2 2.6 5.6 4.1 9 4
                   -.9-4.2 4-6.6 7-3.8
                   1.1 0 3-1.2 3-1.2z"
              />
            </svg>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- ============ SOCIAL LINKS SECTION ============ -->
  <section
    id="contact"
    class="social-links"
    style="background-color:#fafafa;"
  >
    <div class="section-title">
      <h2>Connect With Us</h2>
      <p>Stay updated with our latest news and updates</p>
    </div>
    <div class="social-links-container">
      <a
        href="https://www.linkedin.com/company/103969156/admin/dashboard/"
        target="_blank"
      >
        <svg
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
            d="M16 8a6 6 0 0 1 6 6v7h-4v-7
               a2 2 0 0 0-2-2
               2 2 0 0 0-2 2v7h-4v-7
               a6 6 0 0 1 6-6z"
          />
          <rect width="4" height="12" x="2" y="9" />
          <circle cx="4" cy="4" r="2" />
        </svg>
        LinkedIn
      </a>
      <a
        href="https://x.com/yield_guru"
        target="_blank"
      >
        <svg
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
            d="M22 4s-.7 2.1-2 3.4
               c1.6 10-9.4 17.3-18 11.6
               2.2.1 4.4-.6 6-2
               C3 15.5.5 9.6 3 5
               c2.2 2.6 5.6 4.1 9 4
               -.9-4.2 4-6.6 7-3.8
               1.1 0 3-1.2 3-1.2z"
          />
        </svg>
        X (Twitter)
      </a>
      <a
        href="https://t.me/+zsreNvPx6Nw3MjRk"
        target="_blank"
      >
        <svg
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
          <path d="m22 2-7 20-4-9-9-4Z" />
          <path d="M22 2 11 13" />
        </svg>
        Telegram
      </a>
      <!-- Email Us -->
      <a
        href="https://www.yieldguru.network/get-in-touch"
        target="_blank"
      >
        <svg
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
          <rect width="20" height="16" x="2" y="4" rx="2"/>
          <path
            d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"
          />
        </svg>
        Email Us
      </a>
    </div>
  </section>

  <!-- ============ FOOTER ============ -->
  <footer class="footer">
    <p>
      &copy; 2025 YieldGuru. All rights reserved |
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
        <rect width="20" height="16" x="2" y="4" rx="2"/>
        <path
          d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"
        />
      </svg>
      eliud@yieldguru.co |
      <svg
        xmlns="http://www.w3.org/2000/svg"
        width="16"
        height="16"
        viewBox="0 0 448 512"
        fill="currentColor"
      >
        <path
          d="M380.9 97.1C339.6 55.7 284.2 32 224.1 32 100.3
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
             5.2-27.3 3.7-29.5-1.3-2.1-5.8-3.7-12.1-6.9z"
        />
      </svg>
      +254 705 810850
    </p>
  </footer>

  <!-- ============ INVESTMENT SUCCESS MODAL ============ -->
  <div
    class="modal fade"
    id="successModal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="successModalLabel"
    aria-hidden="true"
  >
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="successModalLabel">
            Registration Successful
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
          <center><img src="welcome.svg" style="width:50%;"/></center>
          <h4 id="modalName" style="text-align:center;"></h4>
          <p style="text-align:center;">
            Your account has been created successfully! Check your email for
            further instructions.
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

  <!-- ============ JS SCRIPTS ============ -->
  <script
    src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"
  ></script>
  <script
    src="https://code.jquery.com/jquery-3.5.1.min.js"
    integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0="
    crossorigin="anonymous"
  ></script>
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"
  ></script>

  <script>
    /**********************************************
     * SWIPER INITIALIZATION
     **********************************************/
    const swiper = new Swiper('.swiper', {
      loop: true,
      autoplay: {
        delay: 5000,
        disableOnInteraction: false,
      },
      pagination: {
        el: '.swiper-pagination',
        clickable: true,
      },
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
    });

    /**********************************************
     * HAMBURGER TOGGLE
     **********************************************/
    function toggleNav() {
      const navLinks = document.querySelector('.nav-links');
      navLinks.classList.toggle('show');
    }

    /**********************************************
     * HERO CTA FORM SUBMISSION (Email + Investment)
     **********************************************/
    const heroCtaForm = document.getElementById('heroCtaForm');
    if (heroCtaForm) {
      heroCtaForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Show "Creating account..." feedback
        const submitBtn = heroCtaForm.querySelector('button[type="submit"]');
        submitBtn.innerText = "Creating account...";
        submitBtn.disabled  = true;

        const formData = new FormData(heroCtaForm);
        formData.append('heroAjax', '1');

        fetch('', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          // Revert button text
          submitBtn.innerText = "Create Account";
          submitBtn.disabled  = false;

          if (data.status === 'success') {
            document.getElementById('modalName').textContent = "Thank you!";
            $('#successModal').modal('show');
            heroCtaForm.reset();
          } else {
            alert(data.message);
          }
        })
        .catch(err => {
          console.error('Error:', err);
          submitBtn.innerText = "Create Account";
          submitBtn.disabled  = false;
        });
      });
    }

    /**********************************************
     * LOGIN FORM VIA AJAX (Email + Password)
     **********************************************/
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
      loginForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(loginForm);
        fetch('', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          const loginAlert = document.getElementById('loginAlert');
          if (data.status === 'success') {
            // Reload page to show new nav
            window.location.reload();
          } else {
            loginAlert.innerHTML = `
              <div class="alert alert-danger" role="alert">
                ${data.message}
              </div>
            `;
          }
        })
        .catch(err => {
          console.error('Error:', err);
          const loginAlert = document.getElementById('loginAlert');
          loginAlert.innerHTML = `
            <div class="alert alert-danger" role="alert">
              An error occurred. Please try again.
            </div>
          `;
        });
      });
    }

    /**********************************************
     * HANDLE SIGNUP VIA AJAX (Investor Form)
     **********************************************/
    document.getElementById('investorForm')?.addEventListener('submit', function(e) {
      e.preventDefault();

      // Password match check
      const password = document.getElementById('password');
      const confirmPassword = document.getElementById('confirmPassword');
      const passwordAlert = document.getElementById('passwordAlert');

      if (password && confirmPassword) {
        if (passwordAlert) {
          passwordAlert.innerHTML = '';
        }

        if (password.value !== confirmPassword.value) {
          if (passwordAlert) {
            passwordAlert.innerHTML = `
              <div class="alert alert-warning" role="alert">
                The passwords don't match
              </div>
            `;
            setTimeout(() => {
              passwordAlert.innerHTML = '';
            }, 3000);
          }
          return;
        }
      }

      const form = document.getElementById('investorForm');
      const formData = new FormData(form);

      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          const fullName = formData.get('name') || 'Investor';
          document.getElementById('modalName').textContent = "Thank you " + fullName + "!";
          $('#successModal').modal('show');
          form.reset();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(err => {
        console.error('Error:', err);
      });
    });

    /**********************************************
     * HANDLE CONTACT FORM VIA AJAX (if present)
     **********************************************/
    document.getElementById('contactForm')?.addEventListener('submit', function(e) {
      e.preventDefault();
      const form = document.getElementById('contactForm');
      const formData = new FormData(form);

      fetch('', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.status === 'success') {
          const contactName = formData.get('contactName') || 'Friend';
          document.getElementById('contactModalName').textContent = "Thank you " + contactName + "!";
          $('#contactSuccessModal').modal('show');
          form.reset();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(err => {
        console.error('Error:', err);
      });
    });
  </script>
</body>
</html>

<?php
/**********************************************
 *  Include PHPMailer from the same folder
 **********************************************/
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Requires PHPMailer in the same directory:
//   index.php
//   PHPMailer/src/Exception.php
//   PHPMailer/src/PHPMailer.php
//   PHPMailer/src/SMTP.php
// require __DIR__ . 'PHPMailer/src/Exception.php';
// require __DIR__ . 'PHPMailer/src/PHPMailer.php';
// require __DIR__ . 'PHPMailer/src/SMTP.php';

/**********************************************
 *  HANDLE AJAX SUBMISSIONS
 **********************************************/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database credentials
    $host   = 'localhost';         // or your DB host
    $dbUser = 'YieldGuru';         // DB username
    $dbPass = 'Kcj034ralio#';      // DB password
    $dbName = 'YieldGuru';         // DB name

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
    function sendHtmlEmail($toEmail, $subject, $bodyHTML) {
        $mail = new PHPMailer(true);
        try {
            // SMTP config
            $mail->isSMTP();
            $mail->Host       = 'mail.yieldguru.network'; 
            $mail->SMTPAuth   = true;
            $mail->Username   = 'hello@yieldguru.network';   // The email you provided
            $mail->Password   = 'Kcj034ralio#';              // The email's password
            $mail->SMTPSecure = 'ssl';                       // or 'tls'
            $mail->Port       = 465;                         // 465 for SSL, 587 for TLS, etc.

            // Basic settings
            $mail->CharSet  = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->isHTML(true);

            // Sender info
            $mail->setFrom('hello@yieldguru.network', 'YieldGuru');  
            // Recipient
            $mail->addAddress($toEmail);

            // Subject & Body
            $mail->Subject = $subject;
            $mail->Body    = $bodyHTML;

            $mail->send();
        } catch (Exception $e) {
            // If email fails, optionally log the error or handle accordingly
        }
    }

    /*
     * 1) INVESTOR FORM SUBMISSION
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
                ':password'         => $password,         // For demonstration only; hash in production
                ':confirm_password' => $confirmPassword,  // Typically not stored
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
     * 2) CONTACT FORM SUBMISSION
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
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

    <style>
        /*******************************************************
         *                 BASE / GLOBAL STYLES
         *******************************************************/
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
        }

        /* Default: show top bar on desktop */
        .top-bar {
            background: var(--primary);
            color: white;
            padding: 0.5rem 0;
        }

        /* Container flex styling */
        .top-bar-content {
            display: flex;
            justify-content: space-between; /* icons on left, contact on right */
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* Social Icons */
        .social-icons {
            display: flex;
            gap: 1rem;
        }

        /* Contact Links (email & phone) */
        .contact-links {
            display: flex;
            gap: 2rem;
        }

        .top-bar a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Hide top bar on mobile screens — e.g., below 768px */
        @media (max-width: 768px) {
          .top-bar {
            display: none;
          }
        }

        /*******************************************************
         *                      HEADER / NAV
         *******************************************************/
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
        /* The actual nav links */
        .nav-links {
            display: flex;
            align-items: center;
        }
        .nav-links a {
            margin-left: 2rem;
            text-decoration: none;
            color: var(--text-dark);
            transition: color 0.3s;
        }
        .nav-links a:hover {
            color: var(--primary);
        }
        /* Hamburger icon container */
        .hamburger {
            display: none; /* Shown only on smaller screens */
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

        /*******************************************************
         *                    HERO / SWIPER
         *******************************************************/
        .hero {
            position: relative;
            height: 80vh;
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
            font-weight: 900;
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

        /*******************************************************
         *                      FEATURES
         *******************************************************/
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
  /* Force 4 columns by default on sufficiently large screens */
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
        .feature-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        /*******************************************************
         *                  SIGNUP SECTION
         *******************************************************/
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
        /* Alert for password mismatch */
        .alert {
            margin-top: -1rem; 
            margin-bottom: 1rem;
        }

        /*******************************************************
         *                 SOCIAL LINKS SECTION
         *******************************************************/
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
            flex-wrap: wrap; /* wrap on smaller devices */
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

        /*******************************************************
         *                    CONTACT SECTION
         *******************************************************/
        .contact {
            max-width: 800px;
            margin: 0 auto;
            padding: 6rem 2rem;
        }
        .contact form .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /*******************************************************
         *                       FOOTER
         *******************************************************/
        .footer {
            background: var(--primary);
            color: white;
            text-align: center;
            padding: 2rem;
        }

        /*******************************************************
         *                    LOGIN MODAL STYLES
         *******************************************************/
        /* Brand color styles for Google, Facebook, WhatsApp */
        .btn-google {
            background-color: #DB4437; /* Google brand color */
            color: #fff;
            margin: 0.5rem 0;
            width: 80%;
            max-width: 300px;
        }
        .btn-facebook {
            background-color: #4267B2; /* Facebook brand color */
            color: #fff;
            margin: 0.5rem 0;
            width: 80%;
            max-width: 300px;
        }
        .btn-whatsapp {
            background-color: #25D366; /* WhatsApp brand color */
            color: #fff;
            margin: 0.5rem 0;
            width: 80%;
            max-width: 300px;
        }
        h5{
            margin-top:20px;
        }
     /* Hero CTA in one horizontal row */
.hero-cta-inline {
  display: flex;             /* Put items in a single row */
  justify-content: center;   /* Center the group horizontally */
  align-items: center;       /* Vertically center them */
  margin: 1rem auto 0;       /* Spacing + center in container */
  max-width: 600px;          /* Optional max width */
  width: 100%;
}

/* Remove all gaps so items touch each other */
.hero-cta-inline > * {
  margin: 0;
  border-radius: 0;  /* We'll handle corners ourselves */
}

/* 
   Style corners so input has left round corner,
   select is in the middle,
   button has right round corner.
*/
.hero-cta-inline input {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-right: none;  /* so it touches the select */
  border-radius: 5px 0 0 5px; 
  font-size: 1rem;
}
.hero-cta-inline select {
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-right: none;  /* so it touches the button */
  border-left: none;   /* so it touches the input */
  font-size: 1rem;
}
.hero-cta-inline button {
  padding: 0.75rem 1.2rem;
  font-size: 1rem;
  font-weight: 500;
  border: 1px solid #ddd;
  border-left: none; 
  border-radius: 0 5px 5px 0;
  background: var(--primary);
  color: #fff;
  cursor: pointer;
  transition: background 0.3s;
}
.hero-cta-inline button:hover {
  background: var(--primary-dark);
}

/* Focus states for input & select */
.hero-cta-inline input:focus,
.hero-cta-inline select:focus {
  outline: none;
  border-color: var(--primary);
}



        /*******************************************************
         *               MEDIA QUERIES FOR RESPONSIVENESS
         *******************************************************/
        /* --- Breakpoint at 992px (Tablet/Laptop) --- */
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

        /* --- Breakpoint at 768px (Tablet) --- */
        @media (max-width: 768px) {
            /* Show hamburger icon, hide nav-links by default */
            .hamburger {
                display: flex;
            }
            .nav-links {
                display: none; /* hidden until we toggle it */
                position: absolute;
                top: 70px; /* below the header */
                right: 1rem;
                background: #fff;
                flex-direction: column;
                padding: 1rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                border-radius: 5px;
            }
            .nav-links a {
                color: var(--text-dark);
                margin: 0.5rem 0;
                display: block;
            }
            /* Toggle .nav-links.show to display it */
            .nav-links.show {
                display: flex;
            }

            .features-grid {
                grid-template-columns: 1fr;
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

        /* --- Breakpoint at 576px (Mobile) --- */
        @media (max-width: 576px) {
            /* Hero Text */
            .hero h1 {
                font-size: 1.8rem;
                margin-bottom: 1rem;
            }
            .hero p {
                font-size: 0.95rem;
            }
            .hero {
                height: 60vh; /* slightly smaller for mobile */
            }

            /* Features */
            .features {
                padding: 3rem 1rem;
            }
            .feature-card img {
                height: 150px;
            }

            /* Signup */
            .signup-section {
                padding: 3rem 1rem;
            }
            .signup-container {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            /* Social Links */
            .social-links {
                padding: 2rem 1rem;
            }
        }
    </style>
</head>
<body>
  <!-- ============ TOP BAR (visible on desktop, hidden on mobile) ============ -->
  <div class="top-bar">
    <div class="top-bar-content">
      <!-- LEFT: Social Media Icons (Email in this case) -->
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

      <!-- RIGHT: Phone -->
      <div class="contact-links">
        <a href="tel:+254722487495">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
               viewBox="0 0 24 24" fill="none" stroke="currentColor"
               stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2
                     19.79 19.79 0 0 1-8.63-3.07
                     19.5 19.5 0 0 1-6-6
                     19.79 19.79 0 0 1-3.07-8.67
                     A2 2 0 0 1 4.11 2h3
                     a2 2 0 0 1 2 1.72
                     12.84 12.84 0 0 0 .7 2.81
                     2 2 0 0 1-.45 2.11L8.09 9.91
                     a16 16 0 0 0 6 6l1.27-1.27
                     a2 2 0 0 1 2.11-.45
                     12.84 12.84 0 0 0 2.81.7
                     A2 2 0 0 1 22 16.92z"/>
          </svg>
          +254 722 487495
        </a>
      </div>
    </div>
  </div>

  <!-- ============ HEADER ============ -->
  <header class="header">
      <nav class="nav">
          <div class="logo">
              <a href="https://yieldguru.network/"><img src="../logo.png" alt="YieldGuru Logo"></a>
          </div>

          <!-- HAMBURGER ICON (shown at <= 768px) -->
          <div class="hamburger" onclick="toggleNav()">
              <span></span>
              <span></span>
              <span></span>
          </div>

          <!-- NAV LINKS -->
          <div class="nav-links">
              <a href="https://www.yieldguru.network">Welcome</a>
              <a href="#features">Features</a>
              <a href="#signup">Invest</a>
              <a href="#contact">Contact</a>
              <!-- New LOGIN BUTTON with icon -->
              <a href="https://yieldguru.network/login/" 
                class="btn" 
                
                style="margin-left:1rem; display:flex; align-items:center; gap:0.5rem;color:#fff;"
              >
                <!-- Feather's 'log-in' icon -->
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
          </div>
      </nav>
  </header>

  <!-- ============ CONTACT SECTION ============ -->
  <section class="contact">
      <div class="section-title">
          <h2>Get in Touch</h2>
          <p>Have questions? We're here to help</p>
      </div>
      <form id="contactForm">
          <div class="form-grid">
              <div class="form-group">
                  <label for="contactName">Name</label>
                  <input type="text" id="contactName" name="contactName" required>
              </div>
              <div class="form-group">
                  <label for="contactEmail">Email</label>
                  <input type="email" id="contactEmail" name="contactEmail" required>
              </div>
              <div class="form-group full-width">
                  <label for="message">Message</label>
                  <textarea id="message" name="message" rows="4" required></textarea>
              </div>
          </div>
          <input type="hidden" name="contactAjax" value="1">
          <button type="submit" class="btn">Send Message</button>
      </form>
  </section>

  <!-- ============ FOOTER ============ -->
  <footer class="footer">
      <p>&copy; 2025 YieldGuru. All rights reserved.</p>
      <p>Powered by YieldGuru</p>
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
          <h5 class="modal-title" id="successModalLabel">Registration Successful</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <center><img src="welcome.svg" style="width:50%;"/></center>
          <h4 id="modalName" style="text-align:center;"></h4>
          <p style="text-align:center;">Your account has been created successfully! Check your email for further instructions.</p>
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
          <h5 class="modal-title" id="contactSuccessModalLabel">Message Sent</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <center><img src="happy.svg" style="width:50%;"/></center>
          <h4 id="contactModalName" style="text-align:center;"></h4>
          <p style="text-align:center;">Thank you for contacting us! We’ll respond shortly.</p>
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
          <div class="d-flex flex-column align-items-center mt-3">
            <!-- Login with Google -->
            <button 
              class="btn-google" 
              onclick="alert('Login with Google')"
            >
              <!-- Google G icon -->
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
                style="margin-right:8px;"
              >
                <path d="M21.8 10.1h-9.6v3.7h5.5c-.3 1.6-1.8 4.4-5.5 4.4-3.3 0-6-2.7-6-6s2.7-6 
                         6-6c1.9 0 3.2.8 4 1.5l2.7-2.6C16.5 3.5 14.4 2.5 12.2 2.5 
                         6.8 2.5 2.5 6.8 2.5 12s4.3 9.5 9.7 9.5 
                         9.5-4.3 9.5-9.5c0-.6-.1-1.2-.2-1.9z"/>
              </svg>
              Login with Google
            </button>

            <!-- Login with Facebook -->
            <button 
              class="btn-facebook" 
              onclick="alert('Login with Facebook')"
            >
              <!-- Facebook f icon -->
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
                style="margin-right:8px;"
              >
                <path d="M18 2h-3a4 4 0 0 0-4 4v3H8v4h3v9h4v-9h3l1-4h-4V6a1 1 0 0 1 1-1h3z"/>
              </svg>
              Login with Facebook
            </button>

            <!-- Login with WhatsApp -->
            <button 
              class="btn-whatsapp" 
              onclick="alert('Login with WhatsApp')"
            >
              <!-- WhatsApp icon -->
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
                style="margin-right:8px;"
              >
                <path d="M5 3h14a2 2 0 0 1 2 2v14
                         a2 2 0 0 1-2 2H5
                         a2 2 0 0 1-2-2V5
                         a2 2 0 0 1 2-2z" />
                <path d="M8 15c1.7 1.4 3.3 2.3 4.5 2.4v-2.4l1.3.1c.1 0 
                         .2 0 .2-.1l.9-1c.1-.1.1-.2 0-.4
                         l-1.5-1.5a.4.4 0 0 0-.5 0l-.9.9
                         a.4.4 0 0 1-.5 0l-1.4-1.4
                         a.4.4 0 0 1 0-.5l.9-.9
                         a.4.4 0 0 0 0-.5l-1.4-1.5
                         a.3.3 0 0 0-.4 0l-1 1
                         a.3.3 0 0 0-.1.2l.1 1.3v2.4z"/>
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
  <!-- Swiper JS -->
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

  <!-- jQuery and Bootstrap JS (for modals) -->
  <script 
    src="https://code.jquery.com/jquery-3.5.1.min.js"
    integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0="
    crossorigin="anonymous">
  </script>
  <script 
    src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js">
  </script>

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
       * SCROLL TO SIGNUP
       **********************************************/
      function scrollToSignup() {
          document.getElementById('signup').scrollIntoView({ behavior: 'smooth' });
      }

      /**********************************************
       * HAMBURGER TOGGLE
       **********************************************/
      function toggleNav() {
          const navLinks = document.querySelector('.nav-links');
          navLinks.classList.toggle('show');
      }

      /**********************************************
       * HANDLE SIGNUP VIA AJAX
       **********************************************/
      document.getElementById('investorForm').addEventListener('submit', function(e) {
          e.preventDefault();

          // Password match check
          const password = document.getElementById('password');
          const confirmPassword = document.getElementById('confirmPassword');
          const passwordAlert = document.getElementById('passwordAlert');

          // Clear prior alerts
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

          // Submit form via AJAX
          const form = document.getElementById('investorForm');
          const formData = new FormData(form);

          fetch('', {
              method: 'POST',
              body: formData
          })
          .then(res => res.json())
          .then(data => {
              console.log('Investor Response:', data);
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
       * HANDLE CONTACT FORM VIA AJAX
       **********************************************/
      document.getElementById('contactForm').addEventListener('submit', function(e) {
          e.preventDefault();
          const form = document.getElementById('contactForm');
          const formData = new FormData(form);

          fetch('', {
              method: 'POST',
              body: formData
          })
          .then(res => res.json())
          .then(data => {
              console.log('Contact Response:', data);
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

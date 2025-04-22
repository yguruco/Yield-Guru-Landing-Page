<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Here you would handle the login form submission
    // e.g. check credentials in DB, redirect, or show errors

    // For now, just echo a message (or do nothing)
    echo json_encode([
        'status'  => 'success',
        'message' => 'Login form would be processed here.'
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>YieldGuru - Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- Favicon -->
  <link rel="icon" href="../favicon.png" type="image/png">

  <!-- Bootstrap CSS -->
  <link 
    rel="stylesheet" 
    href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
  />

  <!-- Google Font & shared Swiper CSS (if needed) -->
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
      background: #f8f9fa; /* Light background for the login page */
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
    .btn:hover {
      background: var(--primary-dark);
      transform: translateY(-1px);
    }

    /*******************************************************
     *                      TOP BAR
     *******************************************************/
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
    /* Hide top bar on mobile screens — e.g. below 768px */
    @media (max-width: 768px) {
      .top-bar {
        display: none;
      }
    }

    /*******************************************************
     *                  HEADER / NAV
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
    @media (max-width: 768px) {
      .hamburger {
        display: flex;
      }
      .nav-links {
        display: none; 
        position: absolute;
        top: 70px; 
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
      .nav-links.show {
        display: flex;
      }
    }

    /*******************************************************
     *                     LOGIN FORM
     *******************************************************/
    .login-section {
      padding: 4rem 1rem;
    }
    .login-container {
      max-width: 500px;
      margin: 0 auto;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      padding: 2rem;
    }
    .login-title {
      text-align: center;
      margin-bottom: 2rem;
    }
    .login-title h2 {
      font-size: 1.8rem;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
    }
    .login-title p {
      color: var(--text-light);
    }
    .login-form-group {
      margin-bottom: 1.2rem;
    }
    .login-form-group label {
      display: block;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
      font-weight: 500;
    }
    .login-form-group input {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #ddd;
      border-radius: 5px;
      transition: border 0.3s;
    }
    .login-form-group input:focus {
      outline: none;
      border-color: var(--primary);
    }
    .login-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }
    .login-actions .remember-me {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .btn-login {
      background-color: #0069D9; /* or your color of choice */
      border-radius: 5px;
      width: 100%;
      text-align: center;
      font-weight: 600;
    }
    .btn-login:hover {
      background-color: #0053ae;
    }
    .login-or {
      text-align: center;
      margin: 1.5rem 0;
      color: var(--text-light);
      font-weight: 500;
      position: relative;
    }
    .login-or::before,
    .login-or::after {
      content: "";
      position: absolute;
      top: 50%;
      width: 40%;
      height: 1px;
      background-color: #ccc;
    }
    .login-or::before {
      left: 0;
    }
    .login-or::after {
      right: 0;
    }
    .login-socials {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      align-items: center;
    }
    .btn-google {
      background-color: #DB4437; /* Google brand color */
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      width: 100%;
    }
    .btn-whatsapp {
      background-color: #25D366; /* WhatsApp brand color */
      color: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      width: 100%;
    }

    /*******************************************************
     *                    FOOTER
     *******************************************************/
    .footer {
      background: var(--primary);
      color: white;
      text-align: center;
      padding: 2rem;
      margin-top: 3rem;
    }
  </style>
</head>

<body>
  <!-- ============ TOP BAR ============ -->
  <div class="top-bar">
    <div class="top-bar-content">
      <!-- LEFT: Social Media Icons -->
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

  <!-- ============ HEADER / NAV ============ -->
  <header class="header">
    <nav class="nav">
      <div class="logo">
        <a href="https://yieldguru.network/"><img src="../logo.png" alt="YieldGuru Logo"></a>
      </div>
      <div class="hamburger" onclick="toggleNav()">
        <span></span>
        <span></span>
        <span></span>
      </div>
      <div class="nav-links">
        <!-- Your main site links here; you might have a link to "Home", "Invest", etc. -->
        <a href="https://yieldguru.network/">Back Home</a>
       
      </div>
    </nav>
  </header>

  <!-- ============ LOGIN SECTION ============ -->
  <section class="login-section">
    <div class="login-container">
      <div class="login-title">
        <h2>Login to YieldGuru</h2>
        <p>Access your account</p>
      </div>

      <!-- LOGIN FORM -->
      <form id="loginForm">
        <div class="login-form-group">
          <label for="loginEmail">Your email</label>
          <input type="email" id="loginEmail" name="loginEmail" required>
        </div>

        <div class="login-form-group">
          <label for="loginPassword">Password</label>
          <input type="password" id="loginPassword" name="loginPassword" required>
        </div>

        <div class="login-actions">
          <div class="remember-me">
            <input type="checkbox" id="rememberMe" name="rememberMe">
            <label for="rememberMe" style="margin-bottom:0;">Remember me</label>
          </div>
          <div>
            <a href="#" style="color: var(--primary);">Forgot password?</a>
          </div>
        </div>

        <button type="submit" class="btn btn-login" style="margin-bottom:1rem;">
          Log in
        </button>
      </form>

      <div class="login-or">or use</div>

      <!-- Social Login Buttons -->
      <div class="login-socials">
        <!-- Google -->
        <button 
          class="btn btn-google" 
          onclick="alert('Google Sign-In goes here')"
        >
          <!-- Google icon -->
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
            <path d="M21.8 10.1h-9.6v3.7h5.5c-.3 1.6-1.8 4.4-5.5 4.4-3.3 0-6-2.7-6-6s2.7-6 
                     6-6c1.9 0 3.2.8 4 1.5l2.7-2.6C16.5 3.5 14.4 2.5 12.2 2.5 
                     6.8 2.5 2.5 6.8 2.5 12s4.3 9.5 9.7 9.5 
                     9.5-4.3 9.5-9.5c0-.6-.1-1.2-.2-1.9z"/>
          </svg>
          Login with Google
        </button>

        <!-- WhatsApp -->
        <button 
          class="btn btn-whatsapp" 
          onclick="alert('WhatsApp Sign-In goes here')"
        >
          <!-- WhatsApp icon -->
          <svg 
            xmlns="http://www.w3.org/2000/svg" 
            width="18" 
            height="18"
            viewBox="0 0 24 24" 
            fill="none" 
            stroke="currentColor" 
            stroke-width="2" 
            stroke-linecap="round" 
            stroke-linejoin="round"
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
  </section>

  <!-- ============ FOOTER ============ -->
  <footer class="footer">
    <p>&copy; 2025 YieldGuru. All rights reserved.</p>
    <p>Powered by YieldGuru</p>
  </footer>

  <!-- ============ JS SCRIPTS ============ -->
  <!-- Swiper JS (only needed if you share the same JS assets) -->
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

  <!-- jQuery and Bootstrap JS -->
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
     * HAMBURGER TOGGLE FOR NAV
     **********************************************/
    function toggleNav() {
      const navLinks = document.querySelector('.nav-links');
      navLinks.classList.toggle('show');
    }

    /**********************************************
     * HANDLE LOGIN FORM (DEMO)
     **********************************************/
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      e.preventDefault();
      // Basic demo alert or do your real login logic here
      alert('Login form submitted. Implement your backend logic now!');
    });
  </script>
</body>
</html>

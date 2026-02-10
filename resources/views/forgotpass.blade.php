<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>E-SPES Login</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/forgotpass.css') }}">

</head>
<body>

<!--header-->
<div class="header">
  <img src="{{ asset('images/CalSPESlogonobg.png') }}" alt="Logo">

  <div class="header-text">
    <h1>Special Program for the Employment of Students</h1>
  <p class="subtitle">SPES Beneficiaries Management Information System</p>
  </div>
</div>

<!-- maincon -->
<div class="main">
  <div class="login-card">
    <h2>Forgot your password?</h2>
    <p>Enter your registered email address. We'll send you a 6-digit verification code</p>

    <div class="input-group">
      <label>Registered Email</label>
      <div class="input-wrapper">
        <i class="fa-solid fa-envelope"></i>
        <input type="text" placeholder="Enter your registered email">
      </div>
    </div>

    <button class="login-btn" onclick="validateLogin()">SEND VERIFICATION CODE</button>

    <!-- alert authen msg-->
    <div id="authMessage"></div>

    <div class="links">
      <a href="{{ url('/login') }}">Back to Login</a>
      <a href="{{ url('/forgotpassverifycode') }}">Need Help?</a>
    </div>
  </div>
  <div class="lowerlink">
<p>Remembered you password? <a href="{{ url('/login') }}">Back to login.</a></p>
</div>
</div>

<!-- footer -->
<div class="footer">
  Copyright © 2026 City College of Calamba. All rights Reserve.
</div>

<script>
function togglePassword() {
  const password = document.getElementById("password");
  const icon = document.getElementById("togglePassword");

  if (password.type === "password") {
    password.type = "text";
    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");
  } else {
    password.type = "password";
    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");
  }
}

// alert
function validateLogin() {
  const username = document.querySelector('.input-wrapper input[type="text"]').value.trim();
  const password = document.getElementById('password').value.trim();
  const authMessage = document.getElementById('authMessage');

  if (username === "" || password === "") {
    authMessage.style.display = "block";
    authMessage.style.backgroundColor = "#FEE3E3"; 
    authMessage.style.color = "#AB1C4E"; 
    authMessage.textContent = "Please fill in both Username and Password.";
    return false;
  }

  // Show authenticating message
  authMessage.style.display = "block";
  authMessage.style.backgroundColor = "#8AFFAE"; 
  authMessage.style.color = "#01782D";
  authMessage.textContent = "Authenticating...";

  setTimeout(() => {
    if (username === "admin" && password === "1234") {
      authMessage.style.backgroundColor = "#8AFFAE";
      authMessage.style.color = "#01782D";
      authMessage.textContent = "Login Successful! Redirecting to the system…";
    } else {
      authMessage.style.backgroundColor = "#FEE3E3"; 
      authMessage.style.color = "#AB1C4E";
      authMessage.textContent = "Invalid credentials. Please try again.";
    }
  }, 2000);
}
</script>

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>E-SPES Login</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/login.css') }}">

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
    <h2>Sign In</h2>
    <p>Enter your credentials to access the system</p>

    <!-- Login Form -->
    <form method="POST" action="{{ route('login.post') }}" id="loginForm">
      @csrf

      <div class="input-group">
        <label>Email Address</label>
        <div class="input-wrapper">
          <i class="fa-solid fa-envelope"></i>
          <input 
            type="email" 
            name="email" 
            value="{{ old('email') }}"
            placeholder="Enter your email address"
            required
            autofocus
          >
        </div>
        @error('email')
        <span style="color: #AB1C4E; font-size: 12px; display: block; margin-top: 5px;">
          {{ $message }}
        </span>
        @enderror
      </div>

      <div class="input-group">
        <label>Password</label>
        <div class="input-wrapper">
          <i class="fa-solid fa-lock"></i>
          <input 
            type="password" 
            id="password"
            name="password"
            class="password-input"
            placeholder="Enter your password"
            required
          >
          <i 
            class="fa-solid fa-eye eye"
            id="togglePassword"
            onclick="togglePassword()"
          ></i>
        </div>
        @error('password')
        <span style="color: #AB1C4E; font-size: 12px; display: block; margin-top: 5px;">
          {{ $message }}
        </span>
        @enderror
      </div>

      <button type="submit" class="login-btn">LOGIN</button>
    </form>

    <div class="links">
      <a href="{{ route('password.request') }}">Forgot Password?</a>
      <a href="#">Need Help?</a>
    </div>

    <!-- Display Success Messages -->
    @if(session('success'))
    <div style="padding: 15px; margin-top: 20px; background-color: #8AFFAE; color: #01782D; border-radius: 8px; font-size: 13px; text-align: center; font-weight: 500;">
      {{ session('success') }}
    </div>
    @endif

    <!-- Display Error Messages -->
    @if(session('error'))
    <div style="padding: 15px; margin-top: 20px; background-color: #FEE3E3; color: #AB1C4E; border-radius: 8px; font-size: 13px; text-align: center; font-weight: 500;">
      {{ session('error') }}
    </div>
    @endif

    <!-- Loading message container -->
    <div id="authMessage"></div>
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

// Show loading message on form submit
document.getElementById('loginForm')?.addEventListener('submit', function() {
  const authMessage = document.getElementById('authMessage');
  if (authMessage) {
    authMessage.style.display = "block";
    authMessage.style.backgroundColor = "#D4EDFF";
    authMessage.style.color = "#0066CC";
    authMessage.style.padding = "15px";
    authMessage.style.borderRadius = "8px";
    authMessage.style.marginTop = "20px";
    authMessage.style.fontSize = "13px";
    authMessage.style.textAlign = "center";
    authMessage.style.fontWeight = "500";
    authMessage.textContent = "Authenticating...";
  }
});
</script>

</body>
</html>

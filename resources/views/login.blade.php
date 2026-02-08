<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>E-SPES Login</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* {
  box-sizing: border-box;
  font-family: 'Inter', sans-serif;
}

body {
  margin: 0;
  background: #F6F6F6;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* header */
.header {
  background: #242242;
  color: white;
  padding: 15px 40px;
  display: flex;
  align-items: center;
  gap: 15px;
}

.header img {
  width: 45px;
  height: 45px;
  object-fit: contain;
}

.header-text h1 {
  margin: 0;
  font-size: 18px;
}

.header-text p {
  margin: 3px 0 0;
  font-size: 12px;
  opacity: 0.9;
}

.subtitle {
  font-style: italic;
}

/* center */
.main {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
}

/* login */
.login-card {
  background: #fff;
  width: 505px;
  padding: 40px;
  border-radius: 17px;
  box-shadow: 0 10px 25px rgba(0,0,0,0.12);
  text-align: center;
}

.login-card h2 {
  margin-bottom: 2px;
}

.login-card p {
  font-size: 13px;
  color: #666;
  margin-bottom: 25px;
}

/* input */
.input-group {
  text-align: left;
  margin-bottom: 15px;
}

.input-group label {
  font-size: 13px;
  color: #666;
}

.input-wrapper {
  display: flex;
  align-items: center;
  border: 2px solid #ccc;
  border-radius: 6px;
  padding: 17px;
  margin-top: 3px;
}

/*.input-wrapper i {
  color: #555;
  margin-right: 10px;
} OLD*/

.input-wrapper i {
  color: #000;     
  margin-right: 10px;
  font-size: 20px;    /* icon size size */
}

.input-wrapper input {
  border: none;
  outline: none;
  flex: 1;
  font-size: 14px;
}

.eye {
  cursor: pointer;
  color: #777;
}

.password-input {
  font-size: 16px;
  color: #777;
}

/* button*/
.login-btn {
  width: 100%;
  padding: 17px;
  margin-top: 10px;
  background: #242242;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: bold;
  font-size: 14px;
  cursor: pointer;
}

.login-btn:hover {
  background: #2f256c;
}

/* links */
.links {
  display: flex;
  justify-content: space-between;
  margin-top: 15px;
  font-size: 12px;
}

.links a {
  text-decoration: none;
  color: #3f51b5;
}

/* footer */
.footer {
  background: #242242;
  color: white;
  text-align: center;
  padding: 15px;
  font-size: 10px;
}

/* auth message */
#authMessage {
  display: none;
  margin-top: 15px;
  padding: 17px;
  background-color: #8AFFAE;
  color: #01782D;
  border-radius: 5px;
  font-size: 14px;
  text-align: left;
}
</style>
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

    <div class="input-group">
      <label>Username or ID</label>
      <div class="input-wrapper">
        <i class="fa-solid fa-user"></i>
        <input type="text" placeholder="Enter your username or ID">
      </div>
    </div>

    <div class="input-group">
      <label>Password</label>
      <div class="input-wrapper">
        <i class="fa-solid fa-lock"></i>
        <input 
          type="password" 
          id="password"
          class="password-input"
          placeholder="Enter your password"
        >
        <i 
          class="fa-solid fa-eye eye"
          id="togglePassword"
          onclick="togglePassword()"
        ></i>
      </div>
    </div>

    <button class="login-btn" onclick="validateLogin()">LOGIN</button>

    <!-- alert authen msg-->
    <div id="authMessage"></div>

    <div class="links">
      <a href="#">Forgot Password?</a>
      <a href="#">Need Help?</a>
    </div>
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

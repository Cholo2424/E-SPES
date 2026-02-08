<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>E-SPES Dashboard</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>

*{
box-sizing:border-box;
font-family:'Inter', sans-serif;
}

body{
margin:0;
background:#ffffff;
min-height:100vh;
display:flex;
flex-direction:column;
}

/* header */
.header{
background:#242242;
color:white;
padding:15px 40px;
display:flex;
align-items:center;
gap:15px;
}

.header img{
width:45px;
height:45px;
object-fit:contain;
}

.header-text h1{
margin:0;
font-size:18px;
}

.header-text p{
margin:3px 0 0;
font-size:12px;
opacity:0.9;
font-style:italic;
}

/* right side header */
.header-right{
margin-left:auto;
display:flex;
align-items:center;
gap:12px;
}

.user-info{
text-align:right;
font-size:12px;
}

.avatar{
width:38px;
height:38px;
border-radius:50%;
background:#e5e5e5;
color:#242242;
display:flex;
align-items:center;
justify-content:center;
font-weight:600;
}

/* main*/
.main{
flex:1;
padding:22px 30px;
display:flex;
gap:11px;
padding: 60px 120px 60px 120px;
}

/* sidebar */
.sidebar{
width:235px; /*255*/
background:#f3f3f3;
border-radius:5px;
padding:0px;
border:1px solid #d0d0d0;
}

/* menu */
.menu-item{
display:flex;
align-items:center;
justify-content:space-between;
padding:34px 14px;
border-radius:6px;
font-size:13.5px;
color:#555;
cursor:pointer;
transition:.2s;
}

.menu-left{
display:flex;
align-items:center;
gap:12px;
}

.menu-item i{
width:18px;
text-align:center;
}

/* hover */
.menu-item:hover{
background:#e6e6e6;
}

/* axt */
.menu-item.active{
background:#ffffff;
color:rgb(40, 32, 32);
padding: 30px;
border-radius: 0px;
}
/*icon sidebar*/
.menu-item.active i{
color:rgb(32, 30, 30);
}

/* arrow rotate 
.menu-item .fa-chevron-right{
transition:transform .2s;
}*/

.menu-item.active .fa-chevron-right{
transform:rotate(90deg);
}

/* main content*/
.content{
flex:1;
background:#ffffff;
border-radius:5px;
padding:25px;
border:1px solid #d0d0d0;
}

/* paa*/
.footer{
background:#242242;
color:white;
text-align:center;
padding:15px;
font-size:10px;
}

</style>
</head>

<body>

<!-- header -->
<div class="header">

<img src="1x1.jpg" alt="SPES Logo">

<div class="header-text">
<h1>Special Program for the Employment of Students</h1>
<p>SPES Beneficiaries Management Information System</p>
</div>

<div class="header-right">
  <div class="user-info">
    <strong>Louraine A. Pecayo</strong><br>
    Coordinator
  </div>
  <div class="avatar">LP</div>
</div>

</div>

<!-- main -->
<div class="main">

<!-- sidebar -->
<div class="sidebar">

<div class="menu-item active">
  <div class="menu-left">
    <i class="fa-solid fa-house"></i>
    Dashboard
  </div>
  <i class="fa-solid fa-chevron-right"></i>
</div>

<div class="menu-item">
  <div class="menu-left">
    <i class="fa-solid fa-user-plus"></i>
    New SPES
  </div>
  <i class="fa-solid fa-chevron-right"></i>
</div>

<div class="menu-item">
  <div class="menu-left">
    <i class="fa-solid fa-briefcase"></i>
    SPES Management
  </div>
  <i class="fa-solid fa-chevron-right"></i>
</div>

<div class="menu-item">
  <div class="menu-left">
    <i class="fa-solid fa-check-square"></i>
    Deployment
  </div>
  <i class="fa-solid fa-chevron-right"></i>
</div>

<div class="menu-item">
  <div class="menu-left">
    <i class="fa-solid fa-bullhorn"></i>
    Announcements
  </div>
  <i class="fa-solid fa-chevron-right"></i>
</div>

<div class="menu-item">
  <div class="menu-left">
    <i class="fa-solid fa-chart-pie"></i>
    Demographical Data
  </div>
  <i class="fa-solid fa-chevron-right"></i>
</div>

<div class="menu-item">
  <div class="menu-left">
    <i class="fa-solid fa-users"></i>
    Team Accounts
  </div>
  <i class="fa-solid fa-chevron-right"></i>
</div>

<div class="menu-item">
  <div class="menu-left">
    <i class="fa-solid fa-gear"></i>
    Settings
  </div>
  <i class="fa-solid fa-chevron-right"></i>
</div>

</div>

<!-- main content -->
<div class="content">
<h2>Dashboard</h2>
<p>Welcome to SPES Beneficiaries Management Information System.</p>
</div>

</div>

<!-- footer -->
<div class="footer">
Copyright © 2026 City College of Calamba. All rights Reserve.
</div>

<script>
const items = document.querySelectorAll('.menu-item');

items.forEach(item=>{
 item.addEventListener('click',()=>{
   items.forEach(i=>i.classList.remove('active'));
   item.classList.add('active');
 });
});
</script>

</body>
</html>

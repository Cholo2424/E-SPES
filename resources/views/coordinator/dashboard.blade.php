<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Coordinator Dashboard - E-SPES</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
  font-family: 'Inter', 'Segoe UI', sans-serif;
}

body {
  background: #f5f5f5;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

/* Header */
.header {
  background: #242242;
  color: white;
  padding: 15px 40px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.header-left {
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
  font-weight: 600;
}

.header-text p {
  margin: 3px 0 0;
  font-size: 12px;
  opacity: 0.9;
  font-style: italic;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 20px;
}

.user-info {
  text-align: right;
  font-size: 13px;
}

.user-info .name {
  font-weight: 600;
  margin-bottom: 3px;
}

.user-info .role {
  font-size: 11px;
  opacity: 0.8;
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 16px;
}

/* Main Container */
.container {
  flex: 1;
  padding: 30px;
  max-width: 1400px;
  width: 100%;
  margin: 0 auto;
}

/* Welcome Section */
.welcome-section {
  background: white;
  padding: 25px;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  margin-bottom: 30px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.welcome-text h2 {
  font-size: 24px;
  color: #242242;
  margin-bottom: 8px;
}

.welcome-text p {
  color: #666;
  font-size: 14px;
}

.logout-btn {
  background: #dc3545;
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: background 0.3s;
}

.logout-btn:hover {
  background: #c82333;
}

/* Stats Cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-bottom: 30px;
}

.stat-card {
  background: white;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  display: flex;
  align-items: center;
  gap: 15px;
}

.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.stat-icon.success { background: #d4edda; color: #28a745; }
.stat-icon.danger { background: #f8d7da; color: #dc3545; }
.stat-icon.info { background: #d1ecf1; color: #17a2b8; }
.stat-icon.warning { background: #fff3cd; color: #ffc107; }

.stat-content h3 {
  font-size: 28px;
  color: #242242;
  margin-bottom: 5px;
}

.stat-content p {
  font-size: 13px;
  color: #666;
}

/* Login History Section */
.section {
  background: white;
  padding: 25px;
  border-radius: 10px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  margin-bottom: 30px;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 2px solid #f0f0f0;
}

.section-header h3 {
  font-size: 18px;
  color: #242242;
}

.view-all-btn {
  color: #667eea;
  text-decoration: none;
  font-size: 14px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 5px;
  transition: color 0.3s;
}

.view-all-btn:hover {
  color: #764ba2;
}

/* Table Styles */
.table-responsive {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

thead {
  background: #f8f9fa;
}

th {
  padding: 12px;
  text-align: left;
  font-size: 13px;
  font-weight: 600;
  color: #495057;
  border-bottom: 2px solid #dee2e6;
}

td {
  padding: 12px;
  font-size: 13px;
  color: #666;
  border-bottom: 1px solid #f0f0f0;
}

.badge {
  display: inline-block;
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
}

.badge.success {
  background: #d4edda;
  color: #28a745;
}

.badge.danger {
  background: #f8d7da;
  color: #dc3545;
}

.empty-state {
  text-align: center;
  padding: 40px;
  color: #999;
}

.empty-state i {
  font-size: 48px;
  margin-bottom: 15px;
  opacity: 0.5;
}

/* Footer */
.footer {
  background: #242242;
  color: white;
  text-align: center;
  padding: 15px;
  font-size: 13px;
}

/* Alert Messages */
.alert {
  padding: 12px 20px;
  border-radius: 8px;
  margin-bottom: 20px;
  font-size: 14px;
}

.alert.success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.alert.error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}
</style>
</head>
<body>

<!-- Header -->
<div class="header">
  <div class="header-left">
    <img src="{{ asset('images/CalSPESlogonobg.png') }}" alt="Logo" onerror="this.src='{{ asset('images/CalSPESlogo.jfif') }}'">
    <div class="header-text">
      <h1>Special Program for the Employment of Students</h1>
      <p class="subtitle">Coordinator Dashboard</p>
    </div>
  </div>
  <div class="header-right">
    <div class="user-info">
      <div class="name">{{ $user->name }}</div>
      <div class="role">{{ ucfirst($user->role) }}</div>
    </div>
    <div class="avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
  </div>
</div>

<!-- Main Container -->
<div class="container">
  <!-- Success Message -->
  @if(session('success'))
  <div class="alert success">
    <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
  </div>
  @endif

  <!-- Welcome Section -->
  <div class="welcome-section">
    <div class="welcome-text">
      <h2>Welcome back, {{ $user->name }}!</h2>
      <p>Last login: {{ $stats['last_login'] }} from {{ $stats['last_login_ip'] }}</p>
    </div>
    <form action="{{ route('logout') }}" method="POST">
      @csrf
      <button type="submit" class="logout-btn">
        <i class="fa-solid fa-right-from-bracket"></i>
        Logout
      </button>
    </form>
  </div>

  <!-- Statistics Cards -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon success">
        <i class="fa-solid fa-check-circle"></i>
      </div>
      <div class="stat-content">
        <h3>{{ $stats['total_logins'] }}</h3>
        <p>Successful Logins</p>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon danger">
        <i class="fa-solid fa-times-circle"></i>
      </div>
      <div class="stat-content">
        <h3>{{ $stats['failed_attempts'] }}</h3>
        <p>Failed Attempts</p>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon info">
        <i class="fa-solid fa-clock"></i>
      </div>
      <div class="stat-content">
        <h3>{{ $stats['total_sessions'] }}</h3>
        <p>Total Sessions</p>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon warning">
        <i class="fa-solid fa-shield-halved"></i>
      </div>
      <div class="stat-content">
        <h3>Active</h3>
        <p>Account Status</p>
      </div>
    </div>
  </div>

  <!-- Recent Login History -->
  <div class="section">
    <div class="section-header">
      <h3><i class="fa-solid fa-history"></i> Recent Login Activity</h3>
      <a href="{{ route('coordinator.login.history') }}" class="view-all-btn">
        View All <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>

    @if($recentLogins->count() > 0)
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>Date & Time</th>
            <th>Status</th>
            <th>IP Address</th>
            <th>Device</th>
            <th>Reason</th>
          </tr>
        </thead>
        <tbody>
          @foreach($recentLogins as $log)
          <tr>
            <td>{{ $log->created_at->format('M d, Y H:i:s') }}</td>
            <td>
              <span class="badge {{ $log->status === 'SUCCESS' ? 'success' : 'danger' }}">
                {{ $log->status }}
              </span>
            </td>
            <td>{{ $log->ip_address }}</td>
            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
              {{ $log->user_agent }}
            </td>
            <td>{{ $log->failure_reason ?? '-' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @else
    <div class="empty-state">
      <i class="fa-solid fa-inbox"></i>
      <p>No login history available</p>
    </div>
    @endif
  </div>

  <!-- Recent Logout History -->
  <div class="section">
    <div class="section-header">
      <h3><i class="fa-solid fa-right-from-bracket"></i> Recent Logout Activity</h3>
      <a href="{{ route('coordinator.logout.history') }}" class="view-all-btn">
        View All <i class="fa-solid fa-arrow-right"></i>
      </a>
    </div>

    @if($recentLogouts->count() > 0)
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>Date & Time</th>
            <th>IP Address</th>
            <th>Session Duration</th>
            <th>Device</th>
          </tr>
        </thead>
        <tbody>
          @foreach($recentLogouts as $logout)
          <tr>
            <td>{{ $logout->created_at->format('M d, Y H:i:s') }}</td>
            <td>{{ $logout->ip_address }}</td>
            <td>{{ $logout->getFormattedDuration() }}</td>
            <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
              {{ $logout->user_agent }}
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @else
    <div class="empty-state">
      <i class="fa-solid fa-inbox"></i>
      <p>No logout history available</p>
    </div>
    @endif
  </div>
</div>

<!-- Footer -->
<div class="footer">
  Copyright © {{ date('Y') }} City College of Calamba. All rights reserved.
</div>

</body>
</html>

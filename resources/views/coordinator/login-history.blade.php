<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login History - E-SPES</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
body { background: #f5f5f5; min-height: 100vh; display: flex; flex-direction: column; }
.header { background: #242242; color: white; padding: 15px 40px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.header-left { display: flex; align-items: center; gap: 15px; }
.header img { width: 45px; height: 45px; object-fit: contain; }
.header-text h1 { margin: 0; font-size: 18px; font-weight: 600; }
.header-text p { margin: 3px 0 0; font-size: 12px; opacity: 0.9; font-style: italic; }
.container { flex: 1; padding: 30px; max-width: 1400px; width: 100%; margin: 0 auto; }
.breadcrumb { margin-bottom: 20px; font-size: 14px; color: #666; }
.breadcrumb a { color: #667eea; text-decoration: none; }
.breadcrumb a:hover { text-decoration: underline; }
.section { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f0f0f0; }
.section-header h2 { font-size: 22px; color: #242242; }
.back-btn { background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: background 0.3s; }
.back-btn:hover { background: #764ba2; }
.table-responsive { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
thead { background: #f8f9fa; }
th { padding: 12px; text-align: left; font-size: 13px; font-weight: 600; color: #495057; border-bottom: 2px solid #dee2e6; }
td { padding: 12px; font-size: 13px; color: #666; border-bottom: 1px solid #f0f0f0; }
.badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; }
.badge.success { background: #d4edda; color: #28a745; }
.badge.danger { background: #f8d7da; color: #dc3545; }
.pagination { display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 20px; }
.pagination a, .pagination span { display: inline-block; padding: 8px 12px; border: 1px solid #dee2e6; border-radius: 5px; text-decoration: none; color: #667eea; font-size: 14px; }
.pagination span { background: #667eea; color: white; }
.pagination a:hover { background: #f8f9fa; }
.empty-state { text-align: center; padding: 60px; color: #999; }
.empty-state i { font-size: 64px; margin-bottom: 20px; opacity: 0.3; }
.footer { background: #242242; color: white; text-align: center; padding: 15px; font-size: 13px; }
</style>
</head>
<body>

<div class="header">
  <div class="header-left">
    <img src="{{ asset('images/CalSPESlogonobg.png') }}" alt="Logo" onerror="this.src='{{ asset('images/CalSPESlogo.jfif') }}'">
    <div class="header-text">
      <h1>Special Program for the Employment of Students</h1>
      <p>Login History</p>
    </div>
  </div>
</div>

<div class="container">
  <div class="breadcrumb">
    <a href="{{ route('coordinator.dashboard') }}"><i class="fa-solid fa-home"></i> Dashboard</a> / Login History
  </div>

  <div class="section">
    <div class="section-header">
      <h2><i class="fa-solid fa-history"></i> Complete Login History</h2>
      <a href="{{ route('coordinator.dashboard') }}" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
      </a>
    </div>

    @if($loginLogs->count() > 0)
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Date & Time</th>
            <th>Status</th>
            <th>Email</th>
            <th>IP Address</th>
            <th>User Agent</th>
            <th>Failure Reason</th>
          </tr>
        </thead>
        <tbody>
          @foreach($loginLogs as $index => $log)
          <tr>
            <td>{{ $loginLogs->firstItem() + $index }}</td>
            <td>{{ $log->created_at->format('M d, Y H:i:s') }}</td>
            <td>
              <span class="badge {{ $log->status === 'SUCCESS' ? 'success' : 'danger' }}">
                {{ $log->status }}
              </span>
            </td>
            <td>{{ $log->email_entered }}</td>
            <td>{{ $log->ip_address }}</td>
            <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
              {{ $log->user_agent }}
            </td>
            <td>{{ $log->failure_reason ?? '-' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="pagination">
      @if ($loginLogs->onFirstPage())
        <span><i class="fa-solid fa-chevron-left"></i></span>
      @else
        <a href="{{ $loginLogs->previousPageUrl() }}"><i class="fa-solid fa-chevron-left"></i></a>
      @endif

      <span>Page {{ $loginLogs->currentPage() }} of {{ $loginLogs->lastPage() }}</span>

      @if ($loginLogs->hasMorePages())
        <a href="{{ $loginLogs->nextPageUrl() }}"><i class="fa-solid fa-chevron-right"></i></a>
      @else
        <span><i class="fa-solid fa-chevron-right"></i></span>
      @endif
    </div>
    @else
    <div class="empty-state">
      <i class="fa-solid fa-inbox"></i>
      <p>No login history available</p>
    </div>
    @endif
  </div>
</div>

<div class="footer">
  Copyright © {{ date('Y') }} City College of Calamba. All rights reserved.
</div>

</body>
</html>

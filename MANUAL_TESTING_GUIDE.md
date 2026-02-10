# Manual Testing Guide - E-SPES Authentication System

## Prerequisites
1. Ensure MySQL server is running (Laragon)
2. Database `e_spes` exists and migrations are run
3. Test users are seeded
4. Laravel server is running: `php artisan serve`

## Test Scenarios

### Scenario 1: Successful Login Flow

**Steps:**
1. Open browser and navigate to `http://127.0.0.1:8000`
2. Should redirect to login page
3. Enter credentials:
   - Email: `coordinator@espes.local`
   - Password: `password123`
4. Click "LOGIN" button

**Expected Results:**
✅ Redirects to `/coordinator/dashboard`
✅ Welcome message displays user name
✅ Shows last login date and IP
✅ Displays 4 statistics cards
✅ Shows recent login activity table
✅ Shows recent logout activity table
✅ User avatar with first letter of name

**Database Verification:**
```sql
-- Should create new entry in login_logs
SELECT * FROM login_logs 
WHERE email_entered = 'coordinator@espes.local' 
ORDER BY created_at DESC 
LIMIT 1;
-- Status should be 'SUCCESS'

-- Should update user's last_login_at and last_login_ip
SELECT user_id, name, last_login_at, last_login_ip, failed_login_attempts 
FROM users 
WHERE email = 'coordinator@espes.local';
-- failed_login_attempts should be 0
```

---

### Scenario 2: Failed Login - Invalid Password

**Steps:**
1. Navigate to login page
2. Enter:
   - Email: `coordinator@espes.local`
   - Password: `wrongpassword`
3. Click "LOGIN"

**Expected Results:**
✅ Stays on login page
✅ Shows error message about invalid credentials
✅ Shows remaining attempts message
✅ Email field pre-filled with entered email

**Database Verification:**
```sql
-- Should create failed login entry
SELECT * FROM login_logs 
WHERE email_entered = 'coordinator@espes.local' 
AND status = 'FAILED'
ORDER BY created_at DESC 
LIMIT 1;
-- Should have failure_reason like '%Invalid password%'

-- Should increment failed_login_attempts
SELECT failed_login_attempts FROM users 
WHERE email = 'coordinator@espes.local';
-- Should be 1
```

---

### Scenario 3: Account Lockout After 5 Failed Attempts

**Steps:**
1. Navigate to login page
2. Enter wrong password 5 times consecutively:
   - Email: `john.doe@espes.local`
   - Password: `wrong123`
3. On 5th attempt, note the error message
4. Try login with CORRECT password:
   - Email: `john.doe@espes.local`
   - Password: `password123`

**Expected Results:**
✅ After 5th failed attempt: Account locked message
✅ Shows "locked for X minutes" message
✅ Even with correct password, login is blocked
✅ Error message includes lockout duration

**Database Verification:**
```sql
-- Check lock status
SELECT user_id, name, email, failed_login_attempts, locked_until, is_active
FROM users 
WHERE email = 'john.doe@espes.local';
-- failed_login_attempts = 5
-- locked_until = timestamp 15 minutes in future

-- Check all failed attempts logged
SELECT COUNT(*) as failed_count 
FROM login_logs 
WHERE email_entered = 'john.doe@espes.local' 
AND status = 'FAILED';
-- Should be at least 5

-- Unlock manually for next test:
UPDATE users 
SET failed_login_attempts = 0, locked_until = NULL 
WHERE email = 'john.doe@espes.local';
```

---

### Scenario 4: Non-Coordinator Login Rejection

**Steps:**
1. Navigate to login page
2. Enter:
   - Email: `admin@espes.local`
   - Password: `password123`
3. Click "LOGIN"

**Expected Results:**
✅ Login rejected
✅ Error: "You do not have permission to access this system"
✅ User not logged in
✅ Stays on login page

**Database Verification:**
```sql
-- Should log failed attempt with specific reason
SELECT * FROM login_logs 
WHERE email_entered = 'admin@espes.local'
ORDER BY created_at DESC 
LIMIT 1;
-- status = 'FAILED'
-- failure_reason = 'User is not a coordinator'
```

---

### Scenario 5: Inactive User Login Rejection

**Steps:**
1. Navigate to login page
2. Enter:
   - Email: `inactive@espes.local`
   - Password: `password123`
3. Click "LOGIN"

**Expected Results:**
✅ Login rejected
✅ Error: "Your account has been deactivated"
✅ User not logged in

**Database Verification:**
```sql
-- Check inactive status
SELECT user_id, name, email, is_active 
FROM users 
WHERE email = 'inactive@espes.local';
-- is_active = 0

-- Check failed login logged
SELECT * FROM login_logs 
WHERE email_entered = 'inactive@espes.local'
ORDER BY created_at DESC 
LIMIT 1;
-- failure_reason = 'Account is inactive'
```

---

### Scenario 6: User Not Found

**Steps:**
1. Navigate to login page
2. Enter:
   - Email: `nonexistent@espes.local`
   - Password: `anypassword`
3. Click "LOGIN"

**Expected Results:**
✅ Error: "These credentials do not match our records"
✅ Login rejected

**Database Verification:**
```sql
-- Should log with null user_id
SELECT * FROM login_logs 
WHERE email_entered = 'nonexistent@espes.local'
ORDER BY created_at DESC 
LIMIT 1;
-- user_id = NULL
-- status = 'FAILED'
-- failure_reason = 'User not found'
```

---

### Scenario 7: Dashboard Statistics Verification

**Steps:**
1. Login as coordinator
2. View dashboard
3. Note the statistics cards

**Expected Results:**
✅ "Successful Logins" - shows count of successful logins
✅ "Failed Attempts" - shows count of failed logins
✅ "Total Sessions" - shows count of logout logs
✅ "Account Status" - shows "Active"

**Database Verification:**
```sql
-- Manual verification of stats
SELECT 
    (SELECT COUNT(*) FROM login_logs WHERE user_id = 1 AND status = 'SUCCESS') as successful_logins,
    (SELECT COUNT(*) FROM login_logs WHERE user_id = 1 AND status = 'FAILED') as failed_attempts,
    (SELECT COUNT(*) FROM logout_logs WHERE user_id = 1) as total_sessions;
-- Numbers should match dashboard display
```

---

### Scenario 8: Recent Login Activity Display

**Steps:**
1. Login as coordinator
2. Scroll to "Recent Login Activity" section
3. View the table

**Expected Results:**
✅ Shows last 10 login attempts (success and failed)
✅ Columns: Date & Time, Status, IP Address, Device, Reason
✅ Success badges are green
✅ Failed badges are red
✅ Most recent at top
✅ "View All" link present

**Database Verification:**
```sql
-- Get last 10 login attempts for user
SELECT 
    log_id,
    created_at,
    status,
    email_entered,
    ip_address,
    user_agent,
    failure_reason
FROM login_logs 
WHERE user_id = 1
ORDER BY created_at DESC 
LIMIT 10;
```

---

### Scenario 9: Complete Login History Page

**Steps:**
1. From dashboard, click "View All" on Recent Login Activity
2. Should navigate to `/coordinator/login-history`

**Expected Results:**
✅ Shows complete login history in table
✅ Paginated (20 per page)
✅ Columns: #, Date & Time, Status, Email, IP, User Agent, Failure Reason
✅ Pagination controls at bottom
✅ "Back to Dashboard" button works
✅ Breadcrumb navigation present

**Test Pagination:**
- If > 20 records exist, should see page numbers
- Click next/previous buttons
- Page numbers should update
- Records should change

---

### Scenario 10: Logout Flow with Session Tracking

**Steps:**
1. Login as coordinator
2. Wait 1-2 minutes (for measurable session duration)
3. Browse dashboard, click through history pages
4. Click "Logout" button

**Expected Results:**
✅ Redirected to login page
✅ Success message: "You have been logged out successfully"
✅ Cannot access dashboard without logging in again
✅ Session destroyed

**Database Verification:**
```sql
-- Check logout was logged
SELECT * FROM logout_logs 
WHERE user_id = 1
ORDER BY created_at DESC 
LIMIT 1;
-- session_duration should be > 60 seconds
-- ip_address should match
-- user_agent should be populated

-- Verify session duration calculation
SELECT 
    logout_id,
    user_id,
    created_at,
    session_duration,
    CONCAT(
        FLOOR(session_duration / 3600), 'h ',
        FLOOR((session_duration % 3600) / 60), 'm ',
        (session_duration % 60), 's'
    ) as formatted_duration
FROM logout_logs 
WHERE user_id = 1
ORDER BY created_at DESC 
LIMIT 1;
```

---

### Scenario 11: Complete Logout History Page

**Steps:**
1. Login as coordinator
2. From dashboard, click "View All" on Recent Logout Activity
3. Should navigate to `/coordinator/logout-history`

**Expected Results:**
✅ Shows complete logout history
✅ Paginated (20 per page)
✅ Columns: #, Date & Time, IP Address, Session Duration, User Agent
✅ Session duration formatted (e.g., "2m 30s")
✅ Pagination controls work
✅ "Back to Dashboard" button works

---

### Scenario 12: Remember Me Functionality

**Steps:**
1. Login with "Remember Me" checked
2. Close browser completely
3. Reopen browser and navigate to site

**Expected Results:**
✅ User still logged in
✅ Redirects to dashboard automatically

**Steps (without Remember Me):**
1. Logout
2. Login WITHOUT checking "Remember Me"
3. Close browser
4. Reopen and navigate to site

**Expected Results:**
✅ Shows login page (session expired)

---

### Scenario 13: Middleware Protection Test

**Steps:**
1. Without logging in, try to directly access:
   - `http://127.0.0.1:8000/coordinator/dashboard`
   - `http://127.0.0.1:8000/coordinator/login-history`
   - `http://127.0.0.1:8000/coordinator/logout-history`

**Expected Results:**
✅ All redirect to login page
✅ Shows message: "Please login to access this page"

**After Login:**
1. Login as coordinator
2. Manually deactivate your account in database:
```sql
UPDATE users SET is_active = 0 WHERE email = 'coordinator@espes.local';
```
3. Try to access dashboard

**Expected Results:**
✅ Logged out automatically
✅ Redirected to login
✅ Error: "Your account has been deactivated"

---

### Scenario 14: CSRF Protection Test

**Steps:**
1. Open browser developer tools (F12)
2. Go to login page
3. View page source and find the CSRF token in the form
4. Note the token value
5. Try to submit form without token or with invalid token

**Expected Results:**
✅ Request rejected with 419 error (CSRF token mismatch)

---

### Scenario 15: Password Visibility Toggle

**Steps:**
1. On login page, enter password
2. Click the eye icon next to password field
3. Click again

**Expected Results:**
✅ First click: password becomes visible (eye icon changes to eye-slash)
✅ Second click: password hidden again (eye-slash changes back to eye)

---

## Common SQL Queries for Testing

### View all login attempts today
```sql
SELECT 
    l.log_id,
    u.name,
    l.email_entered,
    l.status,
    l.ip_address,
    l.failure_reason,
    l.created_at
FROM login_logs l
LEFT JOIN users u ON l.user_id = u.user_id
WHERE DATE(l.created_at) = CURDATE()
ORDER BY l.created_at DESC;
```

### View authentication summary for all users
```sql
SELECT 
    u.user_id,
    u.name,
    u.email,
    u.role,
    u.is_active,
    u.failed_login_attempts,
    u.locked_until,
    u.last_login_at,
    u.last_login_ip,
    (SELECT COUNT(*) FROM login_logs WHERE user_id = u.user_id AND status = 'SUCCESS') as total_logins,
    (SELECT COUNT(*) FROM login_logs WHERE user_id = u.user_id AND status = 'FAILED') as total_failures
FROM users u
ORDER BY u.user_id;
```

### Reset all user lockouts (for testing)
```sql
UPDATE users 
SET failed_login_attempts = 0, locked_until = NULL;
```

### Clear all login logs (for fresh testing)
```sql
TRUNCATE TABLE login_logs;
```

### Clear all logout logs (for fresh testing)
```sql
TRUNCATE TABLE logout_logs;
```

---

## Performance Checks

### Check login speed
- Login should complete in < 2 seconds
- Dashboard should load in < 1 second

### Check pagination performance
- Each page of history should load quickly
- No noticeable lag when switching pages

### Check database query efficiency
- All queries should use indexes
- No N+1 query problems

```sql
-- Check indexes are present
SHOW INDEX FROM login_logs;
SHOW INDEX FROM logout_logs;
SHOW INDEX FROM users;
```

---

## Security Verification

### ✅ Checklist
- [ ] Passwords are hashed (check database - should see bcrypt hash)
- [ ] CSRF tokens present in all forms
- [ ] Session IDs regenerate on login
- [ ] Old sessions invalidated on logout
- [ ] Account lockout working after 5 attempts
- [ ] Role-based access enforced
- [ ] Inactive users blocked
- [ ] SQL injection protected (try: `admin' OR '1'='1`)
- [ ] XSS protected (try: `<script>alert('XSS')</script>` in email)

---

## Test Result Recording

| Scenario | Status | Notes |
|----------|--------|-------|
| 1. Successful Login | ⬜ | |
| 2. Failed Login | ⬜ | |
| 3. Account Lockout | ⬜ | |
| 4. Non-Coordinator Block | ⬜ | |
| 5. Inactive User Block | ⬜ | |
| 6. User Not Found | ⬜ | |
| 7. Dashboard Stats | ⬜ | |
| 8. Recent Activity | ⬜ | |
| 9. Login History | ⬜ | |
| 10. Logout Tracking | ⬜ | |
| 11. Logout History | ⬜ | |
| 12. Remember Me | ⬜ | |
| 13. Middleware Protection | ⬜ | |
| 14. CSRF Protection | ⬜ | |
| 15. Password Toggle | ⬜ | |

Mark ✅ for PASS, ❌ for FAIL, add notes as needed.

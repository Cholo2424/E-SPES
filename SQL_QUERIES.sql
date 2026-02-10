-- ============================================
-- E-SPES Authentication System - SQL Queries
-- ============================================

-- ============================
-- USER MANAGEMENT QUERIES
-- ============================

-- View all users with their status
SELECT 
    user_id,
    name,
    email,
    role,
    is_active,
    failed_login_attempts,
    locked_until,
    last_login_at,
    last_login_ip,
    created_at
FROM users
ORDER BY created_at DESC;

-- View only active coordinators
SELECT user_id, name, email, last_login_at
FROM users
WHERE role = 'coordinator' AND is_active = 1
ORDER BY last_login_at DESC;

-- View locked accounts
SELECT user_id, name, email, failed_login_attempts, locked_until
FROM users
WHERE locked_until IS NOT NULL AND locked_until > NOW()
ORDER BY locked_until DESC;

-- Manually unlock a specific user
UPDATE users 
SET failed_login_attempts = 0, locked_until = NULL 
WHERE email = 'coordinator@espes.local';

-- Unlock all locked accounts
UPDATE users 
SET failed_login_attempts = 0, locked_until = NULL 
WHERE locked_until IS NOT NULL;

-- Deactivate a user account
UPDATE users 
SET is_active = 0 
WHERE email = 'user@example.com';

-- Reactivate a user account
UPDATE users 
SET is_active = 1 
WHERE email = 'user@example.com';

-- Change user role
UPDATE users 
SET role = 'coordinator' 
WHERE email = 'user@example.com';

-- ============================
-- LOGIN LOG QUERIES
-- ============================

-- View all login attempts (with user details)
SELECT 
    l.log_id,
    l.user_id,
    u.name,
    l.email_entered,
    l.status,
    l.ip_address,
    l.failure_reason,
    l.created_at
FROM login_logs l
LEFT JOIN users u ON l.user_id = u.user_id
ORDER BY l.created_at DESC
LIMIT 100;

-- View only successful logins
SELECT 
    l.log_id,
    u.name,
    u.email,
    l.ip_address,
    l.user_agent,
    l.created_at
FROM login_logs l
JOIN users u ON l.user_id = u.user_id
WHERE l.status = 'SUCCESS'
ORDER BY l.created_at DESC
LIMIT 50;

-- View only failed login attempts
SELECT 
    l.log_id,
    l.email_entered,
    l.failure_reason,
    l.ip_address,
    l.created_at
FROM login_logs l
WHERE l.status = 'FAILED'
ORDER BY l.created_at DESC
LIMIT 50;

-- View login attempts for a specific user
SELECT 
    log_id,
    status,
    email_entered,
    ip_address,
    failure_reason,
    created_at
FROM login_logs
WHERE user_id = 1
ORDER BY created_at DESC;

-- View login attempts from today
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

-- View login attempts from last 7 days
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total_attempts,
    SUM(CASE WHEN status = 'SUCCESS' THEN 1 ELSE 0 END) as successful,
    SUM(CASE WHEN status = 'FAILED' THEN 1 ELSE 0 END) as failed
FROM login_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;

-- View failed attempts by user (to identify potential security issues)
SELECT 
    u.user_id,
    u.name,
    u.email,
    COUNT(*) as failed_count,
    MAX(l.created_at) as last_failed_attempt
FROM login_logs l
JOIN users u ON l.user_id = u.user_id
WHERE l.status = 'FAILED'
GROUP BY u.user_id, u.name, u.email
ORDER BY failed_count DESC;

-- View suspicious login attempts (multiple failures from same IP)
SELECT 
    ip_address,
    email_entered,
    COUNT(*) as attempt_count,
    MAX(created_at) as last_attempt
FROM login_logs
WHERE status = 'FAILED'
GROUP BY ip_address, email_entered
HAVING COUNT(*) >= 3
ORDER BY attempt_count DESC;

-- ============================
-- LOGOUT LOG QUERIES
-- ============================

-- View all logout events
SELECT 
    l.logout_id,
    u.name,
    u.email,
    l.ip_address,
    l.session_duration,
    CONCAT(
        FLOOR(l.session_duration / 3600), 'h ',
        FLOOR((l.session_duration % 3600) / 60), 'm ',
        (l.session_duration % 60), 's'
    ) as formatted_duration,
    l.created_at
FROM logout_logs l
JOIN users u ON l.user_id = u.user_id
ORDER BY l.created_at DESC
LIMIT 100;

-- View logout events for specific user
SELECT 
    logout_id,
    ip_address,
    session_duration,
    created_at
FROM logout_logs
WHERE user_id = 1
ORDER BY created_at DESC;

-- View average session duration by user
SELECT 
    u.user_id,
    u.name,
    u.email,
    COUNT(*) as total_sessions,
    AVG(l.session_duration) as avg_duration_seconds,
    CONCAT(
        FLOOR(AVG(l.session_duration) / 60), ' minutes'
    ) as avg_duration_formatted
FROM logout_logs l
JOIN users u ON l.user_id = u.user_id
GROUP BY u.user_id, u.name, u.email
ORDER BY total_sessions DESC;

-- View today's logout events
SELECT 
    l.logout_id,
    u.name,
    l.session_duration,
    l.created_at
FROM logout_logs l
JOIN users u ON l.user_id = u.user_id
WHERE DATE(l.created_at) = CURDATE()
ORDER BY l.created_at DESC;

-- ============================
-- STATISTICS & REPORTS
-- ============================

-- Complete user authentication summary
SELECT 
    u.user_id,
    u.name,
    u.email,
    u.role,
    u.is_active,
    u.failed_login_attempts,
    u.last_login_at,
    u.last_login_ip,
    (SELECT COUNT(*) FROM login_logs WHERE user_id = u.user_id AND status = 'SUCCESS') as successful_logins,
    (SELECT COUNT(*) FROM login_logs WHERE user_id = u.user_id AND status = 'FAILED') as failed_logins,
    (SELECT COUNT(*) FROM logout_logs WHERE user_id = u.user_id) as total_sessions,
    (SELECT MAX(created_at) FROM login_logs WHERE user_id = u.user_id AND status = 'SUCCESS') as last_successful_login,
    (SELECT MAX(created_at) FROM logout_logs WHERE user_id = u.user_id) as last_logout
FROM users u
WHERE u.role = 'coordinator'
ORDER BY u.last_login_at DESC;

-- Daily login statistics
SELECT 
    DATE(created_at) as login_date,
    COUNT(DISTINCT user_id) as unique_users,
    COUNT(*) as total_attempts,
    SUM(CASE WHEN status = 'SUCCESS' THEN 1 ELSE 0 END) as successful,
    SUM(CASE WHEN status = 'FAILED' THEN 1 ELSE 0 END) as failed,
    ROUND(SUM(CASE WHEN status = 'SUCCESS' THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as success_rate
FROM login_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY login_date DESC;

-- Most active users (by login count)
SELECT 
    u.user_id,
    u.name,
    u.email,
    COUNT(*) as login_count,
    MAX(l.created_at) as last_login
FROM login_logs l
JOIN users u ON l.user_id = u.user_id
WHERE l.status = 'SUCCESS'
GROUP BY u.user_id, u.name, u.email
ORDER BY login_count DESC
LIMIT 10;

-- Login activity by hour of day
SELECT 
    HOUR(created_at) as hour,
    COUNT(*) as login_count,
    SUM(CASE WHEN status = 'SUCCESS' THEN 1 ELSE 0 END) as successful
FROM login_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY HOUR(created_at)
ORDER BY hour;

-- Failure reasons summary
SELECT 
    failure_reason,
    COUNT(*) as count
FROM login_logs
WHERE status = 'FAILED' AND failure_reason IS NOT NULL
GROUP BY failure_reason
ORDER BY count DESC;

-- Recent activity for audit (last 24 hours)
SELECT 
    'LOGIN' as event_type,
    u.name,
    l.email_entered as email,
    l.status,
    l.ip_address,
    l.created_at
FROM login_logs l
LEFT JOIN users u ON l.user_id = u.user_id
WHERE l.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
UNION ALL
SELECT 
    'LOGOUT' as event_type,
    u.name,
    u.email,
    'N/A' as status,
    l.ip_address,
    l.created_at
FROM logout_logs l
JOIN users u ON l.user_id = u.user_id
WHERE l.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY created_at DESC;

-- ============================
-- MAINTENANCE & CLEANUP
-- ============================

-- Delete login logs older than 90 days
DELETE FROM login_logs 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Delete logout logs older than 90 days
DELETE FROM logout_logs 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Count total records in each table
SELECT 'users' as table_name, COUNT(*) as record_count FROM users
UNION ALL
SELECT 'login_logs', COUNT(*) FROM login_logs
UNION ALL
SELECT 'logout_logs', COUNT(*) FROM logout_logs;

-- Clear all login logs (use with caution)
-- TRUNCATE TABLE login_logs;

-- Clear all logout logs (use with caution)
-- TRUNCATE TABLE logout_logs;

-- Reset all user failed attempts and locks
-- UPDATE users SET failed_login_attempts = 0, locked_until = NULL;

-- ============================
-- SECURITY AUDIT QUERIES
-- ============================

-- Find accounts with multiple failed attempts today
SELECT 
    u.user_id,
    u.name,
    u.email,
    COUNT(*) as failed_today,
    MAX(l.created_at) as last_failed_attempt,
    u.failed_login_attempts as total_failed_attempts,
    u.locked_until
FROM login_logs l
JOIN users u ON l.user_id = u.user_id
WHERE l.status = 'FAILED' 
  AND DATE(l.created_at) = CURDATE()
GROUP BY u.user_id, u.name, u.email, u.failed_login_attempts, u.locked_until
HAVING COUNT(*) >= 3
ORDER BY failed_today DESC;

-- Find login attempts from unusual IP addresses
SELECT 
    l.email_entered,
    l.ip_address,
    l.status,
    COUNT(*) as attempt_count,
    MIN(l.created_at) as first_attempt,
    MAX(l.created_at) as last_attempt
FROM login_logs l
GROUP BY l.email_entered, l.ip_address, l.status
ORDER BY last_attempt DESC;

-- Find sessions with unusually short or long duration
SELECT 
    l.logout_id,
    u.name,
    u.email,
    l.session_duration,
    CONCAT(
        FLOOR(l.session_duration / 3600), 'h ',
        FLOOR((l.session_duration % 3600) / 60), 'm '
    ) as formatted_duration,
    l.created_at
FROM logout_logs l
JOIN users u ON l.user_id = u.user_id
WHERE l.session_duration < 60 OR l.session_duration > 28800  -- Less than 1 min or more than 8 hours
ORDER BY l.session_duration DESC;

-- ============================
-- TESTING QUERIES
-- ============================

-- Insert test login log (for testing dashboard)
-- INSERT INTO login_logs (user_id, email_entered, status, ip_address, user_agent, created_at)
-- VALUES (1, 'coordinator@espes.local', 'SUCCESS', '127.0.0.1', 'Mozilla/5.0', NOW());

-- Insert test failed login (for testing failed attempts)
-- INSERT INTO login_logs (user_id, email_entered, status, ip_address, user_agent, failure_reason, created_at)
-- VALUES (1, 'coordinator@espes.local', 'FAILED', '127.0.0.1', 'Mozilla/5.0', 'Invalid password', NOW());

-- Insert test logout log
-- INSERT INTO logout_logs (user_id, ip_address, user_agent, session_duration, created_at)
-- VALUES (1, '127.0.0.1', 'Mozilla/5.0', 1800, NOW());

-- ============================
-- BACKUP QUERIES
-- ============================

-- Export user data (structure for backup)
SELECT 
    user_id,
    name,
    email,
    role,
    is_active,
    last_login_at,
    created_at
FROM users
INTO OUTFILE '/tmp/users_backup.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';

-- Note: Adjust path based on MySQL secure_file_priv setting
-- Run: SHOW VARIABLES LIKE "secure_file_priv"; to find allowed directory

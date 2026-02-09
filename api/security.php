<?php
/* ===============================================
   SECURITY & CSRF PROTECTION
   File: api/security.php
   =============================== */

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF token HTML input
 */
function getCSRFTokenInput() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Sanitize output (prevent XSS)
 */
function sanitizeOutput($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect with message
 */
function redirectWithMessage($location, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $location");
    exit;
}

/**
 * Get and clear message
 */
function getFlashMessage() {
    $message = $_SESSION['message'] ?? null;
    $type = $_SESSION['message_type'] ?? 'info';
    
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
    
    if ($message) {
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Display flash message HTML
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    if (!$flash) return '';
    
    $type = $flash['type'];
    $color = $type === 'success' ? '#27ae60' : ($type === 'error' ? '#e74c3c' : '#3498db');
    
    return '
    <div style="background:' . $color . ';color:white;padding:12px 15px;border-radius:6px;margin-bottom:15px;text-align:center;">
        ' . htmlspecialchars($flash['message']) . '
    </div>
    ';
}
?>

<?php
/* ===============================================
   VALIDATION FUNCTIONS
   File: api/validation.php
   =============================== */

/**
 * Validate email format
 */
function validateEmail($email) {
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }
    return false;
}

/**
 * Validate password strength
 */
function validatePassword($password) {
    if (strlen($password) < 8) {
        return ['valid' => false, 'message' => 'Password must be at least 8 characters long.'];
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one uppercase letter.'];
    }
    if (!preg_match('/[a-z]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one lowercase letter.'];
    }
    if (!preg_match('/[0-9]/', $password)) {
        return ['valid' => false, 'message' => 'Password must contain at least one number.'];
    }
    return ['valid' => true, 'message' => 'Password is strong.'];
}

/**
 * Validate fullname
 */
function validateFullname($name) {
    $name = trim($name);
    if (strlen($name) < 2) {
        return ['valid' => false, 'message' => 'Name must be at least 2 characters long.'];
    }
    if (strlen($name) > 100) {
        return ['valid' => false, 'message' => 'Name must not exceed 100 characters.'];
    }
    if (!preg_match('/^[a-zA-Z\s\-\'\.]+$/', $name)) {
        return ['valid' => false, 'message' => 'Name contains invalid characters. Only letters, spaces, hyphens, and apostrophes are allowed.'];
    }
    return ['valid' => true, 'message' => 'Name is valid.'];
}

/**
 * Validate phone number
 */
function validatePhone($phone) {
    $phone = preg_replace('/[^0-9\+\-\s\(\)]/', '', $phone);
    $phone = trim($phone);
    
    if (strlen($phone) < 7) {
        return ['valid' => false, 'message' => 'Phone number must be at least 7 digits.'];
    }
    if (strlen($phone) > 20) {
        return ['valid' => false, 'message' => 'Phone number must not exceed 20 characters.'];
    }
    if (!preg_match('/^[\+]?[0-9\s\-\(\)]+$/', $phone)) {
        return ['valid' => false, 'message' => 'Phone number format is invalid.'];
    }
    return ['valid' => true, 'message' => 'Phone is valid.', 'value' => $phone];
}

/**
 * Validate address
 */
function validateAddress($address) {
    $address = trim($address);
    if (strlen($address) < 5) {
        return ['valid' => false, 'message' => 'Address must be at least 5 characters long.'];
    }
    if (strlen($address) > 255) {
        return ['valid' => false, 'message' => 'Address must not exceed 255 characters.'];
    }
    if (!preg_match('/^[a-zA-Z0-9\s\,\.\-\#]+$/', $address)) {
        return ['valid' => false, 'message' => 'Address contains invalid characters.'];
    }
    return ['valid' => true, 'message' => 'Address is valid.', 'value' => $address];
}

/**
 * Validate product name
 */
function validateProductName($name) {
    $name = trim($name);
    if (strlen($name) < 3) {
        return ['valid' => false, 'message' => 'Product name must be at least 3 characters long.'];
    }
    if (strlen($name) > 150) {
        return ['valid' => false, 'message' => 'Product name must not exceed 150 characters.'];
    }
    return ['valid' => true, 'message' => 'Product name is valid.', 'value' => $name];
}

/**
 * Validate price
 */
function validatePrice($price) {
    if (!is_numeric($price) || $price <= 0) {
        return ['valid' => false, 'message' => 'Price must be a positive number.'];
    }
    if ($price > 999999.99) {
        return ['valid' => false, 'message' => 'Price is too high.'];
    }
    return ['valid' => true, 'message' => 'Price is valid.', 'value' => floatval($price)];
}

/**
 * Validate file upload (image)
 */
function validateImageFile($file) {
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== 0) {
        return ['valid' => false, 'message' => 'File upload failed. Error code: ' . ($file['error'] ?? 'unknown')];
    }

    // Check file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'message' => 'File size exceeds 5MB limit.'];
    }

    // Check file type
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes)) {
        return ['valid' => false, 'message' => 'File type not allowed. Only JPG, PNG, GIF, and WebP are allowed.'];
    }

    // Check file extension
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) {
        return ['valid' => false, 'message' => 'File extension not allowed.'];
    }

    return ['valid' => true, 'message' => 'File is valid.'];
}

/**
 * Sanitize text input
 */
function sanitizeText($text) {
    $text = trim($text);
    $text = stripslashes($text);
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    return $text;
}

/**
 * Check if input is empty
 */
function isEmpty($value) {
    return empty(trim($value ?? ''));
}

/**
 * Get error message
 */
function getErrorMessage($validation) {
    return $validation['message'] ?? 'Validation failed.';
}
?>

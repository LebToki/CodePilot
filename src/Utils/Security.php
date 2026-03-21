<?php
/**
 * Security utilities for CodePilot
 */

namespace CodePilot\Utils;

class Security
{
    /**
     * Sanitize user input to prevent XSS and injection attacks
     */
    public static function sanitizeInput(string $input, string $type = 'string'): string
    {
        switch ($type) {
            case 'filename':
                // Allow only alphanumeric, hyphens, underscores, and dots
                return preg_replace('/[^a-zA-Z0-9\-_.]/', '', $input);
                
            case 'path':
                // Remove dangerous path traversal attempts
                $input = str_replace(['..', '/', '\\', '//', '\\\\'], '', $input);
                return preg_replace('/[^a-zA-Z0-9\-_.\/\\\\]/', '', $input);
                
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
                
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
                
            default:
                return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Validate file path to prevent directory traversal
     */
    public static function validateFilePath(string $path, array $allowedPaths): ?string
    {
        $realPath = realpath($path);
        if (!$realPath) {
            return null;
        }
        
        foreach ($allowedPaths as $allowedPath) {
            $realAllowedPath = realpath($allowedPath);
            if ($realAllowedPath) {
                if ($realPath === $realAllowedPath) {
                    return $realPath;
                }

                $normalizedReal = str_replace('\\', '/', $realPath);
                $normalizedAllowed = rtrim(str_replace('\\', '/', $realAllowedPath), '/') . '/';

                if (strpos($normalizedReal, $normalizedAllowed) === 0) {
                    return $realPath;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken(): string
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken(string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }
    
    /**
     * Rate limiting check
     */
    public static function checkRateLimit(string $identifier, int $maxRequests = 60, int $windowSeconds = 3600): bool
    {
        $cacheDir = dirname(__DIR__, 2) . '/data/rate_limit';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $file = $cacheDir . '/' . md5($identifier) . '.json';
        $now = time();
        
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($now - $data['reset_time'] > $windowSeconds) {
                // Reset window
                $data = ['count' => 1, 'reset_time' => $now];
                file_put_contents($file, json_encode($data));
                return true;
            }
            
            if ($data['count'] >= $maxRequests) {
                return false;
            }
            
            $data['count']++;
            file_put_contents($file, json_encode($data));
            return true;
        }
        
        // First request
        $data = ['count' => 1, 'reset_time' => $now];
        file_put_contents($file, json_encode($data));
        return true;
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent(string $event, string $details = ''): void
    {
        $logDir = dirname(__DIR__, 2) . '/data/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/security.log';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $logEntry = "[$timestamp] [$ip] [$userAgent] $event: $details\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
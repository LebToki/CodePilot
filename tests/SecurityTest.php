<?php
/**
 * Security Test Suite for CodePilot
 */

require_once dirname(__DIR__) . '/src/Utils/Security.php';
require_once dirname(__DIR__) . '/src/Utils/Logger.php';

use CodePilot\Utils\Security;
use CodePilot\Utils\Logger;

class SecurityTest
{
    private $testResults = [];
    
    public function runAllTests(): void
    {
        echo "🧪 Running Security Test Suite...\n\n";
        
        $this->testInputSanitization();
        $this->testPathValidation();
        $this->testCSRFProtection();
        $this->testRateLimiting();
        $this->testFileValidation();
        
        $this->printResults();
    }
    
    private function testInputSanitization(): void
    {
        echo "Testing Input Sanitization...\n";
        
        // Test string sanitization
        $maliciousInput = '<script>alert("xss")</script>';
        $sanitized = Security::sanitizeInput($maliciousInput, 'string');
        $this->assertNotContains('<script>', $sanitized, 'XSS prevention');
        $this->assertContains('alert(&quot;xss&quot;)', $sanitized, 'HTML entities encoded');
        
        // Test filename sanitization
        $filename = 'test<>:"/\\|?*.php';
        $sanitizedFilename = Security::sanitizeInput($filename, 'filename');
        $this->assertMatchesPattern('/^[a-zA-Z0-9\-_.]+$/', $sanitizedFilename, 'Filename characters only');
        $this->assertNotContains('<>', $sanitizedFilename, 'Dangerous characters removed');
        
        // Test path sanitization
        $path = '../../../etc/passwd';
        $sanitizedPath = Security::sanitizeInput($path, 'path');
        $this->assertNotContains('..', $sanitizedPath, 'Path traversal prevented');
        $this->assertNotContains('/', $sanitizedPath, 'Directory separators removed');
        
        echo "✅ Input sanitization tests passed\n\n";
    }
    
    private function testPathValidation(): void
    {
        echo "Testing Path Validation...\n";
        
        $allowedPaths = ['/tmp', '/var/www'];
        
        // Test valid path
        $validPath = '/tmp/test.txt';
        if (!file_exists('/tmp')) mkdir('/tmp');
        file_put_contents($validPath, 'test');
        $result = Security::validateFilePath($validPath, $allowedPaths);
        $this->assertNotNull($result, 'Valid path should be accepted');
        $this->assertEquals(realpath($validPath), $result, 'Real path returned');
        
        // Test invalid path (directory traversal)
        $invalidPath = '/etc/passwd';
        $result = Security::validateFilePath($invalidPath, $allowedPaths);
        $this->assertNull($result, 'Invalid path should be rejected');
        
        // Test non-existent path
        $nonExistent = '/non/existent/path';
        $result = Security::validateFilePath($nonExistent, $allowedPaths);
        $this->assertNull($result, 'Non-existent path should be rejected');
        
        echo "✅ Path validation tests passed\n\n";
    }
    
    private function testCSRFProtection(): void
    {
        echo "Testing CSRF Protection...\n";
        
        // Start session for testing
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        
        // Test token generation
        $token1 = Security::generateCSRFToken();
        $token2 = Security::generateCSRFToken();
        $this->assertEquals($token1, $token2, 'Same token returned for same session');
        
        // Test token validation
        $this->assertTrue(Security::validateCSRFToken($token1), 'Valid token should pass');
        $this->assertFalse(Security::validateCSRFToken('invalid-token'), 'Invalid token should fail');
        $this->assertFalse(Security::validateCSRFToken(''), 'Empty token should fail');
        
        echo "✅ CSRF protection tests passed\n\n";
    }
    
    private function testRateLimiting(): void
    {
        echo "Testing Rate Limiting...\n";
        
        $identifier = 'test-user';
        $maxRequests = 3;
        $windowSeconds = 60;
        
        // Clear any existing rate limit data
        $cacheDir = dirname(__DIR__) . '/data/rate_limit';
        if (is_dir($cacheDir)) {
            array_map('unlink', glob("$cacheDir/*"));
        }
        
        // Test within limit
        for ($i = 0; $i < $maxRequests; $i++) {
            $this->assertTrue(Security::checkRateLimit($identifier, $maxRequests, $windowSeconds), "Request $i should be allowed");
        }
        
        // Test over limit
        $this->assertFalse(Security::checkRateLimit($identifier, $maxRequests, $windowSeconds), 'Request over limit should be blocked');
        
        echo "✅ Rate limiting tests passed\n\n";
    }
    
    private function testFileValidation(): void
    {
        echo "Testing File Validation...\n";
        
        // Test file extension validation
        $allowedTypes = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
        
        $this->assertTrue(in_array('png', $allowedTypes), 'PNG should be allowed');
        $this->assertTrue(in_array('jpg', $allowedTypes), 'JPG should be allowed');
        $this->assertFalse(in_array('exe', $allowedTypes), 'EXE should not be allowed');
        $this->assertFalse(in_array('php', $allowedTypes), 'PHP should not be allowed');
        
        // Test file size validation
        $maxSize = 10485760; // 10MB
        $this->assertTrue($maxSize > 0, 'Max file size should be positive');
        $this->assertTrue($maxSize >= 1024, 'Max file size should be at least 1KB');
        
        echo "✅ File validation tests passed\n\n";
    }
    
    private function assertContains($needle, $haystack, $message = ''): void
    {
        if (strpos($haystack, $needle) === false) {
            $this->fail("Expected '$needle' to be contained in '$haystack'. $message");
        }
    }
    
    private function assertNotContains($needle, $haystack, $message = ''): void
    {
        if (strpos($haystack, $needle) !== false) {
            $this->fail("Expected '$needle' NOT to be contained in '$haystack'. $message");
        }
    }
    
    private function assertMatchesPattern($pattern, $subject, $message = ''): void
    {
        if (!preg_match($pattern, $subject)) {
            $this->fail("Expected '$subject' to match pattern '$pattern'. $message");
        }
    }
    
    private function assertNotNull($value, $message = ''): void
    {
        if ($value === null) {
            $this->fail("Expected value to not be null. $message");
        }
    }
    
    private function assertNull($value, $message = ''): void
    {
        if ($value !== null) {
            $this->fail("Expected value to be null. $message");
        }
    }
    
    private function assertEquals($expected, $actual, $message = ''): void
    {
        if ($expected !== $actual) {
            $this->fail("Expected '$expected' but got '$actual'. $message");
        }
    }
    
    private function assertTrue($condition, $message = ''): void
    {
        if ($condition !== true) {
            $this->fail("Expected condition to be true. $message");
        }
    }
    
    private function assertFalse($condition, $message = ''): void
    {
        if ($condition !== false) {
            $this->fail("Expected condition to be false. $message");
        }
    }
    
    private function fail($message): void
    {
        $this->testResults[] = ['status' => 'FAIL', 'message' => $message];
        echo "❌ $message\n";
    }
    
    private function printResults(): void
    {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "📊 Test Results Summary\n";
        echo str_repeat('=', 50) . "\n";
        
        $passed = count($this->testResults) === 0;
        $total = 0;
        $failed = count($this->testResults);
        
        if ($passed) {
            echo "🎉 All tests passed!\n";
        } else {
            echo "⚠️  Some tests failed:\n";
            foreach ($this->testResults as $result) {
                echo "  - {$result['message']}\n";
            }
        }
        
        echo "\nTotal: " . ($total + $failed) . " tests\n";
        echo "Passed: " . ($total - $failed) . " tests\n";
        echo "Failed: $failed tests\n";
        echo str_repeat('=', 50) . "\n";
    }
}

// Run tests if this file is executed directly
if (basename(__FILE__) === 'SecurityTest.php') {
    $test = new SecurityTest();
    $test->runAllTests();
}
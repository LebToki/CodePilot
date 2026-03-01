<?php
/**
 * Logging utilities for CodePilot
 */

namespace CodePilot\Utils;

class Logger
{
    private static $logDir;
    private static $logLevel;
    
    const DEBUG = 100;
    const INFO = 200;
    const WARNING = 300;
    const ERROR = 400;
    const CRITICAL = 500;
    
    public static function init(string $logDir = null, int $logLevel = self::INFO): void
    {
        self::$logDir = $logDir ?? dirname(__DIR__, 2) . '/data/logs';
        self::$logLevel = $logLevel;
        
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }
    
    public static function debug(string $message, array $context = []): void
    {
        self::log(self::DEBUG, $message, $context);
    }
    
    public static function info(string $message, array $context = []): void
    {
        self::log(self::INFO, $message, $context);
    }
    
    public static function warning(string $message, array $context = []): void
    {
        self::log(self::WARNING, $message, $context);
    }
    
    public static function error(string $message, array $context = []): void
    {
        self::log(self::ERROR, $message, $context);
    }
    
    public static function critical(string $message, array $context = []): void
    {
        self::log(self::CRITICAL, $message, $context);
    }
    
    private static function log(int $level, string $message, array $context = []): void
    {
        if ($level < self::$logLevel) {
            return;
        }
        
        $levelNames = [
            self::DEBUG => 'DEBUG',
            self::INFO => 'INFO',
            self::WARNING => 'WARNING',
            self::ERROR => 'ERROR',
            self::CRITICAL => 'CRITICAL',
        ];
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $levelName = $levelNames[$level] ?? 'UNKNOWN';
        
        $contextStr = '';
        if (!empty($context)) {
            $contextStr = ' ' . json_encode($context);
        }
        
        $logEntry = "[$timestamp] [$ip] [$userAgent] [$levelName] $message{$contextStr}\n";
        
        $logFile = self::$logDir . '/app.log';
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
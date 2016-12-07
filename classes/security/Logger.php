<?php
class Logger {
    
    // A series of error constants
    const ACCOUNT_LOAD_ERROR = 1; 
    const CREDENTIALS_LOAD_ERROR = 2;
    const FAILED_LOGINS_LOAD_ERROR = 3;
    const TOKENS_LOAD_ERROR = 4;
    const INVALID_INPUT_ERROR = 5;
    const REQUEST_LIMIT_EXCEEDED = 6;
    const PERMISSIONS_LOAD_ERROR = 7;
    const ACCOUNT_TYPE_INVALID = 8;
    const ACCOUNT_FILE_DELETE_ERROR = 9;
    const PERMISSION_DENIED = 10;
    
    public static function logError($error, $option = null) {
    
        $message = '';
        
        if ($error === Logger::ACCOUNT_LOAD_ERROR) {
            $message = "Error 001: Failed to load account.";
        } else if ($error === Logger::CREDENTIALS_LOAD_ERROR) {
            $message = "Error 002: Failed to load credentials.";
        } else if ($error === Logger::FAILED_LOGINS_LOAD_ERROR) {
            $message = "Error 003: Failed to read failed logins.";
        } else if ($error === Logger::TOKENS_LOAD_ERROR) {
            $message = "Error 004: Failed to read tokens file.";
        } else if ($error === Logger::INVALID_INPUT_ERROR) {
            $message = "Error 005: Invalid input provided.";
        } else if ($error === Logger::REQUEST_LIMIT_EXCEEDED) {
            $message = "Error 006: Request limit for account exceeded.";
        } else if ($error === Logger::PERMISSIONS_LOAD_ERROR) {
            $message = "Error 007: Failed to load permissions file.";
        } else if ($error === Logger::ACCOUNT_TYPE_INVALID) {
            $message = "Error 008: Invalid account type loaded.";
        } else if ($error === Logger::ACCOUNT_FILE_DELETE_ERROR) {
            $message = "Error 009: Account file attempted to delete does not exist.";
        } else if ($error === Logger::PERMISSION_DENIED) {
            $message = "Error 010: Permission denied.";
        }
        
        if (!($option === null)) {
            $message .= " Info: " . $option;
        }
        
        self::log($message);

    }
    
    public static function log($message) {
        echo $message;
    }
    
    
}
?>
<?php
class Logger {
    
    // A series of error constants
    const ACCOUNT_LOAD_ERROR = 1; 
    const CREDENTIALS_LOAD_ERROR = 2;
    const FAILED_LOGINS_LOAD_ERROR = 3;
    
    const FILE_ERROR;

    public static function logError($error) {
        if ($error === Logger::ACCOUNT_LOAD_ERROR) {
            log("Error 001: Failed to load account.");
        } else if ($error === Logger::CREDENTIALS_LOAD_ERROR) {
            log("Error 002: Failed to load credentials.")
        } else if ($error == Logger::FAILED_LOGINS_LOAD_ERROR) {
            log("Error 003: Failed to read failed logins.");
        }
    }
    
    public static function log($message) {
        ;
    }
    
    
}
>?
<?php

// Function for checking strings before assignment or use
class validator {
    const ID_LENGTH = 10; 
    const BILL_ID_LENGTH = 12;
    const TOKEN_LENGTH = 32; // md5 hashes are 32 characters long
    
    public static function checkIP($ip) {
        if (self::checkString($ip))
            if (!filter_var($ip, FILTER_VALIDATE_IP) === false) 
                return true;
        return false;
    }
    
    public static function checkAddress($address) {
        if (is_string($address)) {
            return true;
        }
        return false;

    }
    
    public static function cleanInput($input) {
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input);
        
        return $input;
    }
    
    public static function checkAccountNumber($accountNumber) {
        if (self::checkString($accountNumber)) {
            if (ctype_digit($accountNumber)) {
                if (strlen($accountNumber) === self::ID_LENGTH) {
                    return true;
                } // else die ("invalid strlen");
            } // else die ("invalid chars");
        } // else die ("invalid string");
        
        return false;
    }
    
    public static function checkBillID($billID) {
        if (self::checkString($billID)) {
            if (ctype_digit($billID)) {
                if (strlen($billID) === self::BILL_ID_LENGTH)
                    return true;
            }
        }
        return false;
    }
    
    public static function checkDateOfBirth($dob) {
        if (self::checkString($dob))
            return true;
        return false;
    }
    
    public static function checkZip($zip) {
        if (self::checkString(zip)) {
            if (ctype_digit($zip)) {
                if (strlen($zip) === 5) {
                    return true;
                } else
                    echo "invalid zip length";
            } else
                echo "not a digit" . $zip;
        } else echo "didn't pass string test";
        
        return false;
    }
    
    public static function checkEmail($email) {
        if (self::checkString($email)) {                 // check string
            //$email = filter_var($email, FILTER_SANITIZE_EMAIL); // removes illegal characters
            if (filter_var($email, FILTER_VALIDATE_EMAIL))  // validates email address
                return true;
        }
        return false;
    }
    
    public static function checkPassword($password) {
        $success = false;
        // Check password is a string
        if (self::checkString($password)) {
            // Clean the password string 
            $cleanPassword = self::cleanInput($password);
            // If the clean version matches the original, then accept it.
            if ($cleanPassword == $password)
                $success = true;
        }
        
        return $success;
    }
    
    public static function checkName($name) {
        if (self::checkString($name)) {    
            // "Only letters and white space allowed"; 
            if (preg_match("/^[a-zA-Z ]*$/",$name)) {
                return true;
            }
        }
        
        return false;
    }
    
    public static function checkAuthToken($authToken) {
        $tokenValid = false;
        $idValid = false;
        
        // Verify token length and type
        if (isset($authToken->token)) 
            if (self::checkString($authToken->token) && ctype_xdigit($authToken->token)) 
                if (strlen($authToken->token) == validator::TOKEN_LENGTH)
                    $tokenValid = true;
        
        // Verify id length and type
        if (isset($authToken->id)) {
            if (self::checkString($authToken->id) && ctype_digit($authToken->id)) {
                if (strlen($authToken->id) == validator::ID_LENGTH) {
                    $idValid = true;
                } else
                    ;// ('Invalid token length');
            } else
                ;// ('Invalid string or not composed of digits');
        } else
            ;// ()'ID is not set');
    
        // Only return true if both tests passed
        return ($tokenValid && $idValid);   
    }
    
    public static function checkAuthValue($authValue) { 
        $valid = false;
        
        if (self::checkString($authValue)) {
            $valid = true;
        }
        
        return $valid;
    }
    
    public static function checkString($value) {        
        // Validate type is string
        if (is_string($value)) {
            $cleanValue = htmlspecialchars($value);
            if ($cleanValue === $value)
                return true;        
        }
        return false;
    }
    
    public static function checkInt($value) {
        if (is_int($value))
            return true;
        else
            return false;
    }
    
    public static function checkDouble($value) {
        if (is_double($value))
            return true;
        else
            return false;
    }
    
    public static function checkBool($value) {
        if (is_bool($value)) 
            return true;
        else
            return false;
    }
    
}

?>
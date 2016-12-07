<?php
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);

require_once $rootDir . '/classes/model/Account.php';
require_once $rootDir . '/classes/security/InputValidator.php'; 

class AdminAccount extends Account {
    function __construct($newFirstName, $newLastName, $newEmail, $authValue) {
        // Input Types: string, string, string, string, address object 

        // All function are inherited from Account superclass
        $a = $this->setFirstName($newFirstName);
        $b = $this->setLastName($newLastName);
        $c = $this->setEmail($newEmail);
        $d = $this->setAuthValue($authValue);
        
        $this->setAdmin();
    
        // If all values were assigned properly
        if ($a && $b && $c && $d) 
            // Mark as successfully constructed
            $this->successConstruct = true;
    }
    
    public static function checkAccount($account) {
        // Make sure superclass is valid and checks out
        if (parent::checkAccount($account)) 
            // Do other subclass checking
            return true;
        
        return false;
    }
    
    public static function load($adminObject) {
        $admin = null;
        $admin = new AdminAccount($adminObject[firstName], $adminObject[lastName], $adminObject[email], $adminObject[authValue]);
        
        if ($admin->success()) {
            $admin->setAccountNumber($adminObject[accountNumber]);

            if (self::checkAccount($admin)) {
                ; // Success
            } else
                throw new Exception("Invalid admin account being loaded");
        }
        return $admin;
    }
}

?>

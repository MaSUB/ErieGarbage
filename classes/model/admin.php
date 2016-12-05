<?php
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);

require_once $rootDir . '/classes/model/account.php';
require_once $rootDir . '/classes/input_validator.php'; 

class Admin extends Account {
    function __construct($newFirstName, $newLastName, $newEmail, $authValue) {
        // Input Types: string, string, string, string, address object 

        // All function are inherited from Account superclass
        $this->setFirstName($newFirstName);
        $this->setLastName($newLastName);
        $this->setEmail($newEmail);
        $this->setAuthValue($newPassword);
        $this->setAdmin();
    }
    
    public static function checkAccount($account) {
        if (parent::validateAccount($account)) 
            return true;
    }
    
    public static function load($adminObject) {
        $admin = null;
        if (isset($adminObject[firstName]) && isset($adminObject[lastName]) && isset($adminObject[email]) && isset($adminObject[authValue])) {
            if (validator::checkName($adminObject[firstName]) && validator::checkName($adminObject[lastName])){
                if (validator::checkEmail($adminObject[email]) && validator::checkString($adminObject[authValue])) {
                    $admin = new Admin($adminObject[firstName], $adminObject[lastName], $adminObject[email], $adminObject[authValue]);
                }
            }
        }
        
        return $admin;
    }
}

?>

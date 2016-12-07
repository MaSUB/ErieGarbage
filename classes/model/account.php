<?php
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);

require_once $rootDir . '/classes/security/InputValidator.php'; // Provides functions to check input before use
require_once $rootDir . '/classes/model/UserAccount.php';

abstract class Account {
    
    const ADMINISTRATOR_ACCOUNT = 'ADMIN_ACCOUNT';
    const USER_ACCOUNT = 'USER_ACCOUNT';
    
    protected $firstName; // first name string
    protected $lastName; // last name string
    protected $email; // email string
    protected $authValue; // email + password hash
    protected $accountNumber; // unique account number string
    protected $accountType; // user account or admin account constants 
    
    protected $successConstruct = false;  // is set to true if objects constructor properly sets all properties

    
    protected function setAdmin() {
        $this->accountType = self::ADMINISTRATOR_ACCOUNT;
    }
    
    protected function setUser() {
        $this->accountType = self::USER_ACCOUNT;
    }
       
    public function getAccountType() {
        return $this->accountType;
    }
    
    
    protected function setFirstName($newName) {
        if (validator::checkString($newName)) {
            $this->firstName = $newName;
            return true;
        }
        return false;
    }
    public function getFirstName() {
        return $this->firstName;
    }
    
    protected function setLastName($newLastName) {
        if (validator::checkString($newLastName)) {
            $this->lastName = $newLastName;
            return true;
        }
        return false;
    }

    public function getLastName(){
        return $this->lastName;
    }    
    
    public function getFullName() {
        return $this->firstName + " " + $this->lastName;
    }
    
    protected function setEmail($newEmail) {
        if (validator::checkString($newEmail)) {
            $this->email = $newEmail;
            return true;
        }
        return false;
    }
    
    public function getEmail() {
        return $this->email;
    }
    
    protected function setAuthValue($newPass) {
        if (validator::checkString($newPass)) {
            $this->authValue = $newPass;
            return true;
        }
        return false;
    }
    
    public function getAuthValue() {
        return $this->authValue;
    }
    
    public function getAccountNumber() {
        return $this->accountNumber;
    }
   
    public function setAccountNumber($accountNumber) {
        $success = false;
        if (validator::checkAccountNumber($accountNumber)) {
            $this->accountNumber = $accountNumber;
            $success = true;
        } else
            throw new Exception('// invalid account number' . $accountNumber);
        return $success;
    }
    
    public function exportJSON() {
        return array(
            "firstName" => $this->firstName,
            "lastName" => $this->lastName,
            'email' => $this->email, 
            'authValue' => $this->authValue,
            'accountNumber' => $this->accountNumber,
            'accountType'=> $this->accountType,
        );
    }
    
    // Validator functions
    public static function checkAccount($account) {
        $validAccount = false;

        // Verify all properties are set
        if (isset($account->firstName) && isset($account->lastName) && isset($account->email) && isset($account->authValue)) {
            if (isset($account->accountNumber) && isset($account->accountType)) { 
                
                // Verify all properties are safe and meet requirements
                if (validator::checkName($account->getLastName()) && validator::checkName($account->getFirstName())) {
                    if (validator::checkEmail($account->getEmail())) {
                        if (validator::checkAuthValue($account->getAuthValue())) {
                            if (validator::checkAccountNumber($account->getAccountNumber())) {
                                if ($account->getAccountType() == self::ADMINISTRATOR_ACCOUNT || $account->accountType == self::USER_ACCOUNT) {
                                    $validAccount = true;
                                } else
                                    throw new Exception('// invalid account type');
                            } else
                                throw new Exception('// invalid account number');
                        } else 
                            throw new Exception('// invalid auth value');
                    } else
                        throw new Exception("// invalid email");
                } else
                    throw new Exception('// invalid first name/last name');
            } else
                throw new Exception('// accountNumber or type not set');
        } else 
            throw new Exception("first, last name, email, or authvalue not set");
        
        return $validAccount;
    }
    
    public function wipe() {
    // Securely wipes all the sensitive information before deallocation
        function wipeString(&$string) {
            for ($i = 0; $i < strlen($string); $i++) {
                $string[i] = 'x';
            }
        }
        
        wipeString($this->firstName);
        wipeString($this->lastName);
        wipeString($this->email);
        wipeString($this->password);
        wipeString($this->accountNumber);
        $this->accountType = 0;
    }
    
    public function success() {
        return $this->successConstruct;
    }
    
    public static abstract function load($account);
}

?>
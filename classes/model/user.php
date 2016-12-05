<?php
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);

require_once $rootDir . '/classes/input_validator.php'; // Provides functions to check input before use
require_once $rootDir . '/classes/model/address.php';
require_once $rootDir . '/classes/model/bill.php';
require_once $rootDir . '/classes/model/account.php';

class User extends Account {
    private $dateOfBirth; // string with date of birth
    private $bills; // array of bill objects
    private $address; // address object
    private $disputes; // array of dispute objects
    private $paymentTypes; // array of payment types
    public $success = true;
    
   function __construct($newFirstName, $newLastName, $newEmail, $newAuthValue, $address, $dateOfBirth) {
       // Input Types:     string,       string,       string,     string,   address object,   string
              
       $this->setFirstName($newFirstName);
       $this->setLastName($newLastName);
       $this->setEmail($newEmail);
       $this->setAuthValue($newAuthValue);
       $this->setAddress($address);
       $this->dateOfBirth = $dateOfBirth;
       $this->setUser();
       
       
       $this->bills = array();
       $this->disputes = array();
       $this->paymentTypes = array();
    }
    
    public function addBill($amount, $due, $month, $billID) {
        // Construct the new bill object
        $bill = new Bill($this->getAccountNumber(), $amount, $due, $month, false, $billID);

        // Verify bill was successfully created
        if (!($bill->getAmount() === null)) {
            array_push($this->bills, $bill);
            
        } else
            throw new Exception("Couldn't create bill. Illegal arguments");
    }
    
    public function getBills() {
        return $this->bills;
    }
    
    public function getAddress() {
        return $this->address;
    }
    
    public function getDateOfBirth() {
        return $this->dateOfBirth;
    }
    
    private function setDateOfBirth($dateOfBirth) {
        $success = false;
        
        if (validator::checkString($dateOfBirth)) {
            $this->dateOfBirth = $dateOfBirth;
            $success = true;
        }
        
        return $success;
    }
    
    private function setBills($bills) {
        $success = true;
        foreach($bills as $bill) {
            if (Bill::checkBill($bill) === false) {
                $success =false;
                break;
            }
        }
        if ($success === true)
            $this->bills = $bills;
        
        return $success;
    }
 
    
    private function setAddress($newAddress) {
        if (Address::checkAddress($newAddress)) {
            $this->address = $newAddress;
            return true;
        } else
            ; // Must be an address object.
        return false;
    }
    
    public function removePayment($payID) { 
    // payID is a string identifier for the payment to remove
        // remove it
    }
    
    public function allPayments() {
        return $this->paymentTypes;
    }
    
    public function exportJSON() {
        $object = parent::exportJSON();
        
        // Iterate through all bills
        $billsArray = array();
        foreach($this->bills as $bill) {
            // Convert to (JSON friendly) associative array and push to array
            array_push($billsArray, $bill->exportJSON());
        }
        
        // Save bills array in array
        $object['bills'] = $billsArray;
        
        // Save address array in array
        $object['address'] = $this->address->exportJSON(); // Converts to (JSON friendly) associative array
        
        // Save disputes
        //$object['disputes'] = get_object_vars($this->disputes);
                
        return $object;
            
    }
    
    public function checkAccount($account) {
        // Make sure superclass is valid and checks out
        if (parent::validateAccount($account)) {
            // Do other subclass checking
            return true;
        }
        
        return false;
    }
    
    public function wipe() {
        parent::wipe(); 
    }
    
    public function updateAccount($firstName, $lastName, $email, $authValue, $streetAddress, $zipCode, $city, $dateOfBirth) {
        $success = true;
        
        if (!($firstName === '')) {
            if (!($this->setFirstName($firstName)))
                $success = false;
            
        } if (!($lastName === '')) {
            if (!($this->setLastName($lastName)))
                $success = false;
            
        } if (!($email === '')) {
            if (!($this->setEmail($email)))
                $success = false;
            
        } if (!($authValue === '')) {
            if (!($this->setAuthValue($authValue)))
                $success = false;
            
        } if (!($streetAddress === '')) {
            if(!($this->address->setStreetAddress($streetAddress)))
                $success = false;
            
        } if (!($zipCode === '')) {
            if(!($this->address->setZipCode($zipCode)))
                $success = false;
            
        } if (!($city === '')) {
            if (!($this->address->setCity($city)))
                $success = false;
            
        } if (!($dateOfBirth)) {
            if (!($this->setDateOfBirth($dateOfBirth)))
                $success = false;
        } 

        return $success;
    }
    
    public static function checkAccountObject($accountObject) {
        
    }
    
    public static function load($accountObject){
    // Input: stdClass object loaded from JSON
    // Output: User object with same properties
        $account = null;
        $account = new User($accountObject[firstName], $accountObject[lastName], $accountObject[email], $accountObject[authValue], Address::load($accountObject[address]), $accountObject[dateOfBirth]);
        if ($account->success) {
            
            $bills = [];
            foreach($accountObject[bills] as $bill) {
                $loadedBill = Bill::load($bill);
                array_push($bills, $loadedBill);
            }
            
            $account->setBills($bills);
            $account->setAccountNumber($accountObject[accountNumber]);
            
            if (self::checkAccount($account)) 
                ; // Success
            else
                throw new Exception("Invalid loading");
        } else
            throw new Exception("Account unable to properly load");
    
        return $account;
    }

}

?>
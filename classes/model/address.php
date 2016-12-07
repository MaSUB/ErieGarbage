<?php
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);

require_once $rootDir . '/classes/security/InputValidator.php'; // Provides functions to check input before use

class address {
    public $streetAddress; // string
    public $zipCode; // int
    public $city; // string
    
    protected $successConstruct = false;
    
    public function __construct($street, $city, $zip) {
        $a = $this->streetAddress = $street;
        $b = $this->zipCode = $zip;
        $c = $this->city = $city;
        
        if ($a && $b && $c)
            $this->successConstruct = true;
    }
    
    public function getStreetAddress() {
        return $streetAddress;
    }
    
    public function getZipCode() {
        return $zipCode;
    }
    
    public function getCity() {
        return $cityName;
    }
    
    public function setZipCode($zipCode) {
        if (validator::checkZip($zipCode)) {
            $this->zipCode = $zipCode;
            return true;
        }
        return false;
    }
    
    public function setCity($city) {
        if (validator::checkString($city)) {
            $this->city = $city;
            return true;
        }
        return false;
    }
    
    public function setStreetAddress($streetAddress) {
        if (validator::checkString($streetAddress)) {
            $this->streetAddress = $streetAddress;
            return true;
        } 
        return false;
    }
    
    public static function load($addressObject) {
        $address = null;
        
        if (isset($addressObject[streetAddress]) && isset($addressObject[zipCode]) && isset($addressObject[city])) {
            if (self::checkAddressObject($addressObject)) {
                $address = new Address($addressObject[streetAddress], $addressObject[city], $addressObject[zipCode]);
            }
        }
        
        return $address;
    }
    
    public static function checkAddress($address) {
        if (validator::checkAddress($address->streetAddress) && validator::checkString($address->city)) {
            if (validator::checkZip($address->zipCode)) {
                return true;
            } 
        }
        return false;
    }
    
     public static function checkAddressObject($address) {
        if (validator::checkAddress($address[streetAddress]) && validator::checkString($address[city])) {
            if (validator::checkZip($address[zipCode])) {
                return true;
            } 
        } 
        return false;
    }
    
    public function exportJSON() {
        return array("streetAddress" => $this->streetAddress, 
                     "zipCode" => $this->zipCode, 
                     "city" => $this->city);
    }
    
    public function success() {
        return $this->successConstruct;
    }
    
    
    
}
?>
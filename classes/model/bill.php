<?php
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);

require_once $rootDir . '/classes/security/InputValidator.php'; // Provides functions to check input before use
require_once $rootDir . '/classes/model/UserAccount.php';

class Bill {
    private $userID; // user id string
    private $amount; // double (number)
    private $isPaid = false; // boolean
    private $dueDate; // string
    private $month; // string
    private $billID;
    
    const BILL_AMOUNT = 100;
    const BILL_DUE_DATE = "1";
    
    function __construct($userID, $amount, $dueDate, $month, $isPaid, $billID) {
        $success = false;
        
        if ($this->setAmount((double)$amount)) {
            if ($this->setUserID($userID)) {
                if ($this->setDueDate($dueDate)) {
                    if ($this->setMonth($month)) {
                        if ($this->setBillID($billID)) {
                            if ($isPaid === true) {
                                $this->markPaid();
                            }
                        }
                        
                        $success = true;
                    } else die('invalid month');
                } else die ("invalid due date");
            } else die ("invalid user id");
        } else die ("invalid amount");
        
        if ($success === false) {
            $this->userID = null;
            $this->amount = null;
            $this->isPaid = null;
            $this->dueDate = null;
            $this->month = null;
        }
        
        return $success;
    }
    
    public function getBillID() {
        return $this->billID;
    }
    
    public function getUserID() {
        return $this->userID;
    }
    
    public function getAmount() {
        return $this->amount;
    }
    
    public function getDueDate() {
        return $this->dueDate;
    }
    
    public function getMonth() {
        return $this->month;
    }
    
    public function getIsPaid() {
        return $this->isPaid;
    }
    
    private function setBillID($billID) {
        if (validator::checkString($billID)) {
            $this->billID = $billID;
            return true;
        }
        return false;
    }
    
    public function setDueDate($dueDate) {
        $success = false;
        if (validator::checkString($dueDate)) {
            $this->dueDate = $dueDate;
            $success = true;
        }
        
        return $success;
    }
    
    public function setMonth($month) {
        $success = false;
        
        if (validator::checkString($month)) {
            $this->month = $month;
            $success = true;
        }
        
        return $success;
    }
    
    public function setUserID($newUserID) {
        $success = false;
        if (validator::checkAccountNumber($newUserID)) {
            $this->userID = $newUserID;
            $success = true;
        }
        
        return $success;
    }
    
    public function setAmount($amount) {
        $success = false;
        if (validator::checkDouble($amount)) {
            $this->amount = $amount;
            $success = true;
        }
    
        return $success;
    }
    
    public function markPaid() {
        $this->isPaid = true;
    }
    
    public static function checkBill($bill) {
    // Validates bill objects of class Bill    
        // Verify all properties are set
        if (($bill->getAmount()) && ($bill->getDueDate()) && ($bill->getIsPaid()) && ($bill->getMonth()) && ($bill->getUserID()) && $bill->getBillID()) {
            
            // Validate all properties
            if (validator::checkDouble($bill->getAmount()) && validator::checkString($bill->getDueDate())) {
                if (validator::checkString($bill->getMonth()) && validator::checkBool($bill->getIsPaid())) {
                    if (validator::checkAccountNumber($bill->getUserID()) && validator::checkBillID($bill->getBillID())) {
                    
                        // Success, return true
                        return true;
                    }
                }
            }
        }
    } 
    
    public static function checkBillObject($bill) {
    // Validates bill objects read from json files (stdClass) 
        // Verify all properties are set
        if (isset($bill[amount]) && isset($bill[dueDate]) && isset($bill[isPaid]) && isset($bill[month]) && isset($bill[userID])) {
            
            // Validate all properties
            var_dump($bill);    
            if (validator::checkString((string)$bill->amount) && validator::checkString($bill->dueDate)) {
                if (validator::checkString($bill->month) && validator::checkBool($bill->isPaid)) {
                    if (validator::checkAccountNumber($bill->userID)) {
                        
                        // Success, return true
                        return true;
                    } 
                } // else die("month, ispaid");
            }  else die('amount-duedate');
        } // else die('not set');
        
        return false;
    }
    
    
    // **** I/O FUNCTIONS **** \\
    
    public function exportJSON() {
        return array("amount" => $this->amount,
                     "dueDate" => $this->dueDate, 
                     "month" => $this->month, 
                     "isPaid" => $this->isPaid,
                     "userID" => $this->userID, 
                     "billID" => $this->billID);
    }
    
    public static function load($billObject) {
        $bill = null;

        $bill = new Bill($billObject[userID], $billObject[amount], $billObject[dueDate], $billObject[month], $billObject[isPaid], $billObject[billID]);

        // Validate bill object created successfully
        if (!($bill->amount === null)) {
            ; // Success

        } else
            throw new Exception("Error loading bill; illegal arguments");

        return $bill;
    }
}

?>
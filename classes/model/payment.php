<?php
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);
require_once $rootDir . '/classes/security/InputValidator.php'; // Provides functions to check input before use

const CREDIT_CARD = 'CreditCard';
const CHECK = 'Check';
const DEBIT_CARD = 'DebitCard';

abstract class AbstractPayment {
    const paymentType;
    
    function __construct($type) {
        if (validator::checkString($type)) 
            if (CREDIT_CARD == $type || CHECK == $type || DEBIT_CARD == $type)
                // If accepted, set the payment type
                $this->paymentType = $type;
            else
                ; // Not an accepted payment type
        else
            ; // Not a valid string
    }
}

// Abstract class for all things in common between DebitCard and CreditCard
abstract class Card extends AbstractPayment {
    private $cardHolder; // string
    private $cardCompany; // string
    private $cardNumber; // integer (16 digits long)
    
    function __construct($cardHolder, $cardCompany, $cardNumber) {
    // Input types: string, string, integer
        parent::__construct(CREDIT_CARD);
        $this->setCardColder($cardHolder);
        $this->setCardCopmany($cardCompany);
        $this->setCardNumber($cardNumber);
    }
    
    private function setCardHolder($newCardHolder) {
        if (validator::checkString($newCardHolder))
            $this->cardHolder = $newCardHolder;
        else
            ; // New card holder was not a string
    }
    
    private function setCardCompany($newCardCompany) {
        if (validator::checkString($newCardCompany)) 
            $this->cardCompany = $newCardCompany;
        else
            ; // New card company was not a string
        
    }
    
    private function setCardNumber($newCardNumber) {
        if ($this->checkCardNumber($newCardNumber))
            $this->cardNumber = $newCardNumber;
        else
            ; // New card number was invalid or not a valid integer
    }
    
    private function checkCardNumber($cardNum) {
        validator::checkInt($cardNum);
        $num_length = strlen((string)$cardNum);
        if ($num_length == 16)
            return true;
        return false;
    }
    
    // PUBLIC get methods
    public function getCardHolder() {
        return $this->cardHolder;
    }
    
    public function getCardNumber() {
        return $this->cardNumber;
    }
    
    public function getCardCompany() {
        return $this->cardCompany;
    }
}

class DebitCard extends Card {
    function __constructor() {
        parent::__constructor(DEBIT_CARD);
    }
}

class CreditCard extends Card {
    function __constructor() {
        parent::__constructor(CREDIT_CARD);
    }
}

class Check extends {
    
}

?>
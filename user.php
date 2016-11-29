<?php
class user{

    private $firstName;
    private $lastName;
    private $email;
    private $password;
    private $address;

    public function getFullName(){
        return firstName + " " + lastName;
    }

    public function setFirstName($n){
        $firstName = $n;
    }
    public function getFirstName(){
        return $firstName;
    }

    public function getLastName(){
        return $lastName;
    }
    public function setLastName($n) {
        $lastName = $n;
    }

    public function setPassword($oldPass, $newPass){
        if($oldPass == $password){
            $password = $newPass;
        }
    }

}

?>
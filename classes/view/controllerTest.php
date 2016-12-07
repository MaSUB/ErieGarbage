<?php
require_once '../controller/DatabaseController.php';
require_once '../model/UserAccount.php';

class ControllerTester {

    function testSave() {
        $databaseController = new DatabaseController();
        if ($databaseController->authenticate('hi@hotmail.com', 'password')) {

            $user = new User('Robert', 'DeNiro', 'hi@hotmail.com', 'password', new Address('Station Road', 16563, 'Erie'), "8849548854");
            $databaseController->updateAccount($user);
            $databaseController->saveAccount();
            echo 'Success'; 

        } else
            echo 'No';
    }
    
    function testLoad() {
        $databaseController = new DatabaseController();
        if ($databaseController->authenticate('hi@hotmail.com', 'password')) {
            if($databaseController->isAuthenticated())
                echo '<br>Success';
        } else
            echo 'No';
    }
    
    function testTokenValidation() {
        $databaseController = new DatabaseController();
        $token = ["id" => "8849548854", "token" => "5ef000d8cb25a1f3a032ed0ad01361aa"];
        if ($databaseController->validateToken($token))
            echo 'Success!';
    }
    
    function registerUser() {
        $databaseController = new DatabaseController();
        $user = new User("Rob", "DeNiro", "hello@gmail.com", "thepassword", new Address("162 Street Ave", "16542", "Erie"));
        $databaseController->registerAccount($user);
    }
    
    function testValidator() {
        echo "checkString(): " . (validator::checkString("st/n/t<br>ring") ? 'true' : 'false');
        echo "checkEmail(): " . (validator::checkEmail("email@hotmail.com") ? 'true' : 'false');
        echo "checkAccount(): " . (account::validateAccount(new User("Rob", "DeNiro", "hello@gmail.com", "thepassword", new Address("162 Street Ave", "16542", "Erie"))) ? 'true' : 'false');
        echo "checkPassword(): " . (validator::checkPassword('password') ? 'true' : 'false');
        echo "checkName(): " . (validator::checkName('roberty') ? 'true' : 'false');
        echo "checkAccountNumber(): " . (validator::checkAccountNumber('234566665456') ? 'true' : 'false');
    }
    
    function addBillToAll() {
        $databaseController = new DatabaseController();
        $databaseController->addNewBillToAll();
    }
    
}

//$tester = new ControllerTester();
//$tester->addBillToAll();
//$tester->testTokenValidation();
;


?>
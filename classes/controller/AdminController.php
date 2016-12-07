<?php
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);
require_once $rootDir . '/classes/controller/ClientController.php';

class AdminController extends ClientController {
    
    public function createAndRegisterAdminAccount($firstName, $lastName, $email, $password) {
        $success = false;
        
        // Make sure user is not blocked for excessive requests
        if ($this->databaseController->verifyRequestLimit()) {
        
            // Get the current account number
            $accountNumber = $this->activeAccount->getAccountNumber();
            
            // Check permissions
            if ($this->databaseController->getPermissions($accountNumber) === self::ACTIVE_ADMIN_PERMISSION()) {

                // Check email and password input strings before hashing
                if (validator::checkEmail($email) && validator::checkString($password)) {
                    
                    // Calculate auth value
                    $authValue = password_hash($email . $password, PASSWORD_DEFAULT);

                    $admin = new Admin($firstName, $lastName, $email, $authValue);

                    // Validate admin object
                    if ($admin->successConstruct === true) { 
                                                
                        // Set account number, if successful, move on
                        if ($admin->setAccountNumber($this->databaseController->generateNewAccountNumber())) {
                            
                            // Register admin account
                            if ($this->databaseController->registerAdminAccount($admin, $authValue))
                                $success = true;
                        } else
                            ; // Couldn't set account number
                    } else
                        die("Account did not construct successfully");
                } else
                    die("Invalid input"); // Email or password is invalid
            } else
                die("Request limit exceeded");
        } else
            throw new Exception("Insufficient permissions to create admin account");

        return $success;
    }
}

?>
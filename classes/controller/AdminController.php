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
            if ($this->databaseController->getPermissions($accountNumber) === DatabaseController::ACTIVE_ADMIN_PERMISSION()) {

                // Check email and password input strings before hashing
                if (validator::checkEmail($email) && validator::checkString($password)) {
                    
                    // Calculate auth value
                    $authValue = password_hash($email . $password, PASSWORD_DEFAULT, ["salt"=>"73bfd72hs7a3h88jvF5Yz9"]);

                    $admin = new AdminAccount($firstName, $lastName, $email, $authValue);

                    // Validate admin object
                    if ($admin->success() === true) { 
                                                
                        // Set account number, if successful, move on
                        if ($admin->setAccountNumber($this->databaseController->generateNewAccountNumber())) {
                            
                            // Register admin account
                            if ($this->databaseController->registerAdminAccount($admin, $authValue))
                                $success = true;
                        } else
                            throw new Exception("Couldn't set account number");
                    } else {
                        Logger::logError(Logger::INVALID_INPUT_ERROR);
                        header("Location: " . View::REGISTER_ADMIN_PAGE . "?fail=true");
                    }
                } else {
                    Logger::logError(Logger::INVALID_INPUT_ERROR);
                    header("Location: " . View::REGISTER_ADMIN_PAGE . "?fail=true");
                }
            } else {
                Logger::logError(Logger::INSUFFICIENT_PERMISSIONS_ERROR);
                header("Location: " . View::UNAUTHORIZED_PAGE);
            }
                
        } else {
            if (validator::checkIP($_SERVER['REMOTE_ADDR']))
                $ip = $_SERVER['REMOTE_ADDR'];
            else
                $ip = "unsafe ip address";
                
            Logger::logError(Logger::REQUEST_LIMIT_EXCEEDED, $ip);
            header('Location: ' . View::TIMEOUT_PAGE);
        }
        
        return $success;
    }
    public function createBillForUser($accountNumber, $bill) {
    // Creates a bill object for the user
    // Input: Account number string and bill object
    // Output: Success value (true/false)
        $success = false;
        
        // Make sure active user is an admin
        if ($this->databaseController->getPermissions() === self::ACTIVE_ADMIN_PERMISSION()) {

            if (validator::checkAccountNumber($accountNumber)) {
                $bill->setBillID($this->databaseController->generateBillID());
                $bill->setAccountID($accountNumber);

                if (Bill::checkBill($bill)) {
                    $databaseController = new DatabaseController();
                    $databaseController->adminLoad($this->activeAccount->getAuthValue(), $accountNumber);
                    

                } else
                    ; // "Invalid bill";
            } else 
                ; // 'Invalid account number'; 
        } else
            header('Location: ' . View::UNAUTHORIZED_PAGE); // insufficient permissions
        return $success;
        
    }
    
    
  
}

?>
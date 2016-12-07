<?php
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);
    
require_once $rootDir . '/classes/security/InputValidator.php';
require_once $rootDir . '/classes/model/UserAccount.php';
require_once $rootDir . '/classes/model/AdminAccount.php';
require_once $rootDir . '/classes/view/View.php';
require_once $rootDir . '/classes/controller/DatabaseController.php';


class ClientController {
    
    // Controller for file access and authentication
    protected $databaseController;
    
    // stores whether user is authenticated or not
    private $authenticated = false;
    
    // stores the active account object authenticated
    protected $activeAccount = null;
    
    // stores the active account's permissions
    private $permissions;
    
    function __construct() {
        // Set default timezone for time functions
        date_default_timezone_set('America/New_York');
        
        // Initialize database controller
        $this->databaseController = new DatabaseController();
        
        // Initialize default value of no permissions
        $this->permissions = DatabaseController::NO_PERMISSIONS();
        
        // Try to authenticate user via cookie tokens
        $this->authenticateToken();
        
    }
    
    /*****************************/ 
    /*****  AUTHENTICATION  ******/
    /*****************************/
    
    public function authenticate($email, $password) {
    // Authenticates a user with email and password credentials and loads into $activeAccount
    // Input: Email and Password Strings
    // Output: Boolean value ($success) indicating successful or failed login
        $success = false;
        // Make sure user hasn't exceeded incorrect auth limit.
        if ($this->databaseController->verifyRequestLimit()) {

            // Verify input strings are valid
            if (validator::checkEmail($email) && validator::checkPassword($password)) {
              
                $authToken = $this->databaseController->authenticate($email, $password);

                // Clean up email and password
                $email = null;
                $password = null;
                
                // Check account was authenticated
                if (!($authToken === null)) {
                    
                    // Saves auth cookie onto user's browser and redirects to home page
                    $this->setCookie($authToken);
                    
                    $success = true;
                    
                } else {
                    // Login request denied (invalid credentials)
                    $this->databaseController->markInvalidLogin();
                    
                    // Redirect to login failed page
                    header('Location: ' . View::LOGIN_PAGE . '?fail=true');
                }
                 
            } else {
                // Invalid inputs: mark as invalid login
                $this->databaseController->markInvalidLogin();
                
                // Log and redirect to login page
                Logger::logError(Logger::INVALID_INPUT_ERROR);
                header('Location: ' . View::LOGIN_PAGE);
            }
            
        } else
            // 'User timed out'; 
            header('Location: ' . View::TIMEOUT_PAGE);
        
    return $success;
        
    }
    
    public function authenticateToken() {
        // Make sure the Cookie and its values exist
        // Output: Success value (true/false)
        $success = false;
        
        if (isset($_COOKIE['eg-auth'])) {
            $authToken = json_decode($_COOKIE['eg-auth']);

            $account = $this->databaseController->authenticateToken($authToken);
            
            if (!($account === null)) {
                // Success authentication
                $this->permissions = $this->databaseController->getPermissions($account->getAccountNumber());  
                $this->authenticated = true;
                $this->activeAccount = $account;
                $success = true;
            } // Account not loaded, invalid token
        } // User not authenticated
        
        return $success;
    }
    
    public function logout() {
        // If user is authenticated
        if ($this->authenticated) {
            // Log active account out
            $this->databaseController->logout($this->activeAccount->getAccountNumber());  
        }
    }
    
    private function setCookie($authToken) {
        // Set the user's cookie
        setcookie('eg-auth', json_encode($authToken), time()+DatabaseController::TOKEN_TIMEOUT(), '/');
    }
    
    /********************************/ 
    /****** ACCOUNT MANAGEMENT ******/
    /********************************/
    
    public function createAndRegisterUserAccount($firstName, $lastName, $email, $password, $address, $city, $zip, $dateOfBirth) {
        // Takes registration form info, validates and creates a new account
        // Input: All account info (strings)
        // Output: Success boolean value (true/false)
        $success = false;
        
        // Make sure user is not blocked for excessive requests
        if ($this->databaseController->verifyRequestLimit()) {
            
            // Create address object
            $addressObject = new Address($address, $city, $zip);
            
            // Verify address successfully constructed (no invalid input)
            if ($addressObject->success()) {
                
                // Validate email and password fields before hashing
                if (validator::checkEmail($email) && validator::checkPassword($password)) {

                    // Calculate auth value
                    $authValue = password_hash($email . $password, PASSWORD_DEFAULT, ["salt" => "73bfd72hs7a3h88jvF5Yz9"]);

                    // Create account object
                    $userAccount = new UserAccount($firstName, $lastName, $email, $authValue, $addressObject, $dateOfBirth);

                    if ($userAccount->success()) {
                        
                        // Add account number
                        $userAccount->setAccountNumber($this->databaseController->generateNewAccountNumber());
                        
                        // Register account into system
                        $this->databaseController->registerUserAccount($userAccount, $authValue);
                        $success = true;
                            
                    } else {
                        Logger::logError(Logger::INVALID_INPUT_ERROR); // Invalid input for user account creation
                    }
                } else {
                    Logger::logError(Logger::INVALID_INPUT_ERROR); // Invalid email or password
                }
            } else {
                Logger::logError(Logger::INVALID_INPUT_ERROR); // Invalid address
            } 
        } else {
            Logger::logError(Logger::REQUEST_LIMIT_EXCEEDED); // request limit exceeded
        }
            
        
        if ($success === false) {
            $this->databaseController->markInvalidLogin();
            header('Location: ' . View::REGISTER_PAGE . ' ?fail=true');
            exit;
        }
        
        return $success;
    }
    
    public function updateAccountSettings($firstName, $lastName, $email, $oldPassword, $newPassword, $streetAddress, $zipCode, $city, $dateOfBirth) {
        $success = false;
        
        // Verify old password was provided
        if (!($oldPassword === '')) {
            
            // Validate old password provided before using
            if (validator::checkPassword($oldPassword)) {
                
                // Verify old password matches
                if ($this->activeAccount->getAuthValue() === 
                    password_hash($this->activeAccount->getEmail() . $oldPassword, PASSWORD_DEFAULT, ["salt"=>"73bfd72hs7a3h88jvF5Yz9"])) {

                    // Calculate new auth value
                    $authValue = '';

                    // Both new email and password provided
                    if (!($email === '') && !($newPassword === '')) {
                        if (validator::checkEmail($email) && validator::checkPassword($newPassword))
                            $authValue = password_hash($email . $newPassword, PASSWORD_DEFAULT, ["salt"=>"73bfd72hs7a3h88jvF5Yz9"]);
                            
                    // Only new email provided
                    } else if (!($email === '')) {
                        if (validator::checkEmail($email)) 
                            $authValue = password_hash($email . $oldPassword, PASSWORD_DEFAULT, ["salt"=>"73bfd72hs7a3h88jvF5Yz9"]);
                            
                    // Only new password provided
                    } else if (!($newPassword === '')) {
                        if (validator::checkPassword($newPassword))
                            $authValue = password_hash($this->activeAccount->getEmail() . $newPassword, PASSWORD_DEFAULT, ["salt"=>"73bfd72hs7a3h88jvF5Yz9"]);
                    }

                    // Update account object
                    if ($this->activeAccount->updateAccount($firstName, $lastName, $email, $authValue, $streetAddress, $zipCode, $city, $dateOfBirth)) {
                        // Success updating account
                        $this->databaseController->updateAccount($this->activeAccount);
                        $success = true;
                    } else
                        Logger::log("Could not update account");
                } else
                    header("Location: " . View::ACCOUNT_SETTINGS_PAGE . "?msg=Password mismatch");
            } else 
                header("Location: " . View::ACCOUNT_SETTINGS_PAGE . "?msg=Invalid old password");
        } else
            header("Location: " . View::ACCOUNT_SETTINGS_PAGE . "?msg=Must provide old password");
        
        return $success;
    }
    
    public function deleteAccount() {
        if ($this->authenticated === true) {
            $this->databaseController->deleteActiveAccount();
        } else
            header('Location: ' . View::LOGIN_PAGE);
    }
  
    public function updatePassword($newPassword, $oldPassword) {
    // Updates the currently authenticated user's password
    // Input: Unvalidated new and old password strings
    // Output: True/false value with success
        $success = false;
        
        // Only can update if user is authenticated
        if ($this->authenticated) {
            
            // Validate user input passwords
            if (validator::checkPassword($newPassword) && validator::checkPassword($oldPassword)) {
                $oldAuthValue = password_hash($this->activeAccount->email . $oldPassword, PASSWORD_DEFAULT, ["salt"=>"73bfd72hs7a3h88jvF5Yz9"]);
                
                // If the provided authentication matches
                if ($oldAuthValue === $this->activeAccount->authValue) {
                    
                    // Update local password
                    $this->activeAccount->setAuthValue(password_hash($this->activeAccount->email . $newPassword, PASSWORD_DEFAULT, ["salt"=>"73bfd72hs7a3h88jvF5Yz9"]));
                    
                    // Save changes onto database
                    $this->databaseController->saveAccount($this->activeAccount);
                    
                } else {
                    // Incorrect authentication
                    header('Location: ' . View::ACCOUNT_SETTINGS . '?fail=true');
                    exit;
                }
                
            } else {
                // Invalid inputs
                Logger::logError(Logger::INVALID_INPUT_ERROR, 'passwords');
                header('Location: ' . View::ACCOUNT_SETTINGS . '?fail=true');
                exit;
            }
        } else {
            // User not authenticated. Redirect to login
            header('Location: ' . View::LOGIN_PAGE);
            // Exit to assure code execution stops.
            exit;
        }
        
        return $success;
    }
    
    // *********************** \\
    // **** Functionality **** \\
    // *********************** \\
    
    public function loadPickupTimes() {
        return $this->databaseController->loadPickupTimes();
    }

    //  ***************************** \\
    //  **** Getters and Setters **** \\
    //  ***************************** \\
    
    public function getActiveAccount() {
    // Returns class active account object, or null if not authenticated yet    
        if ($this->authenticated)
            return $this->activeAccount;
        else
            return null;
    }
    
    public function isAuthenticated() {
    // Returns whether the user has been authenticated and loaded into the controller.
        return $this->authenticated;
    }
    
    public function getPermissions() {
        return $this->permissions;
    }
    
    
    
}
?>
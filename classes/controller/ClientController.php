<?php
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);
    
require_once $rootDir . '/classes/security/InputValidator.php';
require_once $rootDir . '/classes/model/UserAccount.php';
require_once $rootDir . '/classes/model/Account.php';
require_once $rootDir . '/classes/model/Admin.php';
require_once $rootDir .  '/classes/view/View.php';


class ClientController {
    
    // Controller for file access and authentication
    protected $databaseController;
    
    // stores whether user is authenticated or not
    private $authenticated = false;
    
    // stores the active account object authenticated
    protected $activeAccount = null;
    
    function __construct() {
        // Set default timezone for time functions
        date_default_timezone_set('America/New_York');
        
        // Initialize database controller
        $this->databaseController = new DatabaseController();
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
        if ($this->$databaseController->verifyRequestLimit()) {
            // Verify input strings are valid
            if (validator::checkEmail($email) && validator::checkPassword($password)) {

                $account = $this->databaseController->authenticate($email, $password);
                
                // Clean up email and password
                $email = null;
                $password = null;
                
                // Check account was authenticated
                if (!($account === null)) {
                    $this->activeAccount = $account;
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
    
    public function authenticateToken($authToken) {
    // Authenticates the auth token via the database controller and sets to active account
    // Input: AuthToken object
    // Output: Success value (true/false)
        $success = false;
        
        $account = $this->databaseController->authenticateToken($authToken);
        if (!($account === null)) {
            $this->activeAccount = $account;
            $success = true;
        }
        
        return $success;
    }
    
    /********************************/ 
    /****** ACCOUNT MANAGEMENT ******/
    /********************************/
    
    public function createAndRegisterAdminAccount($firstName, $lastName, $email, $password) {
        $success = false;
        
        // Make sure user is not blocked for excessive requests
        if ($this->verifyRequestLimit()) {
        
            // Check permissions
            if ($this->databaseController->getPermissions($this->activeAccount->getAccountNumber()) === self::ACTIVE_ADMIN_PERMISSION()) {

                if (validator::checkEmail($email) && validator::checkString($password)) {
                    // Calculate auth value
                    $authValue = hash('md5', $email . $password);

                    $admin = new Admin($firstName, $lastName, $email, hash('md5', $email.$password));

                    // Validate admin object
                    if ($admin->successConstruct === true) { 
                        
                        // Set account number, if successful, move on
                        if ($admin->setAccountNumber($this->generateNewAccountNumber())) {
                            
                            // Register admin account
                            if ($this->registerAdminAccount($admin, $authValue))
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
    
    public function createAndRegisterUserAccount($firstName, $lastName, $email, $password, $address, $city, $zip, $dateOfBirth) {
        // Takes registration form info, validates and creates a new account
        // Input: All account info (strings)
        // Output: Success boolean value (true/false)
        
        $success = false;
        // Make sure client isn't timed out
        if ($this->verifyRequestLimit()) {
            
            // Validate all inputs
            if (validator::checkName($firstName) && (validator::checkName($lastName))) {
                if (validator::checkEmail($email) && validator::checkPassword($password)) {
                    if (validator::checkString($address) && validator::checkString($city) && validator::checkZip($zip)) {
                        if (validator::checkDateOfBirth($dateOfBirth)) {
                            
                            // Create address object
                            $addressObject = new Address($address, $city, $zip);
                            
                            // Calculate auth value
                            $authValue = hash('md5', $email . $password);
                            
                            // Create account object
                            $userAccount = new UserAccount($firstName, $lastName, $email, $authValue, $addressObject, $dateOfBirth);
                            
                            // Register account into system
                            $this->registerUserAccount($userAccount, $authValue);
                            $success = true;
                            
                        } else
                            throw new Exception("Invalid dob");
                    } else
                        throw new Exception("Invalid address"); // Invalid address
                } else
                    throw new Exception("Invalid email or pw"); // Invalid email or password
            } else
                throw new Exception("Invalid name or last name"); // Invalid name or last name
            
        } else {
            // Request limit exceeded
            header('Location: /timeout.php'); // Request limit exceeded
            exit;
        }
        
        if ($success === false) {
            $this->markInvalidLogin();
            header('Location: ' . View::REGISTER_PAGE . ' ?fail=true');
            exit;
        }
        
        return $success;
    }
    
    private function registerAdminAccount($account, $authValue) {
    // Registers a new admin account into the system and saves it
    // Input: Account object
    // Output: Boolean with success value
        if ($this->authenticated) {
            if ($this->getPermissions() === self::ACTIVE_ADMIN_PERMISSION()) {
                $this->saveAccountObject($account);
                $this->createUserCredentialsEntry($account->getAccountNumber(), $authValue);
                $this->setAdminPermissions($account->getAccountNumber());
                $success = true;
            }
        }
        $success = false;
        
        
        return $success;
    }
    
    private function registerUserAccount($account, $authValue) {
    // Registers a new account into the system current object and saves it to system
    // Input: Account object
    // Output: Boolean with success value
        $success = false;
        
        // Generate account number
        $account->setAccountNumber($this->generateNewAccountNumber());
        
        // Save account object
        $this->saveAccountObject($account);
        
        // Save the user credentials as well
        $this->createUserCredentialsEntry($account->getAccountNumber(), $authValue);
        
        // Set user permissions
        $this->setUserPermissions();
        
        // Report success
        $success = true;
        
        return $success;
        
    }
    
    private function generateNewAccountNumber() {
    // Generates a random new account number for an account
    // Output: Account number string
        $pass = false;
        while ($pass === false) {
            $accountNumber = (string)(rand(1000000000, 9999999999)); // length 10
            // Verify account number does not already exist, else, repeat
            if ($this->accountExists($accountNumber) === false)
                $pass = true;
        }
        return $accountNumber;
    }
    
    public function updateAccountSettings($firstName, $lastName, $email, $oldPassword, $newPassword, $streetAddress, $zipCode, $city, $dateOfBirth) {
        $success = false;
        
        // Verify old password was provided
        if (!($oldPassword === '')) {
            
            // Validate old password provided before using
            if (validator::checkPassword($oldPassword)) {
                
                // Verify old password matches
                if ($this->activeAccount->getAuthValue() === hash('md5', $this->activeAccount->getEmail() . $oldPassword)) {

                    // Calculate new auth value
                    $authValue = '';

                    // Both new email and password provided
                    if (!($email === '') && !($newPassword === '')) {
                        if (validator::checkEmail($email) && validator::checkPassword($newPassword))
                            $authValue = hash('md5', $email . $newPassword);
                            
                    // Only new email provided
                    } else if (!($email === '')) {
                        if (validator::checkEmail($email)) 
                            $authValue = hash('md5', $email . $oldPassword);
                            
                    } else if (!($newPassword === '')) {
                        if (validator::checkPassword($newPassword))
                            $authValue = hash('md5', $this->activeAccount->getEmail() . $newPassword);
                    }

                    // Update account object
                    if ($this->activeAccount->updateAccount($firstName, $lastName, $email, $authValue, $streetAddress, $zipCode, $city, $dateOfBirth)) {
                        // Success updating account
                        $this->saveActiveAccount();
                        if ($this->createUserCredentialsEntry($this->activeAccount, $authValue))
                            $success = true;
                    } else
                        ;// throw new Exception("Could not update account");
                } else
                    die ("Password mismatch");
            } else 
                die ("Illegal old password");
        } else
            die("Must provide old password");
        
        return $success;
    }
    
    public function deleteActiveAccount() {
        if ($this->authenticated === true) {
            if ($this->getPermissions() === self::ACTIVE_USER_PERMISSIONS) {
                $this->deleteAccountFile();
                $this->deleteCredentialsEntry();
                $this->removePermissionsEntry();
                $this->deleteToken();
            } else
                header('Location: ' . View::UNAUTHORIZED_PAGE); // unauthenticated, therefore unauthorized to delete
        } else
            header('Location: ' . View::LOGIN_PAGE);
    }
   
    //  ***************************** \\
    //  ******* FILE HANDLING ******* \\
    //  ***************************** \\
    
    private static function readFile($fileName) {
        $value = null;
        
        if (file_exists($fileName)) {
            $file = fopen($fileName, 'r');
            if (!($file === false)) {
                // Read and decode json object from file
                $object = json_decode(fread($file, filesize($fileName)), true);
                fclose($file);

                // If parsing/reading was successful
                if (!($object === NULL)) {
                    $value = $object;
                } else
                    throw new Exception("Error reading/parsing " . $fileName . " file");
            } else 
                throw new Exception("Error opening " . $fileName . " file");
        } else
            throw new Exception("Error: file " . $fileName . " does not exist");
    
        return $value;
    }
    
    private static function overwriteFile($fileName, $data) {
        $success = false;
        
        //if (file_exists($fileName)) {
            $file = fopen($fileName, 'w+');
            if (!($file === false)) {
                // Read and decode json object from file
                fwrite($file, json_encode($data));
                fclose($file);
                $success = true;

            } else 
                throw new Exception("Error opening " . $fileName . " file");
        //} else
          //  throw new Exception("Error: file " . $fileName . " does not exist");
    
        return $success;
    }
    
    private function saveAccountObject($account) {
    // Saves the new account object onto the json file stores
        $success = false;
        if (User::validateAccount($account)) {
            $accountFileName = self::ACCOUNTS_FILE_DIR() . $account->getAccountNumber() . '.json';

            if ($this->overwriteFile($accountFileName, $account->exportJSON()))
                $success = true;
        } else 
            ; // "Couldn't save account; it is invalid."
        
        return $success;
    }
    
    public function saveActiveAccount() {
    // Saves the new account object onto the json file stores
        $success = false;
        if (User::validateAccount($this->activeAccount)) {
            $accountFileName = self::ACCOUNTS_FILE_DIR() . $this->activeAccount->getAccountNumber() . '.json';

            if ($this->overwriteFile($accountFileName, $this->activeAccount->exportJSON()))
                $success = true;
        } else 
            throw new Exception("Couldn't save account; it is invalid."); // Invalid account
        
        return $success;
    }
    
    private function deleteAccountFile() {
    // Deletes the active account file under /store/accounts/<account>.json
        $success = false;
        
        $accountFileName = self::ACCOUNTS_FILE_DIR() . $this->activeAccount->getAccountNumber() . '.json';
        if (file_exists($accountFileName)) {
            unlink($accountFileName);
            $success = true;
        } else
            throw new Exception("Account file to delete does not exist.");

        return $success;
    }    
    
    private function deleteToken() {
        $success = false;
        $tokens = $this->readFile(self::TOKENS_FILE());
        if (!($tokens === null)) {
            unset($tokens[$this->activeAccount->getAccountNumber()]);
                
            // Rewrite changes to file (overwrite)
            $this->overwriteFile(self::TOKENS_FILE, $tokens);
        } else
            throw new Exception("Couldn't read tokens json file");
        
        return $success;
    }
    
    private function removePermissionsEntry() {
        $success = false;
        $permissions = $this->readFile(self::PERMISSIONS_FILE());
        
        // Make sure file was opened
        if (!($permissions === null)) {
            unset($permissions[$this->activeAccount->getAccountNumber()]);
                
            // Rewrite changes to file (overwrite)
            $this->overwriteFile(self::PERMISSIONS_FILE(), $permissions);
        } else 
            throw new Exception("Couldn't read permissions file");
              
        return $success;
    }
        
    private function deleteCredentialsEntry() {
        $success = false;
        $credentials = $this->readFile(self::CREDENTIALS_FILE());
        if (!($credentials === null)) {
            unset($credentials[$this->activeAccount->getAuthValue()]);
            // Rewrite changes to file (overwrite)
            $this->overwriteFile(self::CREDENTIALS_FILE(), $credentials);
            $success = true;
        }
        return $success;
    }
    
    private function accountExists($accountNumber) {
    // Verifies if the account number exists in the server.
    // Input: Verified account number string
    // Output: Boolean with success value
        $success = false;
        $accountFileName = self::ACCOUNTS_FILE_DIR() . $accountNumber . '.json';
        if (file_exists($accountFileName))
            $success = true;
        
        return $success;
    }
    
    private function updateFailedLoginsFile() {
    // Merge class's failedLogins with store, and save file.
         $recentRequests = $this->readFile(self::FAILED_LOGINS_FILE());
         if (!($recentRequests === null)) {
             $recentRequests = array_merge($recentRequests, $this->failedLogins);
             $this->overwriteFile(self::FAILED_LOGINS_FILE(), $recentRequests);             
         } else
             throw new Exception("Couldn't read failed logins file");
    }
    
    private function createUserCredentialsEntry($accountNumber, $authValue) {
        $success = false;
        $credentials = $this->readFile(self::CREDENTIALS_FILE());
            
        if (!($credentials === null)) {
                
            // Update credentials object
            $credentials[$authValue] = $accountNumber;

            // Open file for overwrite
            $this->overwriteFile(self::CREDENTIALS_FILE(), $credentials);
            $success = true;
                
        } else
            throw new Exception("Could not read credentials file");
        
        return $success;
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
                $oldAuthValue = hash('md5', $this->activeAccount->email . $oldPassword);
                // If the provided authentication matches
                if ($oldAuthValue === $this->activeAccount->authValue)
                    // Update local password
                    $this->activeAccount->setAuthValue(hash('md5', $this->activeAccount->email . $newPassword));
                else
                    // Incorrect authentication
                    throw new Exception("Incorrect authentication");
                
            } else
                throw new Exception("Illegal input detected.");
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
    
    public static function loadPickupTimes() {
    // Loads pickup times from json file and returns it
    // Output: Pickup times object or null if failed

        $pickupTimes = self::readFile(self::PICKUP_TIMES_FILE());
        
        if (($pickupTimes === null)) {
            throw new Exception("Error reading pickup times file");
        }
            
        return $pickupTimes;
    }
    
    public function getDuePayments() {    
    // Reads all bills and gets the ones that are due.
    // Output: Array of Bill objects
        // Authenticate first
        if ($this->authenticated) {
            if ($this->getPermissions() === self::ACTIVE_ADMIN_PERMISSION()) {

                $bills = [];

                $billsStore = $this->readFile(self::BILLS_FILE());
                
                if (!($billsStore === null)) {
                    foreach($billsStore as $billID => $accountNumber) {
                        $accountFileName = self::ACCOUNTS_FILE_DIR() . $accountNumber . '.json';
                        $account = $this->readFile($accountFileName);
                        if (!($account === null)) {

                            // Load associative array into bill object
                            foreach($account['bills'] as $billObject) {
                                $bill = Bill::load($billObject);

                                // Only add if unpaid
                                if (!($bill->getIsPaid()))
                                    array_push($bills, $bill);
                            }
                        }
                    }
                }
            } else
                header('Location: ' . View::UNAUTHORIZED_PAGE); // insufficient permissions
        } else
            header('Location: ' . View::LOGIN_PAGE); // not logged in
        return $bills;
    }
    
    public function adminLoad($adminAuthValue, $accountNumber) {
    // Authenticates the admin and loads the account with account number onto controller
    // Input: Verified account number string and admin authValue
    // Output: success value (true/false)
        
        // Assume no permissions unless reassigned
        $success = false;
        
        // Read files
        $permissionsObj = $this->readFile(self::PERMISSIONS_FILE());
        $credentials = $this->readFile(self::CREDENTIALS_FILE());
        
        // Verify success reading
        if (!($permissionsObj === null) && !($credentials === null)) {
            // Authenticate admin
            if (isset($credentials[$adminAuthValue])) {
                $adminAccountNumber = $credentials[$adminAuthValue];

                if (isset($permissionsObj[$adminAccountNumber])) {
                    // Get permissions for the account number.
                    $permissions = $permissionsObj[$adminAccountNumber];
                    if ($permissions === self::ACTIVE_ADMIN_PERMISSION()) {
                        $this->loadAccount($accountNumber);
                        $success = true;
                    } else 
                        header('Location: ' . View::UNAUTHORIZED_PAGE); // insufficient permissions
                } else 
                    header('Location: ' . View::LOGIN_PAGE); // permission for user not found: login
            } else 
                ; // "Could not find credentials for user"
        } else
            throw new Exception("Couldn't read permissions or credentials files");
        
        return $success;
    }
    
    public function createBillForUser($accountNumber, $bill) {
    // Creates a bill object for the user
    // Input: Account number string and bill object
    // Output: Success value (true/false)
        $success = false;
        
        // Make sure active user is an admin
        if ($this->getPermissions() === self::ACTIVE_ADMIN_PERMISSION()) {

            if (validator::checkAccountNumber($accountNumber)) {
                $bill->setBillID($this->generateBillID());
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
    
    public function generateBillID() {
    // Generates and returns a valid bill id string
        $pass = false;
        $billID = null;
        while ($pass === false) {
            $billID = (string)(rand(100000000000, 999999999900)); // length 12
            // Verify bill id does not already exist, else, repeat
            if ($this->accountForBill($billID) === null)
                $pass = true;
        }
        return $billID;
    }
   
    private function saveBillToFile($billID, $accountNumber) {
    // Saves bill id to file for assocation to account numbers
    // Input: valid bill id and account number strings
    // Output: success value (true/false)
        $success = false;
        
        $bills = $this->readFile(self::BILLS_FILE());
        if (!($bills === null)) {
            // Set entry in object to save to file
            $bills[$billID] = $accountNumber;
            
            $this->overwriteFile(self::BILLS_FILE(), $bills);
        }
        
        return $success;

    }
    
    public function addNewBillToAll() {
        
        // Check for appropriate account permissions
        if ($this->getPermissions() === self::ACTIVE_ADMIN_PERMISSION()) {
        
            $credentials = $this->readFile(self::CREDENTIALS_FILE());
        
            // Verify file is read and parsed successfully
            if (!($credentials === null)) {
                foreach($credentials as $accountNumber) {
                    $dbController = new DatabaseController();
                    $dbController->loadAccount($accountNumber);

                    if ($dbController->getPermissions() == self::ACTIVE_USER_PERMISSION()) {
                        
                        // Generate unique bill id
                        $billID = $dbController->generateBillID();

                        // Add bill with default amount, date, and current month, and generated id
                        $dbController->getActiveAccount()->addBill(Bill::BILL_AMOUNT, Bill::BILL_DUE_DATE, date('F'), $billID);

                        $dbController->saveBillToFile($billID, $this->activeAccount->getAccountNumber());
                        $dbController->saveActiveAccount();

                    } else
                        ; // Bill is not applicable for any account other than an active user
                }
                
            } else
                throw new Exception("Credentials file could not be read.");
        } else
            header('Location: ' . $View::UNAUTHORIZED_PAGE);
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
    
    private function accountForBill($billID) {
    // Gets the account number associated with a bill
    // Input: Valid bill id string
    // Output: Account number string or null if not found
        $accountNumber = null;
        
        // Read bills file
        $bills = $this->readFile(self::BILLS_FILE());
        
        // Make sure file was properly read
        if (!($bills === null)) {
            
            // Find account for bill id
            if (isset($bills[$billID]))
                $accountNumber = $bills[$billID];
        }
        
        return $accountNumber;
    }
    
    
}
?>
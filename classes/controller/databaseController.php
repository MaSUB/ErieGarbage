<?php
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);
    
require_once $rootDir . '/classes/input_validator.php';
require_once $rootDir . '/classes/model/user.php';
require_once $rootDir . '/classes/model/account.php';
require_once $rootDir . '/classes/model/admin.php';

class DatabaseController {
    // Constants declared as private static functions to assure privacy (php doesnt support private constants)
    private static function STORE_DIR() { return ($GLOBALS['rootDir'] . '/store'); }
    private static function ACCOUNTS_FILE_DIR() { return (self::STORE_DIR() . "/accounts/"); }
    private static function CREDENTIALS_FILE() { return (self::STORE_DIR() ."/credentials/credentials.json"); }
    private static function TOKENS_FILE() { return self::STORE_DIR() ."/credentials/tokens.json"; }
    private static function PERMISSIONS_FILE() { return self::STORE_DIR() . "/credentials/permissions.json"; }
    private static function FAILED_LOGINS_FILE() { return self::STORE_DIR() . "/credentials/login-limiter.json"; }
    private static function PICKUP_TIMES_FILE() { return self::STORE_DIR() . "/management/pickup-times.json"; }
    private static function BILLS_FILE() { return self::STORE_DIR() . "/management/bills.json"; }
    private static function LOGIN_TIMEOUT() { return 15 * 60; } // in seconds (mins * 60)
    private static function TOKEN_TIMEOUT() { return 30 * 60; } // in seconds (time untill token expires)
    
    // Permission levels
    public static function NO_PERMISSIONS() { return 'no-permissions'; }
    public static function ACTIVE_USER_PERMISSION() { return 'user'; }
    public static function ACTIVE_ADMIN_PERMISSION() { return 'admin'; }
    public static function DISABLED_USER_PERMISSION() { return 'disabled-user'; }
    
    // Bill due date
    const BILL_DUE_DAY = 1; // 1ST OF EVERY MONTH
    
    // Logging
    private static function LOGGING_ENABLED() { return true; }
    
    // Store ip address requests to limit
    private $failedLogins;
    
    private $authenticated = false;
    private $activeAccount = null; // account object
    
    // stores auth cookie to set upon redirection
    private $authToken = null;
    
    function __construct() {
        
        // Set default timezone
        date_default_timezone_set('America/New_York');
        
        // Load auth failed ips
        $failedLogins = $this->readFile(self::FAILED_LOGINS_FILE());
        if (!($failedLogins === null)) {
            $this->failedLogins = $failedLogins;
        } else 
            // Couldn't read logins file
            throw new Exception("Couldnt read failed logins file");
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
        $this->verifyRequestLimit();
        // Verify input strings are valid
        if (validator::checkEmail($email) && validator::checkPassword($password)) {

            // MD5 hash of email and password concatenation as auth value
            $authValue = hash('md5', $email . $password);
            
            // Validate authentication and get account number
            $accountNumber = $this->validateAuthFromTable($authValue);
            if ($accountNumber === null) {
                // Invalid login, marks it for limiting 
                $this->markInvalidLogin();
                
            } else {
                // Login successful! 
                if ($this->loadAccount($accountNumber)) {
                    // Account loaded.
                    
                    // Generate auth token for user session
                    $this->generateAuthToken($accountNumber);
                    
                    // Saves auth cookie onto user's browser and redirects to home page
                    $this->setCookieAndRedirect();
                    $success = true;
                    
                } else
                    die("Couldn't load account");
            }
        } else {
            // log 'Invalid inputs'; 
            $this->markInvalidLogin();
            
        }
        
    return $success;
        
    }
    
    public function logout() {
        $tokens = $this->readFile(self::TOKENS_FILE());
        if (!($tokens === null)) {
            
            $accountNumber = $this->activeAccount->getAccountNumber();
          
            unset($tokens[$accountNumber]);
            
            // Update tokens
            $this->overwriteFile(self::TOKENS_FILE(), $tokens);

            $this->authenticated = false;
            
            //if ($this->activeAccount != null)
                //$this->activeAccount->wipe();
            
        } else
            throw new Exception("Tokens file could not be read.");
    }
    
    private function validateAuthFromTable($authValue) {
    // Load authentication tables from JSON file and checks if account id exists for it
    // Input: authValue string containing the hashed credentials value
    // Output: Account number string associated with credentials or null if not found
        $accountNumber = null;
        $credentialsTable = $this->readFile(self::CREDENTIALS_FILE());
        // Verify reading was successful
            if (!($credentialsTable === null)) {
                // If auth value is present, accept login
                if (isset($credentialsTable[$authValue])) { 
                    // Success login - save account number
                    $accountNumber = $credentialsTable[$authValue];

                } else {
                    ; header('Location: ' . View::LOGIN_PAGE . '?fail=true'); // 'Incorrect Login' // Account does not exists (redirect)
                }
            } else
                    throw new Exception("Credentials file could not be read");
        
        return $accountNumber;
    }
    
        
        
    public function authenticateToken($authToken) {
    // Validates whether a token contains an active session
    // Input: authToken object with {id, token, expiry}
    // Output: Boolean value ($valid) indicating if token correspons to active session.
        $valid = false;
        // Validate token
        if (validator::checkAuthToken($authToken)) {
            $tokens = $this->readFile(self::TOKENS_FILE());
            
            // Check file properly read
            if (!($tokens === null)) {
                $accountNumber = $authToken->id;

                // If stored token for account number is the same as recieved
                if (isset($tokens[$accountNumber])) {
                    if ($tokens[$accountNumber][token] === $authToken->token) {
                        // And token has not expired
                        if (time() < $authToken->expiry) {
                            if ($this->loadAccount($accountNumber)) {
                                $valid = true;
                            } else die('Couldnt load account');
                        } else die('Expired token');
                    } else die('Token mismatch: ' . $authToken->token . ':' . $tokens->$accountNumber->token);
                } else ;// ('Token not found locally');
            } else
                throw new Exception("Tokens file cannot be read.");
        } else
            header("Location: " . View::UNAUTHORIZED_PAGE);
        
        return $valid;
    }
    
    private function generateAuthToken($accountNumber) {
    // Generates an auth token for the account number, and stores locally for future authentication
    // Input: Verified account number to associate with auth token
    // Output: Authentication token randomized value associated with account
        
        // Generate random, secure 50 byte string and md5 hash it
        $randomToken = md5(openssl_random_pseudo_bytes(50));
        
        // Generate auth token object
        $authToken = array('id' => $accountNumber, 'token' => $randomToken, 'expiry' => time()+self::TOKEN_TIMEOUT());
        
        // Update token locally for future reference
        // Make sure file exists
        $tokens = $this->readFile(self::TOKENS_FILE());
        
        if (!($tokens === null)) {

            // Add/update token entry in object for account number
            $tokens[$accountNumber] = $authToken;
            
            // Update file
            $this->overwriteFile(self::TOKENS_FILE(), $tokens);

        } else {
            throw new Exception("Tokens file cannot be found.");
        }
       
        $this->authToken = $authToken;
        
        return $randomToken;
    }
    
    private function setCookieAndRedirect() {
        if ($this->authToken) {
            // Set the user's cookie
            setcookie('eg-auth', json_encode($this->authToken), time()+self::TOKEN_TIMEOUT(), '/');
            // Redirect home
            header('Location: ' . '/home.php');
            // Exit to assure code does not get executed.
            exit;
        }
    }
    
    // ************************* \\
    // **** AUTH PROTECTION **** \\
    // ************************* \\
    
    private function verifyRequestLimit() {
    // Verifies the user's ip against the log of incorrect logins. 
    // Output: Returns false if the request limit was exceeded by user, true otherwise.
    
        if (validator::checkIP($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
            // Get the recent ip requests logged in failedLogins variable.
            $recentRequests = $this->failedLogins[$ip];
            
            $wrongLogins = 0;
            $earliestTime = $recentRequests[0];
            $counter = 0;
            $recentRequestsUpdated = false;
            
            if ($recentRequests) {
                // Iterate through recent requests in array
                foreach($recentRequests as $requestTime) {

                    if ($requestTime < time() + self::LOGIN_TIMEOUT()) {
                    // Counts towards timeout;
                        $wrongLogins++;

                        // Update earliest time
                        if ($earliestTime > $requestTime)
                            $earliestTime = $requestTime;
                    } else {
                    // Unset value
                        unset($recentRequests[$counter]);
                        $recentRequestsUpdated = true;
                    }

                    $counter++;      
                }

                // Update recent requests file if necessary
                if ($recentRequestsUpdated) {
                    $this->failedLogins[$ip] = array_values($recentRequests);
                    $this->overwriteFile(self::FAILED_LOGINS_FILE(), $this->failedLogins);
                }
            }
            
            // Check if exceeds limit
            if ($wrongLogins >= 4)
                return false;
            else
                return true;
        }
    }
    
    private function markInvalidLogin() {
        if (validator::checkIP($_SERVER['REMOTE_ADDR'])) {
            $serverIP = $_SERVER['REMOTE_ADDR'];
            
            // Push the time onto the array under 
            if (isset($this->failedLogins[$serverIP]))
                array_push($this->failedLogins[$serverIP], time());
            
            else {
                $this->failedLogins[$serverIP] = [ time() ];
            }
        }
    }
    
    // **** Permissions **** \\
    
    public function getPermissions() {
    // Gets permissions for the active account 
    // Input: Verified account number string
    // Output: Permissions string const
        
        // Assume no permissions unless reassigned
        $permissions = self::NO_PERMISSIONS();
        
        if ($this->authenticated) {
            $accountNumber = $this->activeAccount->getAccountNumber();
            
            // Read file
            $permissionsObject = $this->readFile(self::PERMISSIONS_FILE());
            
            // Verify file properly read
            if (!($permissionsObject === null))
                $permissions = $permissionsObject[$accountNumber];
            } else 
                throw new Exception("Permissions file could not be read");
    
        return $permissions;
    }
    
    private function setUserPermissions() {
    // Sets the permission level for the current account as user
        $success = false;
        
        $permissionsObject = $this->readFile(self::PERMISSIONS_FILE());
        if (!($permissionsObject === NULL)) {
            $accountNumber = $this->activeAccount->getAccountNumber();
            $permissionsObject[$accountNumber] = self::ACTIVE_USER_PERMISSION();
            if ($this->overwriteFile(self::PERMISSIONS_FILE(), $permissionsObject)) 
                $success = true;
                    
        } else 
            throw new Exception("Permissions file could not be read");

        return $success;
    }
    
    private function setAdminPermissions($accountNumber) {
    // Sets the permissions for the account number to admin
        if ($this->authenticated) {
            if ($this->getPermissions() === self::ACTIVE_ADMIN_PERMISSION()) {
                $permissions = $this->readFile(self::PERMISSIONS_FILE());
                $permissions[$accountNumber] = self::ACTIVE_ADMIN_PERMISSION();
                
                if ($this->overwriteFile(self::PERMISSIONS_FILE(), $permissionsObject)) 
                    $success = true;
                
            }
        }
    }
    
    /******************************/ 
    /****** ACCOUNT CREATION ******/
    /******************************/
    
    public function createAndRegisterAdminAccount($firstName, $lastName, $email, $password) {
        $admin = new Admin($firstName, $lastName, $email, $password);
        if (Admin::checkAccount($admin)) { 
            
            // Make sure user is not blocked for excessive requests
            if ($this->verifyRequestLimit()) {
                
                // Check permissions
                if ($this->getPermissions() === self::ACTIVE_ADMIN_PERMISSION()) {

                    // Calculate auth value
                    $authValue = hash('md5', $email . $password);

                    
                    $this->registerAdminAccount($account, $authValue);
                }
            }
        
        }
    }
    
    public function createAndRegisterAccount($firstName, $lastName, $email, $password, $address, $city, $zip, $dateOfBirth) {
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
                            $userAccount = new User($firstName, $lastName, $email, $authValue, $addressObject, $dateOfBirth);
                            
                            // Register account into system
                            $this->registerAdminAccount($userAccount, $authValue);
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
            $this->markInvalidLogin();
            header('Location: /timeout.php'); // Request limit exceeded
            exit;
        }
        
        if ($success === false) {
             $this->markInvalidLogin();
            header('Location: /login.php');
            exit;
        }
        
        return $success;
    }
    
    private function registerAdminAccount($account, $authValue) {
    // Registers a new admin account into the system and saves it
    // Input: Account object
    // Output: Boolean with success value
        $success = false;
        $account->setAccountNumber($this->generateNewAccountNumber());
        
        $this->saveAccountObject($account);
        $this->createUserCredentialsEntryForAccountNumber($accountNumber, $authValue);
        $this->setAdminPermissions($accountNumber);
    }
    
    private function registerAccount($account, $authValue) {
    // Registers a new account into the system current object and saves it to system
    // Input: Account object
    // Output: Boolean with success value
        $success = false;
            
        // Set account
        $this->activeAccount = $account;
        
        // Generate account number
        $this->activeAccount->setAccountNumber($this->generateNewAccountNumber());
        
        // Save account object
        $this->saveAccount();
        
        // Save the user credentials as well
        $this->createUserCredentialsEntry($authValue);
        
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
   
    //  ***************************** \\
    //  ******* FILE HANDLING ******* \\
    //  ***************************** \\
    
    private function loadAccount($accountNumber) {
    // Loads the account info for a registered account number.
    // Input: Verified AccountNumber string
    // Output: boolean with success or failure
        $success = false;
        $accountFileName = self::ACCOUNTS_FILE_DIR() . $accountNumber . '.json';
        $account = $this->readFile($accountFileName);
        if (!($account === null)) { 
            
            // Assign to local class variable
            if ($account['accountType'] == Account::USER_ACCOUNT)
                $this->activeAccount = User::load($account);
            else if ($account['accountType'] == Account::ADMINISTRATOR_ACCOUNT)
                $this->activeAccount = Admin::load($account);
            
            
            $this->authenticated = true;
            $success = true;
            
        } else
            throw new Exception("Could not read account file.");
                
        
        return success;
    }
    
    private function saveAccountObject($account) {
    // Saves the new account object onto the json file stores
        $success = false;
        if (User::validateAccount($account)) {
            $accountFileName = self::ACCOUNTS_FILE_DIR() . $account->getAccountNumber() . '.json';

            if ($this->overwriteFile($accountFileName, $account->exportJSON()))
                $success = true;
        } else 
            throw new Exception("Couldn't save account; it is invalid."); // Invalid account
        
        return $success;
    }
    
    public function saveAccount() {
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
    
     
    public function deleteAccount() {
        if ($this->authenticated === true) {
            $this->deleteAccountFile();
            $this->deleteCredentialsEntry();
            $this->removePermissionsEntry();
            $this->deleteToken();
        } else
            throw new Exception('Not authenticated');
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
    
     private function updateRecentRequestsFile() {
        // Load auth failed ips
         
         $recentRequests = $this->readFile(self::FAILED_LOGINS_FILE());
         if (!($recentRequests === null)) {
             $recentRequests = array_merge($recentRequests, $this->failedLogins);
             $this->overwriteFile(self::FAILED_LOGINS_FILE(), $recentRequests);             
         } else
             throw new Exception("Couldn't read failed logins file");
    }
    
    private function createUserCredentialsEntryForAccountNumber($accountNumber, $authValue) {
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
    
    private function createUserCredentialsEntry($authValue) {
        return $this->createUserCredentialsEntryForAccountNumber($this->activeAccount->getAccountNumber(), $authValue);
    }
    
    public static function loadPickupTimes() {
    // Loads pickup times from json file and returns it
    // Output: Pickup times object or null if failed
        $pickupTimes = null;
        $pickupTimes = self::readFile(self::PICKUP_TIMES_FILE());
        
        if (($pickupTimes === null)) {
            throw new Exception("Error reading pickup times file");
        }
            
        return $pickupTimes;
    }
    
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
        
        if (file_exists($fileName)) {
            $file = fopen($fileName, 'w+');
            if (!($file === false)) {
                // Read and decode json object from file
                fwrite($file, json_encode($data));
                fclose($file);
                $success = true;

            } else 
                throw new Exception("Error opening " . $fileName . " file");
        } else
            throw new Exception("Error: file " . $fileName . " does not exist");
    
        return $value;
    }
            
    
    // *********************** \\
    // **** Functionality **** \\
    // *********************** \\
    
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
        
        $permissions = $this->readFile(self::PERMISSIONS_FILE());
        $credentials = $this->readFile(self::CREDENTIALS_FILE());
        if (!($permissions === null) && !($credentials === null)) {
            if (isset($credentials[$adminAuthValue])) {
                $adminAccountNumber = $credentials[$adminAuthValue];

                if (isset($permissions[$adminAccountNumber])) {
                    // Get permissions for the account number.
                    $permissions = $permissions[$adminAccountNumber];
                    if ($permissions === self::ACTIVE_ADMIN_PERMISSION()) {
                        $this->loadAccount($accountNumber);
                        $success = true;
                    }
                } else 
                    header('Location: ' . View::UNAUTHORIZED_PAGE); // permission for user not found: unauthorized
            } else 
                throw new Exception("Could not find credentials for user");
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
                    $databaseController->adminLoad($this->activeUser->getAuthValue(), $accountNumber);
                    

                } else
                    die("Invalid bill");
            } else 
                die('Invalid account number');
        } else
            header('Location: ' . View::UNAUTHORIZED_PAGE);
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
        $credentials = $this->readFile(self::CREDENTIALS_FILE());
        // Verify file is read and parsed successfully
        if (!($credentials === null)) {
            foreach($credentials as $accountNumber) {
                $this->loadAccount($accountNumber);
                    
                // Check for appropriate account permissions
                if ($this->getPermissions() === self::ACTIVE_ADMIN_PERMISSION()) {
                    // Generate unique bill id
                    $billID = $this->generateBillID();
                    
                    // Add bill with default amount, date, and current month, and generated id
                    $this->activeAccount->addBill(Bill::BILL_AMOUNT, Bill::BILL_DUE_DATE, date('F'), $billID);

                    $this->saveBillToFile($billID, $this->activeAccount->getAccountNumber());
                    $this->saveAccount();
                
                } else
                    ; // Bill is not applicable for any account other than an active user
            }
        } else
            throw new Exception("Credentials file could not be read.");
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
            header('Location: /login.php');
            // Exit to assure code execution stops.
            exit;
        }
        
        return $success;
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
    
    public function updateAccount($newAccount) {
        $this->activeAccount = $newAccount;
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
                            $authValue = hash('md5', $this->activeUser->getEmail() . $newPassword);
                    }

                    // Update account object
                    if ($this->activeAccount->updateAccount($firstName, $lastName, $email, $authValue, $streetAddress, $zipCode, $city, $dateOfBirth)) {
                        // Success updating account
                        $this->saveAccount();
                        if ($this->createUserCredentialsEntry($authValue))
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
    
    public function isAuthenticated() {
    // Returns whether the user has been authenticated and loaded into the controller.
        return $this->authenticated;
    }
    
}
?>
<?php
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);
    
require_once $rootDir . '/classes/security/InputValidator.php';
require_once $rootDir . '/classes/model/UserAccount.php';
require_once $rootDir . '/classes/model/AdminAccount.php';
require_once $rootDir . '/classes/view/View.php';
require_once $rootDir . '/classes/security/Logger.php';


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
    private static function LOGIN_TIMEOUT() { return 10 * 60; } // in seconds (mins * 60)
    
    public static function TOKEN_TIMEOUT() { return 15 * 60; } // in seconds (time untill token expires)
    
    // Permission levels
    public static function NO_PERMISSIONS() { return 'no-permissions'; }
    public static function ACTIVE_USER_PERMISSION() { return 'user'; }
    public static function ACTIVE_ADMIN_PERMISSION() { return 'admin'; }
    public static function DISABLED_USER_PERMISSION() { return 'disabled-user'; }
    
    // Bill Due Date
    const BILL_DUE_DAY = 1; // 1ST OF EVERY MONTH
    
    // Logging
    private static function LOGGING_ENABLED() { return true; }
    
    // Store ip address requests to limit
    private $failedLogins;
    
    // stores whether user is authenticated or not
    private $authenticated = false;
    
    // stores the active account object authenticated
    private $activeAccount = null;
    
    // permissions of authenticated user
    private $permissions;
        
    function __construct() {
        
        // Set default timezone
        date_default_timezone_set('America/New_York');
        
        // Load auth failed ips
        $failedLogins = $this->readFile(self::FAILED_LOGINS_FILE());
        if (!($failedLogins === null)) {
            $this->failedLogins = $failedLogins;
        } else {
            // Couldn't read logins file
            Logger::logError(Logger::FAILED_LOGINS_LOAD_ERROR);
            header('Location: ' . View::ERROR_PAGE);
        }
        
        // Set default permissions
        $this->permissions = self::NO_PERMISSIONS();
    }
    
    /*****************************/ 
    /*****  AUTHENTICATION  ******/
    /*****************************/
    
    public function authenticate($email, $password) {
    // Authenticates a user with email and password credentials and returns the associated user object
    // Input: Email and Password Strings
    // Output: Auth token object for authenticated account or null if failure to authenticate
        $authToken = null;
        
        // Verify input strings are valid
        if (validator::checkEmail($email) && validator::checkPassword($password)) {

            // password hash of email and password concatenation as auth value
            $authValue = password_hash($email . $password, PASSWORD_DEFAULT, ["salt" => "73bfd72hs7a3h88jvF5Yz9"]);
            
            // Validate authentication and get account number
            $accountNumber = $this->validateAuthFromTable($authValue);
            if (!($accountNumber === null)) {
                // Login successful! 
                // Load account
                $account = $this->loadAccount($accountNumber);
                    
                // Check account successfully loaded.
                if (!($account === null)) {
                
                    // Generate auth token for user session
                    $authToken = $this->generateAuthToken($accountNumber);
                    
                } else {
                    // Log account load error and redirect user to the error page
                    Logger::logError(Logger::ACCOUNT_LOAD_ERROR, $accountNumber);
                    header("Location: " . View::ERROR_PAGE);
                }
            } else {
                // Invalid login, marks it for limiting 
                $this->markInvalidLogin();
                header('Location: ' . View::LOGIN_PAGE . '?fail=true'); 
            } 
        } else {
            // Invalid inputs 
            $this->markInvalidLogin();
            header('Location: ' . View::LOGIN_PAGE . '?fail=true'); 
        }
        
    return $authToken;
        
    }
    
    public function logout($accountNumber) {
    // Logs the account number out and deletes associated tokens
    // Input: Account number string
    // Output: Success value (true/false)
        $success = false;
        
        $tokens = $this->readFile(self::TOKENS_FILE());
        if (!($tokens === null)) {
            // Make sure account number has active session in
            if (isset($tokens[$accountNumber])) {
                
                // Delete the token from store
                unset($tokens[$accountNumber]);

                // Update tokens file
                if ($this->overwriteFile(self::TOKENS_FILE(), $tokens))
                    $success = true;
            } else {
                Logger::log("Logout function used on account number with no token: " . $accountNumber);
            }
        } else {
            // Log token file load error and redirect to error page.
            Logger::logError(TOKENS_LOAD_ERROR);
            header('Location: ' . View::ERROR_PAGE);
        }
        
        return $success;
    }
    
    private function validateAuthFromTable($authValue) {
    // Load authentication tables from JSON file and checks if account id exists for it
    // Input: authValue string containing the hashed credentials value
    // Output: Account number string associated with credentials or null if not found
        $accountNumber = null;
        $credentialsTable = $this->readFile(self::CREDENTIALS_FILE());
        
        // Verify reading was successful
        if (!($credentialsTable === null)) {
            
            // If auth value is present, accept login (account exists)
            if (isset($credentialsTable[$authValue])) { 
                // Success login - save account number
                $accountNumber = $credentialsTable[$authValue];
            }  
        } else {
            // Log credentials file load error and redirect to error page
            Logger::logError(Logger::CREDENTIALS_LOAD_ERROR);
            header('Location: ' . View::ERROR_PAGE);
        }
        
        return $accountNumber;
    }       
        
    public function authenticateToken($authToken) {
    // Validates whether a token contains an active session
    // Input: authToken object with {id, token, expiry}
    // Output: Boolean value ($valid) indicating if token correspons to active session.
        $account = null;
        // Validate token
        if (validator::checkAuthToken($authToken)) {
            $tokens = $this->readFile(self::TOKENS_FILE());
            
            // Check file properly read
            if (!($tokens === null)) {
                $accountNumber = $authToken->id;

                // Make sure there is a stored token for the account number
                if (isset($tokens[$accountNumber])) {
                    // Validate that tokens match
                    if ($tokens[$accountNumber][token] === $authToken->token) {
                        // Validate token has not expired
                        if (time() < $tokens[$accountNumber][expiry]) {
                            
                            // Load account
                            $account = $this->loadAccount($accountNumber);
                            
                            // Get and store associated permissions
                            $this->permissions = $this->getPermissions($accountNumber);
                            
                            // Make sure account load was successful
                            if (($account === null)) {
                                Logger::logError(Logger::ACCOUNT_LOAD_ERROR); 
                                header('Location: ' . View::ERROR_PAGE);
                            }
                            
                        } else 
                            // Token expired, redirect to login page
                            header('Location: ' . View::LOGIN_PAGE);
                        
                    } else {
                        // Stored token for account does not match the one supplied
                        Logger::logError(Logger::TOKEN_MISMATCH_ERROR);
                        header('Location: ' . View::ERROR_PAGE);
                    }
                } else 
                    ; // Token not found locally: account not authenticated
            } else {
                // Tokens file could not be loaded
                Logger::logError(Logger::TOKENS_LOAD_ERROR);
                header('Location: ' . View::ERROR_PAGE);
            }
        } else {
            // Validation of auth token failed
            Logger::logError(Logger::INVALID_INPUT_ERROR);
            header("Location: " . View::ERROR_PAGE);
        }
        
        return $account;
    }
    
    private function generateAuthToken($accountNumber) {
    // Generates an auth token for the account number, and stores locally for future authentication
    // Input: Verified account number to associate with auth token (string)
    // Output: Authentication token randomized value associated with account (string)
        
        // Generate random, secure 50 byte string and md5 hash it
        $randomToken = md5(openssl_random_pseudo_bytes(50));
        
        // Generate auth token object
        $authToken = array('id' => $accountNumber, 'token' => $randomToken, 'expiry' => ((int)time())+self::TOKEN_TIMEOUT());
        
        // Update token locally for future reference
        // Make sure file exists
        $tokens = $this->readFile(self::TOKENS_FILE());
        
        if (!($tokens === null)) {

            // Add/update token entry in object for account number
            $tokens[$accountNumber] = $authToken;
            
            // Update file
            $this->overwriteFile(self::TOKENS_FILE(), $tokens);

        } else {
            Logger::logError(Logger::TOKENS_LOAD_ERROR);
            header('Location: ' . View::ERROR_PAGE);
            exit;
        }
               
        return $authToken;
    }
    
    // ************************* \\
    // **** AUTH PROTECTION **** \\
    // ************************* \\
    
    public function verifyRequestLimit() {
    // Verifies the user's ip against the log of incorrect logins. 
    // Output: Returns false if the request limit was exceeded by user, true otherwise.
        $success = false;
        
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
            if ($wrongLogins < 5)
                $success = true;
        }
        
        return $success;
    }
    
    public function markInvalidLogin() {
        if (validator::checkIP($_SERVER['REMOTE_ADDR'])) {
            $serverIP = $_SERVER['REMOTE_ADDR'];
            
            // Push the time onto the array under 
            if (isset($this->failedLogins[$serverIP])) {
                array_push($this->failedLogins[$serverIP], time());
                
                // Update the failed logins store   
                $this->updateFailedLoginsFile();
                
            } else {
                $this->failedLogins[$serverIP] = array(time());
            }
            
        } else {
            Logger::logError(Logger::INVALID_INPUT_ERROR);
            header('Location: ' . View::ERROR_PAGE);
            exit;
        }
    }
    
    // *************************** \\
    // ******* PERMISSIONS ******* \\
    // *************************** \\
    
    public function getPermissions($accountNumber) {
    // Gets permissions for the account number
    // Input: Verified account number string
    // Output: Permissions string const
        
        // Assume no permissions unless reassigned
        $permissions = self::NO_PERMISSIONS();
        
        // Read file
        $permissionsObject = $this->readFile(self::PERMISSIONS_FILE());

        // Verify file properly read
        if (!($permissionsObject === null))
            $permissions = $permissionsObject[$accountNumber];
        
        else {
            Logger::logError(Logger::PERMISSIONS_LOAD_ERROR);
            header('Location: ' . View::ERROR_PAGE);
            exit;
        } 
            
        return $permissions;
    }
    
    private function setUserPermissions($accountNumber) {
    // Sets the permission level for the associated account number
        $success = false;
                
        $permissionsObject = $this->readFile(self::PERMISSIONS_FILE());
        if (!($permissionsObject === NULL)) {
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
            if ($this->permissions === self::ACTIVE_ADMIN_PERMISSION()) {
                $permissions = $this->readFile(self::PERMISSIONS_FILE());
                if (!($permissions === null)) {
                    $permissions[$accountNumber] = self::ACTIVE_ADMIN_PERMISSION();

                    if ($this->overwriteFile(self::PERMISSIONS_FILE(), $permissions)) 
                        $success = true;
                }
                
            }
        }
    }
    
    /********************************/ 
    /****** ACCOUNT MANAGEMENT ******/
    /********************************/
    
    public function registerAdminAccount($account, $authValue) {
    // Registers a new admin account into the system and saves it
    // Input: Account object
    // Output: Boolean with success value
        $success = false;
        
        // Make sure user has sufficient permissions
        if ($this->permissions === self::ACTIVE_ADMIN_PERMISSION()) {

            $accountNumber = $account->getAccountNumber();

            $this->saveAccount($account);
            $this->createAccountCredentialsEntry($accountNumber, $authValue);
            $this->setAdminPermissions($accountNumber);
            
            $success = true;
            
        } else {
            Logger::logError(Logger::INSUFFICIENT_PERMISSIONS, $accountNumber);
            header('Location: ' . View::UNAUTHORIZED_PAGE);
        }
        
        return true;
    }
    
    public function registerUserAccount($account, $authValue) {
    // Registers a new account into the system current object and saves it to system
    // Input: Account object
    // Output: Boolean with success value
        $success = false;
        
        $accountNumber = $account->getAccountNumber();
        
        // Save account object
        $this->saveAccount($account);
        
        // Save the user credentials as well
        $this->createAccountCredentialsEntry($accountNumber, $authValue);
        
        // Set user permissions
        $this->setUserPermissions($accountNumber);
        
        // Report success
        $success = true;
        
        return $success;
        
    }
    
    public function generateNewAccountNumber() {
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
    
    
    
    public function deleteAccount($accountNumber, $authValue) {
        if ($this->authenticated === true) {
            if ($this->getPermissions() === self::ACTIVE_ADMIN_PERMISSIONS) {

                $this->deleteAccountFile($accountNumber);
                $this->deleteCredentialsEntry($authValue);
                $this->removePermissionsEntry($accountNumber);
                $this->deleteToken($accountNumber);
            } else
                header('Location: ' . View::UNAUTHORIZED_PAGE); // unauthenticated, therefore unauthorized to delete
        } else
            header('Location: ' . View::LOGIN_PAGE);
    }
    
    public function deleteActiveAccount() {
        if ($this->authenticated === true) {
            if ($this->getPermissions() === self::ACTIVE_USER_PERMISSIONS) {
                $this->deleteAccountFile($this->activeAccount->getAccountNumber());
                $this->deleteCredentialsEntry($this->activeAccount->getAuthValue());
                $this->removePermissionsEntry($this->activeAccount->getAccountNumber());
                $this->deleteToken($this->activeAccount->getAccountNumber());
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
            ; // throw new Exception("Error: file " . $fileName . " does not exist");
    
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
    
    private function loadAccount($accountNumber) {
    // Loads the account info for a registered account number.
    // Input: Verified AccountNumber string
    // Output: Loaded account object

        $accountFileName = self::ACCOUNTS_FILE_DIR() . $accountNumber . '.json';
        $account = $this->readFile($accountFileName);
        if (!($account === null)) { 

            // Assign to local class variable
            if ($account['accountType'] == Account::USER_ACCOUNT)
                $this->activeAccount = UserAccount::load($account);
            else if ($account['accountType'] == Account::ADMINISTRATOR_ACCOUNT)
                $this->activeAccount = AdminAccount::load($account);
            else {
                Logger::logError(Logger::ACCOUNT_TYPE_INVALID);
                header("Location: " . View::ERROR_PAGE);
                exit;
            }
            
            $this->authenticated = true;
            $success = true;
            
        } else {
            Logger::logError(Logger::ACCOUNT_LOAD_ERROR, $accountNumber);
            header('Location: ' . View::ERROR_PAGE);
            exit;
        }
                   
        
        return $this->activeAccount;
    }
    
    public function saveAccount($account) {
    // Saves the new account object onto the json file stores
        $success = false;
        
        if ($account->checkAccount($account)) {

            // Make sure user is changing his own account
            // if ($account->getAccountNumber() === $this->activeAccount->getAccountNumber()) {

                $accountFileName = self::ACCOUNTS_FILE_DIR() . $account->getAccountNumber() . '.json';

                if ($this->overwriteFile($accountFileName, $account->exportJSON()))
                    $success = true;
            /*} else {
                Logger::logError(Logger::PERMISSION_DENIED, "Cannot save account object as other user's id");
                header("Location: " . View::UNAUTHORIZED_PAGE);
                exit;
            }*/
        } else {
            Logger::logError(Logger::INVALID_INPUT_ERROR);
            header("Location: " . View::ERROR_PAGE);
            exit;
        }
        
        return $success;
    }
    
    public function updateAccount($account) {
    // Saves the updated account and updates password
    // Output: Success value (true/false)
        $success = false;
        
        // Validate passed in account object
        if (Account::checkAccount($account)) {
            
            // Get account number
            $accountNumber = $account->getAccountNumber();
            
            // User can obly update his/her own account
            if ($accountNumber === $this->activeAccount->getAccountNumber()) {

                $this->saveAccount($account);
                $this->createAccountCredentialsEntry($accountNumber, $account->getAuthValue());
                $success = true;
            }
            
        }
        
        return $success;
    }
    
    public function saveActiveAccount() {
    // Saves the new account object onto the json file stores
        $success = false;
        if ($this->authenticated) {
            if ($this->activeAccount->validateAccount($this->activeAccount)) {
                $accountFileName = self::ACCOUNTS_FILE_DIR() . $this->activeAccount->getAccountNumber() . '.json';

                if ($this->overwriteFile($accountFileName, $this->activeAccount->exportJSON()))
                    $success = true;
            }  
        } else {
            Logger::logError(Logger::PERMISSION_DENIED, "User is not authenticated and tries to save active account.");
            header("Location: " . View::UNAUTHORIZED_PAGE);
            exit;
        }
        
        return $success;
    }
    
    private function deleteAccountFile($accountNumber) {
    // Deletes the active account file under /store/accounts/<account>.json
        $success = false;
        
        $accountFileName = self::ACCOUNTS_FILE_DIR() . $accountNumber . '.json';
        if (file_exists($accountFileName)) {
            unlink($accountFileName);
            $success = true;
        } else {
            Logger::logError(Logger::ACCOUNT_FILE_DELETE_ERROR, $accountNumber);
            header("Location: " . View::ERROR_PAGE);
            exit;
        }

        return $success;
    }    
    
    private function deleteToken($accountNumber) {
        $success = false;
        $tokens = $this->readFile(self::TOKENS_FILE());
        if (!($tokens === null)) {
            unset($tokens[$accountNumber]);
                
            // Rewrite changes to file (overwrite)
            $this->overwriteFile(self::TOKENS_FILE, $tokens);
        } else
            throw new Exception("Couldn't read tokens json file");
        
        return $success;
    }
    
    private function removePermissionsEntry($accountNumber) {
        $success = false;
        $permissions = $this->readFile(self::PERMISSIONS_FILE());
        
        // Make sure file was opened
        if (!($permissions === null)) {
            unset($permissions[$accountNumber]);
                
            // Rewrite changes to file (overwrite)
            $this->overwriteFile(self::PERMISSIONS_FILE(), $permissions);
        } else 
            throw new Exception("Couldn't read permissions file");
              
        return $success;
    }
        
    private function deleteCredentialsEntry($authValue) {
        $success = false;
        $credentials = $this->readFile(self::CREDENTIALS_FILE());
        if (!($credentials === null)) {
            unset($credentials[$authValue]);
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
    
    private function createAccountCredentialsEntry($accountNumber, $authValue) {
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
        if ($this->permissions === self::ACTIVE_ADMIN_PERMISSION()) {
        
            $credentials = $this->readFile(self::CREDENTIALS_FILE());
        
            // Verify file is read and parsed successfully
            if (!($credentials === null)) {
                foreach($credentials as $accountNumber) {
                    $dbController = new DatabaseController();
                    $dbController->loadAccount($accountNumber);

                    if ($dbController->permissions == self::ACTIVE_USER_PERMISSION()) {
                        
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
    
    public function findCustomerByEmail($email) {
    // Finds customer account by email
    // Input: Email string
    // Output: Account object or null if not found
        // Verify permissions
        if ($this->permissions === self::ACTIVE_ADMIN_PERMISSION()) {
            // Get permissions to extract account numbers from them
            $permissions = self::readFile(self::PERMISSIONS_FILE());
            if (!($permissions === null)) {
                foreach($permissions as $accountNumber => $permission) {
                    // Load the account
                    $account = $this->loadAccount($accountNumber);
                    // Check file read success
                    if (!($account === null)) {
                        // Verify email matches via case insensitive comparisson
                        if (strcasecmp($email === $account->getEmail()) === 0) {
                            // Found match, return it
                            return $account;
                        }
                    }
                }
            }
        }
        
        return null;
        
    }
    
    public function findCustomerByName($firstName, $lastName) {
    // Finds costumer account by name
    // Input: Email string
    // Output: Account object or null if not found
        // Verify permissions
        if ($this->permissions === self::ACTIVE_ADMIN_PERMISSION()) {
            // Get permissions to extract account numbers from them
            $permissions = self::readFile(self::PERMISSIONS_FILE());
            if (!($permissions === null)) {
                // Iterate
                foreach($permissions as $accountNumber => $permission) {
                    // Load the account
                    $account = $this->loadAccount($accountNumber);
                    // Check file read success
                    if (!($account === null)) {
                        // Case insensitive comparisson of given names and account names
                        if (strcasecmp($firstName, $account->getFirstName()) === 0 && strcasecmp($lastName, $account->getLastName()) === 0) {
                            // Found match! Return it.
                            return $account;
                        }
                    }
                }
            }
        }
        
        return null;
    }
    
    //  ***************************** \\
    //  **** Getters and Setters **** \\
    //  ***************************** \\
    
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
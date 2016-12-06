<?php
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);

require_once $rootDir . '/classes/controller/databaseController.php';
require_once $rootDir . '/classes/view/header.php';

abstract class View {

    const LOGIN_PAGE = '/login.php';
    const LOGOUT_PAGE = '/logout.php';
    const UNAUTHORIZED_PAGE = '/unauthorized.php';
    const HOME_PAGE = '/home.php';
    
    protected $loggedIn; // boolean value
    protected $databaseController; // database controller object
    protected $permissions;
    
    function __construct() {
        $success = false;
        // Make sure the Cookie and its values exist
        if (isset($_COOKIE['eg-auth'])) {
            $authToken = json_decode($_COOKIE['eg-auth']);

            $this->databaseController = new DatabaseController();
            $success = $this->databaseController->authenticateToken($authToken);
            if ($success) {
                // Success authentication
                $this->permissions = $this->databaseController->getPermissions();  
                $this->loggedIn = true;
            } else
                header('Location: '. self::LOGOUT_PAGE);
        } else
            // Not authenticated, redirect to login.php
            header('Location: ' . self::LOGIN_PAGE);
        
        if (!$success) {
            // Not authenticated, redirect to login.php
            header('Location: ' . self::LOGIN_PAGE);
        }
    }
    
    // HTML ELEMENTS
    
    // Function to be called by view subclasses to render the page including header
    public function renderPage() {

        if ($this->permissions == DatabaseController::ACTIVE_USER_PERMISSION()) {
            $this->printUserHeader();
            $this->printUserBody();
        } else if ($this->permissions == DatabaseController::ACTIVE_ADMIN_PERMISSION()) {
            $this->printAdminHeader();
            $this->printAdminBody();
        } else if ($this->permissions == DatabaseController::NO_PERMISSIONS()) {
            $this->printUnauthenticatedHeader();
            $this->printUnauthenticatedBody();
        } else {
            throw new Exception("Permissions not assigned?");
        }
        
        // Closing body and html tags
        echo '</body></html>';
    }
    
    protected function printUserHeader() {
    // Prints out all the elements corresponding to the user's header.
        echo Header::USER_HEADER_LOGGED_IN;
    }
    
    protected function printAdminHeader() {
    // Prints out all the elements corresponding to the admin's header.
        echo Header::ADMIN_HEADER_LOGGED_IN;
    }
    
    
    // Both of these methods must be implemented by subclass for functionality
    protected abstract function printUserBody();
    protected abstract function printAdminBody();
    protected abstract function printUnauthenticatedBody();
    protected abstract function printUnauthenticatedHeader();

}
?>

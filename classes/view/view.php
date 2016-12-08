<?php
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);

require_once $rootDir . '/classes/controller/ClientController.php';
require_once $rootDir . '/classes/view/Header.php';

abstract class View {

    // Define constants for page locations to be used in redirecting the client
    const LOGIN_PAGE = '/login.php';
    const LOGOUT_PAGE = '/logout.php';
    const USER_REGISTER_PAGE = '/register.php';
    const UNAUTHORIZED_PAGE = '/unauthorized.php';
    const HOME_PAGE = '/home.php';
    const ADMIN_REGISTER_PAGE = '/admin/createAdmin.php';
    const TIMEOUT_PAGE = '/timeout.php';
    const ACCOUNT_SETTINGS_PAGE = '/user/accountSettings.php';
    const ERROR_PAGE = '/error.php';
    
    protected $loggedIn; // boolean value
    protected $clientController; // database controller object
    protected $permissions;
    
    function __construct() {
        $this->clientController = new ClientController();
        $this->permissions = $this->clientController->getPermissions();
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

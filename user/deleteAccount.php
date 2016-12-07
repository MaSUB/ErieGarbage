<?php 
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);
require_once $rootDir . '/classes/security/InputValidator.php';
require_once $rootDir . '/classes/controller/DatabaseController.php';
require_once $rootDir .  '/classes/view/View.php';

class DeleteAccountView extends View {
    
    function __construct() {
        parent::__construct();
        
        // Verify user is logged in
        if ($this->loggedIn) {
            
            // IF GET REQUEST
            if($_SERVER['REQUEST_METHOD'] === 'GET') { 
                // Validate user's permissions
                if ($this->permissions === DatabaseController::ACTIVE_USER_PERMISSION() || 
                    $this->permissions === DatabaseController::ACTIVE_ADMIN_PERMISSION()) {
                    
                    $this->content = '<div class="content-delete-confirm">
                    <p>Are you sure you want to delete your account? (There is no turning back!)</p>
                    <form action="/user/deleteAccount.php" method="post">
                    <input type="submit" value="Delete Account">
                    </form>
                </div>';
                } else
                    header('Location: ' . View::UNAUTHORIZED_PAGE);

            } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->databaseController->deleteActiveAccount();
                header('Location: ' . View::LOGIN_PAGE);
            }
        } else
            header("Location: " . View::LOGIN_PAGE);
    }
    
    protected function printUserBody() {
        echo $this->content;
    }
    
    protected function printAdminBody() {
        header('Location: ' . View::UNAUTHORIZED_PAGE); 
    }
    
    protected function printUnauthenticatedBody() {
        header('Location: ' . View::LOGIN_PAGE);
    }
    
    protected function printUnauthenticatedHeader() {
        header("Location: " . View::LOGIN_PAGE);
    }
    
}

$deleteAccountView = new DeleteAccountView();
$deleteAccountView->renderPage();

?>

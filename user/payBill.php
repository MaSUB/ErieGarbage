<?php
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);

require_once $rootDir . '/classes/controller/DatabaseController.php';
require_once $rootDir . '/classes/view/Header.php';
require_once $rootDir .  '/classes/view/View.php';

class PayBillView extends View {
    
    function __construct() {
        parent::__construct();
        
        // Only users are allowed to view
        if (!($this->permissions === DatabaseController::ACTIVE_USER_PERMISSION()))
            header('Location: /unauthorized.php');
    }
    
    protected function printUserBody() {
        ;
    }
    
    protected function printUnauthenticatedHeader() {
        header("Location: " . View::UNAUTHORIZED_PAGE);
    }
    
    protected function printAdminBody() {
        ; // Admin should not get here
    }
    
    protected function printUnauthenticatedBody() {
        header('Location: ' . View::LOGIN_PAGE);
    }
}
    
// Execution
$payBillView = new PayBillView();
$payBillView->renderPage();

?>
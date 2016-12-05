<?php
require_once 'classes/view/view.php';

class UnauthorizedView extends View {
    private $message = '<p>account is not authorized to access this functionality</p>';
    
    protected function printUserBody() {
        echo '<p>USER</p> ' . $this->message;
    }
    
    protected function printAdminBody() {
        echo '<p>ADMIN</p> ' . $this->message;;
    }
    
    protected function printUnauthenticatedBody() {
        echo '<p>UNAUTHENTICATED</p>' . $this->message;
    }
}

$unauthorizedView = new UnauthorizedView();
$unauthorizedView->renderPage();
?>

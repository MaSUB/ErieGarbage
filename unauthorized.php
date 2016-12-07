<?php
require_once 'classes/view/View.php';

class UnauthorizedView extends View {
    private $message = 'account is not authorized to access this functionality</p>';
    
    protected function printUserBody() {
        echo '<p>USER ' . $this->message;
    }
    
    protected function printAdminBody() {
        echo '<p>ADMIN ' . $this->message;;
    }
    
    protected function printUnauthenticatedBody() {
        echo '<p>UNAUTHENTICATED ' . $this->message;
    }
    
    protected function printUnauthenticatedHeader() {
        ;
    }
}

$unauthorizedView = new UnauthorizedView();
$unauthorizedView->renderPage();
?>

<?php //    this is the content for the UserHome page.
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);
require_once $rootDir .  '/classes/view/View.php';
require_once $rootDir . '/classes/security/InputValidator.php';

class CreateAdminView extends View {
    private $form = '<div class="content-login">
            <div class="login-form">
                <form id="login-form" action="/admin/createAdmin.php" method="post">
                    <h1>Sign Up</h1>
                    <input type="text" name="firstName" placeholder="First Name"><br>
                    <input type="text" name="lastName" placeholder="Last Name"><br>
                    <input type="text" name="email" placeholder="Email Address"><br>
                    <input type="password" name="password" placeholder="Password"><br>
                    <input type="password" name="verifyPassword" placeholder="Verify Password"><br>
                    <button type="submit">Submit</button>
                </form>
            </div>
        </div>';
    
    function __construct() {
        parent::__construct();
        if ($this->loggedIn) {
            if ($this->databaseController->getPermissions() === DatabaseController::ACTIVE_ADMIN_PERMISSION()) {
                ; // Success
            } else
                // Insufficient priviledges
                header('Location: ' . View::UNAUTHORIZED_PAGE);
        } else
            // Not logged in
            header("Location: " . View::LOGIN_PAGE);
        
    }
    
    protected function printUserBody() {
        header('Location: ' . View::UNAUTHORIZED_PAGE);
    }
    
    protected function printAdminBody() {
        echo $this->form;
    }
    
    protected function printUnauthenticatedBody() {
        header('Location: ' . View::LOGIN_PAGE);
    }
    
    protected function printUnauthenticatedHeader() {
        header('Location: ' . View::LOGIN_PAGE);
    }
    
    public function registerAdmin($firstName, $lastName, $email, $password) {
        if ($this->databaseController->createAndRegisterAdminAccount($firstName, $lastName, $email, $password))
            header('Location: ' . View::LOGIN_PAGE);
        else
            die("Failed to register account.");
    }
    
    
}

$createAdminView = new CreateAdminView();
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $createAdminView->renderPage();

    
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['password'] === $_POST['verifyPassword']) {
        $createAdminView->registerAdmin($_POST['firstName'], $_POST['lastName'], $_POST['email'], $_POST['password']);
        
    } else
        echo 'Password mismatch';
}
?>
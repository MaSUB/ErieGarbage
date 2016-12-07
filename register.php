<?php
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);
require_once $rootDir . '/classes/view/Header.php';
require_once $rootDir .  '/classes/view/View.php';
require_once $rootDir . '/classes/controller/UserController.php';
require_once $rootDir . '/classes/controller/AdminController.php';

class RegisterView extends View{
    // private static constants 
    private static function USER_REGISTER_FORM() {
        return '<div class="content-login">
            <div class="login-form">
                <form id="login-form" action="register.php" method="post">
                    <h1>Sign Up</h1>
                    <input type="text" name="firstName" placeholder="First Name"><br>
                    <input type="text" name="lastName" placeholder="Last Name"><br>
                    <input type="text" name="email" placeholder="Email Address"><br>
                    <input type="password" name="password" placeholder="Password"><br>
                    <input type="password" name="verifyPassword" placeholder="Verify Password"><br>
                    <input type="text" name="address" placeholder="Home Address"><br>
                    <input type="text" name="city" placeholder="City"><br>
                    <input type="text" name="zip" placeholder="Zip Code"><br>
                    <input type="date" name="dateOfBirth"><br>
                    <input type="hidden" name="type" value="user">
                    <button id="submit" href="userHome.php">Submit</button>
                </form>
            </div>
        </div>';
    }
    
    private static function ADMIN_REGISTER_FORM() {
        return '<div class="content-login">
            <div class="login-form">
                <form id="login-form" action="register.php" method="post">
                    <h1>New Admin</h1>
                    <input type="text" name="firstName" placeholder="First Name"><br>
                    <input type="text" name="lastName" placeholder="Last Name"><br>
                    <input type="text" name="email" placeholder="Email Address"><br>
                    <input type="password" name="password" placeholder="Password"><br>
                    <input type="password" name="verifyPassword" placeholder="Verify Password"><br>
                    <input type="hidden" name="type" value="admin">
                    <button id="submit" href="userHome.php">Submit</button>
                </form>
            </div>
        </div>';
    }
    
    protected function printUserHeader() {
        // Redirected authenticated users to the home page
        header('Location: ' . View::HOME_PAGE);
        exit;
    }
    
    // Abstract functions of View
    protected function printUserBody() {
        ;
    }
    
    protected function printAdminBody() {
        echo self::ADMIN_REGISTER_FORM();
    }
    
    protected function printUnauthenticatedHeader() {
        echo Header::LOGGED_OUT;
    }
    
    protected function printUnauthenticatedBody() {
        echo self::USER_REGISTER_FORM();
    }
    
}
    
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $registerView = new RegisterView();
    $registerView->renderPage();

} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify passwords
    if ($_POST['password'] === $_POST['verifyPassword']) {
        if ($_POST['type'] === 'user') { 
            // Register a user
            $dbController = new ClientController();
            if ($dbController->createAndRegisterUserAccount($_POST['firstName'], $_POST['lastName'], $_POST['email'], $_POST['password'], $_POST['address'], $_POST['city'], $_POST['zip'], $_POST['dateOfBirth']))
                header('Location: ' . View::LOGIN_PAGE , '?msg=Success');
        } else { 
            // Register an admin
            $controller = new AdminController();
            if ($controller->createAndRegisterAdminAccount($_POST['firstName'], $_POST['lastName'], $_POST['email'], $_POST['password'])) {
                header('Location: ' . View::LOGIN_PAGE . '?msg=Success');
            }
            
        }
    } else
        echo 'Password mismatch';;
}
?>

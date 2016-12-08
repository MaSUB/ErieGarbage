<?php
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);
require_once $rootDir .  '/classes/view/View.php';
require_once $rootDir . '/classes/security/InputValidator.php';
require_once $rootDir . '/classes/controller/AdminController.php';
class FindCustomerView extends View {
    
    // Stores content to display
    private $content;
    
    const HEAD_DIV = '<div class="content-home">
            <div class="content-row">';
    const TAIL_DIV = '</div></div>';
    
    // Static components
    private static function CUSTOMER_SEARCH_FORM() { 
        return '<p>You can search by first and last name, or email.</p><br>
                <form action="/admin/findCustomer.php" method="post">
                    <p>First Name: </p><input type="text" name="firstName"><br>
                    <p>Last Name: </p><input type="text" name="lastName"><br>
                    <p>Email: </p><input type="email" name="email"><br>
                    <input type="submit" value="Search"><br>
                </form>';
    }
    
    function __construct() {
        $this->clientController = new AdminController();
        $this->permissions = $this->clientController->getPermissions();
        
        // Set up accordingly depending on request type
        if ($_SERVER["REQUEST_METHOD"] === "GET")
            $this->setGet();
        else if ($_SERVER["REQUEST_METHOD"] === "POST")
            $this->setPost();
        else {
            Logger::log("Invalid request type (not post or get) findCustomer.php");
            header("Location: " . View::ERROR_PAGE);
            exit;
        }
    }
    
    // Abstract functions of View
    protected function printUserBody() {
        // Users are unauthorized
    }
    
    protected function printUserHeader() {
        header('Location: ' . View::UNAUTHORIZED_PAGE);
        exit;
    }
    
    protected function printAdminBody() {
        echo self::HEAD_DIV;
        echo $this->content;
        echo self::TAIL_DIV;
    }
    
    protected function printUnauthenticatedHeader() {
        header('Location: ' . View::LOGIN_PAGE);
        exit;
    }
    
    protected function printUnauthenticatedBody() {
        header('Location: ' . View::LOGIN_PAGE);
        exit;
    }
    
    private function setGet() {
        $this->content = self::CUSTOMER_SEARCH_FORM();
    }
    
    private function setPost() {
        $account = null;
        
        if (!empty($_POST['firstName']) && !empty($_POST['lastName'])) {
            $firstName = $_POST['firstName'];
            $lastName = $_POST['lastName'];
            if (validator::checkName($firstName) && validator::checkName($lastName)) {
                $account = $this->clientController->findCustomer($firstName, $lastName, null);
            } else
                ; // Invalid input
        } else if (!empty($_POST['email'])) {
            $email = $_POST['email'];
            if (validator::checkEmail($email)) {
                $account = $this->clientController->findCustomer(null, null, $email);
            }
        }
        
        // If account is found
        if (!($account === null)) {
            $this->content='<p>' . $account->getFirstName() . '</p><br>
                            <p>' . $account->getLastName() . '</p><br>
                            <p>' . $account->getEmail() . '</p><br>';
        } else 
            $this->content = '<p>Account not found!</p>';
    }
}

$customerView = new FindCustomerView();
$customerView->renderPage();
?>
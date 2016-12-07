<?php //    this is the content for the UserHome page.
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);
require_once $rootDir . '/classes/view/Header.php';
require_once $rootDir .  '/classes/view/View.php';
require_once $rootDir . '/classes/controller/UserController.php';

class AccountSettingsView extends View {
    
    private $accountSettingsHTML;
    
    // Option to store const with what page to render
    public $option;
    const USER_SETTINGS = 1;
    const SUCCESS_UPDATING_SETTINGS = 2;
    const FAIL_UPDATING_SETTINGS = 3;
    
    function __construct() {
        $this->clientController = new UserController();
        $this->permissions = $this->clientController->getPermissions();
        
        // Only users are allowed to view
        if (!($this->permissions === DatabaseController::ACTIVE_USER_PERMISSION()))
            header('Location: ' . View::UNAUTHORIZED_PAGE);
        
    }
    
    protected function printUnauthenticatedHeader() {
        header("Location: " . View::UNAUTHORIZED_PAGE);
    }
    
    protected function printUserBody() {
        if ($this->option === self::USER_SETTINGS) {
            
            $account = $this->clientController->getActiveAccount();
            $address = $account->getAddress();

            $firstName = $account->getFirstName();
            $lastName = $account->getLastName();
            $email = $account->getEmail();
            $date = $account->getDateOfBirth();
            $streetAddress = $address->streetAddress;
            $zipCode = $address->zipCode;
            $city = $address->city;

            echo '<div class="content-home">
            <div class="content-row">
                <p>' . (isset($this->message) ? $this->message : '') . '</p><br>
                <form action="/user/accountSettings.php" method="post">
                    <p>First Name: </p><input type="text" placeholder="'. $firstName . '" name="firstName"><br>
                    <p>Last Name: </p><input type="text" placeholder="' . $lastName . '" name="lastName"><br>
                    <p>Email: </p><input type="email" placeholder="' . $email . '"name="email"><br>
                    <p>Old Password: </p><input type="password" placeholder="(old password)" name="oldPassword" require><br>
                    <p>New Password: </p><input type="password" placeholder="(new password)" name="newPassword"><br>
                    <p>Date: </p><input type="date" name="birthDate" placeholder="' . $date . '"><br>
                    <p>Street Address: </p><input type="text" placeholder="' . $streetAddress . '" name="streetAddress"><br>
                    <p>Zip Code: </p><input type="number" placeholder="' . $zipCode . '" name="zipCode"><br>
                    <p>City: </p><input type="text" placeholder="' . $city . '" name="city"><br>
                    <input type="submit" value="Update Account"><br>
                </form>
                <form method="get" action="/user/deleteAccount.php">
                    <button type="submit">Delete Account</button>
                </form>

            </div>
        </div>';

        } else if ($this->option === self::SUCCESS_UPDATING_SETTINGS) {
            echo '<p>Successfully updated settings!</p>';
        } else if ($this->option === self::FAIL_UPDATING_SETTINGS) {
            echo '<p>Failed to update settings...</p>';
        }

    }
    
    public function setOption($option) {
        if ($option === self::USER_SETTINGS || $option === self::SUCCESS_UPDATING_SETTINGS || $option === self::FAIL_UPDATING_SETTINGS) {
            $this->option = $option;
        } else
            throw new Exception("Illegal option in accountSettings view");
    }

    protected function printAdminBody() {
        header('Location: ' . View::UNAUTHORIZED_PAGE); // Admins should not have access to this functionality
    }
    
    protected function printUnauthenticatedBody() {
        header('Location: ' . View::LOGIN_PAGE);
    }
    
    public function getController() {
        return $this->clientController;
    }
    
    public function setMessage($message) {
        $this->message = $message;
    }

}

// Get means user wants to see and modify his info
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $accountSettingsView = new AccountSettingsView();
    
    if (isset($_GET['msg'])) 
        $accountSettingsView->setMessage($_GET['msg']);
    
    $accountSettingsView->option = AccountSettingsView::USER_SETTINGS;
    $accountSettingsView->renderPage();
    
// Post request means user wants to update his account info with provided data    
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accountSettingsView = new AccountSettingsView();
    
    // Attempt to update account, if successful
    if ($accountSettingsView->getController()->updateAccountSettings($_POST['firstName'], $_POST['lastName'], $_POST['email'], $_POST['oldPassword'], $_POST['newPassword'], $_POST['streetAddress'], $_POST['zipCode'], $_POST['city'], $_POST['birthDate']))
        
        // Prepare for proper rendering message
        $accountSettingsView->setOption(AccountSettingsView::SUCCESS_UPDATING_SETTINGS);
    
    else
        $accountSettingsView->setOption(AccountSettingsView::FAIL_UPDATING_SETTINGS);


    $accountSettingsView->renderPage();
}

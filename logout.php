<?php
require_once 'classes/security/InputValidator.php';
require_once 'classes/controller/DatabaseController.php';
require_once 'classes/view/View.php';
    
// POST LOGIN REQUEST MADE
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_COOKIE['eg-auth'])) {
        $authCookie = json_decode($_COOKIE['eg-auth']);
        if (validator::checkAuthToken($authCookie)) {  
            $controller = new ClientController();
            if (!($controller->authenticateToken($authCookie) === null)) {
                
                // Authentication successful
                $controller->logout();
                
                header('Location: ' . View::LOGIN_PAGE);
                exit;
                
            } else {
                header('Location: ' . View::LOGIN_PAGE); // Failed log in, try again
                exit;
            }
        } else
            header("Location: " . View::LOGIN_PAGE);
            ; // ('Illegal auth token');
    } else 
        header('Location: ' . View::LOGIN_PAGE);
       ; // ('Cookie not set');    
}
?>

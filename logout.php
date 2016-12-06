<?php
require_once 'classes/input_validator.php';
require_once 'classes/controller/databaseController.php';
    
// POST LOGIN REQUEST MADE
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_COOKIE['eg-auth'])) {
        $authCookie = json_decode($_COOKIE['eg-auth']);
        if (validator::checkAuthToken($authCookie)) {  
            $controller = new DatabaseController();
            if ($controller->authenticateToken($authCookie)) {
                // Authentication successful
                $controller->logout();
                header('Location: /login.php');
                exit;
            } else {
                header('Location: /login.php'); // Failed log in, try again
                exit;
            }
        } else
            header("Location: /login.php");
            ; // ('Illegal auth token');
    } else 
        header('Location: /login.php')
       ; // ('Cookie not set');    
}
?>

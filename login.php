<?php 
$rootDir = realpath($_SERVER["DOCUMENT_ROOT"]);

require_once $rootDir . '/classes/security/InputValidator.php';
require_once $rootDir . '/classes/controller/ClientController.php';
require_once $rootDir . '/classes/view/Header.php';

const LOGIN_MESSAGE = 'Login failed: invalid credentials.';

// IF GET LOGIN REQUEST
if($_SERVER['REQUEST_METHOD'] === 'GET') { 
    echo Header::LOGGED_OUT;
?>
        <div class="content-login">
            <div class="login-form">
                <form id="login-form" action="/login.php" method="post">
                    <h1>Login</h1>
                    <input type="text" name="email" placeholder="Email Address"><br>
                    <input type="password" name="password" placeholder="Password"><br>
                    <?php
    if ($_GET['fail'] === 'true')
        echo '<p class="login-message">' . LOGIN_MESSAGE . '</p><br>';
                    ?>
                    <button type="submit" id="login">Login</button>
                </form>
                
                <form id="register-redirect-form" action="/register.php" method="get">
                    <button id="register" type="submit">Sign Up</button>
                </form>
                

            </div>
        </div>
    </body>
</html>
<?php
    
// POST LOGIN REQUEST MADE
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = validator::cleanInput($_POST['email']);
        $password = validator::cleanInput($_POST['password']);
        
        $controller = new ClientController();
        if ($controller->authenticate($email, $password)) {
            //echo 'Login successful';
            header('Location: /home.php'); // Logged in, redirect to home
        } else {
            //echo 'Login failed';
            header('Location: /login.php?fail=true'); // Failed log in, try again?
        }
    } else
        header('Location: /login.php?fail=true');
        
}

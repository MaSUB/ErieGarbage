<?php //    this is the content for the UserHome page.
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require_once 'classes/view/header.php';
    echo Header::LOGGED_OUT;
?>
        <div class="content-login">
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
                    <button id="submit" href="userHome.php">Submit</button>
                </form>
            </div>
        </div>
    </body>
</html>
<?php
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['password'] === $_POST['verifyPassword']) {
        require_once 'classes/controller/databaseController.php';
        $dbController = new DatabaseController();
        if ($dbController->createAndRegisterAccount($_POST['firstName'], $_POST['lastName'], $_POST['email'], $_POST['password'], $_POST['address'], $_POST['city'], $_POST['zip'], $_POST['dateOfBirth']))
            header('Location: ' . View::LOGIN_PAGE);
    } else
        echo 'Password mismatch';;
}
?>

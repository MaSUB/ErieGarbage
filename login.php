<?php //    this is the content for the UserHome page.
include 'header.php';
echo $loggedOut;
?>

        <div class="content-login">
            <div class="login-form">
                <form id="login-form" action="register.php">
                    <h1>Login</h1>
                    <input type="text" name="email" placeholder="Email Address"><br>
                    <input type="password" name="password" placeholder="Password"><br>
                    <button id="login" href="userHome.php">Login</button>
                    <button id="register" action="register.php">Sign Up</button>
                </form>
            </div>
        </div>
    </body>
</html>
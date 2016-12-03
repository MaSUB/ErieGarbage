<?php //    this is the content for the UserHome page.
include 'header.php';
echo $userHeaderLoggedIn;
?>

        <div class="content-login">
            <div class="login-form">
                <form id="login-form" action="course_list.html">
                    <h1>Login</h1>
                    <input type="text" name="email" placeholder="Email Address"><br>
                    <input type="password" name="password" placeholder="Password"><br>
                    <button class=".button_sliding_bg" id="login" href="userHome.php">Login</button>
                    <button class=".button_sliding_bg" id="register" href="register.php">Sign Up</button>
                </form>
            </div>
        </div>
    </body>
</html>
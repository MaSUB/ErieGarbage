<?php //    this is the content for the UserHome page.
include 'header.php';
echo $userHeaderLoggedIn;
?>

        <div class="content-login">
            <div class="login-form">
                <form id="login-form" action="course_list.html">
                    <h1>Sign Up</h1>
                    <input type="text" name="firstName" placeholder="First Name"><br>
                    <input type="text" name="lastName" placeholder="Last Name"><br>
                    <input type="text" name="email" placeholder="Email Address"><br>
                    <input type="password" name="password" placeholder="Password"><br>
                    <input type="password" name="password" placeholder="Verify Password"><br>
                    <input type="text" name="address" placeholder="Home Address"><br>
                    <input type="text" name="city" placeholder="City"><br>
                    <input type="text" name="zip" placeholder="Zip Code"><br>
                    <input type="date" name="dateOfBirth" placeholder="  /  /    "><br>
                    <button class=".button_sliding_bg" id="submit" href="userHome.php">Submit</button>
                </form>
            </div>
        </div>
    </body>
</html>
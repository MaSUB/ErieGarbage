<?php

?>
<?php //this is for the normal user header
$userHeaderLoggedIn = '<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>ErieGarbage</title>
  <link rel="stylesheet" href="stylesheet.css" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Karla|Lato" rel="stylesheet">
  </head>
  <body>
    <div class="header">
        <h1 class="header-title">ErieGarbage</h1>
        <ul>
            <li><a href="userHome.php"><div class="menu-btn"><p>Home</p></div></a></li>
            <li><a href="payBill.php"><div class="menu-btn"><p>Pay Bill</p></div></a></li>
            <li><a href="garbageDetail.php"><div class="menu-btn"><p>Garbage Detail</p></div></a></li>
            <li><a href="accountSettings.php"><div class="menu-btn"><p>Account Settings</p></div></a></li>
            <li><a href="complaints.php"><div class="menu-btn"><p>Complaints</p></div></a></li>
            <li><a href="aboutUs.php"><div class="menu-btn"><p>About Us</p></div></a></li>
            <li><a href="LogOut.php"><div class="menu-btn"><p>Log Out</p></div></a></li>
        </ul>
    </div>';
?>

<?php //this is for the admin header
$adminHeaderLoggedIn = '<!DOCTYPE html>
<html>
  <head>
    <meta charset="UTF-8">
    <title>ErieGarbage</title>
  <link rel="stylesheet" href="stylesheet.css" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Karla|Lato" rel="stylesheet">
  </head>
  <body>
    <div class="header">
        <h1 class="header-title">ErieGarbage</h1>
        <ul>
            <li><a href="adminHome.php"><div class="menu-btn"><p>Home</p></div></a></li>
            <li><a href="viewDuePayments.php"><div class="menu-btn"><p>View Due Payments</p></div></a></li>
            <li><a href="createAdmin.php"><div class="menu-btn"><p>Create Admin</p></div></a></li>
            <li><a href="findCustomer.php"><div class="menu-btn"><p>Find Customer</p></div></a></li>
            <li><a href="adminComplaints.php"><div class="menu-btn"><p>Complaints</p></div></a></li>
            <li><a href="aboutUs.php"><div class="menu-btn"><p>Customer Reports</p></div></a></li>
            <li><a href="LogOut.php"><div class="menu-btn"><p>Log Out</p></div></a></li>
        </ul>
    </div>';
?>
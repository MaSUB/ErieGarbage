<?php //this is for the normal user header
class Header {
    const USER_HEADER_LOGGED_IN = '<!DOCTYPE html>
    <html>
      <head>
        <meta charset="UTF-8">
        <title>ErieGarbage</title>
      <link rel="stylesheet" href="/css/stylesheet.css" type="text/css">
      <link href="https://fonts.googleapis.com/css?family=Karla|Lato" rel="stylesheet">
      </head>
      <body>
        <div class="header">
            <h1 class="header-title">ErieGarbage</h1>
            <ul>
                <li><a href="/home.php"><div class="menu-btn"><p>Home</p></div></a></li>
                <li><a href="/user/bills.php"><div class="menu-btn"><p>Pay Bill</p></div></a></li>
                <li><a href="/user/garbageDetails.php"><div class="menu-btn"><p>Garbage Detail</p></div></a></li>
                <li><a href="/user/accountSettings.php"><div class="menu-btn"><p>Account Settings</p></div></a></li>
                <li><a href="/user/complaints.php"><div class="menu-btn"><p>Complaints</p></div></a></li>
                <li><a href="/about.php"><div class="menu-btn"><p>About Us</p></div></a></li>
                <li><a href="/logout.php"><div class="menu-btn"><p>Log Out</p></div></a></li>
            </ul>
        </div>';
    const USER_HEADER_LOGGED_IN_CALENDAR = '<!DOCTYPE html>
    <html>
      <head>
        <meta charset="UTF-8">
        <title>ErieGarbage</title>
      <link rel="stylesheet" href="/css/stylesheet.css" type="text/css">
      <link rel="stylesheet" href="/css/calendar.css" type="text/css">
      <link href="https://fonts.googleapis.com/css?family=Karla|Lato" rel="stylesheet">
      </head>
      <body>
        <div class="header">
            <h1 class="header-title">ErieGarbage</h1>
            <ul>
                <li><a href="/home.php"><div class="menu-btn"><p>Home</p></div></a></li>
                <li><a href="/user/bills.php"><div class="menu-btn"><p>Pay Bill</p></div></a></li>
                <li><a href="/user/garbageDetails.php"><div class="menu-btn"><p>Garbage Detail</p></div></a></li>
                <li><a href="/user/accountSettings.php"><div class="menu-btn"><p>Account Settings</p></div></a></li>
                <li><a href="/user/complaints.php"><div class="menu-btn"><p>Complaints</p></div></a></li>
                <li><a href="/about.php"><div class="menu-btn"><p>About Us</p></div></a></li>
                <li><a href="/logout.php"><div class="menu-btn"><p>Log Out</p></div></a></li>
            </ul>
        </div>';
    const ADMIN_HEADER_LOGGED_IN = '<!DOCTYPE html>
    <html>
      <head>
        <meta charset="UTF-8">
        <title>ErieGarbage</title>
      <link rel="stylesheet" href="/css/stylesheet.css" type="text/css">
      <link href="https://fonts.googleapis.com/css?family=Karla|Lato" rel="stylesheet">
      </head>
      <body>
        <div class="header">
            <h1 class="header-title">ErieGarbage</h1>
            <ul>
                <li><a href="/home.php"><div class="menu-btn"><p>Home</p></div></a></li>
                <li><a href="/admin/duePaymentsView.php"><div class="menu-btn"><p>Due Payments</p></div></a></li>
                <li><a href="/register.php"><div class="menu-btn"><p>Create Admin</p></div></a></li>
                <li><a href="/admin/findCustomer.php"><div class="menu-btn"><p>Find Customer</p></div></a></li>
                <li><a href="/admin/adminComplaints.php"><div class="menu-btn"><p>Complaints</p></div></a></li>
                <li><a href="/about.php"><div class="menu-btn"><p>Customer Reports</p></div></a></li>
                <li><a href="/logout.php"><div class="menu-btn"><p>Log Out</p></div></a></li>
            </ul>
        </div>';
    const LOGGED_OUT = '<!DOCTYPE html>
    <html>
      <head>
        <meta charset="UTF-8">
        <title>ErieGarbage</title>
      <link rel="stylesheet" href="/css/stylesheet.css" type="text/css">
      <link href="https://fonts.googleapis.com/css?family=Karla|Lato" rel="stylesheet">
      </head>
      <body>
        <div class="header">
            <h1 class="header-title">ErieGarbage</h1>
            <ul>
                <li><a href="/home.php"><div class="menu-btn"><p>Home</p></div></a></li>
                <li><a href="/about.php"><div class="menu-btn"><p>About Us</p></div></a></li>
                <li><a href="/logout.php"><div class="menu-btn"><p>Log Out</p></div></a></li>
            </ul>
        </div>';
}
?>
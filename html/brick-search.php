<?php
define('INCLUDE_CHECK',true);
$username = "";
include 'include/connect.php';
include 'include/login.php';
include 'include/functions.php';

$show_login_panel = !handleUserLogin();
$dbh = dbConnect();
printHTMLHeader("Wall of Bricks - Parts Search", "");
printPartSearchForm($dbh, 0);
printHTMLFooter(0, 0, 0, 0, $show_login_panel);

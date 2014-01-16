<?php
define('INCLUDE_CHECK',true);
$username = "";

include 'include/connect.php';
include 'include/login.php';
include 'include/functions.php';

$show_login_panel = !handleUserLogin();
printHTMLHeader("Wall of Bricks", "");
$dbh = dbConnect();

$pab_store_id = 0;
$pab_country = 'USA';
$load_store_from_cookie = 0;

if (array_key_exists('pab_store_id', $_GET)) {
    $pab_store_id = $_GET['pab_store_id'];

    if (!validStoreID($pab_store_id)) {
        print "ERROR: This is not a supported store ID<br>\n";
        printHTMLFooter();
        exit();
    }
    if (array_key_exists('country', $_GET)) {
        $pab_country = $_GET['country'];
    }

// Load the store id from a cookie
} else {
    $load_store_from_cookie = 1;
}

?>

<div id='overview'>
<div id='overview-left'>
<img src='/images/pick-a-brick.jpg' size='500' />
</div>
<div id='overview-right'>
<h1>What is the Wall&nbsp;of&nbsp;Bricks?</h1>
<div>The Wall&nbsp;of&nbsp;Bricks is a site that lets you view the 'Pick A Brick' wall inventory for LEGO stores.  If the inventory for your local LEGO store is incomplete or out of date you can edit the wall to correct it.  The inventories here are provided by LEGO users like you.</div>
<div id='learn-more' class='rounded_corners shadow' url='/newsletter.php'><a href='/newsletter.php'>Learn More</a></div>
<form method='get' action='/pab-display.php' autocomplete='off'>
<?php
pickAStore($dbh, $pab_country, $pab_store_id, 1, 0);
print "</form>\n";
print "</div>\n";
print "</div>\n";

printHTMLFooter($load_store_from_cookie, 0, 0, 0, 0, $show_login_panel);
// close the connection
$dbh = null;
?>

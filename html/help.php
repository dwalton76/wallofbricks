<?php
define('INCLUDE_CHECK',true);
include 'include/connect.php';
include 'include/login.php';
include 'include/functions.php';

$show_login_panel = !handleUserLogin();
printHTMLHeader("Wall of Bricks - FAQ", "");
?>

<div class='question'><span class='qa'>Q:</span><span class='guts'>What is the best way to search for unusual parts?</span></div>
<div class='answer'>
<span class='qa'>A:</span>Bricks, plates, etc are easy to find.  Sometimes though, you don't know if a part is considered a brick, plate, etc. Here is the best way to find those tricky parts.
<li>Pick your color</li>
<li>If you are sure of the part's dimensions then enter them, if not use dimensions "0x0".  "0x0" will search all dimensions.</li>
<li>Try all of the different part types (Brick, Plate, Slope, etc)</li>
If you still can't find the part email a picture to <a href='mailto:help@wallofbricks.com'>help@wallofbricks.com</a> and we will investigate further.
</div>

<div class='question'><span class='qa'>Q:</span><span class='guts'>The Google map for my store is wrong, how do I fix it?</span></div>
<div class='answer'><span class='qa'>A:</span>Send an email to <a href='mailto:help@wallofbricks.com'>help@wallofbricks.com</a> with a link to the correct location in google maps.</div>

<div class='question'><span class='qa'>Q:</span><span class='guts'>The number of columns for my store is wrong, how do I fix it?</span></div>
<div class='answer'><span class='qa'>A:</span>Send an email to <a href='mailto:help@wallofbricks.com'>help@wallofbricks.com</a> with the correct number of columns.</div>

<?php
printHTMLFooter(0, 0, 0, 0);
$dbh = dbConnect();
?>

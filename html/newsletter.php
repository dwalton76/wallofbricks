<?php
define('INCLUDE_CHECK',true);
include 'include/connect.php';
include 'include/login.php';
include 'include/functions.php';

$dbh = dbConnect();
printHTMLHeader("Wall of Bricks - Newsletter", "");

if (isset($_POST['submit'])) {
    $submit_action = $_POST['submit'];

    if ($submit_action == 'Subscribe') {
        $email = $_POST['email'];
        if (!checkEmail($email)) {
            print "ERROR: '$email' is not a valid email address\n";
        } else {
            addEmailToNewsletter($dbh, $email, 1);
        }
    }
} else {
?>
<div id='newsletter'>
<h1>Newsletter</h1>
If you would like to hear about new features for the site please sign up for our newsletter.  We hate spam as much as anyone so we'll try to keep the chatter to a minimum.
<form action="/newsletter.php" method="post">
<br>
<label for="email"><h2>Email Address</h2></label>
<input type="text" name="email" id="email" value="" size="30" /><br><br>
<input type="submit" class="button" name="submit" value="Subscribe" />
</form>
</div>
<?php
}
printHTMLFooter(0, 0, 0, 0);
?>
